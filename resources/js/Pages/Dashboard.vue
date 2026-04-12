<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import { getActiveLanguage, trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import NeuralMap from '@/Components/NeuralMap.vue';
import SmartBriefingPanel from '@/Components/SmartBriefingPanel.vue';

const isRecordingTask = ref(false);
let taskRecognition = null;

onMounted(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        taskRecognition = new SpeechRecognition();
        taskRecognition.continuous = false;
        taskRecognition.interimResults = false;
        taskRecognition.lang = getActiveLanguage() === 'ar' ? 'ar-SA' : 'en-US';

        taskRecognition.onstart = () => { isRecordingTask.value = true; };
        taskRecognition.onend = () => { isRecordingTask.value = false; };
        taskRecognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            taskForm.title += (taskForm.title ? ' ' : '') + transcript;
        };
    }

    if (props.last_ai_analysis) {
        aiPlanText.value = props.last_ai_analysis;
        displayedAiText.value = props.last_ai_analysis;
    }
});

const startTaskVoice = () => {
    if (!taskRecognition) {
        Swal.fire({
            title: trans('Not Supported'),
            text: trans('Your browser does not support voice recognition.'),
            icon: 'error',
            background: 'var(--c-surface)',
            color: 'var(--c-text)'
        });
        return;
    }
    if (isRecordingTask.value) {
        taskRecognition.stop();
    } else {
        taskRecognition.start();
    }
};

const stabilityChartOptions = computed(() => ({
    chart: { type: 'radialBar', sparkline: { enabled: true } },
    plotOptions: {
        radialBar: {
            startAngle: -90,
            endAngle: 90,
            track: { background: "var(--c-border-subtle)", strokeWidth: '97%' },
            dataLabels: {
                name: { show: false },
                value: { offsetY: -2, fontSize: '22px', fontWeight: '900', color: 'var(--c-text)' }
            }
        }
    },
    fill: {
        type: 'gradient',
        gradient: {
            shade: 'dark',
            type: 'horizontal',
            gradientToColors: ['#069BFF'],
            stops: [0, 100]
        }
    },
    colors: ['#22c55e'],
    labels: [trans('Stability Index')],
}));

const stabilitySeries = computed(() => [props.overview.stability_index]);

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
    neural_nodes:       Object,   // { ideas, decisions, people }
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);
const displayedAiText = ref("");
let typingInterval = null;

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
    aiPlanText.value = null;
    displayedAiText.value = "";
    try {
        const response = await axios.post(route('dashboard.generate-plan'), {
            locale: getActiveLanguage()
        });
        aiPlanText.value = response.data.plan;
        typeText(aiPlanText.value);
    } catch (e) {
        aiPlanText.value = trans("Error connecting to AI advisor. Please try again later.");
        typeText(aiPlanText.value);
    } finally {
        isGeneratingPlan.value = false;
    }
};

