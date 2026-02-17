import { defineStore } from 'pinia';
import { db } from '../db/pos-db';
import * as syncService from '../services/sync-service';
import { useCartStore } from './cart';

export const useSessionStore = defineStore('session', {
    state: () => ({
        currentSession: null,
        availableProfiles: [],
        loading: false,
        error: null,
        showOpenSessionModal: false,
    }),

    getters: {
        hasActiveSession: (state) => !!state.currentSession,
        sessionId: (state) => state.currentSession?.id,
        profileName: (state) => state.currentSession?.profile?.name || '',
    },

    actions: {
        async checkCurrentSession() {
            this.loading = true;
            this.error = null;
            try {
                const data = await syncService.getCurrentSession();
                if (data && data.session) {
                    this.currentSession = data.session;
                    this.showOpenSessionModal = false;
                    
                    const cart = useCartStore();
                    cart.profile = data.session.profile;
                } else {
                    this.currentSession = null;
                    this.showOpenSessionModal = true;
                }
            } catch (error) {
                console.error('Check session failed', error);
                // If offline, check if we have a locally stored session? 
                // The task doesn't specify offline session persistence yet.
                // Assuming session check requires online connectivity for now as it calls API.
                this.error = 'Failed to check session status. Please check your connection.';
            } finally {
                this.loading = false;
            }
        },

        async openSession(profileId, openingCashMinor) {
            this.loading = true;
            this.error = null;
            try {
                const data = await syncService.openSession(profileId, openingCashMinor);
                this.currentSession = data.session;
                this.showOpenSessionModal = false;
                
                const cart = useCartStore();
                cart.profile = data.session.profile;
            } catch (error) {
                if (error.response && error.response.status === 409) {
                    // Already open
                    this.currentSession = error.response.data.session;
                    this.showOpenSessionModal = false;
                } else {
                    this.error = error.response?.data?.message || 'Failed to open session.';
                }
            } finally {
                this.loading = false;
            }
        },

        async closeSession(closingCashMinor) {
            this.loading = true;
            this.error = null;
            try {
                const sessionId = this.sessionId;
                await syncService.closeSession(sessionId, closingCashMinor);
                this.currentSession = null;
                this.showOpenSessionModal = true;
                
                const cart = useCartStore();
                await cart.clearCart();
            } catch (error) {
                this.error = error.response?.data?.message || 'Failed to close session.';
                throw error;
            } finally {
                this.loading = false;
            }
        },

        async loadProfiles() {
            try {
                this.availableProfiles = await db.profiles.toArray();
            } catch (error) {
                console.error('Load profiles failed', error);
            }
        }
    }
});
