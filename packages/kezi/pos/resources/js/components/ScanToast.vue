<template>
    <transition name="toast">
        <div v-if="visible" class="fixed top-20 left-1/2 -translate-x-1/2 z-[200] px-6 py-3 rounded-2xl shadow-2xl font-bold text-sm flex items-center gap-3 animate-in fade-in slide-in-from-top-4"
            :class="type === 'success' 
                ? 'bg-emerald-600 text-white' 
                : 'bg-rose-600 text-white'"
        >
            <span>{{ type === 'success' ? '✅' : '❌' }}</span>
            <span>{{ message }}</span>
        </div>
    </transition>
</template>

<script setup>
defineProps({
    visible: Boolean,
    type: { type: String, default: 'success' },
    message: { type: String, default: '' },
});
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from, .toast-leave-to {
    opacity: 0;
    transform: translate(-50%, -10px);
}
</style>