const taskForm = useForm({ title: '' });
const addTask = () => {
    taskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const toggleTask = (id) => {
    router.patch(route('tasks.toggle', id), {}, { preserveScroll: true });
};

const isEditingGoal = ref(!props.goal);
const goalForm = useForm({
    title: props.goal ? props.goal.title : ''
});

const saveGoal = () => {
    goalForm.post(route('goals.store'), {
        preserveScroll: true,
        onSuccess: () => { isEditingGoal.value = false; }
    });
};

const habitForm = useForm({
    name: props.habit ? props.habit.name : ''
});

const isEditingHabit = ref(!props.habit);
const saveHabit = () => {
    habitForm.post(route('habits.store'), {
        preserveScroll: true,
        onSuccess: () => { isEditingHabit.value = false; }
    });
};
const isFocusMode = ref(false);

// ═══ Arabic Voice Selector ═══
const arabicDialects = [
    { label: 'السعودية 🇸🇦',    lang: 'ar-SA', keywords: ['Majed','Maged','Tarik','Saudi'] },
    { label: 'مصر 🇪🇬',        lang: 'ar-EG', keywords: ['Oda','Ossama','Egyptian','Cairo'] },
    { label: 'الإمارات 🇦🇪',   lang: 'ar-AE', keywords: ['Emirati','UAE'] },
    { label: 'السورية 🇸🇾',    lang: 'ar-SY', keywords: ['Syrian'] },
    { label: 'الكويت 🇰🇼',     lang: 'ar-KW', keywords: ['Kuwaiti'] },
    { label: 'المغرب 🇲🇦',     lang: 'ar-MA', keywords: ['Moroccan'] },
    { label: 'تلقائي 🌐',      lang: 'ar',    keywords: [] },
];
const selectedDialect = ref(localStorage.getItem('ar_voice_dialect') || 'ar-SA');
const showVoiceSelector = ref(false);
const availableVoices = ref([]);

onMounted(() => {
    const loadVoices = () => {
        availableVoices.value = window.speechSynthesis?.getVoices() || [];
    };
    loadVoices();
    window.speechSynthesis.onvoiceschanged = loadVoices;
});

const setDialect = (lang) => {
    selectedDialect.value = lang;
    localStorage.setItem('ar_voice_dialect', lang);
    showVoiceSelector.value = false;
};

const getBestVoice = (lang) => {
    const voices = availableVoices.value;
    // Try exact match first
    let voice = voices.find(v => v.lang === lang);
    // Then try prefix match (e.g. ar-SA matches ar-SA-...)
    if (!voice) voice = voices.find(v => v.lang.startsWith(lang));
    // Then try any Arabic voice
    if (!voice) voice = voices.find(v => v.lang.startsWith('ar'));
    return voice || null;
};

const speakBriefing = () => {
    window.speechSynthesis.cancel();
    const msg = new SpeechSynthesisUtterance(props.daily_briefing);
    const isArabic = getActiveLanguage() === 'ar';
    if (isArabic) {
        msg.lang = selectedDialect.value;
        const voice = getBestVoice(selectedDialect.value);
        if (voice) msg.voice = voice;
    } else {
        msg.lang = 'en-US';
    }
    msg.rate = 0.95;
    window.speechSynthesis.speak(msg);
};

const isRoutineModalOpen = ref(false);
const selectedRoutine = ref(null);

const viewRoutine = (routine) => {
    selectedRoutine.value = routine;
    isRoutineModalOpen.value = true;
};

const closeRoutineModal = () => {
    isRoutineModalOpen.value = false;
    selectedRoutine.value = null;
};

const isAdopting = ref(null);
const adoptRoutine = async (id) => {
    isAdopting.value = id;
    try {
        await axios.post(route('dashboard.apply-routine'), { routine_id: id });
        Swal.fire({
            title: trans('Routine Adopted!'),
            text: trans('The neural system has been updated with these tasks.'),
            icon: 'success',
            background: 'var(--c-surface)',
            color: 'var(--c-text)',
            confirmButtonColor: 'var(--c-accent)'
        });
        closeRoutineModal();
        router.reload();
    } catch (e) {
        Swal.fire({
            title: trans('Error'),
            text: trans('Neural synchronization failed.'),
            icon: 'error'
        });
    } finally {
        isAdopting.value = null;
    }
};

const showBotHelp = () => {
    Swal.fire({
        title: trans('Bot Connectivity'),
        html: `
            <div class="text-left text-sm space-y-4">
                <p>🤖 <strong>Why it fails localy?</strong> Telegram cannot reach your computer (localhost).</p>
                <p>🚀 <strong>How to fix:</strong> Use a tool like <b>ngrok</b> to create a public URL.</p>
                <code class="bg-black/20 p-2 block rounded mt-2">ngrok http 8000</code>
                <p>Then set the webhook to: <br/><i class="text-accent underline font-mono text-xs">/api/telegram/webhook</i></p>
            </div>
        `,
        icon: 'info',
        background: 'var(--c-surface)',
        color: 'var(--c-text)',
        confirmButtonColor: 'var(--c-accent)'
    });
};
</script>

<template>
    <Head :title="`${$t('Dashboard')} — Memory OS`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-black text-3xl text-text-main tracking-tight flex items-center gap-3">
                    <span class="w-12 h-12 rounded-2xl bg-accent/10 flex items-center justify-center text-3xl shadow-inner">🧠</span>
                    {{ $t('Your Personal Brain') }}
                </h2>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-black uppercase tracking-widest text-text-muted">{{ $t('Neural Sync') }}</span>
                        <span class="text-xs font-bold text-green-500 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                            {{ $t('Active') }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            
            <!-- Real Bento Grid Structure -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 auto-rows-auto">
                
                <!-- 1. Hero AI Vision (Large Header - Span 4x1) -->
                <div class="md:col-span-4 lg:col-span-4 bento-hero group overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-br from-accent/10 via-transparent to-purple-500/10 -z-10 group-hover:scale-110 transition-transform duration-1000"></div>
                    
                    <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8 relative z-10 w-full">
                        <div class="flex-1 w-full">
                            <div class="flex items-center gap-3 mb-6">
                                <span class="badge-premium">{{ $t('Live Neural Feed') }}</span>
                                <div class="xp-bar-mini" :title="`XP: ${gamification.xp}/${gamification.next_xp}`">
                                    <span class="text-[10px] text-yellow-500 font-black">LVL {{ gamification.level }}</span>
                                    <div class="h-1 w-16 bg-white/10 rounded-full overflow-hidden">
                                        <div class="h-full bg-yellow-500 rounded-full" :style="`width: ${gamification.progress}%`"></div>
                                    </div>
                                </div>
                                <button @click="isFocusMode = !isFocusMode" :class="['focus-pill', isFocusMode ? 'active' : '']">
                                    {{ isFocusMode ? '👁️' : '🕶️' }} {{ $t('Focus Mode') }}
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- Shadow Prediction -->
                                <div class="neural-card-sub p-6 hover-lift">
                                    <h5 class="text-[9px] text-accent font-black uppercase tracking-[0.3em] mb-2 opacity-60">{{ $t('Neural_Prophecy.v1') }}</h5>
                                    <p class="text-text-main text-lg font-light italic bidi-plaintext leading-relaxed">
                                        "{{ shadow_prediction }}"
                                    </p>
                                </div>
                                <!-- Daily Brief -->
                                <div class="neural-card-sub p-6 hover-lift border-accent/20">
                                    <div class="flex justify-between items-center mb-2">
                                        <h5 class="text-[9px] text-accent font-black uppercase tracking-[0.3em] opacity-60">{{ $t('Morning Briefing') }}</h5>
                                        <div class="flex items-center gap-2">
                                            <!-- Voice Selector -->
                                            <select 
                                                v-if="getActiveLanguage() === 'ar'"
                                                v-model="selectedDialect" 
                                                @change="setDialect($event.target.value)"
                                                class="bg-transparent border border-white/10 text-[10px] text-white/70 rounded-md px-1 py-0.5 outline-none focus:border-accent"
                                            >
                                                <option v-for="d in arabicDialects" :key="d.lang" :value="d.lang" class="bg-gray-900">{{ d.label }}</option>
                                            </select>
                                            <button @click="speakBriefing" class="p-1.5 bg-accent/10 rounded-full hover:bg-accent/20 transition-all text-xs" title="استمع للتحليل">🔊</button>
                                        </div>
                                    </div>
                                    <p class="text-text-main text-xs bidi-plaintext leading-relaxed font-medium">
                                        {{ daily_briefing }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex-shrink-0 self-center">
                            <button @click="generatePlan" :disabled="isGeneratingPlan" class="ai-orb-btn group/orb">
                                <template v-if="!isGeneratingPlan">
                                    <span class="text-4xl mb-1 group-hover/orb:scale-125 transition-transform duration-500">✨</span>
                                    <span class="text-[10px] font-black uppercase tracking-widest">{{ $t('Analyze') }}</span>
                                </template>
                                <span v-else class="animate-spin w-8 h-8 border-3 border-accent border-t-transparent rounded-full"></span>
                            </button>
                        </div>
                    </div>

                    <!-- AI Neural Terminal (Expands when text exists) -->
                    <transition name="expand">
                        <div v-if="displayedAiText" class="mt-8 terminal-box">
                            <div class="flex items-center gap-2 mb-4 border-b border-white/5 pb-2">
                                <div class="flex gap-1">
                                    <div class="w-2 h-2 rounded-full bg-red-500/50"></div>
                                    <div class="w-2 h-2 rounded-full bg-yellow-500/50"></div>
                                    <div class="w-2 h-2 rounded-full bg-green-500/50"></div>
                                </div>
                                <span class="text-[9px] font-mono text-text-muted uppercase tracking-widest">{{ $t('Neural_Advisor_Output.log') }}</span>
                            </div>
                            <div class="text-text-main text-sm font-mono leading-relaxed whitespace-pre-wrap bidi-plaintext">
                                {{ displayedAiText }}<span class="w-1.5 h-4 bg-accent inline-block animate-pulse ml-1"></span>
                            </div>
                        </div>
                    </transition>
                </div>

                <!-- ═══ NEW: Neural Intelligence Row ═══ -->
                <!-- Neural Map (3D Graph) - Span 3 -->
                <div class="md:col-span-3 bento-card !p-0 overflow-hidden" style="min-height:400px">
                    <NeuralMap
                        :ideas="neural_nodes?.ideas ?? []"
                        :decisions="neural_nodes?.decisions ?? []"
                        :people="neural_nodes?.people ?? []"
                        :balance="overview?.balance ?? 0"
                    />
                </div>

                <!-- Smart Briefing + Voice + Guardian - Span 1 -->
                <div class="md:col-span-1">
                    <SmartBriefingPanel
                        :briefing="daily_briefing"
                        :balance="overview?.balance ?? 0"
                        :tasks="tasks ?? []"
                        :harmony="harmony_score ?? 0"
                    />
                </div>

                <!-- 2. Wallet (Small - Span 1x1) -->
                <div @click="router.visit(route('money.index'))" class="bento-card hover-lift md:col-span-1 border-green-500/10 hover:border-green-500/30">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-2xl">💰</span>
                        <span class="text-green-400 group-hover:translate-x-1 transition-transform">→</span>
                    </div>
                    <h4 class="text-text-muted text-[10px] uppercase font-black tracking-widest mb-1">{{ $t('Balance') }}</h4>
                    <p class="text-xl font-black text-text-main bidi-plaintext">{{ overview.balance }}</p>
                </div>

                <!-- 3. Stability Index (Small - Span 1x1) -->
                <div class="bento-card md:col-span-1 border-accent/10">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-2xl">🧬</span>
                        <span class="text-[9px] font-black uppercase text-accent">{{ $t('Stability') }}</span>
                    </div>
                    <div class="flex flex-col items-center justify-center h-24">
                        <VueApexCharts type="radialBar" height="150" :options="stabilityChartOptions" :series="stabilitySeries" />
                    </div>
                </div>

                <!-- 4. Logic/Decisions (Medium - Span 2x1) -->
                <div @click="router.visit(route('decisions.index'))" class="bento-card hover-lift md:col-span-2 border-blue-500/10 hover:border-blue-500/30">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">⚖️</span>
                            <h4 class="text-text-muted text-[10px] uppercase font-black tracking-widest">{{ $t('Decision Logic') }}</h4>
                        </div>
                        <span class="text-blue-400">→</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-3xl font-black text-text-main">{{ overview.decision_logic_avg }}%</p>
                            <p class="text-[10px] text-text-muted mt-1 font-mono">{{ overview.sealed_decisions_count }} {{ $t('Sessions Sealed') }}</p>
                        </div>
                        <div class="flex gap-1 h-8 items-end">
                            <div v-for="i in 12" :key="i" class="w-1.5 bg-blue-500/20 rounded-full" :style="`height: ${Math.random()*100}%`"></div>
                        </div>
                    </div>
                </div>

                <!-- 5. Tasks (Large - Span 2x2) -->
                <div class="bento-card md:col-span-2 md:row-span-2 flex flex-col">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-black text-text-main flex items-center gap-2">
                            <span class="p-1.5 bg-blue-500/10 rounded-lg text-blue-400 text-xs">📋</span>
                            {{ $t('Tasks') }}
                        </h3>
                    </div>
                    
                    <form @submit.prevent="addTask" class="mb-6 group/task relative">
                        <input 
                            v-model="taskForm.title" 
                            type="text"
                            :placeholder="$t('Neural command...')"
                            class="bento-input w-full text-sm"
                            required
                        />
                        <button type="button" @click="startTaskVoice" :class="['voice-btn', isRecordingTask ? 'recording' : '']">🎤</button>
                    </form>

                    <div class="space-y-2 overflow-y-auto custom-scroll flex-1 max-h-[300px] pr-2">
                        <div v-for="task in tasks" :key="task.id" 
                            @click="toggleTask(task.id)"
                            :class="['task-pill', task.status === 'completed' ? 'done' : '']"
                        >
                            <div class="task-check">
                                <span v-if="task.status === 'completed'">✓</span>
                            </div>
                            <span class="text-xs font-medium bidi-plaintext flex-1 truncate">{{ task.title }}</span>
                        </div>
                    </div>
                </div>

                <!-- 6. Life Harmony (Medium - Span 2x1) -->
                <div class="bento-card md:col-span-2 border-purple-500/10">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-black text-text-main flex items-center gap-2">
                            <span class="p-1.5 bg-purple-500/10 rounded-lg text-purple-400 text-xs">⚖️</span>
                            {{ $t('Life Harmony') }}
                        </h3>
                    </div>
                    <div class="flex items-center gap-6">
                        <div class="flex-shrink-0 w-20 flex flex-col items-center">
                            <span class="text-2xl font-black text-purple-400">{{ harmony_score }}%</span>
                        </div>
                        <p class="text-[10px] text-text-muted italic bidi-plaintext leading-relaxed">
                            {{ harmony_score > 70 ? $t('Harmony_High_Note') : $t('Harmony_Low_Note') }}
                        </p>
                    </div>
                </div>

                <!-- 7. Goal of the Day (Small - Span 1x1) -->
                <div class="bento-card md:col-span-1 border-red-500/10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xl">🎯</span>
                        <button v-if="!isEditingGoal && goal" @click="isEditingGoal = true" class="text-[8px] font-black text-accent uppercase tracking-widest">{{ $t('Edit') }}</button>
                    </div>
                    <div v-if="goal && !isEditingGoal" class="text-center">
                        <p class="text-xs font-black text-text-main bidi-plaintext line-clamp-3">{{ goal.title }}</p>
                        <p class="text-[8px] text-red-500/60 mt-2 uppercase font-black tracking-widest">{{ $t('MISSION') }}</p>
                    </div>
                    <form v-else @submit.prevent="saveGoal" class="space-y-2">
                        <input v-model="goalForm.title" class="bento-input-sm w-full text-[10px]" :placeholder="$t('Goal...')" required />
                        <button type="submit" class="bento-btn-sm bg-red-400/20 text-red-400 border-red-400/30 w-full">{{ $t('Set') }}</button>
                    </form>
                </div>

                <!-- 8. Habit of the Day (Small - Span 1x1) -->
                <div class="bento-card md:col-span-1 border-green-500/10">
                    <div class="flex justify-between items-center mb-4">
                        <span class="text-xl">🔄</span>
                        <button v-if="!isEditingHabit && habit" @click="isEditingHabit = true" class="text-[8px] font-black text-accent uppercase tracking-widest">{{ $t('Edit') }}</button>
                    </div>
                    <div v-if="habit && !isEditingHabit" class="text-center">
                        <p class="text-xs font-black text-text-main bidi-plaintext line-clamp-3">{{ habit.name }}</p>
                        <p class="text-[8px] text-green-500/60 mt-2 uppercase font-black tracking-widest">{{ $t('HABIT') }}</p>
                    </div>
                    <form v-else @submit.prevent="saveHabit" class="space-y-2">
                        <input v-model="habitForm.name" class="bento-input-sm w-full text-[10px]" :placeholder="$t('Habit...')" required />
                        <button type="submit" class="bento-btn-sm bg-green-400/20 text-green-400 border-green-400/30 w-full">{{ $t('Start') }}</button>
                    </form>
                </div>

                <div class="bento-card md:col-span-2 border-blue-400/20 bg-gradient-to-br from-blue-500/5 to-transparent">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-sm font-black text-text-main flex items-center gap-2">
                            <span class="p-1.5 bg-blue-500/10 rounded-lg text-blue-400 text-xs">🛰️</span>
                            {{ $t('Neural Sync Hub') }}
                        </h3>
                        <span v-if="is_telegram_linked" class="text-[10px] font-black text-green-500 uppercase tracking-widest flex items-center gap-1">
                            <span class="w-1 h-1 bg-green-500 rounded-full animate-pulse"></span>
                            {{ $t('Linked') }}
                        </span>
                    </div>
                    <div v-if="!is_telegram_linked" class="flex flex-col gap-3">
                        <p class="text-[10px] text-text-muted leading-relaxed">
                            {{ $t('Connect your brain to Telegram. Send this code to our bot to start neural logging.') }}
                        </p>
                        <div class="flex flex-col gap-4 bg-white/5 p-4 rounded-2xl border border-white/5 backdrop-blur-md">
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-mono font-black text-accent tracking-widest">{{ sync_code }}</span>
                                <div class="flex flex-col text-right">
                                    <span class="text-[8px] uppercase font-black text-text-muted mb-1">{{ $t('Your Code') }}</span>
                                    <button @click="showBotHelp" class="text-[8px] text-accent underline flex items-center gap-1">
                                        {{ $t('Why is it not working?') }}
                                    </button>
                                </div>
                            </div>
                            <a 
                                :href="`https://t.me/PersonalMemory_Bot?start=${sync_code}`" 
                                target="_blank"
                                class="w-full bg-accent hover:bg-accent/80 text-white text-[11px] font-black py-2 rounded-xl text-center transition-all flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(6,155,255,0.3)]"
                            >
                                <span>✈️</span> {{ $t('Open Neural Bot') }}
                            </a>
                            <p class="text-[8px] text-text-muted text-center italic">{{ $t('Or send code manualy to @PersonalMemory_Bot') }}</p>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-4 py-2">
                        <div class="w-10 h-10 rounded-full bg-green-500/10 flex items-center justify-center text-xl">📲</div>
                        <div>
                            <p class="text-xs font-bold text-text-main">{{ $t('Active Sync') }}</p>
                            <p class="text-[10px] text-text-muted">{{ $t('Receiving data via Telegram') }}</p>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 10. The Visionary Library (New 3D Section) -->
            <div class="mt-16">
                <div class="flex items-end justify-between mb-8 px-4">
                    <div>
                        <h2 class="text-3xl font-black text-text-main tracking-tighter">{{ $t('The Visionary Library') }}</h2>
                        <p class="text-text-muted text-sm font-medium">{{ $t('Adopt high-performance neural routines from world-class leaders.') }}</p>
                    </div>
                    <div class="hidden md:block h-[1px] flex-1 bg-gradient-to-r from-transparent via-white/10 to-transparent mx-8 op-30"></div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="routine in routine_templates" :key="routine.id" 
                         class="routine-3d-wrapper"
                    >
                        <div class="routine-card" :style="`--card-accent: ${routine.color}`">
                            <div class="card-glow"></div>
                            <div class="relative z-10 h-full flex flex-col">
                                <div class="flex justify-between items-start mb-6">
                                    <span class="text-4xl filter drop-shadow-lg">{{ routine.icon }}</span>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-white/40">{{ $t('Neural_Template.v1') }}</span>
                                </div>
                                
                                <h3 class="text-xl font-black text-white mb-1">{{ $t(routine.title) }}</h3>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-accent mb-4">{{ $t(routine.author) }}</p>
                                
                                <p class="text-xs text-white/60 leading-relaxed mb-6 flex-1">
                                    {{ $t(routine.description) }}
                                </p>

                                <div class="space-y-2 mb-8">
                                    <div v-for="task in routine.tasks" :key="task" class="flex items-center gap-2 text-[10px] text-white/40">
                                        <span class="w-1 h-1 rounded-full bg-accent"></span>
                                        {{ $t(task) }}
                                    </div>
                                </div>

                                <button @click="viewRoutine(routine)" 
                                        class="adopt-btn group"
                                >
                                    <span class="flex items-center justify-center gap-2">
                                        {{ $t('View Full Daily Routine') }}
                                        <span class="group-hover:translate-x-1 transition-transform">→</span>
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Routine Detail Modal -->
            <div v-if="isRoutineModalOpen" class="fixed inset-0 z-[300] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/80 backdrop-blur-xl" @click="closeRoutineModal"></div>
                
                <div class="relative w-full max-w-2xl bg-surface border border-border-subtle rounded-[40px] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
                    <div class="h-32 bg-gradient-to-br opacity-20" :style="`background-image: ${selectedRoutine.color}`"></div>
                    
                    <div class="p-8 -mt-12 relative z-10">
                        <div class="flex justify-between items-start mb-8">
                            <div class="flex items-center gap-6">
                                <span class="text-5xl filter drop-shadow-xl">{{ selectedRoutine.icon }}</span>
                                <div>
                                    <h3 class="text-3xl font-black text-text-main tracking-tighter">{{ $t(selectedRoutine.title) }}</h3>
                                    <p class="text-accent font-black uppercase tracking-widest text-[10px] mt-1">{{ $t(selectedRoutine.author) }}</p>
                                </div>
                            </div>
                            <button @click="closeRoutineModal" class="p-2 hover:bg-surface2 rounded-full transition-all text-text-muted">✕</button>
                        </div>
                        
                        <div class="mb-10">
                            <h4 class="text-[10px] font-black text-text-muted uppercase tracking-[0.2em] mb-4 border-b border-border-subtle pb-2">{{ $t('Complete Daily Timeline') }}</h4>
                            <div class="space-y-4 max-h-[400px] overflow-y-auto custom-scroll pr-4">
                                <div v-for="(step, i) in selectedRoutine.full_routine" :key="i" class="flex items-start gap-4 group/step">
                                    <span class="text-[10px] font-bold text-accent font-mono w-20 flex-shrink-0 pt-1">{{ step.time }}</span>
                                    <div class="flex-1 p-4 bg-surface2 border border-border-subtle rounded-2xl group-hover/step:border-accent/30 transition-all">
                                        <p class="text-sm font-medium text-text-main bidi-plaintext">{{ $t(step.task) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4">
                            <button @click="closeRoutineModal" class="flex-1 py-4 text-xs font-black uppercase text-text-muted hover:text-text-main transition-all">{{ $t('Cancel') }}</button>
                            <button 
                                @click="adoptRoutine(selectedRoutine.id)" 
                                :disabled="isAdopting === selectedRoutine.id"
                                class="flex-[3] py-4 rounded-2xl bg-white text-black hover:bg-accent hover:text-white transition-all text-xs font-black uppercase tracking-widest shadow-xl flex items-center justify-center gap-2"
                            >
                                <span v-if="isAdopting !== selectedRoutine.id">{{ $t('Adopt This Identity') }}</span>
                                <span v-else class="animate-pulse">{{ $t('Synchronizing...') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bento-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 32px;
    padding: 1.5rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
    box-shadow: var(--c-shadow);
}

.bento-hero {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 40px;
    padding: 2.5rem;
    position: relative;
    box-shadow: var(--c-shadow);
}

.badge-premium {
    background: var(--c-accent-bg);
    color: var(--c-accent);
    padding: 0.3rem 1rem;
    border-radius: 100px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

.xp-bar-mini {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--c-surface2);
    padding: 0.2rem 0.8rem;
    border-radius: 100px;
    border: 1px solid var(--c-border-subtle);
}

.focus-pill {
    padding: 0.3rem 1rem;
    border-radius: 100px;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    background: var(--c-surface2);
    border: 1px solid var(--c-border-subtle);
    transition: all 0.3s;
}
.focus-pill.active {
    background: var(--c-accent-bg);
    border-color: var(--c-accent);
    color: var(--c-accent);
}

.neural-card-sub {
    background: var(--c-surface2);
    border: 1px solid var(--c-border-subtle);
    border-radius: 24px;
}

.ai-orb-btn {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.4s;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}
.ai-orb-btn:hover {
    border-color: var(--c-accent);
    transform: scale(1.05);
    box-shadow: 0 15px 40px var(--c-accent-bg);
}

.terminal-box {
    background: rgba(0,0,0,0.02);
    border: 1px solid var(--c-border-subtle);
    border-radius: 20px;
    padding: 1.5rem;
}

.bento-input {
    background: var(--c-surface2);
    border: 1px solid var(--c-border-subtle);
    border-radius: 16px;
    padding: 0.8rem 1.2rem;
    color: var(--c-text);
    transition: all 0.3s;
}
.bento-input:focus {
    outline: none;
    border-color: var(--c-accent);
    background: var(--c-surface);
}

.voice-btn {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    background: var(--c-surface2);
    transition: all 0.3s;
}
.voice-btn.recording { background: #ef4444; color: white; animation: pulse 1s infinite; }

.task-pill {
    background: var(--c-surface2);
    border: 1px solid var(--c-border-subtle);
    border-radius: 14px;
    padding: 0.7rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.8rem;
    cursor: pointer;
    transition: all 0.2s;
}
.task-pill:hover { border-color: var(--c-accent-bg); transform: translateX(4px); }
.task-pill.done { opacity: 0.5; }

.task-check {
    width: 16px;
    height: 16px;
    border: 1.5px solid var(--c-border);
    border-radius: 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}
.task-pill.done .task-check { background: var(--c-accent); border-color: var(--c-accent); color: white; }

.bento-btn-sm {
    padding: 0.5rem;
    border-radius: 12px;
    border: 1px solid transparent;
    font-size: 9px;
    font-weight: 900;
    text-transform: uppercase;
    transition: all 0.2s;
}

.expand-enter-active, .expand-leave-active { transition: all 0.5s ease; max-height: 500px; opacity: 1; }
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; transform: translateY(-10px); }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: var(--c-border); border-radius: 10px; }

/* 3D Routine Card Styles */
.routine-3d-wrapper {
    perspective: 1000px;
}

.routine-card {
    background: #111; /* Dark urban background */
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 40px;
    padding: 2.5rem;
    height: 100%;
    position: relative;
    transition: all 0.6s cubic-bezier(0.23, 1, 0.32, 1);
    transform-style: preserve-3d;
    cursor: default;
}

.routine-3d-wrapper:hover .routine-card {
    transform: rotateY(10deg) rotateX(5deg) translateY(-10px);
    border-color: var(--card-accent);
    box-shadow: 0 30px 60px rgba(0,0,0,0.5), 0 0 40px var(--card-accent);
}

.card-glow {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 0%, var(--card-accent), transparent 70%);
    opacity: 0.1;
    transition: opacity 0.6s;
}

.routine-3d-wrapper:hover .card-glow {
    opacity: 0.3;
}

.adopt-btn {
    width: 100%;
    padding: 1.2rem;
    border-radius: 20px;
    background: white;
    color: black;
    font-size: 11px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    transition: all 0.3s;
}

.adopt-btn:hover {
    background: var(--card-accent);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 10px 20px var(--card-accent);
}

/* Light Mode Overrides for Routines */
[data-theme='light'] .routine-card {
    background: #f8fafc;
    border-color: rgba(0,0,0,0.05);
}
[data-theme='light'] .routine-card h3 { color: #1e293b; }
[data-theme='light'] .routine-card p { color: #64748b; }
[data-theme='light'] .adopt-btn { background: #1e293b; color: white; }
[data-theme='light'] .adopt-btn:hover { background: var(--card-accent); }

</style>

