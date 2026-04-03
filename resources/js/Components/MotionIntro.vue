<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close']);

const currentStep = ref(0);
const isTransitioning = ref(false);
const audioRef = ref(null);
const autoPlayTimer = ref(null);
const isMuted = ref(false);

const steps = [
    {
        id: 'intro',
        title: 'Memory OS',
        subtitle: 'The Digital Architect for Your Life',
        bg: 'from-black via-blue-950 to-black',
        videoHint: 'Cinematic Core'
    },
    {
        id: 'chaos',
        title: 'End the Chaos',
        subtitle: 'No more lost ideas, forgotten tasks, or messy budgets.',
        bg: 'from-black via-indigo-950 to-black',
        particles: ['💡', '💰', '⚖️', '🤝', '📄', '📅']
    },
    {
        id: 'unity',
        title: 'Everything in Flow',
        subtitle: 'A single, intelligent system that grows with you.',
        bg: 'from-black via-slate-900 to-black',
    },
    {
        id: 'power',
        title: 'Decide Better',
        subtitle: 'Using AI to provide clarity when things get complex.',
        bg: 'from-black via-emerald-950 to-black',
    }
];

const toggleMute = () => {
    isMuted.value = !isMuted.value;
    if (audioRef.value) audioRef.value.muted = isMuted.value;
};

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        triggerTransition(() => currentStep.value++);
    } else {
        closeIntro();
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        triggerTransition(() => currentStep.value--);
    }
};

const triggerTransition = (callback) => {
    isTransitioning.value = true;
    setTimeout(() => {
        callback();
        isTransitioning.value = false;
        resetAutoPlay();
    }, 600);
};

const resetAutoPlay = () => {
    if (autoPlayTimer.value) clearInterval(autoPlayTimer.value);
    autoPlayTimer.value = setInterval(() => {
        if (props.show && !isTransitioning.value) {
            nextStep();
        }
    }, 6000); // 6 seconds per scene
};

const closeIntro = () => {
    if (audioRef.value) {
        audioRef.value.pause();
        audioRef.value.currentTime = 0;
    }
    clearInterval(autoPlayTimer.value);
    emit('close');
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        currentStep.value = 0;
        resetAutoPlay();
        setTimeout(() => {
            if (audioRef.value) {
                audioRef.value.muted = isMuted.value;
                audioRef.value.play().catch(e => {
                    console.warn("Autoplay was prevented. Click anywhere to start sound.");
                });
            }
        }, 100);
    } else {
        clearInterval(autoPlayTimer.value);
    }
});

onUnmounted(() => {
    clearInterval(autoPlayTimer.value);
});

const stepData = computed(() => steps[currentStep.value]);
</script>

