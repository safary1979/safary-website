<?php

namespace App\Filament\Resources\ApiAccountResource\Pages;

use App\Filament\Resources\ApiAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApiAccount extends EditRecord
{
    protected static string $resource = ApiAccountResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Never pre-fill decrypted keys into the form
        $data['api_key']        = '';
        $data['api_secret']     = '';
        $data['api_passphrase'] = '';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Skip blank fields — keep existing encrypted value
        if (empty($data['api_key']))        unset($data['api_key']);
        if (empty($data['api_secret']))     unset($data['api_secret']);
        if (empty($data['api_passphrase'])) unset($data['api_passphrase']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
