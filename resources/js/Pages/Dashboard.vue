<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { getActiveLanguage, trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import NeuralMap from '@/Components/NeuralMap.vue';
import SmartBriefingPanel from '@/Components/SmartBriefingPanel.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';

const props = defineProps({
    tasks:              Array,
    habit:              Object,
    goal:               Object,
    overview:           Object,
    gamification:       Object,
    shadow_prediction:  String,
    daily_briefing:     String,
    harmony_score:      Number,
    sync_code:          String,
    is_telegram_linked: Boolean,
    routine_templates:  Array,
    last_ai_analysis:   String,
    neural_nodes:       Object,
    ar_voice_dialect:   String,
});

const isRecordingTask = ref(false);
let taskRecognition = null;
const aiPlanText = ref(null);
const displayedAiText = ref("");
let typingInterval = null;
const selectedDialect = ref(props.ar_voice_dialect || 'ar-SA');
const useRealisticVoice = ref(localStorage.getItem('use_realistic_voice') === 'true');
const isGeneratingPlan = ref(false);

onMounted(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        taskRecognition = new SpeechRecognition();
        taskRecognition.continuous = false;
        taskRecognition.lang = getActiveLanguage() === 'ar' ? 'ar-SA' : 'en-US';
        taskRecognition.onstart = () => { isRecordingTask.value = true; };
        taskRecognition.onend = () => { isRecordingTask.value = false; };
        taskRecognition.onresult = (e) => { taskForm.title += ' ' + e.results[0][0].transcript; };
    }

    if (props.last_ai_analysis) {
        displayedAiText.value = props.last_ai_analysis;
    }
});

const toggleVoiceMode = () => {
    useRealisticVoice.value = !useRealisticVoice.value;
    localStorage.setItem('use_realistic_voice', useRealisticVoice.value);
};

const typeText = (text) => {
    displayedAiText.value = "";
    let i = 0;
    if (typingInterval) clearInterval(typingInterval);
    typingInterval = setInterval(() => {
        if (i < text.length) {
            displayedAiText.value += text.charAt(i);
            i++;
        } else {
            clearInterval(typingInterval);
        }
    }, 15);
};

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const res = await axios.post(route('dashboard.generate-plan'), { locale: getActiveLanguage() });
        typeText(res.data.plan);
    } catch (e) {
        typeText(trans("Error connecting to AI advisor."));
    } finally {
        isGeneratingPlan.value = false;
    }
};

const speakBriefing = async (text) => {
    if (!text) return;
    if (useRealisticVoice.value) {
        try {
            const res = await axios.post(route('dashboard.speak'), { text, dialect: selectedDialect.value });
            if (res.data.url) { new Audio(res.data.url).play(); return; }
        } catch (e) { console.warn("Realistic voice failed, fallback:", e); }
    }
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = selectedDialect.value;
    const voices = window.speechSynthesis.getVoices();
    const voice = voices.find(v => v.lang === selectedDialect.value) || voices.find(v => v.lang.startsWith('ar'));
    if (voice) utterance.voice = voice;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
};

const speakDisplayedText = () => speakBriefing(displayedAiText.value);
const speakCombinedBriefing = () => speakBriefing(`${props.daily_briefing}. ${props.shadow_prediction}`);

const taskForm = useForm({ title: '' });
const addTask = () => taskForm.post(route('tasks.store'), { preserveScroll: true, onSuccess: () => taskForm.reset() });
const toggleTask = (id) => router.patch(route('tasks.toggle', id), {}, { preserveScroll: true });

const showBotHelp = () => {
    Swal.fire({
        title: trans('Bot Connectivity'),
        html: `<div class="text-left text-sm space-y-4">
            <p>🤖 Local Sync: ${window.location.hostname === 'localhost' ? '⚠️ Localhost' : '✅ Online'}</p>
            <button id="pkBtn" class="w-full py-2 bg-blue-600 text-white rounded-xl text-xs font-bold">🚀 Set Webhook</button>
        </div>`,
        didOpen: () => {
            document.getElementById('pkBtn').onclick = async () => {
                try {
                    const res = await axios.post(route('dashboard.set-webhook'));
                    Swal.fire({ title: 'Success', text: res.data.success, icon: 'success' });
                } catch (e) {
                    Swal.fire({ title: 'Error', text: e.response?.data?.error || 'Failed', icon: 'error' });
                }
            };
        },
        background: 'var(--c-surface)', color: 'var(--c-text)'
    });
};

const mainModules = [
    { title: 'Idea Lab',        icon: '💡', desc: 'Idea Lab Desc',        route: 'ideas.index',    color: 'blue' },
    { title: 'Smart Budget',    icon: '💰', desc: 'Smart Budget Desc',    route: 'money.index',    color: 'green' },
    { title: 'Decision Advisor',icon: '⚖️', desc: 'Decision Advisor Desc',route: 'decisions.index', color: 'purple' },
    { title: 'Health & Mood',   icon: '🧬', desc: 'Health & Mood Desc',   route: 'health.index',    color: 'teal' },
];

