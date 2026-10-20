<?php

namespace App\Filament\Pages;

use App\Services\TwoFactorService;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Illuminate\Support\HtmlString;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent(),
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            \Filament\Forms\Components\Section::make('Two-factor authentication')
                ->description('Опціональний додатковий код після логіну.')
                ->schema([
                    \Filament\Forms\Components\Toggle::make('two_factor_enabled')
                        ->label('Увімкнути 2FA')
                        ->live()
                        ->dehydrated(false),
                    \Filament\Forms\Components\Placeholder::make('two_factor_setup')
                        ->label('Налаштування')
                        ->content(fn (): HtmlString => $this->twoFactorSetupContent())
                        ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('two_factor_enabled') && ! $this->getUser()->hasTwoFactorEnabled()),
                    \Filament\Forms\Components\TextInput::make('two_factor_code')
                        ->label('Код підтвердження')
                        ->helperText('Введи 6 цифр з authenticator app, щоб увімкнути 2FA.')
                        ->numeric()
                        ->length(6)
                        ->dehydrated(false)
                        ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('two_factor_enabled') && ! $this->getUser()->hasTwoFactorEnabled()),
                    \Filament\Forms\Components\Placeholder::make('two_factor_enabled_notice')
                        ->hiddenLabel()
                        ->content('2FA увімкнена. Щоб вимкнути, зніми перемикач і збережи профіль.')
                        ->visible(fn (\Filament\Forms\Get $get): bool => (bool) $get('two_factor_enabled') && $this->getUser()->hasTwoFactorEnabled()),
                ]),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['two_factor_enabled'] = $this->getUser()->hasTwoFactorEnabled();
        $data['two_factor_code'] = null;

        session()->forget('two_factor_setup_secret');

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = $this->getUser();
        $wantsTwoFactor = (bool) ($this->data['two_factor_enabled'] ?? false);

        unset($data['two_factor_enabled'], $data['two_factor_code']);

        if (! $wantsTwoFactor && $user->hasTwoFactorEnabled()) {
            $data['two_factor_secret'] = null;
            $data['two_factor_confirmed_at'] = null;
            session()->forget('two_factor_verified_at');

            return $data;
        }

        if ($wantsTwoFactor && ! $user->hasTwoFactorEnabled()) {
            $secret = session('two_factor_setup_secret');
            $code = (string) ($this->data['two_factor_code'] ?? '');

            if (! $secret || ! app(TwoFactorService::class)->verify($secret, $code)) {
                Notification::make()
                    ->title('Невірний 2FA код')
                    ->body('2FA не увімкнено. Перевір код і спробуй ще раз.')
                    ->danger()
                    ->send();

                $this->halt();
            }

            $data['two_factor_secret'] = $secret;
            $data['two_factor_confirmed_at'] = now();
            session(['two_factor_verified_at' => now()->timestamp]);
            session()->forget('two_factor_setup_secret');
        }

        return $data;
    }

    private function twoFactorSetupContent(): HtmlString
    {
        $secret = session('two_factor_setup_secret');

        if (! $secret) {
            $secret = app(TwoFactorService::class)->generateSecret();
            session(['two_factor_setup_secret' => $secret]);
        }

        $url = app(TwoFactorService::class)->otpauthUrl(
            (string) config('app.name', 'Laravel'),
            (string) $this->getUser()->email,
            $secret,
        );

        return new HtmlString(
            '<div style="display:grid;gap:0.6rem;">'
            . '<div>Додай новий TOTP-акаунт в Google Authenticator, Authy або 1Password.</div>'
            . '<div style="font-size:0.8rem;color:#94a3b8;">Secret:</div>'
            . '<code style="display:block;padding:0.55rem 0.7rem;border:1px solid #374151;border-radius:0.45rem;background:#111827;color:#f8fafc;word-break:break-all;">' . e($secret) . '</code>'
            . '<div style="font-size:0.8rem;color:#94a3b8;">otpauth URL для ручного імпорту:</div>'
            . '<code style="display:block;padding:0.55rem 0.7rem;border:1px solid #374151;border-radius:0.45rem;background:#111827;color:#cbd5e1;word-break:break-all;">' . e($url) . '</code>'
            . '</div>'
        );
    }

    protected function getRedirectUrl(): ?string
    {
        return \Filament\Facades\Filament::getUrl();
    }
}
