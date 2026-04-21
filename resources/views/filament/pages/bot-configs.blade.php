<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="md:col-span-1">
            <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 p-4">
                <h3 class="text-sm font-semibold mb-3 text-gray-950 dark:text-white">Папки конфігів</h3>
                @forelse ($this->getConfigTree() as $folder)
                    <div class="mb-3">
                        <div class="text-xs font-semibold text-primary-600 dark:text-primary-400 mb-1">
                            {{ $folder['name'] }}
                        </div>
                        <ul class="space-y-1">
                            @foreach ($folder['files'] as $file)
                                <li>
                                    <button type="button"
                                        wire:click="openFile(@js($file['path']))"
                                        class="text-sm text-left w-full px-2 py-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800 {{ $selectedPath === $file['path'] ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300' }}">
                                        {{ $file['name'] }}
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">
                        Папка <code>{{ env('BOT_CONFIGS_PATH') }}</code> порожня або не існує.
                    </p>
                @endforelse
            </div>
        </div>

        <div class="md:col-span-3">
            @if ($selectedPath)
                <div class="mb-3 text-xs text-gray-500 font-mono">{{ $selectedPath }}</div>
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            @else
                <div class="fi-section rounded-xl bg-white dark:bg-gray-900 ring-1 ring-gray-950/5 dark:ring-white/10 p-8 text-center text-gray-500">
                    Оберіть файл зліва для перегляду та редагування
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
