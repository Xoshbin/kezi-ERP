<div class="fi-fo-field-wrp">
    <div class="fi-fo-field-wrp-label">
        <label class="fi-fo-field-wrp-label-text text-sm font-medium leading-6 text-gray-950 dark:text-white">
            {{ __('numbering.settings.preview') }}
        </label>
    </div>
    <div class="fi-input-wrp flex rounded-lg shadow-sm ring-1 ring-gray-950/10 transition duration-75 bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950/50 dark:to-primary-900/50 dark:ring-white/20 border-primary-200 dark:border-primary-800">
        <div class="min-w-0 flex-1 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 dark:bg-primary-900">
                        <x-heroicon-o-hashtag class="h-4 w-4 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="flex-1">
                    <code class="text-lg font-mono text-primary-700 dark:text-primary-300 font-bold tracking-wide">{{ $example }}</code>
                    <p class="text-xs text-primary-600/70 dark:text-primary-400/70 mt-1">
                        {{ __('numbering.settings.next_number') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="fi-fo-field-wrp-hint">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            {{ __('numbering.settings.preview_help') }}
        </p>
    </div>
</div>
