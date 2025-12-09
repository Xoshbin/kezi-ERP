@php use Filament\Facades\Filament; @endphp
<div class="space-y-6">
    {{-- Explanation Section --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg border border-blue-200 dark:border-blue-800">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                          clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-1">
                    {{ __('Why is this required?') }}
                </h4>
                <p class="text-sm text-blue-700 dark:text-blue-300">
                    {{ $explanation }}
                </p>
            </div>
        </div>
    </div>

    {{-- Solution Section --}}
    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-green-800 dark:text-green-200 mb-1">
                    {{ __('What you need to do') }}
                </h4>
                <p class="text-sm text-green-700 dark:text-green-300">
                    {{ $solution }}
                </p>
            </div>
        </div>
    </div>

    {{-- Step-by-step Instructions --}}
    <div class="bg-gray-50 dark:bg-gray-900/20 p-4 rounded-lg border border-gray-200 dark:border-gray-800">
        <h4 class="text-sm font-medium text-gray-800 dark:text-gray-200 mb-3 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                      clip-rule="evenodd"></path>
            </svg>
            {{ __('Step-by-step instructions') }}
        </h4>
        <ol class="space-y-2">
            @foreach($steps as $step)
                <li class="flex items-start space-x-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full flex items-center justify-center text-xs font-medium">
                        {{ $loop->iteration }}
                    </span>
                    <span class="text-sm text-gray-700 dark:text-gray-300 flex-1">
                        {{ $step }}
                    </span>
                </li>
            @endforeach
        </ol>
    </div>

    {{-- Help Text --}}
    <div class="bg-amber-50 dark:bg-amber-900/20 p-4 rounded-lg border border-amber-200 dark:border-amber-800">
        <div class="flex items-start space-x-3">
            <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                          d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                          clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h4 class="text-sm font-medium text-amber-800 dark:text-amber-200 mb-1">
                    {{ __('Important') }}
                </h4>
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    {{ $help_text }}
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('filament.jmeryar.accounting.resources.vendor-bills.create', ['tenant' => Filament::getTenant()]) }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                      clip-rule="evenodd"></path>
            </svg>
            {{ __('Create Vendor Bill') }}
        </a>

        <a href="{{ route('filament.jmeryar.accounting.resources.vendor-bills.index', ['tenant' => Filament::getTenant()]) }}"
           target="_blank"
           class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                      d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                      clip-rule="evenodd"></path>
            </svg>
            {{ __('View Vendor Bills') }}
        </a>
    </div>
</div>
