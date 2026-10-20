<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class ExcelTableImporter
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const DOC_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const PKG_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /** @return array{sheet_name:string, headers:array<int,string>, rows:array<int,array<int,mixed>>} */
    public function parse(string $path, ?string $sheetName = null): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'xlsx' => $this->parseXlsx($path, $sheetName),
            'csv' => $this->parseCsv($path),
            default => throw new RuntimeException('Підтримуються лише файли .xlsx та .csv.'),
        };
    }

    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Не вдалося відкрити CSV-файл.');
        }

        try {
            $matrix = [];
            while (($line = fgetcsv($handle, null, ',', '"', '')) !== false) {
                if (count($matrix) >= 250001) {
                    throw new RuntimeException('У таблиці забагато рядків (ліміт 250 000).');
                }
                $matrix[] = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $line);
            }
        } finally {
            fclose($handle);
        }

        if (isset($matrix[0][0])) {
            $matrix[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $matrix[0][0]);
        }

        return $this->normalizeMatrix($matrix, pathinfo($path, PATHINFO_FILENAME));
    }

    private function parseXlsx(string $path, ?string $requestedSheet): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Не вдалося відкрити книгу Excel.');
        }

        try {
            $workbookXml = $this->loadXml($zip->getFromName('xl/workbook.xml'), 'Структуру книги Excel не знайдено.');
            $relationshipsXml = $this->loadXml($zip->getFromName('xl/_rels/workbook.xml.rels'), 'Зв’язки аркушів Excel не знайдено.');
            $sharedStrings = $this->readSharedStrings($zip);
            $targets = [];

            foreach ($relationshipsXml->children(self::PKG_REL_NS)->Relationship as $relationship) {
                $attributes = $relationship->attributes();
                $targets[(string) $attributes['Id']] = (string) $attributes['Target'];
            }

            $sheets = [];
            foreach ($workbookXml->children(self::MAIN_NS)->sheets->children(self::MAIN_NS)->sheet as $sheet) {
                $attributes = $sheet->attributes();
                $relationshipAttributes = $sheet->attributes(self::DOC_REL_NS);
                $name = (string) $attributes['name'];
                $relationshipId = (string) $relationshipAttributes['id'];
                $state = (string) ($attributes['state'] ?? 'visible');

                if ($state === 'visible' && isset($targets[$relationshipId])) {
                    $sheets[$name] = $this->worksheetPath($targets[$relationshipId]);
                }
            }

            if ($sheets === []) {
                throw new RuntimeException('У книзі немає видимих аркушів із таблицями.');
            }

            $sheetName = $requestedSheet ?: array_key_first($sheets);
            if (! isset($sheets[$sheetName])) {
                throw new RuntimeException('Аркуш «' . $sheetName . '» не знайдено у книзі.');
            }

            $sheetXml = $this->loadXml($zip->getFromName($sheets[$sheetName]), 'Не вдалося прочитати вибраний аркуш.');
            $matrix = [];
            foreach ($sheetXml->children(self::MAIN_NS)->sheetData->children(self::MAIN_NS)->row as $row) {
                if (count($matrix) >= 250001) {
                    throw new RuntimeException('У таблиці забагато рядків (ліміт 250 000).');
                }

                $cells = [];
                foreach ($row->children(self::MAIN_NS)->c as $cell) {
                    $attributes = $cell->attributes();
                    $reference = (string) $attributes['r'];
                    $column = $this->columnIndex($reference);
                    $type = (string) ($attributes['t'] ?? '');
                    $cellContent = $cell->children(self::MAIN_NS);

                    if ($type === 'inlineStr') {
                        $value = $this->innerText($cellContent->is ?? null);
                    } elseif ($type === 's') {
                        $stringIndex = (int) ($cellContent->v ?? -1);
                        $value = $sharedStrings[$stringIndex] ?? '';
                    } else {
                        $value = (string) ($cellContent->v ?? '');
                        if ($type === 'b') {
                            $value = $value === '1';
                        } elseif ($value !== '' && is_numeric($value)) {
                            $number = (float) $value;
                            $value = floor($number) === $number ? (int) $number : $number;
                        }
                    }

                    $cells[$column] = $value;
                }

                if ($cells !== []) {
                    $width = max(array_keys($cells)) + 1;
                    $line = array_fill(0, $width, null);
                    foreach ($cells as $index => $value) {
                        $line[$index] = $value;
                    }
                    $matrix[] = $line;
                }
            }

            return $this->normalizeMatrix($matrix, $sheetName);
        } finally {
            $zip->close();
        }
    }

    private function readSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = $this->loadXml($xml, 'Не вдалося прочитати текстові значення книги.');
        $strings = [];
        foreach ($document->children(self::MAIN_NS)->si as $item) {
            $strings[] = $this->innerText($item);
        }

        return $strings;
    }

    private function innerText(?SimpleXMLElement $element): string
    {
        if ($element === null) {
            return '';
        }

        $element->registerXPathNamespace('x', self::MAIN_NS);
        $parts = $element->xpath('.//x:t');

        return implode('', array_map(static fn (SimpleXMLElement $part): string => (string) $part, $parts ?: []));
    }

    private function loadXml(string|false $xml, string $error): SimpleXMLElement
    {
        if ($xml === false) {
            throw new RuntimeException($error);
        }

        $parsed = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOBLANKS);
        if (! $parsed instanceof SimpleXMLElement) {
            throw new RuntimeException($error);
        }

        return $parsed;
    }

    private function worksheetPath(string $target): string
    {
        $target = str_replace('\\', '/', $target);
        $target = ltrim($target, '/');
        if (! str_starts_with($target, 'xl/')) {
            $target = 'xl/' . $target;
        }
        $target = preg_replace('#/+#', '/', $target);

        if (str_contains($target, '../')) {
            throw new RuntimeException('Некоректний шлях аркуша у книзі Excel.');
        }

        return $target;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return $index - 1;
    }

    private function normalizeMatrix(array $matrix, string $sheetName): array
    {
        $matrix = array_values(array_filter($matrix, static fn (array $row): bool => count(array_filter($row, static fn ($value): bool => $value !== null && $value !== '')) > 0));
        if (count($matrix) < 2) {
            throw new RuntimeException('На аркуші має бути рядок заголовків і хоча б один рядок даних.');
        }

        $headers = array_map(static fn ($value, $index): string => trim((string) ($value ?? '')) ?: 'Column ' . ($index + 1), $matrix[0], array_keys($matrix[0]));
        $headerCounts = [];
        foreach ($headers as &$header) {
            $headerCounts[$header] = ($headerCounts[$header] ?? 0) + 1;
            if ($headerCounts[$header] > 1) {
                $header .= ' (' . $headerCounts[$header] . ')';
            }
        }
        unset($header);

        if (count($headers) < 2) {
            throw new RuntimeException('Для імпорту потрібно щонайменше два стовпці.');
        }

        $width = count($headers);
        $rows = [];
        foreach (array_slice($matrix, 1) as $row) {
            $row = array_slice(array_pad($row, $width, null), 0, $width);
            $rows[] = $row;
        }

        return ['sheet_name' => $sheetName, 'headers' => $headers, 'rows' => $rows];
    }
}
