<template>
    <div class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-xl transition-all duration-500">
        <div class="bg-white dark:bg-gray-900 w-full max-w-md rounded-[2.5rem] shadow-2xl border border-white/20 dark:border-gray-800 overflow-hidden transform transition-all scale-100 opacity-100">
            <!-- Header -->
            <div class="p-8 pb-4 text-center">
                <div class="w-20 h-20 bg-primary-100 dark:bg-primary-900/30 rounded-3xl flex items-center justify-center text-primary-600 mx-auto mb-6 shadow-inner">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight">Open Session</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-2 font-medium">Ready to start your shift?</p>
            </div>

            <div class="p-8 pt-4 space-y-6">
                <!-- Profile Selection -->
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-widest text-gray-400 ml-1">Terminal Profile</label>
                    <div class="relative group">
                        <select 
                            v-model="form.profileId"
                            class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-primary-500 rounded-2xl py-4 px-5 appearance-none outline-none transition-all font-bold text-gray-800 dark:text-gray-200"
                        >
                            <option value="" disabled>Select a profile</option>
                            <option v-for="profile in sessionStore.availableProfiles" :key="profile.id" :value="profile.id">
                                {{ profile.name }}
                            </option>
                        </select>
                        <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Opening Cash -->
                <div class="space-y-2">
                    <label class="text-xs font-bold uppercase tracking-widest text-gray-400 ml-1">Opening Cash Balance</label>
                    <div class="relative group">
                        <span class="absolute inset-y-0 left-5 flex items-center text-primary-600 font-bold text-lg">$</span>
                        <input 
                            v-model="form.openingCash"
                            type="number" 
                            step="0.01"
                            placeholder="0.00"
                            class="w-full bg-gray-50 dark:bg-gray-800 border-2 border-transparent focus:border-primary-500 rounded-2xl py-4 pl-10 pr-5 outline-none transition-all font-black text-2xl text-gray-900 dark:text-white"
                        >
                    </div>
                    <p class="text-[10px] text-gray-400 ml-1">Count all physical cash in the drawer before starting.</p>
                </div>

                <!-- Error Display -->
                <div v-if="sessionStore.error" class="bg-rose-50 dark:bg-rose-500/10 border border-rose-100 dark:border-rose-500/20 text-rose-600 dark:text-rose-400 px-4 py-3 rounded-2xl text-sm font-medium flex gap-3 items-center animate-shake">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    {{ sessionStore.error }}
                </div>

                <!-- Submit Button -->
                <button 
                    @click="handleOpenSession"
                    :disabled="sessionStore.loading || !isValid"
                    class="w-full bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed text-white py-5 rounded-3xl font-black text-xl shadow-xl shadow-primary-500/30 transition-all active:scale-[0.98] flex items-center justify-center gap-3"
                >
                    <span v-if="sessionStore.loading" class="animate-spin rounded-full h-6 w-6 border-2 border-white/20 border-t-white"></span>
                    <span v-else>Open Session & Start Selling</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, computed, onMounted, watch } from 'vue';
import { useSessionStore } from '../stores/session';

const sessionStore = useSessionStore();

const form = reactive({
    profileId: '',
    openingCash: '0.00'
});

const isValid = computed(() => {
    return form.profileId && parseFloat(form.openingCash) >= 0;
});

onMounted(async () => {
    await sessionStore.loadProfiles();
    
    // Auto-select if only one profile exists
    if (sessionStore.availableProfiles.length === 1) {
        form.profileId = sessionStore.availableProfiles[0].id;
    }
});

const handleOpenSession = async () => {
    if (!isValid.value) return;
    
    // Convert to minor units (cents)
    const minorUnits = Math.round(parseFloat(form.openingCash) * 100);
    await sessionStore.openSession(form.profileId, minorUnits);
};
</script>

<style scoped>
.animate-shake {
    animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
}

@keyframes shake {
    10%, 90% { transform: translate3d(-1px, 0, 0); }
    20%, 80% { transform: translate3d(2px, 0, 0); }
    30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
    40%, 60% { transform: translate3d(4px, 0, 0); }
}
</style>
