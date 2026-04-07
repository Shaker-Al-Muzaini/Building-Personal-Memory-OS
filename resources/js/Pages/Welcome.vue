<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import * as THREE from "three";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";
import MotionIntro from "@/Components/MotionIntro.vue";
import GlowingTubesCursor from "@/Components/GlowingTubesCursor.vue";
import { useTheme } from "@/Composables/useTheme";

defineProps({ canLogin: Boolean, canRegister: Boolean });
const { isDark } = useTheme();

const canvasRef = ref(null);
const showIntro = ref(false);
const scrollWidth = ref(0);
let renderer, animId, scene, cam, pts, crystal;

onMounted(() => {
    scene = new THREE.Scene();
    cam = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    cam.position.z = 6;

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    canvasRef.value.appendChild(renderer.domElement);

    // ─── Neural Crystal Core ───
    const crystalGeo = new THREE.IcosahedronGeometry(2, 0); // Low poly for crystal look
    const crystalMat = new THREE.MeshPhongMaterial({
        color: 0x3b82f6,
        wireframe: true,
        transparent: true,
        opacity: 0.1,
        emissive: 0x3b82f6,
        emissiveIntensity: 0.5
    });
    crystal = new THREE.Mesh(crystalGeo, crystalMat);
    scene.add(crystal);

    // ─── Floating Neural Particles ───
    const ptsGeo = new THREE.BufferGeometry();
    const count = 1500;
    const pos = new Float32Array(count * 3);
    for(let i=0; i<count*3; i++) pos[i] = (Math.random() - 0.5) * 15;
    ptsGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    
    const ptsMat = new THREE.PointsMaterial({
        color: 0x3b82f6,
        size: 0.02,
        transparent: true,
        opacity: 0.4,
        blending: THREE.AdditiveBlending
    });
    pts = new THREE.Points(ptsGeo, ptsMat);
    scene.add(pts);

    // Lights
    const light = new THREE.PointLight(0x3b82f6, 1);
    light.position.set(5, 5, 5);
    scene.add(light);
    scene.add(new THREE.AmbientLight(0xffffff, 0.2));

    const animate = () => {
        animId = requestAnimationFrame(animate);
        pts.rotation.y += 0.0005;
        crystal.rotation.y -= 0.001;
        crystal.rotation.z += 0.0005;
        
        // Pulse effect
        const scale = 1 + Math.sin(Date.now() * 0.001) * 0.05;
        crystal.scale.set(scale, scale, scale);
        
        renderer.render(scene, cam);
    };
    animate();

    window.addEventListener('mousemove', handleMouseMove);
});

const onResize = () => {
    if (cam && renderer) {
        cam.aspect = window.innerWidth / window.innerHeight;
        cam.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    }
};

const onScroll = () => {
    const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
    const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
    scrollWidth.value = (winScroll / height) * 100;
};

const mouseX = ref(0);
const mouseY = ref(0);
const handleMouseMove = (e) => {
    mouseX.value = e.clientX;
    mouseY.value = e.clientY;
    
    // Parallax effect on 3D
    if(crystal) {
        crystal.rotation.x = (e.clientY / window.innerHeight - 0.5) * 0.2;
        crystal.rotation.y = (e.clientX / window.innerWidth - 0.5) * 0.2;
    }
};

onUnmounted(() => {
    cancelAnimationFrame(animId);
    window.removeEventListener('mousemove', handleMouseMove);
    window.removeEventListener('resize', onResize);
    window.removeEventListener('scroll', onScroll);
    renderer?.dispose();
    scene?.clear();
});
</script>

