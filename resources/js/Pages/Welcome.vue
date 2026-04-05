<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import * as THREE from "three";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import ThemeToggle from "@/Components/ThemeToggle.vue";
import MotionIntro from "@/Components/MotionIntro.vue";
import { useTheme } from "@/Composables/useTheme";

defineProps({ canLogin: Boolean, canRegister: Boolean });
const { isDark } = useTheme();

const canvasRef = ref(null);
const showIntro = ref(false);
let renderer, animId;

onMounted(() => {
    const scene = new THREE.Scene();
    const cam = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    cam.position.z = 5;
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    canvasRef.value.appendChild(renderer.domElement);

    const geo = new THREE.IcosahedronGeometry(2.5, 6);
    const mat = new THREE.PointsMaterial({ color: 0x3b82f6, size: 0.015, transparent: true, opacity: 0.3, blending: THREE.AdditiveBlending });
    const pts = new THREE.Points(geo, mat);
    scene.add(pts);

    const animate = () => {
        animId = requestAnimationFrame(animate);
        pts.rotation.y += 0.0008;
        pts.rotation.x += 0.0003;
        renderer.render(scene, cam);
    };
    animate();

    const onResize = () => {
        cam.aspect = window.innerWidth / window.innerHeight;
        cam.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    };
    window.addEventListener('resize', onResize);

    // Scroll reveal
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) e.target.classList.add('in');
            else e.target.classList.remove('in');
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.sr').forEach(el => observer.observe(el));
});

onUnmounted(() => {
    cancelAnimationFrame(animId);
    renderer?.dispose();
});
</script>

<template>
    <Head :title="`${$t('Memory OS')} — ${$t('Second Brain')}`" />

    <div class="lp-root" :class="isDark ? 'os-dark' : 'os-light'">

        <!-- BG -->
        <div ref="canvasRef" class="lp-canvas"></div>

        <!-- Navbar -->
        <nav class="lp-nav">
            <div class="flex items-center gap-2">
                <span class="text-xl">🧠</span>
                <span class="font-black text-lg text-white tracking-tight">{{ $t('Memory OS') }}</span>
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
                <Link :href="route('register')" class="lp-btn-main">{{ $t('Start your journey for free') }}</Link>
                <button @click="showIntro = true" class="lp-btn-ghost">
                    <span class="lp-play">▶</span> {{ $t('Watch the Story') }}
                </button>
            </div>
        </section>

        <MotionIntro :show="showIntro" @close="showIntro = false" />

        <!-- Features -->
        <section class="lp-section">
            <h2 class="sr lp-section-title">{{ $t('Everything in Flow') }}</h2>
            <p class="sr lp-section-sub">{{ $t('A single, intelligent system that grows with you.') }}</p>

            <div class="lp-grid">
                <div v-for="(f, i) in features" :key="i"
                     class="sr lp-card"
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
                     class="sr lp-why"
                     :style="`transition-delay:${i * 0.1}s`">
                    <span class="lp-why-icon">{{ w.icon }}</span>
                    <strong class="lp-why-title">{{ $t(w.title) }}</strong>
                    <p class="lp-why-desc">{{ $t(w.desc) }}</p>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="lp-cta-section">
            <div class="sr lp-cta-box">
                <h2 class="lp-cta-title">{{ $t('End the Chaos') }}</h2>
                <p class="lp-cta-desc">{{ $t('No more lost ideas, forgotten tasks, or messy budgets.') }}</p>
                <Link :href="route('register')" class="lp-btn-main">{{ $t('Start your journey for free') }} →</Link>
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

<style>
/* ─── Root ─── */
.lp-root {
    background: #000;
    min-height: 100vh;
    color: #fff;
    overflow-x: hidden;
}

.lp-canvas {
    position: fixed; inset: 0; z-index: 0;
    pointer-events: none; opacity: 0.25;
}

