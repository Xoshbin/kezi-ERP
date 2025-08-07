// Import the custom theme CSS file, which in turn imports the base Filament theme.
import '../../../css/filament/jmeryar/theme.css';

// AlpineJS component for money input formatting
document.addEventListener('alpine:init', () => {
    Alpine.data('moneyInput', ({ state }) => ({
        state: state,
        
        init() {
            // Format the initial value from the state for display
            if (this.state) {
                this.formatDisplay(this.state, this.$el);
            }
            
            // When the state changes from the server, reformat the input
            this.$watch('state', (newState) => this.formatDisplay(newState, this.$el));
        },

        onInput(event) {
            let value = event.target.value;
            const cursorPosition = event.target.selectionStart;
            const originalLength = value.length;

            // Remove non-numeric characters, allowing one decimal point
            let cleanValue = value.replace(/[^0-9.]/g, '');
            const parts = cleanValue.split('.');
            if (parts.length > 2) {
                cleanValue = parts + '.' + parts.slice(1).join('');
            }

            // Update the Livewire state with the clean numeric string
            this.state = cleanValue;

            // Format the input for display without adding decimals automatically
            if (cleanValue) {
                const integerPart = parts;
                const decimalPart = parts !== undefined ? '.' + parts : '';
                const formattedInteger = new Intl.NumberFormat('en-US').format(integerPart);
                event.target.value = formattedInteger + decimalPart;
            } else {
                event.target.value = '';
            }
            
            // Attempt to restore the cursor position after formatting
            const newLength = event.target.value.length;
            event.target.setSelectionRange(cursorPosition + (newLength - originalLength), cursorPosition + (newLength - originalLength));
        },
        
        formatDisplay(value, el) {
            if (value === null || value === undefined) {
                el.value = '';
                return;
            }
            
            let cleanValue = String(value).replace(/,/g, '');
            const number = parseFloat(cleanValue);

            if (!isNaN(number)) {
                // Format with 2 decimal places for display consistency on load/update
                el.value = number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                el.value = '';
            }
        }
    }));
});