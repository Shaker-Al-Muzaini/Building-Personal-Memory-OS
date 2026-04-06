<script setup>
import { useTheme } from '@/Composables/useTheme';
const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button @click="toggleTheme" class="theme-btn hover-lift" :title="isDark ? 'Light Mode' : 'Dark Mode'">
        <div class="icon-container">
            <transition name="morph">
                <span v-if="isDark" key="sun" class="icon sun">☀️</span>
                <span v-else key="moon" class="icon moon">🌙</span>
            </transition>
        </div>
        <div class="glow-orb"></div>
    </button>
</template>

<style scoped>
.theme-btn {
    position: relative;
    width: 44px; height: 44px;
    border-radius: 14px;
    border: 1px solid var(--c-border);
    background: var(--c-surface);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: var(--c-shadow);
}

.theme-btn:hover {
    border-color: var(--c-accent);
    background: var(--c-surface2);
}

.icon-container {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
}

.icon {
    font-size: 1.2rem;
    display: block;
}

.glow-orb {
    position: absolute;
    inset: 0;
    background: var(--c-accent);
    opacity: 0;
    filter: blur(15px);
    transition: opacity 0.4s;
    z-index: 1;
}

.theme-btn:hover .glow-orb {
    opacity: 0.15;
}

/* Morph Animation */
.morph-enter-active, .morph-leave-active {
    transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.morph-enter-from {
    transform: rotate(-90deg) scale(0);
    opacity: 0;
}

.morph-leave-to {
    transform: rotate(90deg) scale(0);
    opacity: 0;
}
</style>

