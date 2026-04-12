<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    ideas:     { type: Array, default: () => [] },
    decisions: { type: Array, default: () => [] },
    people:    { type: Array, default: () => [] },
    balance:   { type: Number, default: 0 },
});

const canvasRef = ref(null);
const tooltip   = ref({ visible: false, x: 0, y: 0, text: '', color: '' });

let ctx, animId, nodes = [], W, H;

// ─── Node categories config ───
const CATS = {
    idea:     { color: '#3b82f6', emoji: '💡', label: 'Idea' },
    decision: { color: '#8b5cf6', emoji: '⚖️', label: 'Decision' },
    person:   { color: '#f43f5e', emoji: '🤝', label: 'Person' },
    finance:  { color: '#10b981', emoji: '💰', label: 'Finance' },
    core:     { color: '#f59e0b', emoji: '🧠', label: 'Memory OS' },
};

// ─── Build node graph ───
function buildNodes() {
    nodes = [];
    const cx = W / 2, cy = H / 2;

    // Core node
    nodes.push({ id: 'core', label: 'Memory OS', cat: 'core', x: cx, y: cy, vx: 0, vy: 0, r: 24, angle: 0, orbit: 0 });

    // Finance node
    nodes.push({
        id: 'finance', label: `💰 ${props.balance >= 0 ? '+' : ''}${props.balance}$`,
        cat: 'finance', x: cx + 140, y: cy - 60, vx: 0, vy: 0, r: 16, angle: Math.PI * 0.3, orbit: 160
    });

    // Ideas
    props.ideas.slice(0, 6).forEach((idea, i) => {
        const angle = (i / 6) * Math.PI * 2;
        nodes.push({
            id: 'idea_' + i, label: (idea.content || '').slice(0, 20) + '…',
            cat: 'idea', x: cx + Math.cos(angle) * 200, y: cy + Math.sin(angle) * 200,
            vx: 0, vy: 0, r: 12, angle, orbit: 200
        });
    });

    // Decisions
    props.decisions.slice(0, 5).forEach((dec, i) => {
        const angle = (i / 5) * Math.PI * 2 + 0.6;
        nodes.push({
            id: 'dec_' + i, label: (dec.problem || '').slice(0, 20) + '…',
            cat: 'decision', x: cx + Math.cos(angle) * 270, y: cy + Math.sin(angle) * 270,
            vx: 0, vy: 0, r: 12, angle, orbit: 270
        });
    });

    // People
    props.people.slice(0, 6).forEach((p, i) => {
        const angle = (i / 6) * Math.PI * 2 + 1.2;
        nodes.push({
            id: 'person_' + i, label: p.name || '',
            cat: 'person', x: cx + Math.cos(angle) * 340, y: cy + Math.sin(angle) * 340,
            vx: 0, vy: 0, r: 12, angle, orbit: 340
        });
    });
}

// ─── Draw ───
function draw(time) {
    ctx.clearRect(0, 0, W, H);

    const cx = W / 2, cy = H / 2;
    const t = time * 0.0003;

    // Edges - drawn with gradient lines
    nodes.slice(1).forEach(n => {
        const core = nodes[0];
        const grad = ctx.createLinearGradient(core.x, core.y, n.x, n.y);
        const col = CATS[n.cat].color;
        grad.addColorStop(0, col + '44');
        grad.addColorStop(1, col + '11');
        ctx.beginPath();
        ctx.moveTo(core.x, core.y);
        ctx.lineTo(n.x, n.y);
        ctx.strokeStyle = grad;
        ctx.lineWidth = 1;
        ctx.stroke();
    });

    // Animate nodes in orbit
    nodes.forEach(n => {
        if (n.orbit > 0) {
            n.angle += (n.orbit < 200 ? 0.004 : n.orbit < 280 ? 0.002 : 0.001);
            n.x = cx + Math.cos(n.angle) * n.orbit;
            n.y = cy + Math.sin(n.angle) * n.orbit;
        }
    });

    // Draw nodes
    nodes.forEach(n => {
        const cfg = CATS[n.cat];

        // Glow
        const glow = ctx.createRadialGradient(n.x, n.y, 0, n.x, n.y, n.r * 3);
        glow.addColorStop(0, cfg.color + '55');
        glow.addColorStop(1, 'transparent');
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.r * 3, 0, Math.PI * 2);
        ctx.fillStyle = glow;
        ctx.fill();

        // Circle
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
        ctx.fillStyle = cfg.color + 'cc';
        ctx.fill();
        ctx.strokeStyle = cfg.color;
        ctx.lineWidth = 2;
        ctx.stroke();

        // Icon
        ctx.fillStyle = '#fff';
        ctx.font = `${n.r * 0.9}px serif`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(cfg.emoji, n.x, n.y);

        // Label (small)
        if (n.cat !== 'core') {
            ctx.font = '10px system-ui';
            ctx.fillStyle = cfg.color;
            ctx.fillText(n.label, n.x, n.y + n.r + 12);
        }
    });

    animId = requestAnimationFrame(draw);
}

