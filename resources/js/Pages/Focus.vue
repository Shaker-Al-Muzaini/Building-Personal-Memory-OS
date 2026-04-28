<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    tasks: Array
});

const isFocusing = ref(false);
const workDurationMinutes = ref(25);
const breakDurationMinutes = ref(5);
const timerSeconds = ref(25 * 60);

const totalFocusTime = ref(0);
const isBreak = ref(false);
let interval = null;

const aiPlan = ref(null);
const isLoadingPlan = ref(false);

const pendingTasks = computed(() => {
    return props.tasks.filter(t => t.status === 'pending');
});

const formatTime = (totalSeconds) => {
    const m = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const s = (totalSeconds % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
};

const getAIPlan = async () => {
    isLoadingPlan.value = true;
    try {
        const response = await axios.post(route('focus.plan'));
        aiPlan.value = response.data.plan;
    } catch(e) {
        aiPlan.value = trans('Neural system offline.');
    } finally {
        isLoadingPlan.value = false;
    }
};

const adjustTime = (amount) => {
    if (isFocusing.value) return;
    if (isBreak.value) {
        let newT = breakDurationMinutes.value + amount;
        if(newT < 1) newT = 1;
        breakDurationMinutes.value = newT;
        timerSeconds.value = newT * 60;
    } else {
        let newT = workDurationMinutes.value + amount;
        if(newT < 1) newT = 1;
        workDurationMinutes.value = newT;
        timerSeconds.value = newT * 60;
    }
};

const toggleTimer = () => {
    if (isFocusing.value) {
        clearInterval(interval);
        isFocusing.value = false;
    } else {
        isFocusing.value = true;
        interval = setInterval(() => {
            if (timerSeconds.value > 0) {
                timerSeconds.value--;
                if (!isBreak.value) totalFocusTime.value++;
            } else {
                clearInterval(interval);
                isFocusing.value = false;
                isBreak.value = !isBreak.value;
                timerSeconds.value = isBreak.value ? breakDurationMinutes.value * 60 : workDurationMinutes.value * 60;
                new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg').play().catch(()=>{});
            }
        }, 1000);
    }
};

const resetTimer = () => {
    clearInterval(interval);
    isFocusing.value = false;
    timerSeconds.value = isBreak.value ? breakDurationMinutes.value * 60 : workDurationMinutes.value * 60;
};

const finishTask = (id) => {
    router.patch(route('tasks.toggle', id), {}, { 
        preserveScroll: true, 
        preserveState: true 
    });
};

onUnmounted(() => {
    if (interval) clearInterval(interval);
});
</script>

<template>
    <Head :title="$t('Deep Focus')" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-8">
            
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- Timer Core (Cinema Display) -->
                <div class="flex-1">
                    <div class="n-card p-12 lg:p-24 flex flex-col items-center justify-center min-h-[600px] relative overflow-hidden group transition-all duration-1000"
                         :class="isFocusing ? (isBreak ? 'border-emerald-500/20 shadow-lg shadow-emerald-500/5' : 'border-rose-500/20 shadow-lg shadow-rose-500/5') : ''">
                        
                        <div class="absolute inset-0 bg-gradient-to-b from-slate-500/5 to-transparent -z-10"></div>

                        <div class="flex items-center gap-3 mb-8">
                            <span class="w-2 h-2 rounded-full animate-pulse" :class="isFocusing ? (isBreak ? 'bg-emerald-500' : 'bg-rose-500') : 'bg-blue-500'"></span>
                            <h3 class="text-slate-400 tracking-[0.6em] uppercase text-[10px] font-black">
                                {{ isBreak ? $t('Restoration Phase') : $t('Cognitive Block') }}
                            </h3>
                        </div>
                        
                        <!-- Duration Adjuster (Compact) -->
                        <div class="flex items-center gap-8 mb-4 transition-all duration-500" :class="isFocusing ? 'opacity-0 scale-95 pointer-events-none' : 'opacity-100'">
                            <button @click="adjustTime(-5)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-500 flex items-center justify-center font-bold text-lg shadow-inner">
                                -
                            </button>
                            <div class="text-center">
                                <p class="text-[8px] text-slate-400 font-black uppercase tracking-widest mb-1">{{ isBreak ? $t('Rest Sequence') : $t('Work Duration') }}</p>
                                <p class="n-h3 text-xl">{{ isBreak ? breakDurationMinutes : workDurationMinutes }} <span class="text-[10px] opacity-40">{{ $t('min') }}</span></p>
                            </div>
                            <button @click="adjustTime(5)" class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-blue-500 flex items-center justify-center font-bold text-lg shadow-inner">
                                +
                            </button>
                        </div>

                        <!-- The Timer (Big & Bold) -->
                        <div class="text-[8rem] md:text-[12rem] font-mono leading-none tracking-tighter font-black transition-all duration-1000 select-none drop-shadow-2xl"
                             :class="isFocusing ? (isBreak ? 'text-emerald-500' : 'text-rose-500') : 'text-slate-800 dark:text-white'">
                            {{ formatTime(timerSeconds) }}
                        </div>

                        <!-- Controls (Sleek) -->
                        <div class="flex gap-8 mt-16">
                            <button @click="toggleTimer" class="w-24 h-24 rounded-[30px] flex items-center justify-center text-4xl transition-all duration-500 shadow-xl"
                                    :class="isFocusing ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700' : 'bg-blue-600 text-white shadow-blue-500/20'">
                                <span>{{ isFocusing ? '⏸' : '▶' }}</span>
                            </button>
                            <button @click="resetTimer" class="w-24 h-24 rounded-[30px] bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 flex items-center justify-center text-2xl text-slate-400 hover:text-slate-800 dark:hover:text-white transition-all shadow-inner">
                                ⟲
                            </button>
                        </div>

                        <!-- Total focus time -->
                        <div class="mt-12 px-6 py-2 rounded-full bg-slate-100 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 flex items-center gap-3">
                            <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Total Shielding') }}:</span>
                            <span class="text-blue-500 font-mono font-black text-lg">{{ formatTime(totalFocusTime) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Side Controls (Tasks & AI) -->
                <div class="w-full lg:w-[400px] shrink-0 space-y-8">
                    
                    <!-- Kill List (Tasks) -->
                    <div class="n-card p-8">
                        <div class="flex justify-between items-center mb-8">
                            <div class="flex items-center gap-3">
                                <div class="w-2 h-2 rounded-full bg-rose-500"></div>
                                <h2 class="n-h2 text-lg">{{ $t('Kill List') }}</h2>
                            </div>
                            <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[9px] font-black text-slate-400">{{ pendingTasks.length }} {{ $t('Active Targets') }}</span>
                        </div>

                        <div class="space-y-3 max-h-[300px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="t in pendingTasks" :key="t.id" 
                                class="p-4 rounded-2xl bg-slate-50/50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-800 flex justify-between items-center group">
                                <span class="text-sm n-p line-clamp-1 bidi-plaintext">{{ t.title }}</span>
                                <button @click="finishTask(t.id)" class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 flex items-center justify-center text-xs text-slate-300 hover:bg-emerald-500 hover:text-white hover:border-emerald-500 transition-all">
                                    ✓
                                </button>
                            </div>
                            <div v-if="pendingTasks.length === 0" class="py-12 text-center n-p opacity-40 italic">
                                {{ $t('All threats eliminated.') }}
                            </div>
                        </div>
                    </div>

                    <!-- AI Focus Protocol -->
                    <div class="n-card p-8 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-indigo-500/5 to-transparent -z-10"></div>
                        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/5 rounded-full blur-3xl"></div>
                        
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-xl shadow-inner">
                                    🧠
                                </div>
                                <div>
                                    <h2 class="n-h2 text-lg leading-none">{{ $t('AI Focus Advisor') }}</h2>
                                    <p class="text-[9px] text-blue-500 font-black uppercase tracking-widest mt-1">{{ $t('Focus Protocol') }}</p>
                                </div>
                            </div>
                        </div>

                        <p class="text-[11px] n-p italic opacity-60 mb-5 bidi-plaintext">{{ $t('Let AI analyze your tasks and create an optimized focus plan.') }}</p>
                        
                        <div v-if="!aiPlan && !isLoadingPlan">
                            <button @click="getAIPlan" class="w-full n-btn n-btn-primary py-3.5 gap-2 text-sm shadow-lg shadow-blue-500/20">
                                <span>⚡</span> {{ $t('Generate Focus Strategy') }}
                            </button>
                        </div>

                        <div v-if="isLoadingPlan" class="py-10 text-center">
                            <div class="flex justify-center gap-1 mb-4">
                                <div class="w-2 h-6 bg-blue-500/40 rounded-full animate-pulse"></div>
                                <div class="w-2 h-8 bg-blue-500/60 rounded-full animate-pulse" style="animation-delay: 0.15s"></div>
                                <div class="w-2 h-5 bg-blue-500/30 rounded-full animate-pulse" style="animation-delay: 0.3s"></div>
                            </div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Analyzing cognitive patterns...') }}</p>
                        </div>

                        <div v-if="aiPlan" class="space-y-3 max-h-[400px] overflow-y-auto custom-scroll pe-2">
                            <template v-if="typeof aiPlan === 'string'">
                                <div v-for="(step, index) in aiPlan.split(/\d+\.\s+/).filter(s => s.trim())" :key="index"
                                     class="p-4 rounded-2xl bg-white dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 shadow-sm hover:border-blue-500/30 transition-all hover:shadow-md">
                                    <div class="flex items-start gap-3">
                                        <span class="w-7 h-7 rounded-lg bg-gradient-to-br from-blue-500/20 to-indigo-500/20 text-blue-600 dark:text-blue-400 flex items-center justify-center text-[11px] font-black shrink-0 mt-0.5 border border-blue-500/10">
                                            {{ index + 1 }}
                                        </span>
                                        <p class="text-xs n-p leading-relaxed bidi-plaintext">{{ step.trim() }}</p>
                                    </div>
                                </div>
                            </template>
                            <button @click="getAIPlan" class="w-full mt-2 n-btn n-btn-secondary py-2 text-[10px] gap-2">
                                <span>🔄</span> {{ $t('Refresh') }}
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 4px; }
.custom-scroll::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.4); }
</style>
