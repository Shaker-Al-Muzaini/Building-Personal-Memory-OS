<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { getActiveLanguage, trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';

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
});

const startTaskVoice = () => {
    if (!taskRecognition) {
        Swal.fire({
            title: trans('Not Supported'),
            text: trans('Your browser does not support voice recognition.'),
            icon: 'error',
            background: '#0d1304',
            color: '#fff'
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
    tasks: Array,
    habit: Object,
    goal: Object,
    overview: Object,
    gamification: Object,
    shadow_prediction: String, // التنبؤ المستقبلي من الـ AI
    daily_briefing: String,    // الإيجاز الصباحي المكتوب
    harmony_score: Number      // مؤشر تناغم الحياة
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const response = await axios.post(route('dashboard.generate-plan'), {
            locale: getActiveLanguage()
        });
        aiPlanText.value = response.data.plan;
    } catch (e) {
        aiPlanText.value = "Error connecting to AI advisor. Please try again later.";
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

const speakBriefing = () => {
    // إيقاف أي صوت شغال حالياً فوراً لمنع التكرار
    window.speechSynthesis.cancel();
    
    const msg = new SpeechSynthesisUtterance(props.daily_briefing);
    msg.lang = 'ar-SA';
    msg.rate = 0.9;
    msg.pitch = 1.0;
    
    // تأمين التشغيل مرة واحدة فقط
    window.speechSynthesis.speak(msg);
};
</script>

<template>
    <Head :title="`${$t('Dashboard')} — Memory OS`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-3xl text-text-main tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-accent/20 flex items-center justify-center text-2xl">🧠</span>
                {{ $t('Your Personal  Brain') }}
            </h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-10">
            
            <!-- Hero AI Section -->
            <div class="relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-accent/20 to-purple-500/20 blur-[100px] -z-10 opacity-50 group-hover:opacity-100 transition-opacity duration-1000"></div>
                
                <div class="bg-glass-bg backdrop-blur-2xl border border-glass-border p-10 rounded-[40px] shadow-2xl relative overflow-hidden">
                    <!-- Neural Pulse Decoration -->
                    <div class="absolute -top-20 -left-20 w-64 h-64 bg-accent/10 rounded-full blur-[80px] animate-pulse"></div>
                    
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-10 relative z-10">
                        <div class="text-center lg:text-start flex-1">
                            <div class="flex items-center gap-4 mb-4 justify-center lg:justify-start">
                                <span class="bg-accent/20 text-accent px-4 py-1 rounded-full text-xs font-black uppercase tracking-widest animate-bounce">{{ $t('Live Neural Feed') }}</span>
                                <div class="flex items-center gap-2 px-3 py-1 bg-white/5 rounded-full border border-white/10 group cursor-pointer" :title="`XP: ${gamification.xp}/${gamification.next_xp}`">
                                    <span class="text-xs">🏆</span>
                                    <span class="text-[10px] text-yellow-500 font-black uppercase tracking-widest">
                                        {{ $t('Level') }} {{ gamification.level }}
                                    </span>
                                    <div class="h-1.5 w-12 bg-white/10 rounded-full overflow-hidden ml-1">
                                        <div class="h-full bg-yellow-500 rounded-full" :style="`width: ${gamification.progress}%`"></div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 px-3 py-1 bg-white/5 rounded-full border border-white/10">
                                    <span :class="['w-2 h-2 rounded-full', overview.stability_index > 70 ? 'bg-green-500 shadow-[0_0_10px_#22c55e]' : 'bg-red-500 shadow-[0_0_10px_#ef4444]']"></span>
                                    <span class="text-[10px] text-gray-400 font-black uppercase tracking-widest">
                                        {{ overview.stability_index > 70 ? $t('Peak Performance') : $t('System Conflict') }}
                                    </span>
                                </div>
                                <button 
                                    @click="isFocusMode = !isFocusMode" 
                                    :class="['flex items-center gap-2 px-3 py-1 rounded-full border transition-all', isFocusMode ? 'bg-accent/40 border-accent/60' : 'bg-input-bg border-border-subtle hover:bg-card-hover']"
                                >
                                    <span class="text-[12px]">{{ isFocusMode ? '👁️' : '🕶️' }}</span>
                                    <span class="text-[10px] text-text-main font-black uppercase tracking-widest">{{ $t('Focus') }}</span>
                                </button>
                            </div>
                            <h3 class="text-4xl font-black text-text-main mb-4 leading-tight">
                                {{ $                             <div class="mt-8 relative group/prophet">
                                <div class="absolute -inset-1 bg-gradient-to-r from-accent to-purple-500 rounded-3xl blur opacity-20 group-hover/prophet:opacity-40 transition-opacity"></div>
                                <div class="relative p-6 bg-glass-bg rounded-3xl border border-glass-border backdrop-blur-xl flex items-start gap-4">
                                     <div class="w-12 h-12 rounded-full bg-accent/20 flex items-center justify-center text-2xl animate-pulse">🔮</div>
                                     <div class="flex-1">
                                        <h5 class="text-[10px] text-accent font-black uppercase tracking-[0.4em] mb-2 opacity-70">{{ $t('Neural_Prophecy.v1') }}</h5>
                                        <p class="text-text-main text-lg font-light leading-relaxed bidi-plaintext italic">
                                            "{{ shadow_prediction }}"
                                        </p>
                                     </div>
                                </div>
                            </div>
             </p>
                                     </div>
                                </div>
                            </div>

                            <!-- Daily Briefing Section (Brain Briefing) -->
                            <div class="mt-8 p-6 bg-accent/5 border border-accent/10 rounded-[35px] relative overflow-hidden group/brief">
                                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover/brief:opacity-30 transition-opacity">📻</div>
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-xs font-black text-accent uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-1.5 h-1.5 bg-accent rounded-full animate-pulse"></span>
                                        {{ $t('Morning Neural Briefing') }}
                                    </h4>
                                    <button @click="speakBriefing" class="p-2 bg-accent/20 rounded-full hover:bg-accent/40 transition-all text-xs">🔊</button>
                                </div>
                                <p class="text-text-main text-sm leading-relaxed bidi-plaintext font-medium">
                                    {{ daily_briefing }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <button 
                                @click="generatePlan" 
                                :disabled="isGeneratingPlan"
                                class="dashboard-ai-btn group/btn"
                            >
                                <span v-if="isGeneratingPlan" class="animate-spin w-8 h-8 border-4 border-white border-t-transparent rounded-full block"></span>
                                <span v-if="!isGeneratingPlan" class="flex flex-col items-center">
                                    <span class="text-3xl mb-1 group-hover/btn:scale-125 transition-transform duration-500">✨</span>
                                    <span class="text-sm font-black uppercase tracking-widest">{{ $t('Analyze Day') }}</span>
                                </span>
                                <span v-else class="text-sm font-black uppercase tracking-widest">{{ $t('Thinking...') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- AI Result Box -->
                    <transition name="fade">
                        <div v-if="aiPlanText" class="mt-12 p-8 bg-glass-bg rounded-3xl border border-accent/20 relative shadow-inner">
                            <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl">🤖</div>
                            <h4 class="text-accent font-black text-xl mb-6 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                AI Advisor:
                            </h4>
                            <div class="text-text-main leading-[1.8] text-lg whitespace-pre-wrap font-light">
                                {{ aiPlanText }}
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Overview Section (Quick Access) -->
            <transition name="fade">
                <div v-if="!isFocusMode" class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Money Glance -->
                    <div @click="router.visit(route('money.index'))" class="bg-gradient-to-br from-green-500/10 to-transparent border border-green-500/10 p-6 rounded-3xl cursor-pointer hover:border-green-500/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-3xl">💰</span>
                            <span class="text-green-500 font-bold group-hover:scale-110 transition-transform">→</span>
                        </div>
                        <h4 class="text-text-muted text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Wallet Balance') }}</h4>
                        <p class="text-2xl font-black text-text-main bidi-plaintext">{{ overview.balance }}</p>
                    </div>

                    <!-- Stability Index Gauge -->
                    <div class="bg-gradient-to-br from-accent/10 to-transparent border border-accent/10 p-6 rounded-3xl relative overflow-hidden group">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-3xl">🧬</span>
                            <span class="text-accent font-bold group-hover:rotate-45 transition-transform">⚙️</span>
                        </div>
                        <h4 class="text-gray-400 text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Stability Index') }}</h4>
                        <div class="flex flex-col items-center">
                            <VueApexCharts type="radialBar" height="140" :options="stabilityChartOptions" :series="stabilitySeries" />
                        </div>
                        <p class="text-[10px] text-center text-gray-500 mt-[-20px] font-mono">{{ $t('Neural Balance') }}</p>
                    </div>

                    <!-- Neural Logic Score -->
                    <div @click="router.visit(route('decisions.index'))" class="bg-gradient-to-br from-blue-500/10 to-transparent border border-blue-500/10 p-6 rounded-3xl cursor-pointer hover:border-blue-500/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-3xl">⚖️</span>
                            <span class="text-blue-500 font-bold group-hover:scale-110 transition-transform">→</span>
                        </div>
                        <h4 class="text-text-muted text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Logic Avg') }}</h4>
                        <div class="flex items-baseline gap-2">
                            <p class="text-2xl font-black text-text-main">{{ overview.decision_logic_avg }}%</p>
                            <span class="text-[10px] text-text-muted font-mono">({{ overview.sealed_decisions_count }} {{ $t('Sealed') }})</span>
                        </div>
                    </div>

                    <!-- Idea Reminder -->
                    <div @click="router.visit(route('ideas.index'))" class="bg-gradient-to-br from-purple-500/10 to-transparent border border-purple-500/10 p-6 rounded-3xl cursor-pointer hover:border-purple-500/30 transition-all group">
                        <div class="flex justify-between items-start mb-4">
                            <span class="text-3xl">💡</span>
                            <span class="text-purple-500 font-bold group-hover:scale-110 transition-transform">→</span>
                        </div>
                        <h4 class="text-text-muted text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Last Idea') }}</h4>
                        <p class="text-text-main font-bold truncate bidi-plaintext">{{ overview.last_idea || $t('No ideas yet') }}</p>
                    </div>
                </div>
            </transition>

            <div v-if="!isFocusMode" class="h-12"></div>

            <!-- Dashboard Grid -->
            <div :class="['grid grid-cols-1 gap-6 transition-all duration-700', isFocusMode ? 'lg:grid-cols-2 max-w-4xl mx-auto' : 'md:grid-cols-2 lg:grid-cols-4']">
                
                <!-- Tasks List -->
                <div class="dashboard-card group">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-black text-text-main flex items-center gap-2">
                            <span class="p-1.5 bg-blue-500/10 rounded-lg text-blue-400 text-sm">📋</span>
                            {{ $t('Tasks of the Day') }}
                        </h3>
                    </div>

                    <form @submit.prevent="addTask" class="mb-6 relative flex items-center group/task">
                        <input 
                            v-model="taskForm.title" 
                            type="text"
                            :placeholder="$t('Add task')"
                            class="dashboard-input w-full bidi-plaintext ltr:pl-10 rtl:pr-10 ltr:pr-20 rtl:pl-20 py-3 text-sm transition-all"
                            required
                            dir="auto"
                        />
                        <div class="absolute ltr:right-2 rtl:left-2 flex items-center gap-1">
                             <button 
                                type="button"
                                @click="startTaskVoice"
                                :class="['w-8 h-8 rounded-lg flex items-center justify-center transition-all text-xs', isRecordingTask ? 'bg-red-500 animate-pulse text-white' : 'bg-white/5 text-gray-500 hover:text-accent']"
                            >
                                🎤
                            </button>
                            <button type="submit" class="px-3 py-1.5 bg-accent text-white rounded-lg font-black hover:bg-accent/80 transition-all text-sm">
                                +
                            </button>
                        </div>
                    </form>

                    <div class="space-y-2 max-h-[250px] overflow-y-auto ltr:pr-1 rtl:pl-1 custom-scroll">
                        <div v-for="task in tasks" :key="task.id" 
                            @click="toggleTask(task.id)"
                            :class="['flex items-center gap-3 p-3 bg-white/5 border border-white/5 rounded-xl cursor-pointer hover:border-accent/40 transition-all', task.status === 'completed' ? 'opacity-50' : '']"
                        >
                            <div :class="['w-4 h-4 rounded-full border flex items-center justify-center text-[8px]', task.status === 'completed' ? 'bg-accent border-accent text-white' : 'border-border-subtle']">
                                <span v-if="task.status === 'completed'">✓</span>
                            </div>
                            <span :class="['flex-1 truncate text-xs bidi-plaintext', task.status === 'completed' ? 'line-through text-text-muted' : 'text-text-main']">
                                {{ task.title }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Harmony Gauge (Unique to this Brain) -->
                <transition name="fade">
                    <div v-if="!isFocusMode" class="dashboard-card group relative overflow-hidden">
                        <div class="absolute -top-10 -right-10 w-40 h-40 bg-purple-500/10 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-black text-white flex items-center gap-2">
                                <span class="p-1.5 bg-purple-500/10 rounded-lg text-purple-400 text-sm">⚖️</span>
                                {{ $t('Life Harmony') }}
                            </h3>
                        </div>
                        
                        <div class="flex flex-col items-center">
                            <VueApexCharts 
                                type="radialBar" 
                                height="240"
                                :options="{
                                    chart: { type: 'radialBar', sparkline: { enabled: true } },
                                    plotOptions: {
                                        radialBar: {
                                            startAngle: -90, endAngle: 90,
                                            track: { background: 'var(--c-surface2)', strokeWidth: '97%' },
                                            dataLabels: {
                                                name: { show: false },
                                                value: { offsetY: -2, fontSize: '20px', fontWeight: '900', color: 'var(--c-text)' }
                                            }
                                        }
                                    },
                                    fill: { gradient: { enabled: true, shade: 'dark', type: 'horizontal', gradientToColors: ['#8B5CF6'], stops: [0, 100] } },
                                    stroke: { lineCap: 'round' },
                                    labels: [trans('Harmony')],
                                }" 
                                :series="[harmony_score || 0]" 
                            />
                            <p class="text-[8px] text-gray-500 uppercase tracking-[0.3em] font-mono -mt-6">{{ $t('Neural_Balance.v2') }}</p>
                            <p class="mt-4 text-[10px] text-purple-400 text-center font-bold px-2 leading-relaxed bidi-plaintext italic">
                                {{ harmony_score > 70 ? $t('عقلك في حالة تناغم مذهلة!') : $t('هناك اختلال بسيط في التوازن، الـ AI يراقب.') }}
                            </p>
                        </div>
                    </div>
                </transition>

                <!-- Daily Goal -->
                <div class="dashboard-card group">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-xl font-black text-white flex items-center gap-2">
                            <span class="p-1.5 bg-red-500/10 rounded-lg text-red-400 text-sm">🎯</span>
                            {{ $t('Goal of the Day') }}
                        </h3>
                        <button v-if="!isEditingGoal && goal" @click="isEditingGoal = true" class="text-[10px] font-bold text-accent hover:underline uppercase tracking-widest">
                            {{ $t('Edit') }}
                        </button>
                    </div>

                    <div v-if="goal && !isEditingGoal" class="h-[200px] flex flex-col items-center justify-center bg-glass-bg rounded-[30px] border border-glass-border relative overflow-hidden group">
                        <div class="absolute inset-0 bg-red-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <span class="text-4xl mb-4 drop-shadow-2xl group-hover:scale-110 transition-transform duration-500">🚀</span>
                        <h4 class="text-lg font-black text-text-main text-center px-4 bidi-plaintext">{{ goal.title }}</h4>
                        <p class="text-[8px] text-text-muted mt-2 uppercase tracking-[0.3em] font-mono">{{ $t('Mission critical') }}</p>
                    </div>

                    <div v-else class="h-[200px] flex flex-col items-center justify-center">
                         <form @submit.prevent="saveGoal" class="w-full space-y-3">
                            <input 
                                v-model="goalForm.title" 
                                type="text"
                                :placeholder="$t('Set your main mission...')"
                                class="dashboard-input w-full text-center py-3 text-sm border-red-500/20 focus:border-red-500"
                                required
                            />
                            <button type="submit" class="w-full bg-red-500/20 text-red-400 border border-red-500/30 py-3 rounded-2xl text-xs font-black shadow-lg hover:bg-red-500/30 active:scale-95 transition-all">
                                {{ goal ? $t('Update Mission') : $t('Launch Mission') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Daily Habit -->
                <transition name="fade">
                    <div v-if="!isFocusMode" class="dashboard-card group">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-black text-white flex items-center gap-2">
                                <span class="p-1.5 bg-green-500/10 rounded-lg text-green-400 text-sm">🔄</span>
                                {{ $t('Habit of the Day') }}
                            </h3>
                            <button v-if="!isEditingHabit && habit" @click="isEditingHabit = true" class="text-[10px] font-bold text-accent hover:underline uppercase tracking-widest">
                                {{ $t('Edit') }}
                            </button>
                        </div>

                        <div v-if="habit && !isEditingHabit" class="h-[200px] flex flex-col items-center justify-center bg-glass-bg rounded-[30px] border border-glass-border relative overflow-hidden group">
                            <div class="absolute inset-0 bg-green-500/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center text-2xl font-black text-green-400 mb-4 border border-green-500/30 group-hover:scale-110 transition-transform">
                                {{ habit.name.substring(0, 1) }}
                            </div>
                            <h4 class="text-lg font-black text-text-main text-center px-4 bidi-plaintext">{{ habit.name }}</h4>
                            <p class="text-[8px] text-text-muted mt-2 uppercase tracking-[0.2em]">{{ $t('Habit Subtitle') }}</p>
                        </div>

                        <div v-else class="h-[200px] flex flex-col items-center justify-center">
                             <form @submit.prevent="saveHabit" class="w-full space-y-3">
                                <input 
                                    v-model="habitForm.name" 
                                    type="text"
                                    :placeholder="$t('One habit...')"
                                    class="dashboard-input w-full text-center py-3 text-sm border-green-500/20 focus:border-green-500"
                                    required
                                />
                                <button type="submit" class="w-full bg-green-500/20 text-green-400 border border-green-500/30 py-3 rounded-2xl text-xs font-black shadow-lg hover:bg-green-500/30 active:scale-95 transition-all">
                                    {{ habit ? $t('Update Habit') : $t('Start Habit') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </transition>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-card {
    background: var(--c-card-bg);
    backdrop-filter: blur(40px);
    border: 1px solid var(--c-border);
    border-radius: 40px;
    padding: 1.5rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.dashboard-card:hover {
    background: var(--c-card-hover);
    border-color: var(--c-accent);
    transform: translateY(-8px);
}

.dashboard-ai-btn {
    background: var(--c-card-bg);
    color: var(--c-text);
    padding: 1.2rem 3rem;
    border-radius: 100px;
    font-weight: 700;
    font-size: 1.2rem;
    border: 1px solid var(--c-border);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dashboard-ai-btn:hover {
    background: var(--c-card-hover);
    border-color: var(--c-accent);
    box-shadow: 0 0 30px rgba(6, 155, 255, 0.15);
}

.dashboard-input {
    background: var(--c-input-bg);
    border: 1px solid var(--c-border-subtle);
    border-radius: 20px;
    padding: 1rem 1.5rem;
    color: var(--c-text);
    font-size: 1.1rem;
    transition: all 0.3s;
}

.dashboard-input:focus {
    outline: none;
    border-color: var(--c-accent);
    background: var(--c-surface);
    box-shadow: 0 0 20px rgba(6, 155, 255, 0.2);
}

.task-pill {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 1.2rem 1.5rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.03);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.task-pill:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(6, 155, 255, 0.4);
}

.custom-scroll::-webkit-scrollbar { width: 3px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(6, 155, 255, 0.3); border-radius: 10px; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s, transform 0.5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(20px); }
</style>
