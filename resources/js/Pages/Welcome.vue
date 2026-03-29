<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { onMounted, onUnmounted, ref } from "vue";
import * as THREE from "three";

defineProps({
    canLogin: { type: Boolean },
    canRegister: { type: Boolean },
});

const canvasContainer = ref(null);
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

onMounted(() => { initThree(); });
onUnmounted(() => { window.removeEventListener("resize", () => {}); });
</script>

<template>
    <Head title="Personal Memory OS — عقل موازٍ للحياة" />

    <div class="os-premium min-h-screen text-white font-cairo selection:bg-accent/40 overflow-x-hidden" dir="rtl">
        
        <!-- Background FX -->
        <div ref="canvasContainer" class="fixed inset-0 z-0 pointer-events-none opacity-40"></div>

        <!-- Navbar (From Screenshot Style) -->
        <nav class="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-12 py-6 bg-black">
            <div class="flex items-center gap-2">
                <span class="font-black text-2xl tracking-tighter text-white">MEMORY OS</span>
                <span class="text-3xl">🧠</span>
            </div>
            <div class="flex items-center gap-10" v-if="canLogin">
                <Link v-if="!$page.props.auth.user" :href="route('login')" class="text-sm font-bold text-gray-300 hover:text-white transition">دخول</Link>
                <Link v-if="!$page.props.auth.user" :href="route('register')" class="bg-white text-black px-8 py-3 rounded-full text-base font-black border-2 border-white hover:bg-black hover:text-white transition">انضم الآن</Link>
                <Link v-else :href="route('dashboard')" class="bg-white text-black px-8 py-3 rounded-full text-base font-black shadow-lg">لوحة التحكم</Link>
            </div>
        </nav>

        <main class="relative z-10 pt-48 pb-32 px-6 max-w-7xl mx-auto flex flex-col items-center">
            
            <!-- Focused Hero Section -->
            <div class="text-center mb-24 max-w-5xl">
                <h1 class="font-black mb-10 leading-[1.3] tracking-tighter">
                    <span class="block text-4xl md:text-5xl text-gray-400 font-medium mb-6">حوّل حياتك إلى</span>
                    <span class="block text-6xl md:text-9xl rakez-gradient drop-shadow-[0_15px_30px_rgba(6,155,255,0.4)]">تجربة رقمية ذكية</span>
                </h1>

                <p class="text-xl md:text-3xl text-gray-400 font-light max-w-4xl mx-auto leading-relaxed mb-20">
                    نظّم أفكارك، أموالك، علاقاتك، وقراراتك في نظام واحد يتطور معك.<br/>
                    <span class="text-white font-medium border-b-2 border-accent pb-1 inline-block mt-4">ليس مجرد موقع، بل هو عقلك الثاني.</span>
                </p>

                <div class="flex items-center justify-center">
                    <Link :href="route('register')" class="mega-cta-final">
                        ابدأ رحلتك بالمجان
                    </Link>
                </div>
            </div>

            <!-- Bento Tiles -->
            <div class="w-full grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="p-card group">
                    <div class="p-icon bg-blue-500/10 text-blue-400">💡</div>
                    <h3 class="text-xl font-black mb-3">مختبر الأفكار</h3>
                    <p class="text-sm text-gray-400">حلل أفكارك وطورها بذكاء.</p>
                </div>

                <div class="p-card group">
                    <div class="p-icon bg-green-500/10 text-green-400">💰</div>
                    <h3 class="text-xl font-black mb-3">المستشار المالي</h3>
                    <p class="text-sm text-gray-400">ميزانية وتحليل مالي ذكي.</p>
                </div>

                <div class="p-card group">
                    <div class="p-icon bg-purple-500/10 text-purple-400">⚖️</div>
                    <h3 class="text-xl font-black mb-3">مساعد القرارات</h3>
                    <p class="text-sm text-gray-400">مستشارك الخاص عند الحيرة.</p>
                </div>

                <div class="p-card group">
                    <div class="p-icon bg-rose-500/10 text-rose-400">🤝</div>
                    <h3 class="text-xl font-black mb-3">دليل العلاقات</h3>
                    <p class="text-sm text-gray-400">تحسين جودة علاقاتك الاجتماعية.</p>
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
    text-align: right;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

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
