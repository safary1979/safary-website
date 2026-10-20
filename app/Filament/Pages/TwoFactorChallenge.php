<?php

namespace App\Filament\Pages;

use App\Services\TwoFactorService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class TwoFactorChallenge extends Page
{
    protected static ?string $slug = 'two-factor-challenge';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.two-factor-challenge';

    public ?string $code = null;

    public bool $isVerifying = false;

    public static function canAccess(): bool
    {
        return Filament::auth()->check();
    }

    public function mount(): void
    {
        $user = Filament::auth()->user();

        if (! $user?->hasTwoFactorEnabled()) {
            $this->redirect(Filament::getUrl());
        }
    }

    /**
     * Verify a complete code after the debounced Livewire model update.
     *
     * Keeping this on the server means keyboard entry, paste, and authenticator
     * autofill all use the same verification path as the explicit form submit.
     */
    public function updatedCode(mixed $value): void
    {
        $code = substr(preg_replace('/\D+/', '', (string) $value) ?? '', 0, 6);
        $this->code = $code;

        if (preg_match('/^\d{6}$/', $code) === 1) {
            $this->verify();
        }
    }

    public function verify(): void
    {
        if ($this->isVerifying) {
            return;
        }

        $code = preg_replace('/\D+/', '', (string) $this->code) ?? '';
        $this->code = substr($code, 0, 6);

        if (preg_match('/^\d{6}$/', $this->code) !== 1) {
            Notification::make()
                ->title('Введи 6 цифр 2FA коду')
                ->warning()
                ->send();

            return;
        }

        $this->isVerifying = true;

        try {
            $user = Filament::auth()->user();
            $twoFactor = app(TwoFactorService::class);

            if (! $user?->hasTwoFactorEnabled() || ! $twoFactor->verify($user->two_factor_secret, $this->code)) {
                Notification::make()
                    ->title('Невірний 2FA код')
                    ->danger()
                    ->send();

                return;
            }

            session(['two_factor_verified_at' => now()->timestamp]);

            $this->redirect(Filament::getUrl(), navigate: false);
        } finally {
            $this->isVerifying = false;
        }
    }
}
