<div>
    {{-- Main layout grid for the panels --}}
    <x-filament::grid class="gap-6" xl-grid-cols="3">

        {{-- Left Panel: Bank Statement Lines --}}
        <x-filament::section class="xl:col-span-1">
            <x-slot name="heading">
                Bank Transactions
            </x-slot>

            <div class="mt-4 space-y-2">
                @forelse ($statementLines as $line)
                    <div class="flex items-center p-3 border rounded-md shadow-sm hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        <input type="checkbox" wire:model.live="selectedLines" value="{{ $line->id }}" class="w-4 h-4 mr-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <div class="flex-grow text-sm">
                            <p class="font-medium text-gray-900 dark:text-white">{{ $line->description }}</p>
                            <p class="text-gray-500 dark:text-gray-400">{{ $line->date->toFormattedDateString() }} - {{ $line->partner->name ?? '' }}</p>
                        </div>
                        <div class="text-sm font-mono text-right text-gray-700 dark:text-gray-200">{{ $line->amount }}</div>
                    </div>
                @empty
                    <p class="text-center text-gray-500">No unreconciled bank lines.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Right Panel: System Payments --}}
        <x-filament::section class="xl:col-span-1">
            <x-slot name="heading">
                System Payments (Confirmed)
            </x-slot>

            <div class="mt-4 space-y-2">
                 @forelse ($payments as $payment)
                    <div class="flex items-center p-3 border rounded-md shadow-sm hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        <input type="checkbox" wire:model.live="selectedPayments" value="{{ $payment->id }}" class="w-4 h-4 mr-4 text-primary-600 bg-gray-100 border-gray-300 rounded focus:ring-primary-500 dark:focus:ring-primary-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        <div class="flex-grow text-sm">
                           <p class="font-medium text-gray-900 dark:text-white">{{ $payment->partner->name ?? 'N/A' }}</p>
                           <p class="text-gray-500 dark:text-gray-400">{{ $payment->reference }} ({{ $payment->payment_type }})</p>
                        </div>
                        <div class="text-sm font-mono text-right text-gray-700 dark:text-gray-200">{{ $payment->amount }}</div>
                    </div>
                @empty
                    <p class="text-center text-gray-500">No confirmable payments found.</p>
                 @endforelse
            </div>
        </x-filament::section>

        {{-- Third Panel: Summary & Actions --}}
        <x-filament::section class="xl:col-span-1">
            <x-slot name="heading">
                Summary
            </x-slot>

            <div class="mt-4 space-y-4">
                <div class="p-4 bg-gray-100 rounded-lg dark:bg-gray-800">
                    <dl class="space-y-2">
                        <div class="flex items-center justify-between">
                            <dt class="text-sm font-medium text-gray-500">Bank Total:</dt>
                            <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $this->bankTotal }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-sm font-medium text-gray-500">To Balance:</dt>
                            <dd class="text-sm font-mono text-gray-900 dark:text-white">{{ $this->paymentTotal }}</dd>
                        </div>
                        <div class="flex items-center justify-between pt-2 mt-2 border-t dark:border-white/10">
                            <dt class="font-semibold text-gray-900 dark:text-white">Difference:</dt>
                            <dd @class([
                                'font-semibold font-mono',
                                'text-gray-900 dark:text-white' => $this->difference->isZero(),
                                'text-danger-600 dark:text-danger-400' => !$this->difference->isZero(),
                            ])>
                                {{ $this->difference }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <x-filament::button
                    type="button"
                    wire:click="reconcile"
                    class="w-full"
                    icon="heroicon-m-check-circle"
                    :disabled="$this->difference->isZero() === false || (count($selectedLines) === 0 && count($selectedPayments) === 0)"
                >
                    Reconcile
                </x-filament::button>
            </div>
        </x-filament::section>

    </x-filament::grid>
</div>
