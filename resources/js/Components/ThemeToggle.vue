<script setup>
import { useTheme } from '@/Composables/useTheme';
const { isDark, toggleTheme } = useTheme();
</script>

<template>
    <button @click="toggleTheme" class="theme-btn" :title="isDark ? 'Switch to Light' : 'Switch to Dark'">
        <span class="theme-icon">{{ isDark ? '☀️' : '🌙' }}</span>
    </button>
</template>

<style scoped>
.theme-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: 1px solid var(--c-border);
    background: var(--c-surface2);
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.theme-btn:hover {
    background: var(--c-accent-bg);
    border-color: var(--c-accent);
    transform: scale(1.08);
}
.theme-icon { font-size: 1rem; line-height: 1; }
</style>