/* ─── Navbar ─── */
.lp-nav {
    position: fixed; top: 0; left: 0; right: 0; z-index: 50;
    display: flex; align-items: center; justify-content: space-between;
    padding: 1rem 2rem;
    background: rgba(0,0,0,0.85);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

.lp-link { font-size: 0.875rem; color: rgba(255,255,255,0.5); transition: color .2s; }
.lp-link:hover { color: #fff; }

.lp-btn-sm {
    background: #fff; color: #000;
    padding: 0.5rem 1.2rem; border-radius: 100px;
    font-weight: 800; font-size: 0.875rem;
    transition: opacity .2s;
}
.lp-btn-sm:hover { opacity: 0.85; }

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
    color: rgba(59,130,246,0.8);
    border: 1px solid rgba(59,130,246,0.25);
    padding: 0.3rem 1rem; border-radius: 100px;
    margin-bottom: 1.5rem;
}

.lp-title {
    font-size: clamp(2.5rem, 7vw, 5rem);
    font-weight: 900;
    line-height: 1.1;
    letter-spacing: -0.03em;
    margin-bottom: 1.2rem;
    background: linear-gradient(135deg, #fff 40%, #3b82f6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.lp-sub {
    font-size: 1rem; color: rgba(255,255,255,0.4);
    max-width: 480px; margin: 0 auto 2.5rem;
    line-height: 1.7;
}

.lp-cta-row { display: flex; align-items: center; justify-content: center; gap: 1rem; flex-wrap: wrap; }

.lp-btn-main {
    background: #3b82f6; color: #fff;
    padding: 0.85rem 2rem; border-radius: 100px;
    font-weight: 800; font-size: 0.95rem;
    transition: all .3s; display: inline-block;
    box-shadow: 0 0 24px rgba(59,130,246,0.35);
}
.lp-btn-main:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 0 40px rgba(59,130,246,0.5); }

.lp-btn-ghost {
    display: flex; align-items: center; gap: 0.6rem;
    color: rgba(255,255,255,0.4); font-size: 0.875rem; font-weight: 600;
    border: 1px solid rgba(255,255,255,0.1); padding: 0.85rem 1.5rem;
    border-radius: 100px; transition: all .2s; background: transparent;
}
.lp-btn-ghost:hover { color: #fff; border-color: rgba(255,255,255,0.25); }

.lp-play {
    width: 28px; height: 28px;
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.6rem;
}

/* ─── Sections ─── */
.lp-section {
    position: relative; z-index: 10;
    max-width: 1100px; margin: 0 auto;
    padding: 5rem 1.5rem;
}

.lp-section-title {
    text-align: center; font-size: 2rem; font-weight: 900;
    letter-spacing: -0.02em; color: #fff; margin-bottom: 0.6rem;
}
.lp-section-sub {
    text-align: center; color: rgba(255,255,255,0.35);
    font-size: 0.9rem; margin-bottom: 3rem;
}

/* ─── Cards ─── */
.lp-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.lp-card {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 20px; padding: 1.8rem;
    transition: all 0.4s ease;
}
.lp-card:hover {
    background: rgba(255,255,255,0.04);
    border-color: rgba(59,130,246,0.2);
    transform: translateY(-4px);
}

.lp-icon {
    width: 60px; height: 60px; border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.8rem; margin-bottom: 1.2rem;
    transition: transform 0.3s ease;
}
.lp-card:hover .lp-icon { transform: scale(1.1); }

.lp-card-title {
    font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem;
}
.lp-card-desc { font-size: 0.82rem; color: rgba(255,255,255,0.35); line-height: 1.6; }

/* ─── Why ─── */
.lp-why-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }

.lp-why {
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.06);
    border-radius: 20px; padding: 1.8rem;
    transition: all 0.4s ease;
}
.lp-why:hover { background: rgba(255,255,255,0.04); transform: translateY(-3px); }

.lp-why-icon { font-size: 2rem; display: block; margin-bottom: 1rem; }
.lp-why-title { display: block; font-size: 1rem; font-weight: 800; color: #fff; margin-bottom: 0.4rem; }
.lp-why-desc { font-size: 0.82rem; color: rgba(255,255,255,0.35); line-height: 1.6; }

/* ─── CTA ─── */
.lp-cta-section {
    position: relative; z-index: 10;
    max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem 5rem;
}

.lp-cta-box {
    background: rgba(59,130,246,0.06);
    border: 1px solid rgba(59,130,246,0.15);
    border-radius: 24px; padding: 3.5rem 2rem;
    text-align: center;
}

.lp-cta-title { font-size: 1.8rem; font-weight: 900; color: #fff; margin-bottom: 0.6rem; }
.lp-cta-desc { font-size: 0.9rem; color: rgba(255,255,255,0.4); margin-bottom: 2rem; }

/* ─── Footer ─── */
.lp-footer {
    position: relative; z-index: 10;
    text-align: center; padding: 1.5rem;
    font-size: 0.75rem; color: rgba(255,255,255,0.15);
    border-top: 1px solid rgba(255,255,255,0.04);
    letter-spacing: 0.15em;
}

/* ─── Scroll Reveal ─── */
.sr {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}
.sr.in { opacity: 1; transform: translateY(0); }

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
</style>
