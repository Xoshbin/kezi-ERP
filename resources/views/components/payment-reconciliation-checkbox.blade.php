<input 
    type="checkbox" 
    wire:click="togglePayment({{ $getRecord()->id }})"
    @checked(in_array($getRecord()->id, $this->selectedPayments))
    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring-primary-500"
>