<template>
    <Head :title="`${$t('Memory OS')} — ${$t('Second Brain')}`" />

    <div class="lp-root">
        <!-- Scroll Progress -->
        <div id="scroll-progress" :style="{ width: scrollWidth + '%' }"></div>

        <!-- BG -->
        <div ref="canvasRef" class="lp-canvas"></div>

        <!-- Navbar -->
        <nav class="lp-nav">
            <div class="flex items-center gap-2">
                <span class="text-xl">🧠</span>
                <span class="font-black text-lg tracking-tight">{{ $t('Memory OS') }}</span>
            </div>
            <div class="flex items-center gap-4">
                <ThemeToggle />
                <LanguageSwitcher />
                <template v-if="canLogin">
                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="lp-link">{{ $t('Sign In') }}</Link>
                    <Link v-if="!$page.props.auth.user" :href="route('register')" class="lp-btn-sm">{{ $t('Join Now') }}</Link>
                    <Link v-else :href="route('dashboard')" class="lp-btn-sm">{{ $t('Dashboard') }}</Link>
                </template>
            </div>
        </nav>

        <!-- Hero -->
        <section class="lp-hero">
            <p class="lp-badge">{{ $t('Second Brain') }} v2.0</p>
            <h1 class="lp-title">{{ $t('Smart Experience') }}</h1>
            <p class="lp-sub">{{ $t('Bento Description') }}</p>
            <div class="lp-cta-row">
                <Link :href="route('register')" class="lp-btn-main hover-lift">{{ $t('Start your journey for free') }}</Link>
                <button @click="showIntro = true" class="lp-btn-ghost hover-lift">
                    <span class="lp-play">▶</span> {{ $t('Watch the Story') }}
                </button>
            </div>
        </section>

        <MotionIntro :show="showIntro" @close="showIntro = false" />
        <GlowingTubesCursor />

        <!-- Features -->
        <section class="lp-section">
            <h2 class="sr lp-section-title">{{ $t('Everything in Flow') }}</h2>
            <p class="sr lp-section-sub">{{ $t('A single, intelligent system that grows with you.') }}</p>

            <div class="lp-grid">
                <div v-for="(f, i) in features" :key="i"
                     class="sr lp-card hover-lift"
                     :style="`transition-delay:${i * 0.08}s`">
                    <div class="lp-icon" :style="`background:${f.bg}; box-shadow: 0 4px 20px ${f.glow}`">{{ f.icon }}</div>
                    <h3 class="lp-card-title">{{ $t(f.title) }}</h3>
                    <p class="lp-card-desc">{{ $t(f.desc) }}</p>
                </div>
            </div>
        </section>

        <!-- Why Section -->
        <section class="lp-section">
            <h2 class="sr lp-section-title">{{ $t('Decide Better') }}</h2>
            <div class="lp-why-row">
                <div v-for="(w, i) in whys" :key="i"
                     class="sr lp-why hover-lift"
                     :style="`transition-delay:${i * 0.1}s`">
                    <span class="lp-why-icon">{{ w.icon }}</span>
                    <strong class="lp-why-title">{{ $t(w.title) }}</strong>
                    <p class="lp-why-desc">{{ $t(w.desc) }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="lp-cta-section">
            <div class="sr lp-cta-box glass-surface">
                <h2 class="lp-cta-title">{{ $t('End the Chaos') }}</h2>
                <p class="lp-cta-desc">{{ $t('No more lost ideas, forgotten tasks, or messy budgets.') }}</p>
                <Link :href="route('register')" class="lp-btn-main hover-lift">{{ $t('Start your journey for free') }} →</Link>
            </div>
        </section>

        <footer class="lp-footer">Memory OS &copy; 2026</footer>
    </div>
</template>

<script>
export default {
    data() {
        return {
            features: [
                { icon: '💡', title: 'Idea Lab',        desc: 'Idea Lab Desc',        bg: 'rgba(59,130,246,0.12)',  glow: 'rgba(59,130,246,0.15)' },
                { icon: '💰', title: 'Smart Budget',    desc: 'Smart Budget Desc',    bg: 'rgba(16,185,129,0.12)', glow: 'rgba(16,185,129,0.15)' },
                { icon: '⚖️', title: 'Decision Advisor',desc: 'Decision Advisor Desc',bg: 'rgba(139,92,246,0.12)', glow: 'rgba(139,92,246,0.15)' },
                { icon: '🤝', title: 'Social Hub',      desc: 'Social Hub Desc',      bg: 'rgba(244,63,94,0.12)',  glow: 'rgba(244,63,94,0.15)' },
                { icon: '⏳', title: 'Deep Focus',      desc: 'Pomodoro focus system powered by AI planning.', bg: 'rgba(245,158,11,0.12)', glow: 'rgba(245,158,11,0.15)' },
                { icon: '🧬', title: 'Health & Mood',   desc: 'Track your biological energy to understand how your sleep and mood affect your financial decisions and time management.', bg: 'rgba(20,184,166,0.12)', glow: 'rgba(20,184,166,0.15)' },
            ],
            whys: [
                { icon: '🧠', title: 'End the Chaos',      desc: 'No more lost ideas, forgotten tasks, or messy budgets.' },
                { icon: '⚡', title: 'Everything in Flow',  desc: 'A single, intelligent system that grows with you.' },
                { icon: '🔮', title: 'Decide Better',       desc: 'Using AI to provide clarity when things get complex.' },
            ]
        };
    }
};
</script>

<style scoped>
/* ─── Root ─── */
.lp-root {
    background: var(--c-bg);
    min-height: 100vh;
    color: var(--c-text);
    overflow-x: hidden;
    transition: background 0.3s ease, color 0.3s ease;
}

.lp-canvas {
    position: fixed; inset: 0; z-index: 0;
    pointer-events: none; opacity: 0.4;
}

/* ─── Navbar ─── */
.lp-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 50;
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 2rem;
    background: var(--c-nav-bg);
    backdrop-filter: blur(20px);
    border-bottom: 1px solid var(--c-border);
}

.lp-link { font-size: 0.875rem; color: var(--c-text-muted); transition: color .2s; }
.lp-link:hover { color: var(--c-text); }

.lp-btn-sm {
    background: var(--c-accent); color: #fff;
    padding: 0.5rem 1.2rem; border-radius: 100px;
    font-weight: 800; font-size: 0.875rem;
    transition: all .2s;
    box-shadow: 0 4px 12px var(--c-accent-bg);
}
.lp-btn-sm:hover { opacity: 0.85; transform: translateY(-1px); }

