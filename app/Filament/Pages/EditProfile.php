<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    protected function getRedirectUrl(): ?string
    {
        return \Filament\Facades\Filament::getUrl();
    }
}
