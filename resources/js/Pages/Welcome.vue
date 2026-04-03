<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import * as THREE from "three";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import MotionIntro from "@/Components/MotionIntro.vue";
import { getActiveLanguage } from "laravel-vue-i18n";

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const canvasContainer = ref(null);
const showIntro = ref(false);
let scene, camera, renderer, particles;

const initThree = () => {
    scene = new THREE.Scene();
    camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
    camera.position.z = 5;

    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    
    const updateSize = () => {
        const width = window.innerWidth;
        const height = window.innerHeight;
        renderer.setSize(width, height);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
    };
    
    canvasContainer.value.appendChild(renderer.domElement);
    updateSize();

    // High-End Particle Orb
    const geometry = new THREE.IcosahedronGeometry(2.2, 8);
    const material = new THREE.PointsMaterial({
        color: 0x069BFF,
        size: 0.025,
        transparent: true,
        opacity: 0.5,
        blending: THREE.AdditiveBlending
    });
    
    particles = new THREE.Points(geometry, material);
    scene.add(particles);

    const animate = () => {
        requestAnimationFrame(animate);
        particles.rotation.y += 0.0015;
        const time = Date.now() * 0.0015;
        const s = 1 + Math.sin(time) * 0.05;
        particles.scale.set(s, s, s);
        renderer.render(scene, camera);
    };

    animate();
    window.addEventListener("resize", updateSize);
};

onMounted(() => { 
    initThree(); 
});

onUnmounted(() => { 
    window.removeEventListener("resize", () => {}); 
});
</script>