/* ─── Hero ─── */
.lp-hero {
    position: relative; z-index: 10;
    text-align: center;
    padding: 160px 1.5rem 80px;
}

.lp-badge {
    display: inline-block;
    font-size: 0.7rem; font-weight: 800;
    letter-spacing: 0.35em; text-transform: uppercase;
    color: var(--c-accent);
    border: 1px solid var(--c-accent-bg);
    padding: 0.3rem 1rem; border-radius: 100px;
    background: var(--c-accent-bg);
    margin-bottom: 1.5rem;
}

.lp-title {
    font-size: clamp(2.5rem, 7vw, 5rem);
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: 1.2rem;
    background: linear-gradient(135deg, var(--c-text) 40%, var(--c-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lp-sub {
    font-size: 1.1rem; color: var(--c-text-muted);
    max-width: 550px; margin: 0 auto 2.5rem;
    line-height: 1.7;
}

.lp-cta-row { display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; }

.lp-btn-main {
    background: var(--c-accent); color: #fff;
    padding: 1rem 2.5rem; border-radius: 100px;
    font-weight: 800; font-size: 1rem;
    transition: all .3s; display: inline-block;
    box-shadow: 0 8px 24px var(--c-accent-bg);
}
.lp-btn-main:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 12px 32px var(--c-accent-bg); }

.lp-btn-ghost {
    display: flex; align-items: center; gap: 0.6rem;
    color: var(--c-text); font-size: 0.9rem; font-weight: 700;
    border: 1px solid var(--c-border); padding: 1rem 2rem;
    border-radius: 100px; transition: all .2s; background: var(--c-surface);
}
.lp-btn-ghost:hover { border-color: var(--c-accent); background: var(--c-surface2); }

.lp-play {
    width: 28px; height: 28px;
    border: 1px solid var(--c-accent);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.6rem; color: var(--c-accent);
}

/* ─── Sections ─── */
.lp-section {
    position: relative; z-index: 10;
    max-width: 1200px; margin: 0 auto;
    padding: 6rem 1.5rem;
}

.lp-section-title {
    text-align: center; font-size: 2.5rem; font-weight: 900;
    letter-spacing: -0.02em; color: var(--c-text); margin-bottom: 0.8rem;
}
.lp-section-sub {
    text-align: center; color: var(--c-text-muted);
    font-size: 1rem; margin-bottom: 4rem;
}

/* ─── Cards ─── */
.lp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.lp-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 30px; padding: 2.5rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    box-shadow: var(--c-shadow);
}
.lp-card:hover {
    border-color: var(--c-accent);
    box-shadow: 0 20px 40px rgba(0,0,0,0.05);
}

.lp-icon {
    width: 65px; height: 65px; border-radius: 20px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin-bottom: 1.5rem;
    transition: transform 0.4s ease;
}
.lp-card:hover .lp-icon { transform: scale(1.15) rotate(5deg); }

.lp-card-title {
    font-size: 1.25rem; font-weight: 900; color: var(--c-text); margin-bottom: 0.6rem;
}
.lp-card-desc { font-size: 0.95rem; color: var(--c-text-muted); line-height: 1.7; }

/* ─── Why ─── */
.lp-why-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }

.lp-why {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 30px; padding: 2.5rem;
    transition: all 0.4s ease;
    box-shadow: var(--c-shadow);
}

.lp-why-icon { font-size: 2.5rem; display: block; margin-bottom: 1.2rem; }
.lp-why-title { display: block; font-size: 1.25rem; font-weight: 900; color: var(--c-text); margin-bottom: 0.6rem; }
.lp-why-desc { font-size: 0.95rem; color: var(--c-text-muted); line-height: 1.7; }

/* ─── CTA ─── */
.lp-cta-section {
    position: relative; z-index: 10;
    max-width: 1200px; margin: 0 auto; padding: 4rem 1.5rem 8rem;
}

.lp-cta-box {
    border-radius: 40px; padding: 5rem 2rem;
    text-align: center;
}

.lp-cta-title { font-size: 3rem; font-weight: 900; color: var(--c-text); margin-bottom: 1rem; }
.lp-cta-desc { font-size: 1.1rem; color: var(--c-text-muted); margin-bottom: 3rem; max-width: 600px; margin-left: auto; margin-right: auto; }

/* ─── Footer ─── */
.lp-footer {
    position: relative; z-index: 10;
    text-align: center; padding: 3rem;
    font-size: 0.85rem; color: var(--c-text-muted);
    border-top: 1px solid var(--c-border);
    letter-spacing: 0.2em;
    opacity: 0.6;
}

/* ─── Scroll Reveal ─── */
.sr {
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.8s cubic-bezier(0.2, 0, 0.2, 1), transform 0.8s cubic-bezier(0.2, 0, 0.2, 1);
}
.sr.in { opacity: 1; transform: translateY(0); }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 10px; }
</style>

