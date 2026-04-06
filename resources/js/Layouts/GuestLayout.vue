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
        class="flex min-h-screen flex-col items-center pt-6 sm:justify-center sm:pt-0"
        :class="isDark ? 'os-dark bg-black' : 'os-light bg-gray-50'"
        style="background: var(--c-bg); color: var(--c-text)"
    >
        <div class="z-10">
            <Link href="/">
                <ApplicationLogo class="h-20 w-20 fill-current text-accent transition-colors" />
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden px-6 py-4 shadow-xl sm:max-w-md sm:rounded-2xl z-20"
            style="background: var(--c-surface); border: 1px solid var(--c-border)"
        >
            <slot />
        </div>
        <GlowingTubesCursor :x="mouseX" :y="mouseY" />
    </div>
</template>
