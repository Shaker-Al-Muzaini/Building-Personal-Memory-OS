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
const showRoutineModal = ref(false);
const activeBlueprint = ref(null);
const selectedRoutineTasks = ref([]);

const openRoutineModal = (tpl) => {
    activeBlueprint.value = tpl;
    // By default select the core 'tasks' summary
    selectedRoutineTasks.value = [...tpl.tasks];
    showRoutineModal.value = true;
};

const toggleRoutineTask = (task) => {
    const idx = selectedRoutineTasks.value.indexOf(task);
    if (idx > -1) selectedRoutineTasks.value.splice(idx, 1);
    else selectedRoutineTasks.value.push(task);
};

const submitRoutine = () => {
    router.post(route('dashboard.apply-routine'), {
        routine_id: activeBlueprint.value.id,
        selected_tasks: selectedRoutineTasks.value
    }, {
        onSuccess: () => {
            showRoutineModal.value = false;
        }
    });
};

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

const sortedTasks = computed(() => {
    return [...props.tasks].sort((a, b) => {
        if (a.status === 'pending' && b.status === 'completed') return -1;
        if (a.status === 'completed' && b.status === 'pending') return 1;
        return 0;
    });
});

</script>

<template>
    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-6">

            <!-- TOP BANNER: Welcome & Strategic Insight (Compact) -->
            <div class="ai-briefing-compact n-card">
                <div class="flex-shrink-0 flex items-center gap-3 border-e border-slate-200 dark:border-slate-700 pe-6">
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
                             class="module-item group cursor-pointer">
                            <div class="absolute top-0 left-0 w-full h-1" :style="{ background: getGradientForModule(mod.title) }"></div>
                            <div class="module-icon group-hover:scale-110 transition-transform">{{ mod.icon }}</div>
                            <h4 class="n-h3">{{ $t(mod.title) }}</h4>
                        </div>
                    </div>

                    <!-- NEURAL SUMMARIES (Organized Lists) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Ideas -->
                        <div class="n-card p-5 group hover:border-blue-500/30 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-widest flex items-center gap-2">
                                    <span class="text-lg">💡</span> {{ $t('ideas') }}
                                </h4>
                                <span class="text-[9px] font-black bg-blue-500/10 text-blue-500 px-2 py-0.5 rounded-full">{{ neural_nodes.ideas.length }}</span>
                            </div>
                            <div class="space-y-2 max-h-[120px] overflow-y-auto custom-scroll pr-1">
                                <div v-for="idea in neural_nodes.ideas.slice(0, 3)" :key="idea.id" 
                                    class="text-[11px] n-p line-clamp-1 border-s-2 border-blue-500/20 ps-2 py-0.5">
                                    {{ idea.content }}
                                </div>
                                <div v-if="neural_nodes.ideas.length === 0" class="text-[10px] opacity-30 italic py-4 text-center">{{ $t('No ideas yet.') }}</div>
                            </div>
                        </div>
                        <!-- Decisions -->
                        <div class="n-card p-5 group hover:border-purple-500/30 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-[10px] font-black text-purple-500 uppercase tracking-widest flex items-center gap-2">
                                    <span class="text-lg">⚖️</span> {{ $t('decisions') }}
                                </h4>
                                <span class="text-[9px] font-black bg-purple-500/10 text-purple-500 px-2 py-0.5 rounded-full">{{ neural_nodes.decisions.length }}</span>
                            </div>
                            <div class="space-y-2 max-h-[120px] overflow-y-auto custom-scroll pr-1">
                                <div v-for="dec in neural_nodes.decisions.slice(0, 3)" :key="dec.id" 
                                    class="text-[11px] n-p line-clamp-1 border-s-2 border-purple-500/20 ps-2 py-0.5">
                                    {{ dec.problem }}
                                </div>
                                <div v-if="neural_nodes.decisions.length === 0" class="text-[10px] opacity-30 italic py-4 text-center">{{ $t('No pending decisions.') }}</div>
                            </div>
                        </div>
                        <!-- People -->
                        <div class="n-card p-5 group hover:border-emerald-500/30 transition-all">
                            <div class="flex items-center justify-between mb-4">
                                <h4 class="text-[10px] font-black text-emerald-500 uppercase tracking-widest flex items-center gap-2">
                                    <span class="text-lg">👥</span> {{ $t('people') }}
                                </h4>
                                <span class="text-[9px] font-black bg-emerald-500/10 text-emerald-500 px-2 py-0.5 rounded-full">{{ neural_nodes.people.length }}</span>
                            </div>
                            <div class="space-y-2 max-h-[120px] overflow-y-auto custom-scroll pr-1">
                                <div v-for="person in neural_nodes.people.slice(0, 3)" :key="person.id" 
                                    class="text-[11px] n-p flex justify-between items-center border-s-2 border-emerald-500/20 ps-2 py-0.5">
                                    <span>{{ person.name }}</span>
                                    <span class="text-[7px] font-black opacity-40">{{ person.importance }}</span>
                                </div>
                                <div v-if="neural_nodes.people.length === 0" class="text-[10px] opacity-30 italic py-4 text-center">{{ $t('No people added yet.') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- DAILY MISSIONS (Professional List) -->
                    <div class="n-card overflow-hidden">
                        <div class="flex items-center justify-between mb-6 border-b border-slate-100 dark:border-slate-800/50 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-xl">📋</div>
                                <div>
                                    <h3 class="n-h3 leading-none">{{ $t('Daily Missions') }}</h3>
                                    <p class="text-[10px] text-slate-400 mt-1 uppercase tracking-wider">{{ $t('Operational') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full bg-indigo-500/10 text-indigo-500 text-[10px] font-black border border-indigo-500/20">
                                    {{ tasks.filter(t => t.status === 'pending').length }} {{ $t('PENDING') }}
                                </span>
                            </div>
                        </div>

                        <form @submit.prevent="addTask" class="flex gap-2 mb-6 group">
                            <div class="relative flex-1">
                                <input v-model="taskForm.title" type="text" :placeholder="$t('Inject new objective...')" 
                                    class="n-input pr-10 focus:ring-indigo-500/30 transition-all" />
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none opacity-40 group-focus-within:opacity-100">
                                    <span class="text-xs">⏎</span>
                                </div>
                            </div>
                            <button type="submit" class="n-btn n-btn-primary w-12 h-10 p-0 rounded-xl shadow-lg shadow-indigo-500/20">+</button>
                        </form>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-[400px] overflow-y-auto pe-2 custom-scroll">
                            <template v-if="sortedTasks.length > 0">
                                <div v-for="task in sortedTasks" :key="task.id" @click="toggleTask(task.id)"
                                     class="p-4 rounded-2xl border border-slate-100 dark:border-slate-800/50 hover:border-indigo-500/40 flex items-center gap-4 cursor-pointer transition-all bg-slate-50/30 dark:bg-slate-900/30 hover:bg-white dark:hover:bg-slate-800/80 group">
                                    
                                    <!-- Checkbox at the START (RTL Friendly) -->
                                    <div :class="['w-6 h-6 rounded-lg border-2 transition-all flex items-center justify-center flex-shrink-0', 
                                        task.status === 'completed' ? 'bg-indigo-500 border-indigo-500 shadow-lg shadow-indigo-500/30' : 'border-slate-200 dark:border-slate-700 group-hover:border-indigo-500/50']">
                                        <svg v-if="task.status === 'completed'" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>

                                    <span :class="['text-sm font-semibold transition-all flex-1 truncate', 
                                        task.status === 'completed' ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-200']">
                                        {{ task.title }}
                                    </span>
                                </div>
                            </template>
                            <div v-else class="col-span-full py-12 text-center border-2 border-dashed border-slate-100 dark:border-slate-800 rounded-2xl">
                                <div class="text-3xl mb-2 opacity-20">🧊</div>
                                <p class="text-xs text-slate-400 font-medium">{{ $t('No tasks yet') }}</p>
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
                    <div class="n-card overflow-hidden group">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <span class="animate-pulse">⚡</span>
                                <h3 class="n-h3 leading-none">{{ $t('AI Insight') }}</h3>
                            </div>
                            <button @click="generatePlan" :disabled="isGeneratingPlan" 
                                class="text-[10px] font-black text-indigo-500 hover:text-indigo-600 transition-colors uppercase tracking-tighter">
                                {{ isGeneratingPlan ? $t('Thinking...') : '◈ ' + $t('Refresh') }}
                            </button>
                        </div>
                        
                        <div class="relative p-5 rounded-2xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-900 dark:to-slate-900/50 border border-slate-100 dark:border-slate-800/50 overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-20 h-20 bg-indigo-500/5 rounded-full blur-2xl"></div>
                            <div class="absolute -left-4 -bottom-4 w-20 h-20 bg-blue-500/5 rounded-full blur-2xl"></div>
                            
                            <p v-if="!displayedAiText" class="text-[11px] n-p italic text-center py-6">
                                {{ $t('Searching the void of possibilities...') }}
                            </p>
                            <div v-else class="space-y-4 relative z-10">
                                <p class="text-xs font-bold leading-relaxed bidi-plaintext text-slate-700 dark:text-slate-300">
                                    {{ displayedAiText.length > 220 ? displayedAiText.substring(0, 220) + '...' : displayedAiText }}
                                </p>
                                <div class="flex items-center justify-between pt-2">
                                    <button v-if="displayedAiText.length > 220" @click="speakDisplayedText" 
                                        class="n-btn n-btn-secondary px-3 py-1.5 text-[9px] gap-2 border-indigo-500/10 hover:border-indigo-500/30">
                                        <span>🔊</span> {{ $t('Listen Full') }}
                                    </button>
                                    <div class="flex gap-1">
                                        <span class="w-1 h-1 rounded-full bg-indigo-500/20"></span>
                                        <span class="w-1 h-1 rounded-full bg-indigo-500/40"></span>
                                        <span class="w-1 h-1 rounded-full bg-indigo-500/60"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BLUEPRINTS (Professional Sidebar) -->
                    <div class="n-card">
                        <h3 class="n-h3 mb-4">🚀 {{ $t('Blueprints') }}</h3>
                        <div class="space-y-2 max-h-[200px] overflow-y-auto pe-2 custom-scroll">
                            <div v-for="tpl in routine_templates" :key="tpl.id"
                                 class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 flex items-center gap-3 hover:bg-slate-50 dark:hover:bg-slate-900 transition-all">
                                <div class="w-8 h-8 rounded bg-blue-500/10 flex items-center justify-center text-sm flex-shrink-0">{{ tpl.icon }}</div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-[11px] font-black truncate">{{ $t(tpl.title) }}</h4>
                                    <p class="text-[8px] text-slate-400 font-bold uppercase tracking-tighter">{{ $t(tpl.author) }}</p>
                                </div>
                                <button @click="openRoutineModal(tpl)"
                                        class="n-btn n-btn-primary px-2 py-1 text-[9px]">{{ $t('Apply') }}</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- ROUTINE SELECTION MODAL -->
        <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 scale-100" leave-to-class="opacity-0 scale-95">
            <div v-if="showRoutineModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="n-card w-full max-w-lg p-0 overflow-hidden shadow-2xl">
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ activeBlueprint.icon }}</span>
                            <div>
                                <h3 class="n-h2 text-lg">{{ $t(activeBlueprint.title) }}</h3>
                                <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest">{{ $t('Blueprint Customization') }}</p>
                            </div>
                        </div>
                        <button @click="showRoutineModal = false" class="text-slate-400 hover:text-rose-500">✕</button>
                    </div>
                    
                    <div class="p-6 space-y-4 max-h-[60vh] overflow-y-auto custom-scroll">
                        <p class="n-p text-xs italic opacity-70">{{ $t(activeBlueprint.description) }}</p>
                        
                        <div class="space-y-2">
                            <label class="text-[9px] font-black text-indigo-500 uppercase tracking-widest">{{ $t('Select Protocols to Inject') }}</label>
                            <div class="grid grid-cols-1 gap-2">
                                <div v-for="item in activeBlueprint.full_routine" :key="item.task"
                                     @click="toggleRoutineTask(item.task)"
                                     class="p-3 rounded-xl border border-slate-100 dark:border-slate-800 flex items-center gap-3 cursor-pointer transition-all hover:bg-slate-50 dark:hover:bg-slate-900/50"
                                     :class="selectedRoutineTasks.includes(item.task) ? 'border-indigo-500/50 bg-indigo-500/5' : ''">
                                    
                                    <div class="w-5 h-5 rounded border-2 flex items-center justify-center transition-all shrink-0"
                                         :class="selectedRoutineTasks.includes(item.task) ? 'bg-indigo-500 border-indigo-500 text-white' : 'border-slate-200 dark:border-slate-700'">
                                        <span v-if="selectedRoutineTasks.includes(item.task)" class="text-[10px]">✓</span>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center">
                                            <span class="text-[11px] font-bold" :class="selectedRoutineTasks.includes(item.task) ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-700 dark:text-slate-200'">{{ $t(item.task) }}</span>
                                            <span class="text-[9px] font-mono text-slate-400">{{ item.time }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 bg-slate-50 dark:bg-slate-950/30 border-t border-slate-100 dark:border-slate-800 flex gap-3">
                        <button @click="showRoutineModal = false" class="flex-1 n-btn n-btn-secondary">{{ $t('Cancel') }}</button>
                        <button @click="submitRoutine" class="flex-1 n-btn n-btn-primary">
                            {{ $t('Adopt Selected') }} ({{ selectedRoutineTasks.length }})
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles moved to dashboard.css for better theme support */
</style>
