<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    x: { type: Number, default: 0 },
    y: { type: Number, default: 0 }
});

// We use 15 circles for performance but with a bigger impact
const circleCount = 15;

// The effect should follow the mouse with a slight lerp or offset for smoothness
const followers = ref([]);
</script>

<template>
    <div 
        class="mouse-follower-container pointer-events-none"
        :style="{ 
            transform: `translate3d(${x}px, ${y}px, 0)`,
        }"
    >
        <div class="circles-wrapper">
            <div 
                v-for="i in circleCount" 
                :key="i"
                class="circle"
                :style="{ '--i': i }"
            ></div>
        </div>
    </div>
</template>

<style scoped>
.mouse-follower-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 0;
    height: 0;
    z-index: 9999;
    pointer-events: none;
    perspective: 1000px;
    will-change: transform;
}

.circles-wrapper {
    position: absolute;
    transform-style: preserve-3d;
    left: -50px; /* Center circle on mouse */
    top: -50px;
}

.circle {
    position: absolute;
    background: transparent;
    border: 2px solid var(--c-accent); /* Base color, will be hue-shifted */
    width: calc(var(--i) * 1.8rem); /* Slightly bigger rings for better visibility */
    aspect-ratio: 1;
    border-radius: 50%;
    transform-style: preserve-3d;
    transform: rotateX(70deg) translateZ(50px);
    animation: animate-3d 3s ease-in-out calc(var(--i) * 0.1s) infinite;
    /* Neon glow effect */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2), 
                inset 0 0 20px var(--c-accent);
    opacity: calc(1.2 - (var(--i) * 0.07));
    backdrop-filter: blur(1px);
}

@keyframes animate-3d {
    0%, 100% {
        transform: rotateX(75deg) translateZ(50px) translateY(-40px) rotate(0deg);
        filter: hue-rotate(0deg) brightness(1);
    }
    50% {
        transform: rotateX(75deg) translateZ(50px) translateY(40px) rotate(180deg);
        filter: hue-rotate(360deg) brightness(1.5);
    }
}

/* Specific adjustments for Light Mode to make it pop but not be too harsh */
:global(.os-light) .circle {
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.05), 
                inset 0 0 10px var(--c-accent-bg);
}
</style>
