@php
    use Filament\Facades\Filament;

    $panel = Filament::getCurrentPanel();
    $clusters = $panel?->getClusters() ?? [];
    $currentCluster = null;
    
    // Get current cluster if we're in one
    $currentUrl = request()->url();
    foreach ($clusters as $cluster) {
        $clusterUrl = $cluster::getUrl();
        if (str_starts_with($currentUrl, $clusterUrl)) {
            $currentCluster = $cluster;
            break;
        }
    }
@endphp

@if (count($clusters) > 0)
    <x-filament::dropdown
        placement="bottom-start"
        teleport
        class="fi-app-switcher"
    >
        <x-slot name="trigger">
            <x-filament::icon-button
                color="gray"
                icon="heroicon-o-squares-2x2"
                icon-size="lg"
                :label="__('navigation.app_switcher')"
                class="fi-app-switcher-btn"
            />
        </x-slot>

        <div class="fi-app-switcher-content p-2">
            <div class="grid grid-cols-3 gap-2">
                @foreach ($clusters as $cluster)
                    @php
                        $isActive = $currentCluster === $cluster;
                        $icon = $cluster::getNavigationIcon();
                        $label = $cluster::getNavigationLabel();
                        $url = $cluster::getUrl();
                    @endphp
                    
                    <a
                        href="{{ $url }}"
                        @class([
                            'fi-app-switcher-item flex flex-col items-center justify-center gap-1 rounded-lg p-3 text-sm transition-colors',
                            'hover:bg-gray-100 dark:hover:bg-white/5',
                            'bg-primary-50 dark:bg-primary-400/10 text-primary-600 dark:text-primary-400' => $isActive,
                            'text-gray-700 dark:text-gray-200' => ! $isActive,
                        ])
                    >
                        @if ($icon)
                            <x-filament::icon
                                :icon="$icon"
                                class="h-6 w-6"
                            />
                        @endif
                        <span class="text-center text-xs font-medium leading-tight">
                            {{ $label }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </x-filament::dropdown>
@endif