// ─── Mouse hover tooltip ───
function onMouseMove(e) {
    const rect = canvasRef.value.getBoundingClientRect();
    const mx = e.clientX - rect.left;
    const my = e.clientY - rect.top;

    let hit = null;
    nodes.forEach(n => {
        const dx = mx - n.x, dy = my - n.y;
        if (Math.sqrt(dx*dx + dy*dy) < n.r + 8) hit = n;
    });

    if (hit) {
        tooltip.value = { visible: true, x: e.clientX, y: e.clientY - 40, text: hit.label, color: CATS[hit.cat].color };
    } else {
        tooltip.value.visible = false;
    }
}

function resize() {
    W = canvasRef.value.offsetWidth;
    H = canvasRef.value.offsetHeight;
    canvasRef.value.width = W;
    canvasRef.value.height = H;
    buildNodes();
}

onMounted(() => {
    ctx = canvasRef.value.getContext('2d');
    resize();
    window.addEventListener('resize', resize);
    canvasRef.value.addEventListener('mousemove', onMouseMove);
    animId = requestAnimationFrame(draw);
});

onUnmounted(() => {
    cancelAnimationFrame(animId);
    window.removeEventListener('resize', resize);
});
</script>

<template>
    <div class="neural-map-shell">
        <div class="neural-map-header">
            <h3 class="neural-title">🕸️ Neural Connections Map</h3>
            <div class="neural-legend">
                <span v-for="(cfg, key) in { idea: {color:'#3b82f6'}, decision: {color:'#8b5cf6'}, person: {color:'#f43f5e'}, finance: {color:'#10b981'} }" :key="key"
                    class="legend-dot" :style="{ background: cfg.color }">
                    {{ key }}
                </span>
            </div>
        </div>
        <canvas ref="canvasRef" class="neural-canvas"></canvas>
        <!-- Tooltip -->
        <Teleport to="body">
            <div v-if="tooltip.visible"
                class="neural-tooltip"
                :style="{ top: tooltip.y + 'px', left: tooltip.x + 'px', borderColor: tooltip.color, color: tooltip.color }">
                {{ tooltip.text }}
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.neural-map-shell {
    position: relative;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 24px;
    overflow: hidden;
    width: 100%;
}

.neural-map-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem 0.5rem;
}

.neural-title {
    font-size: 0.9rem;
    font-weight: 800;
    color: var(--c-text);
    letter-spacing: -0.02em;
}

.neural-legend {
    display: flex;
    gap: 0.5rem;
}
.legend-dot {
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #fff;
    padding: 0.15rem 0.5rem;
    border-radius: 100px;
}

.neural-canvas {
    display: block;
    width: 100%;
    height: 340px;
}
</style>

<style>
.neural-tooltip {
    position: fixed;
    pointer-events: none;
    z-index: 9999;
    background: var(--c-surface);
    border: 1px solid;
    border-radius: 10px;
    padding: 0.3rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
    transform: translateX(-50%);
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    backdrop-filter: blur(12px);
}
</style>
