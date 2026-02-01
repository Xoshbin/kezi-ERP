<div class="px-4 py-3 bg-gray-50 dark:bg-white/5 border-t border-gray-200 dark:border-white/10">
    <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
            {{ $label }}
        </span>
        <span class="text-lg font-bold text-primary-600 dark:text-primary-400">
            {{ number_format($total, 2) }} {{ $currency }}
        </span>
    </div>
</div>
