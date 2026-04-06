<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const segments = 15;
const trail = ref(Array.from({ length: segments }, () => ({ x: 0, y: 0 })));
const mouse = ref({ x: 0, y: 0 });

const handleMouseMove = (e) => {
    mouse.value.x = e.clientX;
    mouse.value.y = e.clientY;
};

// Smooth tracking logic
let rafId = null;
const updateTrail = () => {
    let tx = mouse.value.x;
    let ty = mouse.value.y;

    for (let i = 0; i < segments; i++) {
        let p = trail.value[i];
        p.x += (tx - p.x) * 0.45; 
        p.y += (ty - p.y) * 0.45;
        tx = p.x;
        ty = p.y;
    }
    
    rafId = requestAnimationFrame(updateTrail);
};

onMounted(() => {
    window.addEventListener('mousemove', handleMouseMove);
    updateTrail();
});

onUnmounted(() => {
    window.removeEventListener('mousemove', handleMouseMove);
    if (rafId) cancelAnimationFrame(rafId);
});
</script>

<template>
    <div class="cursor-trail-container">
        <div 
            v-for="(p, i) in trail" 
            :key="i"
            class="trail-segment"
            :style="{
                transform: `translate3d(${p.x}px, ${p.y}px, 0) scale(${1 - i/segments})`,
                opacity: 0.8 * (1 - i/segments),
                backgroundColor: 'var(--c-accent)',
                boxShadow: `0 0 ${10 - i}px var(--c-accent), 0 0 ${20 - i}px var(--c-accent)`
            }"
        ></div>
    </div>
</template>

<style scoped>
.cursor-trail-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 999999;
}

.trail-segment {
    position: absolute;
    top: -6px;
    left: -6px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    will-change: transform, opacity;
    filter: blur(0.5px);
}
</style>

