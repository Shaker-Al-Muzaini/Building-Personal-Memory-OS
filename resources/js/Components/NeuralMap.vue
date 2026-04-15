<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import * as THREE from 'three';

const props = defineProps({
    ideas:     { type: Array, default: () => [] },
    decisions: { type: Array, default: () => [] },
    people:    { type: Array, default: () => [] },
    balance:   { type: Number, default: 0 },
});

const containerRef = ref(null);
const tooltip      = ref({ visible: false, x: 0, y: 0, text: '', color: '' });

let scene, camera, renderer, animationId;
let nodesGroup;
let nodeMeshes = [];
let raycaster = new THREE.Raycaster();
let mouse     = new THREE.Vector2();

const CATS = {
    idea:     { color: 0x3b82f6, emoji: '💡' },
    decision: { color: 0x8b5cf6, emoji: '⚖️' },
    person:   { color: 0xf43f5e, emoji: '🤝' },
    finance:  { color: 0x10b981, emoji: '💰' },
    core:     { color: 0xf59e0b, emoji: '🧠' },
};

// Helper to create text sprites for 3D labels
function createTextSprite(text, color) {
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    canvas.width = 512;
    canvas.height = 128;
    
    ctx.font = 'bold 64px Inter, Cairo, sans-serif';
    ctx.fillStyle = '#ffffff';
    ctx.textAlign = 'center';
    ctx.shadowColor = color;
    ctx.shadowBlur = 10;
    ctx.fillText(text, 256, 80);

    const texture = new THREE.CanvasTexture(canvas);
    const spriteMat = new THREE.SpriteMaterial({ map: texture, transparent: true });
    const sprite = new THREE.Sprite(spriteMat);
    sprite.scale.set(80, 20, 1);
    return sprite;
}

function initThree() {
    if (!containerRef.value) return;
    const W = containerRef.value.offsetWidth;
    const H = containerRef.value.offsetHeight;

    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(75, W / H, 0.1, 2000);
    camera.position.z = 600;

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(W, H);
    renderer.setPixelRatio(window.devicePixelRatio);
    containerRef.value.appendChild(renderer.domElement);

    // Lights
    scene.add(new THREE.AmbientLight(0xffffff, 0.8));
    const pointLight = new THREE.PointLight(0x069BFF, 4, 1000);
    pointLight.position.set(0, 0, 200);
    scene.add(pointLight);

    nodesGroup = new THREE.Group();
    scene.add(nodesGroup);

    buildGraph();
    animate();
}

function buildGraph() {
    while(nodesGroup.children.length > 0) nodesGroup.remove(nodesGroup.children[0]);
    nodeMeshes = [];

    const addNode = (id, label, cat, pos, size) => {
        // Node Sphere
        const geo = new THREE.SphereGeometry(size, 24, 24);
        const mat = new THREE.MeshPhongMaterial({ 
            color: CATS[cat].color, 
            emissive: CATS[cat].color,
            emissiveIntensity: 0.6,
            shininess: 100 
        });
        const mesh = new THREE.Mesh(geo, mat);
        mesh.position.copy(pos);
        mesh.userData = { id, label, cat };
        nodesGroup.add(mesh);
        nodeMeshes.push(mesh);

        // Label Sprite
        if (label) {
            const shortLabel = label.length > 15 ? label.substring(0, 12) + '...' : label;
            const sprite = createTextSprite(shortLabel, '#' + CATS[cat].color.toString(16).padStart(6, '0'));
            sprite.position.set(pos.x, pos.y + size + 20, pos.z);
            nodesGroup.add(sprite);
        }

        // Connection to core
        if (id !== 'core') {
            const points = [new THREE.Vector3(0,0,0), pos];
            const lineGeo = new THREE.BufferGeometry().setFromPoints(points);
            const lineMat = new THREE.LineBasicMaterial({ 
                color: CATS[cat].color, 
                transparent: true, 
                opacity: 0.3 
            });
            nodesGroup.add(new THREE.Line(lineGeo, lineMat));
        }
    };

    // Core
    addNode('core', 'MEMORY OS', 'core', new THREE.Vector3(0,0,0), 35);

    // Finance
    addNode('finance', `${props.balance}$`, 'finance', new THREE.Vector3(150, 100, 50), 15);

    // Dynamic Nodes with spherical distribution
    const distribute = (items, radius, cat, key) => {
        items.slice(0, 10).forEach((item, i) => {
            const phi = Math.acos(-1 + (2 * i) / items.length);
            const theta = Math.sqrt(items.length * Math.PI) * phi;
            const pos = new THREE.Vector3().setFromSphericalCoords(radius, phi, theta);
            addNode(`${cat}_${i}`, item[key], cat, pos, 12);
        });
    };

    distribute(props.ideas, 220, 'idea', 'content');
    distribute(props.decisions, 320, 'decision', 'problem');
    distribute(props.people, 420, 'person', 'name');
}

