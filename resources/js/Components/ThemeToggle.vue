<script setup>
import { useTheme } from '@/Composables/useTheme';
const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button @click="toggleTheme" 
        class="relative w-12 h-12 rounded-2xl flex items-center justify-center transition-all duration-500 overflow-hidden border border-border group"
        :class="isDark ? 'bg-indigo-600/10 border-indigo-500/20' : 'bg-amber-500/10 border-amber-500/20'"
        :title="isDark ? 'Switch to Light Phase' : 'Switch to Dark Phase'">
        
        <div class="absolute inset-0 bg-gradient-to-br opacity-0 group-hover:opacity-100 transition-opacity duration-500"
            :class="isDark ? 'from-indigo-500/20 to-transparent' : 'from-amber-500/20 to-transparent'"></div>
        
        <div class="relative z-10 flex items-center justify-center">
            <transition name="neural-morph" mode="out-in">
                <span v-if="isDark" key="sun" class="text-xl filter drop-shadow-[0_0_8px_rgba(99,102,241,0.8)]">☀️</span>
                <span v-else key="moon" class="text-xl filter drop-shadow-[0_0_8px_rgba(245,158,11,0.8)]">🌙</span>
            </transition>
        </div>
    </button>
</template>

<style scoped>
.neural-morph-enter-active, .neural-morph-leave-active {
    transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.neural-morph-enter-from {
    transform: rotate(-180deg) scale(0);
    opacity: 0;
}
.neural-morph-leave-to {
    transform: rotate(180deg) scale(0);
    opacity: 0;
}
</style>
