<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import axios from 'axios';
import { getActiveLanguage, trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import NeuralMap from '@/Components/NeuralMap.vue';
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
    webhook_status:     String,
    app_url:            String,
    telegram_bot_token: String
});

const isRecordingTask = ref(false);
let taskRecognition = null;
const displayedAiText = ref("");
let typingInterval = null;
const selectedDialect = ref(props.ar_voice_dialect || 'ar-SA');
const useRealisticVoice = ref(localStorage.getItem('use_realistic_voice') === 'true');
const isGeneratingPlan = ref(false);

const getGradientForModule = (title) => {
    const gradients = {
        'Idea Lab': 'linear-gradient(135deg, #0ea5e9 0%, #06b6d4 50%, #0891b2 100%)',
        'Smart Budget': 'linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%)',
        'Decision Advisor': 'linear-gradient(135deg, #8b5cf6 0%, #7c3aed 50%, #6d28d9 100%)',
        'Health & Mood': 'linear-gradient(135deg, #14b8a6 0%, #0d9488 50%, #0f766e 100%)',
    };
    return gradients[title] || 'linear-gradient(135deg, #3b82f6 0%, #1e40af 100%)';
};

onMounted(() => {
    if (props.last_ai_analysis) {
        displayedAiText.value = props.last_ai_analysis;
    }
});

// ...existing code...

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

const isSpeaking = ref(false);

let currentAudio = null;

const speakBriefing = async (text) => {
    if (!text) return;
    
    if (isSpeaking.value) {
        window.speechSynthesis.cancel();
        if (currentAudio) {
            currentAudio.pause();
            currentAudio = null;
        }
        isSpeaking.value = false;
        return;
    }

    if (useRealisticVoice.value) {
        try {
            const res = await axios.post(route('dashboard.speak'), { text, dialect: selectedDialect.value });
            if (res.data.url) { 
                currentAudio = new Audio(res.data.url);
                currentAudio.onplay = () => isSpeaking.value = true;
                currentAudio.onended = () => { isSpeaking.value = false; currentAudio = null; };
                currentAudio.play(); 
                return; 
            }
        } catch (e) { console.warn("Realistic voice failed, fallback:", e); }
    }

    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = selectedDialect.value;
    utterance.onstart = () => isSpeaking.value = true;
    utterance.onend = () => isSpeaking.value = false;
    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
};

const speakDisplayedText = () => speakBriefing(displayedAiText.value);
const speakCombinedBriefing = () => speakBriefing(`${props.daily_briefing}. ${props.shadow_prediction}`);

const taskForm = useForm({ title: '' });
const addTask = () => taskForm.post(route('tasks.store'), { preserveScroll: true, onSuccess: () => taskForm.reset() });
const toggleTask = (id) => router.patch(route('tasks.toggle', id), {}, { preserveScroll: true });

const mainModules = [
    { title: 'Idea Lab',        icon: '💡', desc: 'Idea Lab Desc',        route: 'ideas.index',    color: 'blue' },
    { title: 'Smart Budget',    icon: '💰', desc: 'Smart Budget Desc',    route: 'money.index',    color: 'green' },
    { title: 'Decision Advisor',icon: '⚖️', desc: 'Decision Advisor Desc',route: 'decisions.index', color: 'purple' },
    { title: 'Health & Mood',   icon: '🧬', desc: 'Health & Mood Desc',   route: 'health.index',    color: 'teal' },
];

</script>