<template>
    <Transition name="fade-overlay">
        <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden font-cairo select-none bg-gradient-to-br" :class="stepData.bg">
            
            <!-- Background Sound -->
            <audio ref="audioRef" loop>
                <source src="https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3" type="audio/mpeg">
            </audio>

            <!-- 3D Grid BG -->
            <div class="absolute inset-0 z-0 perspective-grid opacity-20 pointer-events-none"></div>

            <!-- Main Content Container -->
            <div class="relative z-10 w-full max-w-5xl text-center px-6">
                <Transition name="scene-3d" mode="out-in">
                    <div :key="currentStep" class="flex flex-col items-center justify-center p-4">
                        
                        <!-- Visual Container -->
                        <div class="visual-container mb-6 md:mb-10 h-32 md:h-56 flex items-center justify-center">
                            <template v-if="stepData.id === 'intro'">
                                <div class="relative group scale-75 md:scale-100">
                                    <div class="absolute inset-0 bg-blue-500/20 blur-[60px] animate-pulse"></div>
                                    <div class="brain-3d-wrapper">
                                        <div class="crystal-core"></div>
                                        <span class="text-7xl md:text-9xl block animate-float">🧠</span>
                                    </div>
                                </div>
                            </template>
                            
                            <template v-else-if="stepData.id === 'chaos'">
                                <div class="chaos-3d relative w-32 h-32 md:w-56 md:h-56 scale-90">
                                    <div v-for="(p, i) in stepData.particles" :key="i" 
                                        class="absolute text-4xl md:text-5xl floating-node-3d"
                                        :style="{ 
                                            top: (10 + Math.random() * 80) + '%', 
                                            left: (10 + Math.random() * 80) + '%',
                                            animationDelay: (i * 0.3) + 's' 
                                        }">
                                        <div class="node-glass p-2 md:p-3">{{ p }}</div>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="stepData.id === 'unity'">
                                <div class="unity-3d relative scale-75 md:scale-100">
                                    <svg class="w-48 h-48 md:w-64 md:h-64" viewBox="0 0 200 200">
                                        <defs>
                                            <linearGradient id="flowGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" style="stop-color:#42b3ff;stop-opacity:1" />
                                                <stop offset="100%" style="stop-color:#06459c;stop-opacity:1" />
                                            </linearGradient>
                                        </defs>
                                        <circle cx="100" cy="100" r="80" fill="none" stroke="url(#flowGrad)" stroke-width="0.5" stroke-dasharray="10 5" class="animate-spin-slow" />
                                        <text x="100" y="115" text-anchor="middle" font-size="50" class="drop-shadow-2xl">💎</text>
                                    </svg>
                                </div>
                            </template>

                            <template v-else>
                                <div class="power-3d scale-75 md:scale-100">
                                    <div class="relative">
                                        <div class="absolute inset-0 bg-emerald-500/30 blur-[80px] animate-pulse"></div>
                                        <div class="text-7xl md:text-9xl animate-pulse-fast">🚀</div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Text Container -->
                        <div class="text-content mb-8 max-w-2xl">
                            <h2 class="text-3xl md:text-6xl font-black mb-3 md:mb-5 text-white tracking-tighter drop-shadow-glow leading-tight">
                                {{ $t(stepData.title) }}
                            </h2>
                            <p class="text-sm md:text-lg text-blue-100/50 font-light leading-relaxed px-2">
                                {{ $t(stepData.subtitle) }}
                            </p>
                        </div>

                        <!-- Progress Bar -->
                        <div class="w-full max-w-[150px] md:max-w-xs h-[2px] bg-white/10 rounded-full overflow-hidden mb-8">
                            <div class="h-full bg-white transition-all duration-[6000ms] linear" 
                                :key="currentStep" 
                                style="width: 100%"
                            ></div>
                        </div>

                        <!-- Navigation Controls -->
                        <div class="flex items-center gap-3 md:gap-6">
                            <button @click="prevStep" v-if="currentStep > 0" class="w-10 h-10 md:w-12 md:h-12 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/5 text-white/50 hover:text-white transition group">
                                <span class="group-hover:-translate-x-1 transition-transform">←</span>
                            </button>
                            
                            <button @click="nextStep" class="min-w-[180px] md:min-w-[240px] px-8 py-3 md:py-4 rounded-full bg-white text-black font-black text-lg md:text-xl hover:scale-105 active:scale-95 transition-all shadow-xl">
                                {{ currentStep === steps.length - 1 ? $t('Start Experience') : $t('Next Scene') }}
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>

            <!-- Overlays -->
            <button @click="closeIntro" class="absolute top-10 right-10 text-white/40 hover:text-white transition text-[10px] font-black uppercase tracking-[0.5em] group z-20">
                {{ $t('Skip Introduction') }} <span class="group-hover:translate-x-2 transition-transform inline-block">→</span>
            </button>

            <button @click="toggleMute" class="absolute bottom-10 right-10 w-12 h-12 rounded-full border border-white/10 flex items-center justify-center hover:bg-white/5 transition text-xl group z-20">
                <span v-if="!isMuted">🔊</span>
                <span v-else class="text-white/30">🔇</span>
            </button>

            <div class="absolute bottom-10 left-10 flex flex-col gap-4 text-[10px] font-black uppercase tracking-[0.3em] text-white/20 px-4 md:px-0">
                <span>Memory OS // v1.0</span>
                <span>Neural Interface Core</span>
            </div>

            <div class="absolute top-10 flex gap-6 px-12 w-full justify-between items-center pointer-events-none">
                <div class="h-1px flex-1 bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                <div class="text-white/40 text-[10px] font-bold tracking-[.5em]">{{ String(currentStep + 1).padStart(2, '0') }} / 04</div>
                <div class="h-1px flex-1 bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
            </div>

        </div>
    </Transition>
</template>

<style scoped>
.font-cairo { font-family: 'Cairo', sans-serif; }

.scene-3d-enter-active { animation: zoom-3d-in 1s cubic-bezier(0.19, 1, 0.22, 1) both; }
.scene-3d-leave-active { animation: zoom-3d-out 0.6s cubic-bezier(0.19, 1, 0.22, 1) both; }

@keyframes zoom-3d-in {
    0% { opacity: 0; transform: translateZ(-500px) scale(0.5); filter: blur(20px); }
    100% { opacity: 1; transform: translateZ(0) scale(1); filter: blur(0); }
}

@keyframes zoom-3d-out {
    0% { opacity: 1; transform: translateZ(0) scale(1); filter: blur(0); }
    100% { opacity: 0; transform: translateZ(500px) scale(1.5); filter: blur(20px); }
}

.perspective-grid {
    background-image: 
        linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 50px 50px;
    transform: perspective(1000px) rotateX(60deg) translateY(-200px);
    mask-image: radial-gradient(ellipse at center, black, transparent 80%);
}

.drop-shadow-glow {
    filter: drop-shadow(0 0 30px rgba(255, 255, 255, 0.2));
}

.animate-float { animation: float 6s ease-in-out infinite; }
@keyframes float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-20px) rotate(5deg); }
}

.floating-node-3d { animation: flow-3d infinite alternate ease-in-out; }
@keyframes flow-3d {
    from { transform: translate3d(0, 0, 0) scale(1); }
    to { transform: translate3d(30px, -40px, 100px) scale(1.2); }
}

.node-glass {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}

.animate-spin-slow { animation: spin 20s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

.animate-pulse-fast { animation: pulse-fast 1s infinite alternate; }
@keyframes pulse-fast {
    from { transform: scale(1); filter: brightness(1); }
    to { transform: scale(1.05); filter: brightness(1.5); }
}

@media (max-width: 768px) {
    .perspective-grid { background-size: 30px 30px; }
}

.fade-overlay-enter-active, .fade-overlay-leave-active { transition: all 1s ease; }
.fade-overlay-enter-from, .fade-overlay-leave-to { opacity: 0; filter: blur(50px); }
</style>
