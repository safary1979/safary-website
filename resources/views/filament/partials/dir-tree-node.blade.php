{{--
  Recursive directory tree node.
  Variables expected:
    $node         — ['name', 'relPath', 'fullPath', 'hasChildren', 'children']
    $depth        — current nesting level (0 = root children)
    $expandedDirs — array of expanded relPaths (from Livewire component)
    $activeDirs   — array of selected relPaths (from Livewire component)
--}}
@php
    $isExpanded = in_array($node['relPath'], $expandedDirs, true);
    $isSelected = in_array($node['relPath'], $activeDirs, true);
    $indent     = $depth * 1.1;   // rem
@endphp

<div style="padding-left: {{ $indent }}rem;">
    <div style="display:flex; align-items:center; gap:.25rem; padding:.18rem 0;">

        {{-- Expand / collapse toggle --}}
        @if ($node['hasChildren'])
            <button wire:click="toggleExpandDir(@js($node['relPath']))"
                    title="{{ $isExpanded ? 'Згорнути' : 'Розгорнути' }}"
                    style="flex-shrink:0; width:1.1rem; height:1.1rem; display:flex; align-items:center;
                           justify-content:center; font-size:.7rem; cursor:pointer;
                           color:#6b7280; background:transparent; border:0; padding:0;
                           transition: transform .1s;">
                {{ $isExpanded ? '▾' : '▸' }}
            </button>
        @else
            <span style="flex-shrink:0; width:1.1rem; display:inline-block;"></span>
        @endif

        {{-- Folder icon + name (clickable = select/deselect) --}}
        <button wire:click="toggleBotDir(@js($node['relPath']))"
                title="{{ $node['fullPath'] }}"
                style="display:flex; align-items:center; gap:.3rem; font-size:0.78rem;
                       padding:.2rem .55rem; border-radius:.35rem; cursor:pointer;
                       border:1px solid {{ $isSelected ? '#3b82f6' : '#1f2937' }};
                       background:{{ $isSelected ? '#1e3a5f' : 'transparent' }};
                       color:{{ $isSelected ? '#60a5fa' : '#9ca3af' }};
                       transition: all .12s;">
            <span style="font-size:.72rem; opacity:.7;">
                {{ $node['hasChildren'] ? '📂' : '📁' }}
            </span>
            @if ($isSelected)
                <span style="font-size:.6rem; color:#60a5fa;">✓</span>
            @endif
            {{ $node['name'] }}
        </button>
    </div>

    {{-- Children (only when expanded) --}}
    @if ($isExpanded && !empty($node['children']))
        @foreach ($node['children'] as $child)
            @include('filament.partials.dir-tree-node', [
                'node'         => $child,
                'depth'        => $depth + 1,
                'expandedDirs' => $expandedDirs,
                'activeDirs'   => $activeDirs,
            ])
        @endforeach
    @endif
</div>
