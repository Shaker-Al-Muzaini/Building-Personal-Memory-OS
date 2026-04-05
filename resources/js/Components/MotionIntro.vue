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
const lastSpokenText = ref('');
const lastSpeechTime = ref(0);
const voicesLoaded = ref(false);
const sceneSpoken = ref(-1);

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
    if (isMuted.value) {
        window.speechSynthesis.cancel();
    } else {
        sceneSpoken.value = -1; // Force re-speak
        speakCurrentScene();
    }
};

const unlockAudio = () => {
    if (audioRef.value && audioRef.value.paused) {
        audioRef.value.muted = isMuted.value;
        audioRef.value.play().catch(() => {});
    }
    // Only speak the first scene if it hasn't been spoken yet
    if (currentStep.value === 0 && sceneSpoken.value === -1) {
        speakCurrentScene();
    }
};

const speakText = (stepIndex, title, subtitle) => {
    if (!window.speechSynthesis || stepIndex === sceneSpoken.value) return;
    
    // Immediate Lock
    sceneSpoken.value = stepIndex;
    window.speechSynthesis.cancel();
    
    const lang = getActiveLanguage() || 'ar';
    const speechLang = lang === 'ar' ? 'ar-SA' : 'en-US';
    
    setTimeout(() => {
        if (!title && !subtitle) {
            sceneSpoken.value = -1;
            return;
        }
        
        const fullText = `${title}. ${subtitle}`;
        const utterance = new SpeechSynthesisUtterance(fullText);
        utterance.lang = speechLang;
        
        // Balanced speed for the new 6s/9s durations
        utterance.rate = stepIndex === 0 ? 1.1 : 0.9; 
        utterance.pitch = 1.05; 
        utterance.volume = 1.0;
        
        const voices = window.speechSynthesis.getVoices();
        const preferredVoice = voices.find(v => 
            v.lang.startsWith(speechLang) && 
            (v.name.includes('Zeina') || 
             v.name.includes('Muna') || 
             v.name.includes('Nadia') || 
             v.name.includes('Microsoft') ||
             v.name.toLowerCase().includes('female'))
        );
        
        if (preferredVoice) utterance.voice = preferredVoice;

        if (!isMuted.value) {
            window.speechSynthesis.speak(utterance);
        }
    }, 400); // Wait for transition visuals to stabilize
};

const speakCurrentScene = () => {
    const activeContainer = document.querySelector('.scene-3d-enter-to, .scene-3d-enter-active');
    let title, subtitle;
    
    if (activeContainer) {
        title = activeContainer.querySelector('h2')?.innerText;
        subtitle = activeContainer.querySelector('p')?.innerText;
    } 
    
    if (!title || !subtitle) {
        title = document.querySelector('.text-content-balanced h2')?.innerText;
        subtitle = document.querySelector('.text-content-balanced p')?.innerText;
    }
    
    speakText(currentStep.value, title, subtitle);
};

const nextStep = () => {
    if (currentStep.value < steps.length - 1) {
        triggerTransition(() => {
            currentStep.value++;
        });
    } else {
        closeIntro();
    }
};

const prevStep = () => {
    if (currentStep.value > 0) {
        triggerTransition(() => {
            currentStep.value--;
        });
    }
};

const triggerTransition = (callback) => {
    isTransitioning.value = true;
    window.speechSynthesis.cancel(); 
    setTimeout(() => {
        callback();
        isTransitioning.value = false;
        resetAutoPlay();
        // Wait another 300ms after transition ends for stable text
        setTimeout(() => speakCurrentScene(), 300);
    }, 600);
};

const getDuration = () => {
    return currentStep.value === 0 ? 6000 : 9000;
};

const resetAutoPlay = () => {
    if (autoPlayTimer.value) clearInterval(autoPlayTimer.value);
    autoPlayTimer.value = setInterval(() => {
        if (props.show && !isTransitioning.value) {
            nextStep();
        }
    }, getDuration()); 
};

const closeIntro = () => {
    if (audioRef.value) {
        audioRef.value.pause();
        audioRef.value.currentTime = 0;
    }
    window.speechSynthesis.cancel();
    clearInterval(autoPlayTimer.value);
    emit('close');
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        currentStep.value = 0;
        sceneSpoken.value = -1; // Reset tracker
        resetAutoPlay();
        
        // Use a single, clean trigger for the first scene
        setTimeout(() => {
            if (audioRef.value) {
                audioRef.value.muted = isMuted.value;
                audioRef.value.play().catch(() => {});
            }
            speakCurrentScene();
        }, 500); 

    } else {
        window.speechSynthesis.cancel();
        clearInterval(autoPlayTimer.value);
    }
});

onMounted(() => {
    if (window.speechSynthesis) {
        window.speechSynthesis.onvoiceschanged = () => {
            voicesLoaded.value = true;
        };
    }
});

onUnmounted(() => {
    window.speechSynthesis.cancel();
    clearInterval(autoPlayTimer.value);
});

