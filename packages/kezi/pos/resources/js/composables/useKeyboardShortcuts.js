import { onMounted, onUnmounted } from 'vue';

export function useKeyboardShortcuts(actions) {
    // actions = {
    //     focusSearch: () => {},
    //     openPayment: () => {},
    //     clearCart: () => {},
    //     toggleOrderHistory: () => {},
    //     closeSession: () => {},
    //     closeModal: () => {},
    //     incrementLastItem: () => {},
    //     decrementLastItem: () => {},
    //     removeLastItem: () => {},
    // }

    const handleKeyDown = (event) => {
        // Always allow Escape
        if (event.key === 'Escape') {
            event.preventDefault();
            actions.closeModal?.();
            return;
        }

        // Skip if input is focused (except function keys)
        const isFKey = event.key.startsWith('F') && event.key.length <= 3;
        const activeEl = document.activeElement;
        const isInputFocused = activeEl && (
            activeEl.tagName === 'INPUT' || 
            activeEl.tagName === 'TEXTAREA'
        );

        if (isInputFocused && !isFKey) return;

        switch (event.key) {
            case 'F2':
                event.preventDefault();
                actions.focusSearch?.();
                break;
            case 'F4':
                event.preventDefault();
                actions.openPayment?.();
                break;
            case 'F8':
                event.preventDefault();
                actions.clearCart?.();
                break;
            case 'F9':
                event.preventDefault();
                actions.toggleOrderHistory?.();
                break;
            case 'F10':
                event.preventDefault();
                actions.closeSession?.();
                break;
            case '+':
            case '=':
                actions.incrementLastItem?.();
                break;
            case '-':
                actions.decrementLastItem?.();
                break;
            case 'Delete':
                actions.removeLastItem?.();
                break;
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
}
