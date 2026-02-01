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
    // List of clusters to hide from the app switcher
    $hiddenClusters = [
        'Jmeryar\Payment\Filament\Clusters\Payment\PaymentCluster',
        'Jmeryar\Product\Filament\Clusters\Product\ProductCluster',
    ];
@endphp

@if (count($clusters) > 0)
    <div 
        x-data="{ open: false }" 
        x-on:click.away="open = false"
        x-on:keydown.escape.window="open = false"
        style="position: relative;"
    >
        {{-- Trigger Button --}}
        <x-filament::icon-button
            x-on:click="open = !open"
            icon="heroicon-o-squares-2x2"
            icon-size="lg"
            color="gray"
            :label="__('navigation.app_switcher')"
        />

        {{-- Dropdown Panel --}}
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-95"
            x-cloak
            style="
                position: fixed;
                top: 90px;
                left: 12px;
                width: 420px;
                max-height: calc(100vh - 120px);
                overflow-y: auto;
                overflow-x: hidden;
                background: white;
                border-radius: 12px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
                border: 1px solid rgba(0, 0, 0, 0.08);
                z-index: 9999;
                padding: 16px;
            "
            class="dark:bg-gray-900 dark:border-white/10"
        >
            {{-- Pointer Arrow --}}
            <div style="
                position: absolute;
                top: -8px;
                left: 24px;
                width: 16px;
                height: 16px;
                background: white;
                border-left: 1px solid rgba(0, 0, 0, 0.08);
                border-top: 1px solid rgba(0, 0, 0, 0.08);
                transform: rotate(45deg);
                z-index: -1;
            " class="dark:bg-gray-900 dark:border-white/10"></div>
            <div style="
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
            ">
                @foreach ($clusters as $cluster)
                    @if (in_array($cluster, $hiddenClusters))
                        @continue
                    @endif
                    @php
                        $isActive = $currentCluster === $cluster;
                        $icon = $cluster::getNavigationIcon();
                        $label = $cluster::getNavigationLabel();
                        $url = $cluster::getUrl();
                    @endphp
                    
                    <a
                        href="{{ $url }}"
                        x-on:click="open = false"
                        style="
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            gap: 10px;
                            padding: 12px 8px;
                            border-radius: 10px;
                            text-decoration: none;
                            transition: background-color 0.15s ease;
                            min-width: 0;
                            overflow: hidden;
                            {{ $isActive ? 'background-color: rgb(255, 251, 235);' : '' }}
                        "
                        onmouseover="this.style.backgroundColor='{{ $isActive ? 'rgb(254, 243, 199)' : 'rgb(249, 250, 251)' }}'"
                        onmouseout="this.style.backgroundColor='{{ $isActive ? 'rgb(255, 251, 235)' : 'transparent' }}'"
                    >
                        {{-- Icon Circle --}}
                        <div style="
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            width: 48px;
                            height: 48px;
                            border-radius: 50%;
                            {{ $isActive 
                                ? 'background-color: rgb(254, 215, 170); color: rgb(194, 65, 12);' 
                                : 'background-color: rgb(243, 244, 246); color: rgb(107, 114, 128);' 
                            }}
                        ">
                            @if ($icon)
                                <x-filament::icon
                                    :icon="$icon"
                                    style="width: 24px; height: 24px;"
                                />
                            @endif
                        </div>
                        
                        {{-- Label --}}
                        <span style="
                            font-size: 12px;
                            font-weight: 500;
                            text-align: center;
                            line-height: 1.3;
                            max-width: 100%;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            white-space: nowrap;
                            {{ $isActive 
                                ? 'color: rgb(194, 65, 12);' 
                                : 'color: rgb(55, 65, 81);' 
                            }}
                        ">
                            {{ $label }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@endif
