<?php

namespace App\Filament\Pages;

class GeneratedAutofonArchive extends GeneratedAutofon
{
    protected static ?string $title = 'Архів автофону';
    protected static string $view = 'filament.pages.generated-autofon-archive';

    public ?string $section = null;
    protected ?array $archiveIndex = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function index(): array
    {
        if (! $this->loaded) {
            return ['available' => false, 'loading' => true];
        }

        return $this->archiveIndex ??= $this->fetchSnapshot('/v1/autofon/archive');
    }

    public function selectSection(string $section): void
    {
        $this->refreshStatus();
        $this->archiveIndex = null;
        abort_unless(array_key_exists($section, $this->index()['data']['sections'] ?? []), 404);
        $this->section = $section;
    }

    public function snapshot(): array
    {
        if (! $this->loaded || $this->section === null) {
            return ['available' => true, 'data' => []];
        }

        return $this->snapshotCache ??= $this->fetchSnapshot('/v1/autofon/archive/section', ['section' => $this->section]);
    }
}