const stepData = computed(() => steps[currentStep.value]);
</script>

<template>
    <Transition name="fade-overlay">
        <div v-if="show" @click="unlockAudio" class="fixed inset-0 z-[100] flex items-center justify-center overflow-hidden font-cairo select-none bg-gradient-to-br transition-all duration-1000" :class="stepData.bg">
            
            <!-- Background Sound (Ambient Spacey Tech) -->
            <audio ref="audioRef" loop preload="auto">
                <source src="https://www.soundhelix.com/examples/mp3/SoundHelix-Song-8.mp3" type="audio/mpeg">
            </audio>

            <!-- 3D Neural Mesh Background -->
            <div class="absolute inset-0 z-0 opacity-10 pointer-events-none overflow-hidden">
                <div class="neural-nodes absolute inset-0"></div>
            </div>

             <!-- Scene Content (Balanced Split Layout) -->
            <div class="relative z-10 w-full max-w-6xl px-6 md:px-12 py-10 min-h-screen flex flex-col justify-center items-center">
                <Transition name="scene-3d" mode="out-in">
                    <div :key="currentStep" 
                         class="flex flex-col md:flex-row items-center justify-between gap-12 md:gap-20 w-full"
                         :class="getActiveLanguage() === 'ar' ? 'md:flex-row-reverse' : 'md:flex-row'">
                        
                        <!-- column 1: Visuals (Scale Up) -->
                        <div class="flex-1 flex items-center justify-center min-h-[300px] md:min-h-[500px]">
                            <div class="visual-wrapper relative transition-all duration-1000 scale-110 md:scale-150">
                                <!-- Scene Visuals (Modular Templates) -->
                                <template v-if="stepData.id === 'intro'">
                                    <div class="brain-orb-large">
                                        <div class="orb-inner flex items-center justify-center text-[10rem] md:text-[12rem] drop-shadow-[0_0_100px_rgba(59,130,246,0.4)]">🧠</div>
                                        <div class="orb-ring ring1 !border-blue-400/20"></div>
                                        <div class="orb-ring ring2 !border-blue-300/10"></div>
                                    </div>
                                </template>
                                
                                <template v-else-if="stepData.id === 'chaos'">
                                    <div class="chaos-field-large relative w-64 h-64 md:w-96 md:h-96 glass-container rounded-[40px]">
                                        <div v-for="(p, i) in stepData.particles" :key="i" 
                                            class="absolute glass-node-v2"
                                            :style="{ 
                                                top: (15 + Math.random() * 70) + '%', 
                                                left: (15 + Math.random() * 70) + '%',
                                                animationDelay: (i * 0.5) + 's'
                                            }">
                                            <span class="text-4xl md:text-6xl">{{ p }}</span>
                                        </div>
                                    </div>
                                </template>

                                <template v-else-if="stepData.id === 'unity'">
                                    <div class="unity-flow relative w-64 h-64 md:w-96 md:h-96 flex items-center justify-center">
                                        <div class="absolute inset-0 animate-spin-slow">
                                            <svg viewBox="0 0 100 100" class="w-full h-full opacity-20">
                                                <circle cx="50" cy="50" r="48" fill="none" stroke="white" stroke-dasharray="1 4" stroke-width="0.5" />
                                            </svg>
                                        </div>
                                        <div class="text-9xl md:text-[11rem] drop-shadow-[0_0_80px_rgba(255,255,255,0.4)] animate-pulse">💎</div>
                                    </div>
                                </template>

                                <template v-else>
                                    <div class="power-insight-large">
                                        <div class="text-[12rem] md:text-[15rem] filter drop-shadow-[0_0_120px_rgba(16,185,129,0.4)] animate-bounce-slow">🚀</div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Column 2: Narration (Balanced Text) -->
                        <div class="flex-1 text-center md:text-right" :class="getActiveLanguage() === 'ar' ? 'md:text-right' : 'md:text-left'">
                            <div class="text-content-balanced max-w-xl mx-auto md:mx-0">
                                <h4 class="text-blue-400/80 font-black tracking-[0.4em] uppercase text-xs md:text-sm mb-4 md:mb-6 animate-fade-in">
                                    {{ $t('Memory OS') }} // {{ stepData.id }}
                                </h4>
                                <h2 class="text-4xl md:text-7xl font-black mb-6 md:mb-8 text-white tracking-tighter leading-[1.1] drop-shadow-glow">
                                    {{ $t(stepData.title) }}
                                </h2>
                                <div class="h-1 w-20 bg-white/20 mb-8 rounded-full" :class="getActiveLanguage() === 'ar' ? 'md:ml-auto md:mr-0' : 'md:mr-auto md:ml-0'"></div>
                                <p class="text-lg md:text-2xl text-blue-100/60 font-light leading-relaxed mb-12">
                                    {{ $t(stepData.subtitle) }}
                                </p>

                                <!-- Controls & Progress (Integrated) -->
                                <div class="flex flex-col gap-8">
                                    <div class="w-full flex items-center gap-4">
                                        <div class="flex-1 h-[2px] bg-white/5 rounded-full overflow-hidden">
                                            <div class="h-full bg-white transition-all linear" 
                                                 :key="currentStep" 
                                                 :style="{ width: '100%', transitionDuration: getDuration() + 'ms' }">
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-black tracking-widest text-white/20">0{{ currentStep + 1 }}/04</span>
                                    </div>

                                    <div class="flex items-center gap-4 md:gap-6 justify-center md:justify-start">
                                        <button @click="prevStep" v-if="currentStep > 0" class="w-12 h-12 md:w-16 md:h-16 rounded-full border border-white/5 flex items-center justify-center hover:bg-white/5 text-white/30 hover:text-white transition group backdrop-blur">
                                            <span class="group-hover:-translate-x-1 transition-transform text-2xl">←</span>
                                        </button>
                                        
                                        <button @click="nextStep" class="flex-1 md:flex-none min-w-[200px] md:min-w-[280px] px-8 py-5 md:py-6 rounded-full bg-white text-black font-black text-lg md:text-xl hover:scale-105 active:scale-95 transition-all shadow-xl">
                                            {{ currentStep === steps.length - 1 ? $t('Start Experience') : $t('Next Scene') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </Transition>
            </div>

            <!-- Header/Footer Overlays (Minimal) -->
            <button @click="closeIntro" class="absolute top-10 right-10 md:right-20 text-white/20 hover:text-white transition text-xs font-black uppercase tracking-[0.5em] group z-20">
                {{ $t('Skip') }} <span class="group-hover:translate-x-2 transition-transform inline-block">→</span>
            </button>

            <button @click="toggleMute" class="absolute top-10 left-10 md:left-20 w-10 h-10 rounded-full border border-white/5 flex items-center justify-center hover:bg-white/5 transition text-lg group z-20">
                <span v-if="!isMuted">🔊</span>
                <span v-else class="text-white/20">🔇</span>
            </button>

        </div>
    </Transition>
</template>

<style scoped>
/* Scoped Styles for Refined Visuals */
.font-cairo { font-family: 'Cairo', sans-serif; }

.scene-3d-enter-active { animation: zoom-3d-in 1.2s cubic-bezier(0.19, 1, 0.22, 1) both; }
.scene-3d-leave-active { animation: zoom-3d-out 0.8s cubic-bezier(0.19, 1, 0.22, 1) both; }

@keyframes zoom-3d-in {
    0% { opacity: 0; transform: scale(3.5) translateY(50px); filter: blur(30px); }
    100% { opacity: 1; transform: scale(1) translateY(0); filter: blur(0); }
}

@keyframes zoom-3d-out {
    0% { opacity: 1; transform: scale(1); filter: blur(0); }
    100% { opacity: 0; transform: scale(0.2) translateY(-50px); filter: blur(30px); }
}

/* Refined Proportions */
.brain-orb-large {
    position: relative;
    width: 300px; height: 300px;
    display: flex; align-items: center; justify-content: center;
}

.glass-node-v2 {
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(15px);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 2rem;
    border-radius: 30px;
    box-shadow: 0 15px 45px rgba(0,0,0,0.4);
    animation: flow-3d-large infinite alternate ease-in-out;
}

@keyframes flow-3d-large {
    from { transform: translate3d(0, 0, 0) scale(1); }
    to { transform: translate3d(40px, -40px, 100px) scale(1.1); }
}

.animate-fade-in { animation: fadeIn 1s ease-out forwards; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

.animate-bounce-slow { animation: bounceSlow 3s infinite ease-in-out; }
@keyframes bounceSlow { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-30px); } }

/* New Animations */
.animate-draw-line {
    stroke-dasharray: 100;
    stroke-dashoffset: 100;
    animation: draw 2s ease-out forwards;
}

@keyframes draw { to { stroke-dashoffset: 0; } }

.animate-core-glow {
    animation: core-pulse 4s infinite alternate ease-in-out;
}

@keyframes core-pulse {
    0% { filter: drop-shadow(0 0 10px rgba(66,179,255,0.3)); transform: scale(1); }
    100% { filter: drop-shadow(0 0 50px rgba(66,179,255,0.8)); transform: scale(1.1); }
}

.animate-zoom-glitch {
    animation: glitch-zoom 2s infinite alternate;
}

@keyframes glitch-zoom {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.05) skewX(2deg); opacity: 1; }
    100% { transform: scale(1.1); opacity: 0.9; }
}

.neural-nodes {
    background-image: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 50px 50px;
    animation: move-bg 100s linear infinite;
}

@keyframes move-bg {
    from { background-position: 0 0; }
    to { background-position: 1000px 1000px; }
}

@keyframes spin { to { transform: rotate(360deg); } }

.fade-overlay-enter-active, .fade-overlay-leave-active { transition: all 1.2s ease; }
.fade-overlay-enter-from, .fade-overlay-leave-to { opacity: 0; filter: blur(100px); }
</style>
