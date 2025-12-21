<x-filament::dropdown maxHeight="36rem" teleport>
    <x-slot name="trigger" style="justify-self: center; align-self: center; padding: 5px 0;">
        @if (isset($currentLanguage) && $showFlags)
            <x-filament::link tag="button">
                <div style="width: 2rem; height: 2rem; border-radius: 9999px; overflow: hidden;">
                    {{ svg('flag-1x1-'.$currentLanguage['flag'], '') }}
                </div>
            </x-filament::link>
        @else
            <x-filament::icon-button icon="heroicon-o-language" label="Language switcher"/>
        @endif
    </x-slot>

    <x-filament::dropdown.list style="max-height: 20rem; overflow-y: auto;">
        @foreach ($otherLanguages as $language)
            @php
                $isCurrent = false;
                if (isset($currentLanguage)) {
                    $isCurrent = $currentLanguage['code'] === $language['code'];
                }
            @endphp
            <x-filament::dropdown.list.item :href="route('filament-language-switcher.switch', ['code' => $language['code']])" tag="a">
                <span class="fi-dropdown-list-item-label" style="text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: 100%; text-align: left; display: flex; justify-content: flex-start; gap: 0.75rem;">
                    @if ($showFlags)
                        <div style="width: 1.5rem; height: 1.5rem; flex-shrink: 0;">
                            {{ svg('flag-4x3-'.$language['flag'], '') }}
                        </div>
                        <span>{{ $language['name'] }}</span>
                    @else
                        <span style="{{ $isCurrent ? 'font-weight: 600;' : '' }}">
                            {{ str($language['code'])->upper()->value() . " - {$language['name']}" }}
                        </span>
                    @endif
                </span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
