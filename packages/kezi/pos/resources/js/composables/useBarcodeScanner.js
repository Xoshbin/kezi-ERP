import { ref, onMounted, onUnmounted } from 'vue';

export function useBarcodeScanner(onScan) {
    const isListening = ref(true);
    let buffer = '';
    let lastKeyTime = 0;
    const THRESHOLD_MS = 50;  // Max time between keystrokes for scanner
    const MIN_LENGTH = 3;     // Minimum barcode length
    let timeoutId = null;

    const handleKeyDown = (event) => {
        if (!isListening.value) return;
        
        // Don't intercept if user is focused on an input/textarea
        const activeEl = document.activeElement;
        const isInputFocused = activeEl && (
            activeEl.tagName === 'INPUT' || 
            activeEl.tagName === 'TEXTAREA' || 
            activeEl.isContentEditable
        );
        
        // EXCEPTION: Allow scanner even when search input is focused
        // (common POS pattern — cashier can scan while search bar is active)
        // We detect this by speed: scanner input is always faster than human typing
        
        const now = Date.now();
        const timeDelta = now - lastKeyTime;
        lastKeyTime = now;

        if (event.key === 'Enter') {
            if (buffer.length >= MIN_LENGTH && timeDelta < THRESHOLD_MS) {
                // This looks like scanner input!
                event.preventDefault();
                event.stopPropagation();
                onScan(buffer);
                buffer = '';
                clearTimeout(timeoutId);
                return;
            }
            buffer = '';
            return;
        }

        // Only collect printable characters
        if (event.key.length === 1) {
            if (timeDelta > 500) {
                // Too slow — reset buffer (new sequence)
                buffer = '';
            }
            buffer += event.key;
            
            // Auto-clear buffer after timeout (in case no Enter comes)
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => { buffer = ''; }, 500);
        }
    };

    onMounted(() => {
        window.addEventListener('keydown', handleKeyDown, true); // capture phase
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown, true);
        clearTimeout(timeoutId);
    });

    return {
        isListening,
        pauseScanner: () => { isListening.value = false; },
        resumeScanner: () => { isListening.value = true; },
    };
}