<template>
    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-6">

            <!-- TOP BANNER: Welcome & Strategic Insight (Compact) -->
            <div class="ai-briefing-compact n-card">
                <div class="flex-shrink-0 flex items-center gap-3 border-r border-slate-200 dark:border-slate-700 pr-6">
                    <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-xl">✨</div>
                    <div>
                        <h2 class="n-h3 leading-none">{{ $t('Neural Hub') }}</h2>
                        <p class="text-[10px] font-bold text-blue-500 uppercase mt-1">{{ $page.props.auth.user.name }}</p>
                    </div>
                </div>
                
                <div class="flex-1 min-w-0">
                    <p class="n-p truncate italic text-blue-600/80 dark:text-blue-400/80 bidi-plaintext">"{{ shadow_prediction }}"</p>
                </div>

                <button @click="speakCombinedBriefing" 
                    :class="['flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all', isSpeaking ? 'bg-rose-500 text-white animate-pulse' : 'bg-blue-500/10 text-blue-500']">
                    {{ isSpeaking ? '⏹' : '🔊' }}
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- LEFT CONTENT (8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    
                    <!-- MODULES GRID (Sleek) -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="mod in mainModules" :key="mod.title" @click="router.visit(route(mod.route))"
                             class="module-item group">
                            <div class="absolute top-0 left-0 w-full h-1" :style="{ background: getGradientForModule(mod.title) }"></div>
                            <div class="module-icon group-hover:scale-110 transition-transform">{{ mod.icon }}</div>
                            <h4 class="n-h3">{{ $t(mod.title) }}</h4>
                        </div>
                    </div>

                    <!-- DAILY MISSIONS (Professional List) -->
                    <div class="n-card">
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800 pb-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl">📋</span>
                                <h3 class="n-h3">{{ $t('Daily Missions') }}</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-indigo-500/10 text-indigo-500 text-[10px] font-black">{{ tasks.filter(t => t.status === 'pending').length }} PENDING</span>
                            </div>
                        </div>

                        <form @submit.prevent="addTask" class="flex gap-2 mb-4">
                            <input v-model="taskForm.title" type="text" :placeholder="$t('Inject new objective...')" class="n-input flex-1" />
                            <button type="submit" class="n-btn n-btn-primary px-4">+</button>
                        </form>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="task in tasks" :key="task.id" @click="toggleTask(task.id)"
                                 class="p-3 rounded-lg border border-slate-100 dark:border-slate-800 hover:border-blue-500/30 flex items-center justify-between cursor-pointer transition-all bg-slate-50/50 dark:bg-slate-900/50">
                                <span :class="['text-xs font-bold transition-all flex-1 truncate', task.status === 'completed' ? 'opacity-30 line-through' : '']">{{ task.title }}</span>
                                <div :class="['w-5 h-5 rounded border-2 transition-all flex items-center justify-center flex-shrink-0', task.status === 'completed' ? 'bg-blue-500 border-blue-500' : 'border-slate-300 dark:border-slate-700']">
                                    <span v-if="task.status === 'completed'" class="text-white text-[10px] font-black">✓</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT CONTENT (4 Cols) -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- STABILITY ORB (Compact) -->
                    <div class="n-card text-center flex flex-col items-center">
                        <h3 class="n-h3 mb-4">{{ $t('Stability Flow') }}</h3>
                        <div class="relative flex items-center justify-center mb-4">
                            <svg class="w-24 h-24 transform -rotate-90">
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="4" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                <circle cx="48" cy="48" r="40" stroke="currentColor" stroke-width="4" fill="transparent"
                                    class="text-blue-500 transition-all duration-1000" stroke-linecap="round"
                                    :stroke-dasharray="2 * Math.PI * 40"
                                    :stroke-dashoffset="2 * Math.PI * 40 * (1 - harmony_score / 100)" />
                            </svg>
                            <span class="absolute text-2xl font-black text-blue-500">{{ harmony_score }}%</span>
                        </div>
                        <p class="text-[10px] italic n-p">"{{ harmony_score > 70 ? $t('Harmony_High_Note') : $t('Harmony_Low_Note') }}"</p>
                    </div>

                    <!-- AI ANALYSIS (Shrinked & Professional) -->
                    <div class="n-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="n-h3">⚡ {{ $t('AI Insight') }}</h3>
                            <button @click="generatePlan" :disabled="isGeneratingPlan" class="text-[10px] font-black text-blue-500 hover:underline uppercase">◈ Refresh</button>
                        </div>
                        
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800">
                            <p v-if="!displayedAiText" class="text-[11px] n-p italic text-center py-4">"Ready for deep analysis..."</p>
                            <div v-else class="space-y-4">
                                <p class="text-xs font-medium leading-relaxed bidi-plaintext italic text-slate-700 dark:text-slate-300">
                                    {{ displayedAiText.length > 200 ? displayedAiText.substring(0, 200) + '...' : displayedAiText }}
                                </p>
                                <button v-if="displayedAiText.length > 200" @click="speakDisplayedText" class="text-[10px] font-black text-blue-500 uppercase">🔊 Listen Full</button>
                            </div>
                        </div>
                    </div>

                    <!-- BLUEPRINTS (Professional Sidebar) -->
                    <div class="n-card">
                        <h3 class="n-h3 mb-4">🚀 {{ $t('Blueprints') }}</h3>
                        <div class="space-y-2 max-h-[200px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="tpl in routine_templates" :key="tpl.id"
                                 class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
                                <div class="w-8 h-8 rounded bg-blue-500/10 flex items-center justify-center text-sm flex-shrink-0">{{ tpl.icon }}</div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[11px] font-black truncate">{{ tpl.title }}</h4>
                                </div>
                                <button @click="router.post(route('dashboard.apply-routine'), { routine_id: tpl.id })"
                                        class="n-btn n-btn-primary px-2 py-1 text-[9px]">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles moved to dashboard.css for better theme support */
</style>
