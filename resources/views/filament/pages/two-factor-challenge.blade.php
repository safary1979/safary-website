<x-filament-panels::page>
    <form wire:submit="verify" wire:loading.attr="aria-busy" style="display: grid; gap: 1rem;">
        <div>
            <h1 style="font-size: 1.25rem; font-weight: 700; color: #f8fafc;">Two-factor authentication</h1>
            <p id="two-factor-hint" style="margin-top: 0.35rem; color: #94a3b8; font-size: 0.9rem;">
                Введи 6-значний код з authenticator app. Після 6 цифр він перевіриться автоматично.
                Можна також натиснути Enter або кнопку.
            </p>
        </div>

        <input
            type="text"
            wire:model.live.debounce.250ms="code"
            inputmode="numeric"
            autocomplete="one-time-code"
            pattern="[0-9]{6}"
            maxlength="6"
            required
            aria-label="6-значний код двофакторної автентифікації"
            aria-describedby="two-factor-hint"
            autofocus
            style="width: 100%; border: 1px solid #374151; border-radius: 0.5rem; background: #111827; color: #f8fafc; padding: 0.65rem 0.8rem; font-size: 1.1rem; letter-spacing: 0.18em;"
        />

        <button
            type="submit"
            wire:loading.attr="disabled"
            style="border: 1px solid #d97706; border-radius: 0.5rem; background: #f59e0b; color: #111827; padding: 0.6rem 0.9rem; font-weight: 700;"
        >
            <span wire:loading.remove>Підтвердити</span>
            <span wire:loading>Обробляю…</span>
        </button>
    </form>
</x-filament-panels::page>
