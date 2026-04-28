<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import GlowingTubesCursor from '@/Components/GlowingTubesCursor.vue';
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();
const mouseX = ref(0);
const mouseY = ref(0);

const handleMove = (e) => {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
};

onMounted(() => window.addEventListener('mousemove', handleMove));
onUnmounted(() => window.removeEventListener('mousemove', handleMove));
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0 relative overflow-hidden transition-all duration-700"
        :class="isDark ? 'dark' : 'light'"
        style="background: var(--c-bg); color: var(--c-text)"
    >
        <!-- BACKGROUND CRYSTAL BLOOM -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-blue-500/10 rounded-full blur-[120px] pointer-events-none -z-10 animate-pulse"></div>

        <div class="z-10 mb-8 transform hover:scale-110 transition-transform duration-500">
            <Link href="/">
                <div class="w-20 h-20 rounded-[2.5rem] bg-slate-900/10 dark:bg-slate-100/10 backdrop-blur-xl border border-slate-200 dark:border-slate-800 flex items-center justify-center text-4xl shadow-2xl">
                    🧠
                </div>
            </Link>
        </div>

        <div
            class="w-full sm:max-w-md px-8 py-10 shadow-2xl sm:rounded-[40px] z-20 backdrop-blur-3xl"
            style="background: var(--c-surface); border: 1px solid var(--c-border)"
        >
            <slot />
        </div>

        <div class="mt-8 text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.5em] opacity-50">
            {{ $t('Memory OS') }} — v3.1 Neural Hub
        </div>

        <GlowingTubesCursor :x="mouseX" :y="mouseY" />
    </div>
</template>