function animate() {
    animationId = requestAnimationFrame(animate);
    // Slow, fluid rotation for a "floating in space" feeling
    nodesGroup.rotation.y += 0.0005;
    nodesGroup.rotation.z += 0.0002;
    renderer.render(scene, camera);
}

function onMouseMove(e) {
    const rect = containerRef.value.getBoundingClientRect();
    mouse.x = ((e.clientX - rect.left) / rect.width) * 2 - 1;
    mouse.y = -((e.clientY - rect.top) / rect.height) * 2 + 1;

    raycaster.setFromCamera(mouse, camera);
    const intersects = raycaster.intersectObjects(nodeMeshes);

    if (intersects.length > 0) {
        const n = intersects[0].object.userData;
        tooltip.value = { 
            visible: true, 
            x: e.clientX, 
            y: e.clientY - 40, 
            text: n.label, 
            color: '#' + CATS[n.cat].color.toString(16).padStart(6, '0') 
        };
        document.body.style.cursor = 'pointer';
    } else {
        tooltip.value.visible = false;
        document.body.style.cursor = 'default';
    }
}

function handleResize() {
    if (!containerRef.value) return;
    const W = containerRef.value.offsetWidth;
    const H = containerRef.value.offsetHeight;
    camera.aspect = W / H;
    camera.updateProjectionMatrix();
    renderer.setSize(W, H);
}

onMounted(() => {
    initThree();
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    cancelAnimationFrame(animationId);
    window.removeEventListener('resize', handleResize);
    renderer?.dispose();
    scene?.clear();
});

watch(() => [props.ideas, props.decisions, props.people, props.balance], buildGraph, { deep: true });

</script>

<template>
    <div class="neural-map-container">
        <div class="neural-map-meta">
            <h3 class="text-[10px] font-black uppercase tracking-[0.4em] text-white/30">3D Neural Infrastructure</h3>
            <div class="flex gap-4 mt-2">
                <div v-for="(cfg, key) in CATS" :key="key" class="flex items-center gap-1.5 opacity-60 hover:opacity-100 transition-opacity">
                    <span class="w-2 h-2 rounded-full" :style="{ background: '#' + cfg.color.toString(16) }"></span>
                    <span class="text-[9px] font-bold text-white/70 uppercase">{{ key }}</span>
                </div>
            </div>
        </div>
        
        <div ref="containerRef" class="neural-canvas-wrapper" @mousemove="onMouseMove"></div>

        <Teleport to="body">
            <transition name="pop">
                <div v-if="tooltip.visible"
                    class="neural-hit-tooltip"
                    :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px', borderColor: tooltip.color, color: tooltip.color }">
                    {{ tooltip.text }}
                </div>
            </transition>
        </Teleport>
    </div>
</template>

<style scoped>
.neural-map-container {
    position: relative;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at center, rgba(6,155,255,0.05) 0%, transparent 70%);
}

.neural-map-meta {
    position: absolute;
    top: 2rem;
    left: 2.5rem;
    z-index: 10;
    pointer-events: none;
}

.neural-canvas-wrapper {
    width: 100%;
    height: 100%;
    min-height: 500px;
}

.pop-enter-active, .pop-leave-active { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.pop-enter-from, .pop-leave-to { opacity: 0; transform: scale(0.5) translateX(-50%); }

.neural-hit-tooltip {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.85);
    border: 1px solid;
    border-radius: 16px;
    padding: 0.75rem 1.25rem;
    font-size: 0.8rem;
    font-weight: 800;
    white-space: nowrap;
    transform: translateX(-50%);
    box-shadow: 0 10px 40px rgba(0,0,0,0.8);
    backdrop-filter: blur(20px);
}
</style>