<template>
    <Head :title="`${$t('Memory OS')} — ${$t('Second Brain')}`" />

    <div class="os-premium min-h-screen text-white font-cairo selection:bg-accent/40 overflow-x-hidden transition-all duration-500">
        
        <!-- Background FX -->
        <div ref="canvasContainer" class="fixed inset-0 z-0 pointer-events-none opacity-40"></div>

        <!-- Navbar (Bilingual Optimized) -->
        <nav class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-12 py-6 bg-black">
            <div class="flex items-center gap-2">
                <span class="font-black text-2xl tracking-tighter text-white">{{ $t('Memory OS') }}</span>
                <span class="text-3xl">🧠</span>
            </div>
            
            <div class="flex items-center gap-8">
                <LanguageSwitcher />

                <div class="flex items-center gap-6" v-if="canLogin">
                    <Link v-if="!$page.props.auth.user" :href="route('login')" class="text-sm font-bold text-gray-300 hover:text-white transition">{{ $t('Sign In') }}</Link>
                    <Link v-if="!$page.props.auth.user" :href="route('register')" class="bg-white text-black px-8 py-3 rounded-full text-base font-black border-2 border-white hover:bg-black hover:text-white transition">{{ $t('Join Now') }}</Link>
                    <Link v-else :href="route('dashboard')" class="bg-white text-black px-8 py-3 rounded-full text-base font-black shadow-lg">{{ $t('Dashboard') }}</Link>
                </div>
            </div>
        </nav>

        <main class="relative z-10 pt-48 pb-32 px-6 max-w-7xl mx-auto flex flex-col items-center">
            
            <!-- Hero Section -->
            <div class="text-center mb-24 max-w-5xl">
                <h1 class="font-black mb-10 leading-tight tracking-tighter">
                    <span class="block text-4xl md:text-5xl text-gray-400 font-medium mb-4">{{ $t('Turn your life into') }}</span>
                    <span class="block text-6xl md:text-9xl rakez-gradient glow-text">{{ $t('Smart Experience') }}</span>
                </h1>

                <p class="text-xl md:text-3xl text-gray-400 font-light max-w-4xl mx-auto leading-relaxed mb-20">
                    {{ $t('Bento Description') }}<br/>
                    <span class="text-white font-medium border-b-2 border-accent pb-1 inline-block mt-4">{{ $t('Not just a website') }}</span>
                </p>

                <div class="flex flex-col items-center justify-center gap-6">
                    <Link :href="route('register')" class="mega-cta-final">
                        {{ $t('Start your journey for free') }}
                    </Link>
                    
                    <button @click="showIntro = true" class="flex items-center gap-3 text-white/60 hover:text-white transition-all font-bold tracking-widest text-sm uppercase group">
                        <span class="w-10 h-10 rounded-full border border-white/20 flex items-center justify-center group-hover:bg-white group-hover:text-black transition-all">▶</span>
                        {{ $t('Watch the Story') }}
                    </button>
                </div>
            </div>

            <!-- Interactive Motion Intro -->
            <MotionIntro :show="showIntro" @close="showIntro = false" />

            <!-- Bento Tiles -->
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="p-card group cursor-default">
                    <div class="p-icon bg-blue-500/10 text-blue-400 group-hover:bg-blue-500 group-hover:text-white transition-all">💡</div>
                    <h3 class="text-xl font-black mb-3">{{ $t('Idea Lab') }}</h3>
                    <p class="text-sm text-gray-500 line-clamp-2">{{ $t('Idea Lab Desc') }}</p>
                </div>

                <div class="p-card group cursor-default">
                    <div class="p-icon bg-green-500/10 text-green-400 group-hover:bg-green-500 group-hover:text-white transition-all">💰</div>
                    <h3 class="text-xl font-black mb-3">{{ $t('Smart Budget') }}</h3>
                    <p class="text-sm text-gray-400 line-clamp-2">{{ $t('Smart Budget Desc') }}</p>
                </div>

                <div class="p-card group cursor-default">
                    <div class="p-icon bg-purple-500/10 text-purple-400 group-hover:bg-purple-500 group-hover:text-white transition-all">⚖️</div>
                    <h3 class="text-xl font-black mb-3">{{ $t('Decision Advisor') }}</h3>
                    <p class="text-sm text-gray-400 line-clamp-2">{{ $t('Decision Advisor Desc') }}</p>
                </div>

                <div class="p-card group cursor-default">
                    <div class="p-icon bg-rose-500/10 text-rose-400 group-hover:bg-rose-500 group-hover:text-white transition-all">🤝</div>
                    <h3 class="text-xl font-black mb-3">{{ $t('Social Hub') }}</h3>
                    <p class="text-sm text-gray-400 line-clamp-2">{{ $t('Social Hub Desc') }}</p>
                </div>
            </div>

        </main>

        <footer class="mt-40 py-12 bg-black border-t border-white/5 text-center">
            <p class="text-gray-500 text-[10px] font-black tracking-[0.4em] uppercase">Memory OS &copy; 2026</p>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700;900&display=swap');

.font-cairo { font-family: 'Cairo', sans-serif; }

.os-premium {
    background-color: #000000;
}

.rakez-gradient {
    background: linear-gradient(to bottom, #42b3ff 30%, #06459c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.glow-text {
    text-shadow: 0 0 30px rgba(66, 179, 255, 0.3);
}

.mega-cta-final {
    background: #069BFF;
    color: white;
    padding: 1.5rem 5rem;
    border-radius: 100px;
    font-weight: 950;
    font-size: 1.6rem;
    box-shadow: 0 0 40px rgba(6, 155, 255, 0.4);
    transition: all 0.3s ease;
    display: inline-block;
}

.mega-cta-final:hover {
    transform: scale(1.05);
    background: #0084DE;
    box-shadow: 0 0 60px rgba(6, 155, 255, 0.6);
}

.p-card {
    background: rgba(255, 255, 255, 0.01);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 2.5rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

/* RTL / LTR alignment auto-switch */
[dir="rtl"] .p-card { text-align: right; }
[dir="ltr"] .p-card { text-align: left; }

.p-card:hover {
    background: rgba(255, 255, 255, 0.02);
    border-color: rgba(6, 155, 255, 0.3);
    transform: translateY(-5px);
}

.p-icon {
    width: 60px; height: 60px;
    border-radius: 18px;
    display: flex;
    align-items: center; justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 2rem;
}

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-thumb { background: #1f2937; border-radius: 10px; }
</style>