</script>

<template>
    <div class="min-h-screen bg-[#050505] text-white font-sans selection:bg-blue-500/30">
        <!-- Background Effects -->
        <div class="fixed inset-0 z-0 overflow-hidden pointer-events-none">
            <div class="absolute top-[10%] left-[10%] w-[500px] h-[500px] bg-blue-600/10 blur-[150px] rounded-full"></div>
            <div class="absolute bottom-[10%] right-[10%] w-[500px] h-[500px] bg-purple-600/10 blur-[150px] rounded-full"></div>
        </div>

        <!-- Navbar -->
        <nav class="sticky top-0 z-50 px-6 py-4 backdrop-blur-2xl border-b border-white/5 bg-black/40">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div @click="router.visit('/')" class="flex items-center gap-4 cursor-pointer">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-lg shadow-blue-500/20">
                        <span class="text-xl">🧠</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-black tracking-tighter text-white uppercase">{{ $t('Memory OS') }}</h1>
                        <p class="text-[9px] text-blue-400 font-bold tracking-[0.2em] uppercase">{{ $t('Stability Index') }}: 98%</p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <button @click="showBotHelp" class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 border border-white/10">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span class="text-[10px] font-black text-white/50 uppercase tracking-widest">{{ $t('Neural Sync') }}</span>
                    </button>
                    <div class="h-6 w-px bg-white/10"></div>
                    <ThemeToggle />
                    <LanguageSwitcher />
                    <Link :href="route('profile.edit')" class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center hover:bg-white/10">👤</Link>
                </div>
            </div>
        </nav>

        <main class="relative z-10 max-w-7xl mx-auto p-4 md:p-8 space-y-8">
            
            <!-- CORE INTEL GRID -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- Left: Status & Briefing -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- Harmony Card -->
                    <div class="p-8 rounded-[40px] bg-white/5 border border-white/10 backdrop-blur-3xl relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-8 opacity-10 group-hover:scale-125 transition-transform duration-1000">🧬</div>
                        <h3 class="text-[10px] font-black uppercase tracking-[0.4em] text-blue-400 mb-6">{{ $t('Life Harmony') }}</h3>
                        <div class="flex items-baseline gap-4 mb-6">
                            <span class="text-6xl font-black text-white tracking-tighter">{{ harmony_score }}%</span>
                            <span class="text-xs font-bold text-white/30 tracking-widest uppercase">/ Neural Stability</span>
                        </div>
                        <p class="text-sm leading-relaxed text-white/60 mb-6 font-medium">
                            {{ harmony_score > 70 ? $t('Harmony_High_Note') : $t('Harmony_Low_Note') }}
                        </p>
                        <div class="w-full h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-1000" :style="{ width: harmony_score + '%' }"></div>
                        </div>
                    </div>

                    <!-- Briefing Control -->
                    <div class="p-8 rounded-[40px] bg-gradient-to-br from-white/5 to-transparent border border-white/10 backdrop-blur-md">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.4em] text-white/30">{{ $t('Strategic Briefing') }}</h3>
                            <button @click="speakCombinedBriefing" class="w-10 h-10 rounded-full bg-accent/20 flex items-center justify-center hover:bg-accent/40 transition-all text-xs">🔊</button>
                        </div>
                        <div class="space-y-4">
                            <p class="text-lg font-light leading-relaxed text-white/80 bidi-plaintext italic">"{{ shadow_prediction }}"</p>
                            <div class="p-4 rounded-2xl bg-black/40 border border-white/5 text-xs text-white/50 leading-relaxed font-medium">
                                {{ daily_briefing }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Neural Map -->
                <div class="lg:col-span-7 h-[600px] rounded-[48px] bg-white/5 border border-white/10 backdrop-blur-sm overflow-hidden shadow-2xl relative">
                    <NeuralMap 
                        :ideas="neural_nodes.ideas" 
                        :decisions="neural_nodes.decisions" 
                        :people="neural_nodes.people" 
                        :balance="Number(overview.balance)"
                    />
                </div>
            </div>

            <!-- AI STRATEGIC PLAN AREA -->
            <div class="p-10 md:p-16 rounded-[60px] bg-gradient-to-br from-blue-600/10 via-indigo-600/5 to-transparent border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-50"></div>
                
                <div class="max-w-4xl mx-auto space-y-12">
                    <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                        <div class="flex items-center gap-6">
                            <div class="w-16 h-16 rounded-3xl bg-blue-600 flex items-center justify-center text-3xl shadow-2xl shadow-blue-500/40 animate-pulse">🌌</div>
                            <div>
                                <h2 class="text-3xl font-black text-white tracking-tighter">{{ $t('Strategic Insight') }}</h2>
                                <p class="text-xs text-blue-400 font-bold tracking-[0.3em] uppercase opacity-60">Neural_Nexus.v7 // Final Synthesis</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <button @click="toggleVoiceMode" :class="['px-6 py-3 rounded-2xl border text-[10px] font-black transition-all tracking-widest', useRealisticVoice ? 'bg-blue-600 border-blue-400 text-white shadow-xl shadow-blue-500/30' : 'bg-white/5 border-white/10 text-white/30']">
                                {{ useRealisticVoice ? '💎 REALISTIC VOICE' : '🤖 SYSTEM VOICE' }}
                            </button>
                            <button v-if="displayedAiText" @click="speakDisplayedText" class="w-12 h-12 rounded-2xl bg-white text-black flex items-center justify-center hover:scale-110 transition-transform">🔊</button>
                        </div>
                    </div>

                    <div v-if="!displayedAiText" class="text-center py-10 space-y-6">
                        <p class="text-xl text-white/30 font-light max-w-sm mx-auto">Generate a high-level strategic roadmap for your biological and financial optimization.</p>
                        <button @click="generatePlan" :disabled="isGeneratingPlan" class="px-10 py-5 bg-white text-black font-black rounded-[24px] hover:scale-105 active:scale-95 transition-all shadow-2xl disabled:opacity-50">
                            {{ isGeneratingPlan ? 'SYNTHESIZING...' : 'GENERATE ROADMAP' }}
                        </button>
                    </div>

                    <div v-else class="prose prose-invert prose-blue max-w-none">
                        <div class="text-xl md:text-3xl font-light leading-[1.6] text-blue-50/90 whitespace-pre-wrap bidi-plaintext selection:bg-blue-500">
                            {{ displayedAiText }}<span class="inline-block w-2 h-8 bg-blue-500 ml-2 animate-pulse align-middle"></span>
                        </div>
                        <button v-if="!isGeneratingPlan" @click="generatePlan" class="mt-12 text-blue-400 text-xs font-black uppercase tracking-[0.4em] hover:text-white transition-colors">◈ Re-Analyze Neural Sequence</button>
                    </div>
                </div>
            </div>

            <!-- BENTO MODULES -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div v-for="mod in mainModules" :key="mod.title" @click="router.visit(route(mod.route))" class="p-8 rounded-[40px] bg-white/5 border border-white/5 hover:border-white/20 transition-all group cursor-pointer hover:-translate-y-2">
                    <div class="text-4xl mb-6 group-hover:scale-125 transition-transform duration-500">{{ mod.icon }}</div>
                    <h4 class="text-lg font-black text-white mb-2">{{ $t(mod.title) }}</h4>
                    <p class="text-[10px] text-white/40 leading-relaxed uppercase tracking-wider font-bold">{{ $t(mod.desc) }}</p>
                </div>
            </div>

            <!-- SYNC HUB (Redesigned) -->
            <div class="p-10 rounded-[48px] bg-black/40 border border-white/5 backdrop-blur-xl">
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-sm font-black text-white/20 uppercase tracking-[0.4em]">Integrated Neural Hub</h3>
                    <div v-if="is_telegram_linked" class="flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/10 border border-green-500/20 text-green-500 text-[10px] font-black uppercase tracking-widest">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span> Linked
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div class="space-y-4">
                        <p class="text-2xl font-black text-white tracking-tighter">Your life, synchronized in real-time via Telegram.</p>
                        <p class="text-sm text-white/40 leading-relaxed">Simply send voice notes, receipts, or thoughts. Our neural bot will categorize and analyze everything instantly.</p>
                    </div>

                    <div v-if="!is_telegram_linked" class="p-8 rounded-[32px] bg-white/5 border border-white/10 space-y-6">
                        <div class="flex items-center justify-between">
                            <div class="space-y-1">
                                <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">Initialization Code</p>
                                <p class="text-4xl font-mono font-black text-white tracking-widest">{{ sync_code }}</p>
                            </div>
                            <a :href="`https://t.me/PersonalMemory_Bot?start=${sync_code}`" target="_blank" class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center text-3xl shadow-xl shadow-blue-500/20 hover:scale-110 transition-transform">✈️</a>
                        </div>
                        <p class="text-[10px] text-white/30 italic">Send this code to @PersonalMemory_Bot to begin. Link will expire in 24 hours.</p>
                    </div>
                    <div v-else class="flex items-center gap-6 p-8 rounded-[32px] bg-green-500/5 border border-green-500/10">
                        <span class="text-5xl">✅</span>
                        <div>
                            <p class="text-lg font-black text-white">System Synchronized</p>
                            <p class="text-xs text-green-500/60 font-bold uppercase tracking-widest">Connected as @farhan_bot</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</template>

<style>
/* Custom animations for premium feel */
@keyframes pulse-slow { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.6; } }
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
</style>
