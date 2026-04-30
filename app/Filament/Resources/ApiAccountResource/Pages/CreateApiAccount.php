<?php

namespace App\Filament\Resources\ApiAccountResource\Pages;

use App\Filament\Resources\ApiAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiAccount extends CreateRecord
{
    protected static string $resource = ApiAccountResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Remove empty key fields so mutators don't overwrite with blank
        if (empty($data['api_key']))        unset($data['api_key']);
        if (empty($data['api_secret']))     unset($data['api_secret']);
        if (empty($data['api_passphrase'])) unset($data['api_passphrase']);
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
