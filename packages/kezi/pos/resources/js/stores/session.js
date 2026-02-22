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
        lastClosedSessionSummary: null,
    }),

    getters: {
        hasActiveSession: (state) => !!state.currentSession,
        sessionId: (state) => state.currentSession?.id || null,
        userName: (state) => state.currentSession?.user?.name || '',
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
                    await this.loadProfiles();
                }
            } catch (error) {
                if (error.response && error.response.status === 404) {
                    this.currentSession = null;
                    this.showOpenSessionModal = true;
                    await this.loadProfiles();
                } else {
                    console.error('Check session failed', error);
                    this.currentSession = null;
                    this.showOpenSessionModal = true;
                    this.error = 'Failed to check session status. Please check your connection.';
                }
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
                const data = await syncService.closeSession(sessionId, closingCashMinor);
                
                this.lastClosedSessionSummary = data.summary;
                this.currentSession = null;
                this.showOpenSessionModal = true;
                
                const cart = useCartStore();
                await cart.clearCart();
                
                return data;
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
