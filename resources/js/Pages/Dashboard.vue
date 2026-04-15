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
    webhook_status:     String, // New
    app_url:            String  // New
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
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 flex items-center justify-center shadow-2xl shadow-blue-500/40">
                        <span class="text-2xl">🧠</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-black tracking-tighter text-white uppercase leading-none">{{ $t('Memory OS') }}</h1>
                        <p class="text-[10px] text-blue-400 font-bold tracking-[0.3em] uppercase opacity-60 mt-1">{{ $t('Stability Index') }}: {{ overview.stability_index }}%</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button @click="showBotHelp" class="group flex items-center gap-3 px-5 py-2.5 rounded-2xl bg-white/5 border border-white/10 hover:bg-blue-600/20 hover:border-blue-500/50 transition-all duration-500">
                        <div class="relative w-2 h-2">
                             <span class="absolute inset-0 rounded-full bg-green-500 animate-ping opacity-75"></span>
                             <span class="relative block w-2 h-2 rounded-full bg-green-500"></span>
                        </div>
                        <span class="text-[11px] font-black text-white/70 uppercase tracking-widest group-hover:text-blue-400 transition-colors">{{ $t('Neural Sync') }}</span>
                    </button>
                    <div v-if="is_telegram_linked" class="flex items-center gap-2 px-4 py-2 rounded-2xl bg-green-500/10 border border-green-500/20 text-green-500 text-[10px] font-black uppercase tracking-widest animate-pulse">
                         LINKED
                    </div>
                </div>
            </div>
        </template>

        <!-- Main Content Area with Enhanced Layout -->
        <main class="relative z-10 max-w-[1600px] mx-auto p-4 md:p-8">
            
            <!-- TOP SECTION: Briefing & Map -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-12">
                
                <!-- Left: Briefing & Stability -->
                <div class="lg:col-span-4 flex flex-col gap-8">
                    <!-- Stability Portal -->
                    <div class="relative p-10 rounded-[48px] bg-gradient-to-br from-white/10 to-transparent border border-white/10 backdrop-blur-3xl overflow-hidden group">
                        <div class="absolute -top-24 -right-24 w-64 h-64 bg-blue-500/20 blur-[100px] rounded-full group-hover:bg-blue-500/30 transition-all duration-1000"></div>
                        
                        <h3 class="text-[11px] font-black uppercase tracking-[0.5em] text-blue-400 mb-8">{{ $t('Stability Flow') }}</h3>
                        
                        <div class="relative flex items-center justify-center mb-8">
                            <svg class="w-48 h-48 transform -rotate-90">
                                <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="8" fill="transparent" class="text-white/5" />
                                <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="8" fill="transparent" 
                                    class="text-blue-500 transition-all duration-1000"
                                    :stroke-dasharray="2 * Math.PI * 88" 
                                    :stroke-dashoffset="2 * Math.PI * 88 * (1 - harmony_score / 100)" />
                            </svg>
                            <div class="absolute flex flex-col items-center">
                                <span class="text-5xl font-black text-white tracking-tighter">{{ harmony_score }}%</span>
                                <span class="text-[10px] font-bold text-white/30 tracking-widest">STABLE</span>
                            </div>
                        </div>

                        <p class="text-sm leading-relaxed text-white/70 font-medium text-center italic opacity-80">
                             "{{ harmony_score > 70 ? $t('Harmony_High_Note') : $t('Harmony_Low_Note') }}"
                        </p>
                    </div>

                    <!-- AI Briefing Node -->
                    <div class="p-8 rounded-[48px] bg-white/5 border border-white/10 backdrop-blur-xl relative group">
                        <div class="flex items-center justify-between mb-8">
                            <h3 class="text-[10px] font-black uppercase tracking-[0.4em] text-white/40">{{ $t('Strategic Briefing') }}</h3>
                            <button @click="speakCombinedBriefing" class="w-12 h-12 rounded-2xl bg-blue-600/10 hover:bg-blue-600 text-blue-400 hover:text-white flex items-center justify-center transition-all duration-500 shadow-xl group-hover:scale-110">🔊</button>
                        </div>
                        <div class="space-y-6">
                            <p class="text-xl font-light leading-relaxed text-blue-50/90 bidi-plaintext italic border-l-2 border-blue-500/30 pl-6">"{{ shadow_prediction }}"</p>
                            <div class="p-6 rounded-[30px] bg-black/40 border border-white/5 text-[13px] text-white/50 leading-relaxed font-medium">
                                {{ daily_briefing }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Neural Map (Full Height) -->
                <div class="lg:col-span-8 h-[750px] rounded-[60px] bg-black/40 border border-white/10 backdrop-blur-sm overflow-hidden shadow-[0_0_100px_rgba(0,0,0,0.5)] relative group">
                    <div class="absolute top-8 left-8 z-10">
                        <h3 class="text-[11px] font-black uppercase tracking-[0.5em] text-white/20">3D Neural Infrastructure</h3>
                    </div>
                    <NeuralMap 
                        :ideas="neural_nodes.ideas" 
                        :decisions="neural_nodes.decisions" 
                        :people="neural_nodes.people" 
                        :balance="Number(overview.balance)"
                    />
                </div>
            </div>

            <!-- MIDDLE SECTION: Tasks & Success Routines -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <!-- Daily Missions Hub -->
                <div class="p-10 rounded-[54px] bg-gradient-to-br from-blue-600/5 to-transparent border border-white/10 backdrop-blur-3xl space-y-8 relative overflow-hidden">
                    <div class="absolute -bottom-20 -left-20 w-80 h-80 bg-blue-500/5 blur-[120px] rounded-full"></div>
                    
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl bg-blue-600/20 flex items-center justify-center text-xl">📋</div>
                            <h3 class="text-sm font-black text-white/80 uppercase tracking-[0.4em]">{{ $t('Daily Missions') }}</h3>
                        </div>
                        <span class="px-4 py-1 rounded-full bg-blue-500/20 text-blue-400 text-[10px] font-black tracking-widest">{{ tasks.filter(t => t.status === 'pending').length }} {{ $t('Remaining') }}</span>
                    </div>
                    
                    <form @submit.prevent="addTask" class="relative group">
                        <input 
                            v-model="taskForm.title"
                            type="text" 
                            :placeholder="$t('Inject new objective...')"
                            class="w-full bg-white/5 border border-white/10 rounded-[24px] py-5 px-8 text-lg focus:ring-blue-500 focus:border-blue-500 transition-all font-light placeholder:text-white/20"
                        />
                        <button type="submit" class="absolute right-6 top-1/2 -translate-y-1/2 w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center hover:scale-110 active:scale-90 transition-all shadow-lg shadow-blue-500/40">➕</button>
                    </form>

                    <div class="space-y-4 max-h-[400px] overflow-y-auto pr-4 custom-scroll">
                        <transition-group name="task-list">
                            <div v-for="task in tasks" :key="task.id" 
                                 @click="toggleTask(task.id)"
                                 class="p-6 rounded-[30px] bg-white/[0.03] border border-white/5 flex items-center justify-between group cursor-pointer hover:bg-white/10 transition-all duration-500">
                                <span :class="['text-base font-light transition-all duration-500', task.status === 'completed' ? 'text-white/20 line-through' : 'text-white/80 group-hover:text-white']">{{ task.title }}</span>
                                <div :class="['w-8 h-8 rounded-2xl border-2 transition-all duration-500 flex items-center justify-center', task.status === 'completed' ? 'bg-blue-600 border-blue-600' : 'border-white/10 bg-white/5']">
                                    <span v-if="task.status === 'completed'" class="text-white text-xs font-black">✓</span>
                                </div>
                            </div>
                        </transition-group>
                        <p v-if="tasks.length === 0" class="text-center text-white/10 text-sm py-12 font-light italic tracking-widest">{{ $t('Neural queue clear. No active missions.') }}</p>
                    </div>
                </div>

                <!-- Neural Blueprints Section -->
                <div class="p-10 rounded-[54px] bg-gradient-to-tr from-purple-600/5 to-transparent border border-white/10 backdrop-blur-3xl space-y-8 relative overflow-hidden">
                    <div class="absolute -top-20 -right-20 w-80 h-80 bg-purple-500/5 blur-[120px] rounded-full"></div>

                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-purple-600/20 flex items-center justify-center text-xl">🚀</div>
                        <h3 class="text-sm font-black text-white/80 uppercase tracking-[0.4em]">{{ $t('Neural Blueprints') }}</h3>
                    </div>

                    <div class="grid grid-cols-1 gap-6">
                        <div v-for="tpl in routine_templates" :key="tpl.id" 
                             class="p-6 rounded-[32px] bg-white/[0.03] border border-white/5 hover:border-blue-500/40 transition-all duration-500 group relative overflow-hidden flex items-center gap-6">
                            
                            <div class="w-16 h-16 rounded-[24px] bg-gradient-to-br from-white/10 to-transparent flex items-center justify-center text-3xl shadow-xl group-hover:scale-110 transition-transform duration-500">{{ tpl.icon }}</div>
                            <div class="flex-1">
                                <h4 class="text-lg font-black text-white group-hover:text-blue-400 transition-colors">{{ tpl.title }}</h4>
                                <p class="text-[11px] text-white/30 font-bold uppercase tracking-widest mt-1">{{ tpl.author }}</p>
                            </div>
                            <button @click="router.post(route('dashboard.apply-routine'), { routine_id: tpl.id })" 
                                    class="px-6 py-3 rounded-2xl bg-blue-600 text-white text-xs font-black hover:scale-105 hover:shadow-[0_0_20px_rgba(37,99,235,0.4)] active:scale-95 transition-all">
                                APPLY
                            </button>

                            <!-- Background Author Name as Decor -->
                            <span class="absolute right-[-5%] bottom-[-15%] text-7xl font-black text-white/[0.02] uppercase italic group-hover:text-white/[0.04] transition-all duration-1000">{{ tpl.author.split(' ').pop() }}</span>
                        </div>
                    </div>
                    
                    <div class="p-6 rounded-[30px] bg-blue-600/10 border border-blue-500/20 flex items-start gap-4">
                        <span class="text-2xl">💡</span>
                        <p class="text-xs text-blue-300 leading-relaxed font-medium">Applying a blueprint will inject multiple strategic tasks into your daily schedule to simulate the life patterns of high-performers.</p>
                    </div>
                </div>
            </div>

            <!-- BOTTOM SECTION: AI & Modules -->
            <div class="space-y-12">
                <!-- AI Strategy Area (Centerpiece) -->
                <div class="p-12 md:p-20 rounded-[70px] bg-gradient-to-br from-blue-600/10 via-black to-transparent border border-white/10 backdrop-blur-3xl shadow-2xl relative overflow-hidden">
                    <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')] opacity-10 pointer-events-none"></div>
                    <div class="absolute top-0 left-0 w-full h-px bg-gradient-to-r from-transparent via-blue-500 to-transparent opacity-50"></div>
                    
                    <div class="max-w-4xl mx-auto space-y-12 relative z-10">
                        <div class="flex flex-col md:flex-row items-center justify-between gap-12">
                            <div class="flex items-center gap-8">
                                <div class="w-20 h-20 rounded-[30px] bg-blue-600 flex items-center justify-center text-4xl shadow-[0_0_50px_rgba(37,99,235,0.5)] animate-pulse">🌌</div>
                                <div>
                                    <h2 class="text-4xl font-black text-white tracking-tighter">{{ $t('Strategic Insight') }}</h2>
                                    <p class="text-[10px] text-blue-400 font-bold tracking-[0.5em] uppercase opacity-70 mt-2">Neural_Nexus.v7 // Final Synthesis</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <button @click="toggleVoiceMode" :class="['px-8 py-4 rounded-3xl border text-[11px] font-black transition-all duration-500 tracking-widest', useRealisticVoice ? 'bg-blue-600 border-blue-400 text-white shadow-2xl shadow-blue-500/40' : 'bg-white/5 border-white/10 text-white/30 hover:text-white/60']">
                                    {{ useRealisticVoice ? '💎 REALISTIC VOICE' : '🤖 SYSTEM VOICE' }}
                                </button>
                                <button v-if="displayedAiText" @click="speakDisplayedText" class="w-14 h-14 rounded-3xl bg-white text-black flex items-center justify-center hover:scale-110 active:scale-90 transition-transform shadow-2xl">🔊</button>
                            </div>
                        </div>

                        <div v-if="!displayedAiText" class="text-center py-16 space-y-8">
                            <p class="text-2xl text-white/30 font-light max-w-lg mx-auto leading-relaxed">Let the AI analyze your patterns and generate an optimized strategic roadmap for your biological and financial success.</p>
                            <button @click="generatePlan" :disabled="isGeneratingPlan" class="px-16 py-6 bg-white text-black font-black rounded-[30px] hover:scale-105 active:scale-95 transition-all shadow-3xl disabled:opacity-50">
                                {{ isGeneratingPlan ? 'SYNTHESIZING...' : 'OPEN THE NEURAL ORACLE' }}
                            </button>
                        </div>

                        <div v-else class="space-y-10">
                            <div class="text-2xl md:text-4xl font-light leading-[1.6] text-blue-50/90 whitespace-pre-wrap bidi-plaintext selection:bg-blue-500 tracking-tight">
                                {{ displayedAiText }}<span class="inline-block w-3 h-10 bg-blue-500 ml-3 animate-pulse align-middle"></span>
                            </div>
                            <button v-if="!isGeneratingPlan" @click="generatePlan" class="text-blue-400 text-xs font-black uppercase tracking-[0.5em] hover:text-white transition-all duration-500 flex items-center gap-3">
                                ◈ <span>Re-Analyze Neural Sequence</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- BENTO NAVIGATION MODULES -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="mod in mainModules" :key="mod.title" @click="router.visit(route(mod.route))" 
                         class="p-10 rounded-[50px] bg-white/5 border border-white/5 hover:border-white/20 hover:bg-white/[0.08] transition-all duration-700 group cursor-pointer hover:-translate-y-4 shadow-2xl relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/5 blur-[50px] rounded-full group-hover:bg-white/10 transition-all duration-1000"></div>
                        
                        <div class="text-6xl mb-10 group-hover:scale-125 group-hover:rotate-6 transition-transform duration-700">{{ mod.icon }}</div>
                        <h4 class="text-2xl font-black text-white mb-3">{{ $t(mod.title) }}</h4>
                        <p class="text-[11px] text-white/40 leading-relaxed uppercase tracking-widest font-bold">{{ $t(mod.desc) }}</p>
                    </div>
                </div>

                <!-- SYNC HUB (Final Focus) -->
                <div class="p-12 md:p-20 rounded-[70px] bg-black border border-white/10 backdrop-blur-3xl shadow-3xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/5 to-purple-600/5 opacity-50"></div>
                    
                    <div class="flex flex-col lg:flex-row items-center justify-between gap-16 relative z-10">
                        <div class="max-w-xl space-y-8">
                            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-blue-500/10 border border-blue-500/20 text-blue-400 text-[10px] font-black uppercase tracking-widest">
                                Neural Sync v2.0
                            </div>
                            <h3 class="text-5xl font-black text-white tracking-tighter leading-none">Your life, synchronized in <span class="text-blue-500">real-time.</span></h3>
                            <p class="text-xl text-white/40 font-light leading-relaxed">Simply send voice notes, receipts, or thoughts. Our neural bot will categorize and analyze everything instantly into your OS.</p>
                        </div>

                        <div class="w-full lg:w-fit">
                            <div v-if="!is_telegram_linked" class="p-10 rounded-[48px] bg-white/5 border border-white/10 space-y-8 min-w-[350px]">
                                <div class="flex items-center justify-between gap-8">
                                    <div class="space-y-1">
                                        <p class="text-[11px] font-black text-blue-400 uppercase tracking-widest opacity-60">Neural ID Code</p>
                                        <p class="text-5xl font-mono font-black text-white tracking-[0.2em]">{{ sync_code }}</p>
                                    </div>
                                    <a :href="`https://t.me/PersonalMemory_Bot?start=${sync_code}`" target="_blank" 
                                       class="w-20 h-20 rounded-[30px] bg-blue-600 flex items-center justify-center text-4xl shadow-[0_0_30px_rgba(37,99,235,0.4)] hover:scale-110 active:scale-90 transition-transform">✈️</a>
                                </div>
                                <p class="text-xs text-white/30 italic font-medium leading-relaxed">Send this code to @PersonalMemory_Bot to begin. Code will decompose in 24 hours.</p>
                            </div>
                            <div v-else class="flex items-center gap-10 p-12 rounded-[48px] bg-green-500/5 border border-green-500/10 shadow-2xl">
                                <div class="relative">
                                    <div class="absolute inset-0 bg-green-500 blur-2xl opacity-20"></div>
                                    <span class="text-7xl relative">🛡️</span>
                                </div>
                                <div>
                                    <p class="text-2xl font-black text-white">System Synchronized</p>
                                    <p class="text-sm text-green-500/60 font-bold uppercase tracking-widest mt-1">Encrypted Link Active</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- NEURAL DIAGNOSTICS (High Visibility) -->
                <div class="mt-20 p-10 rounded-[40px] bg-white border-2 border-red-500 shadow-2xl relative z-20">
                    <div class="flex items-center gap-4 mb-8">
                        <span class="w-4 h-4 rounded-full bg-red-600 animate-ping"></span>
                        <h3 class="text-lg font-black text-red-600 uppercase tracking-widest">Neural System Diagnostics</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-12">
                        <div class="space-y-4">
                            <div>
                                <p class="text-[11px] text-black/60 uppercase font-black mb-1">Active Neural Link (APP_URL)</p>
                                <p class="text-sm font-mono text-black font-bold break-all">{{ app_url }}</p>
                            </div>
                            <div>
                                <p class="text-[11px] text-black/60 uppercase font-black mb-1">Telegram Webhook Status</p>
                                <p class="text-xl font-mono text-red-600 font-black uppercase">{{ webhook_status || 'NOT_CONFIGURED' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center gap-4">
                            <button @click="router.post(route('dashboard.set-webhook'))" class="w-full px-8 py-4 bg-red-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-xl">
                                🔗 FORCE RE-CONNECT TELEGRAM BOT
                            </button>
                            <p class="text-[10px] text-black/40 text-center font-medium italic">Click this only if Webhook Status is "NOT_CONFIGURED"</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </AuthenticatedLayout>
</template>

<style>
/* Custom animations for premium feel */
@keyframes pulse-slow { 0%, 100% { opacity: 0.3; } 50% { opacity: 0.6; } }
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
</style>
