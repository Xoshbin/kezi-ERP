import { defineStore } from 'pinia';

export const useConnectivityStore = defineStore('connectivity', {
    state: () => ({
        isOnline: navigator.onLine,
        isSyncing: false,
        lastSyncAt: null
    }),
    actions: {
        setOnline(status) {
            this.isOnline = status;
        },
        setSyncing(status) {
            this.isSyncing = status;
        },
        updateLastSync() {
            this.lastSyncAt = new Date();
        },
        initListeners() {
            window.addEventListener('online', () => this.setOnline(true));
            window.addEventListener('offline', () => this.setOnline(false));
        }
    }
});
