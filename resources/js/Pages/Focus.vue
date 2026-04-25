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
        <template #header>
            <div class="backdrop-blur-xl bg-slate-900/50 border-b border-white/5 px-6 py-8">
                <div class="max-w-[1600px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-2xl shadow-xl shadow-indigo-500/20 animate-float">
                            ⏳
                        </div>
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-white uppercase">{{ $t('Deep Synthesis') }}</h2>
                            <p class="text-[10px] text-indigo-400 font-bold tracking-widest uppercase opacity-70 mt-1">✧ {{ $t('Cognitive_Shield.v1') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-slate-950 text-slate-200 py-12 relative overflow-hidden">
            <!-- Background Focus Glow -->
            <div class="absolute inset-0 z-0 pointer-events-none">
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[800px] blur-[150px] rounded-full transition-all duration-1000"
                     :class="isFocusing ? (isBreak ? 'bg-emerald-500/10' : 'bg-rose-500/10') : 'bg-indigo-500/5'"></div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 relative z-10">
                
                <!-- Main Focus Interface -->
                <div class="flex flex-col lg:flex-row gap-10">
                    
                    <!-- Timer Core (Cinema Display) -->
                    <div class="flex-1">
                        <div class="neural-card-premium p-12 md:p-20 flex flex-col items-center justify-center min-h-[600px] relative overflow-hidden transition-all duration-1000 group"
                             :class="isFocusing ? (isBreak ? 'border-emerald-500/30 shadow-emerald-500/10' : 'border-rose-500/30 shadow-rose-500/10') : 'border-white/5'">
                            
                            <div class="absolute inset-0 bg-gradient-to-b from-white/[0.02] to-transparent pointer-events-none"></div>

                            <div class="relative z-10 w-full flex flex-col items-center">
                                <div class="flex items-center gap-3 mb-8">
                                    <span class="w-2 h-2 rounded-full animate-pulse" :class="isFocusing ? (isBreak ? 'bg-emerald-500' : 'bg-rose-500') : 'bg-indigo-500'"></span>
                                    <h3 class="text-slate-500 tracking-[0.6em] uppercase text-[10px] font-black">
                                        {{ isBreak ? $t('Restoration Phase') : $t('Cognitive Block') }}
                                    </h3>
                                </div>
                                
                                <!-- Duration Adjuster -->
                                <div class="flex items-center gap-8 mb-4 transition-all duration-500" :class="isFocusing ? 'opacity-0 -translate-y-4 pointer-events-none' : 'opacity-100'">
                                    <button @click="adjustTime(5)" class="w-12 h-12 rounded-xl bg-slate-900 border border-white/5 hover:border-indigo-500/50 text-white flex items-center justify-center font-bold text-xl transition-all shadow-inner">
                                        +
                                    </button>
                                    <div class="text-center">
                                        <p class="text-[9px] text-slate-500 font-black uppercase tracking-widest mb-1">{{ isBreak ? $t('Rest Sequence') : $t('Work Duration') }}</p>
                                        <p class="text-white font-black text-xl">{{ isBreak ? breakDurationMinutes : workDurationMinutes }} <span class="text-xs text-slate-600">MIN</span></p>
                                    </div>
                                    <button @click="adjustTime(-5)" class="w-12 h-12 rounded-xl bg-slate-900 border border-white/5 hover:border-indigo-500/50 text-white flex items-center justify-center font-bold text-xl transition-all shadow-inner">
                                        -
                                    </button>
                                </div>

                                <!-- The Timer -->
                                <div class="text-[9rem] md:text-[14rem] font-mono leading-none tracking-tighter font-black transition-all duration-1000 select-none drop-shadow-2xl"
                                     :class="isFocusing ? (isBreak ? 'text-emerald-400 drop-shadow-[0_0_30px_rgba(52,211,153,0.3)]' : 'text-rose-400 drop-shadow-[0_0_30px_rgba(248,113,113,0.3)]') : 'text-white'">
                                    {{ formatTime(timerSeconds) }}
                                </div>

                                <!-- Controls -->
                                <div class="flex gap-10 mt-16">
                                    <button @click="toggleTimer" class="w-24 h-24 rounded-[30px] flex items-center justify-center text-4xl transition-all duration-500 shadow-2xl relative group/play"
                                            :class="isFocusing ? 'bg-slate-900 text-white border border-white/10' : 'bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-indigo-500/20'">
                                        <div class="absolute inset-0 bg-white/20 rounded-[30px] opacity-0 group-hover/play:opacity-100 transition-opacity"></div>
                                        <span class="relative z-10">{{ isFocusing ? '⏸' : '▶' }}</span>
                                    </button>
                                    <button @click="resetTimer" class="w-24 h-24 rounded-[30px] bg-slate-950 border border-white/5 flex items-center justify-center text-2xl text-slate-600 hover:text-white hover:border-white/20 transition-all shadow-inner">
                                        ⟲
                                    </button>
                                </div>

                                <!-- Stats -->
                                <div class="mt-12 flex items-center gap-6">
                                    <div class="px-6 py-2 rounded-full bg-slate-900 border border-white/5 flex items-center gap-3">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $t('Total Shielding') }}:</span>
                                        <span class="text-indigo-400 font-mono font-black">{{ formatTime(totalFocusTime) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Side Controls (Tasks & AI) -->
                    <div class="w-full lg:w-[400px] shrink-0 space-y-8">
                        
                        <!-- Target Tasks -->
                        <div class="neural-card-premium p-8">
                            <div class="flex justify-between items-center mb-8">
                                <h2 class="text-xl font-black text-white uppercase tracking-tight flex items-center gap-3">
                                    <span class="w-2 h-2 rounded-full bg-rose-500 shadow-[0_0_10px_rgba(244,63,94,0.8)]"></span>
                                    {{ $t('Kill List') }}
                                </h2>
                                <span class="px-3 py-1 rounded-lg bg-slate-950 border border-white/5 text-[9px] font-black text-slate-500 tracking-widest uppercase">{{ pendingTasks.length }} ACTIVE</span>
                            </div>

                            <div class="space-y-3 custom-scrollbar max-h-[300px] overflow-y-auto pr-2">
                                <div v-for="t in pendingTasks" :key="t.id" 
                                    class="p-4 bg-slate-900/50 border border-white/5 rounded-2xl flex justify-between items-center group/task hover:border-indigo-500/30 transition-all duration-500 shadow-inner">
                                    <span class="text-slate-300 text-sm font-medium line-clamp-1 group-hover/task:text-white transition-colors">{{ t.title }}</span>
                                    <button @click="finishTask(t.id)" class="w-8 h-8 rounded-xl border border-white/10 flex items-center justify-center transition-all duration-300 hover:bg-emerald-500 hover:border-emerald-500 group-hover/task:opacity-100 opacity-30 text-transparent hover:text-white shadow-xl">
                                        ✓
                                    </button>
                                </div>
                                <div v-if="pendingTasks.length === 0" class="py-12 text-center opacity-20">
                                    <p class="text-xs font-black uppercase tracking-widest">{{ $t('All threats eliminated.') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- AI Planner -->
                        <div class="neural-card-premium p-8 relative overflow-hidden group/ai">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent pointer-events-none"></div>
                            
                            <h2 class="text-lg font-black text-indigo-400 flex items-center gap-3 mb-6">
                                <span class="text-2xl animate-float">🧠</span> {{ $t('Focus Protocol') }}
                            </h2>
                            
                            <div v-if="!aiPlan && !isLoadingPlan" class="py-6">
                                <button @click="getAIPlan" class="btn-neural-premium w-full py-4 bg-indigo-600/20 text-indigo-400 border-indigo-500/30 hover:bg-indigo-600/30">
                                    {{ $t('Optimize Sessions') }}
                                </button>
                            </div>

                            <div v-if="isLoadingPlan" class="py-10 text-center">
                                <div class="w-1 h-8 bg-indigo-500/50 rounded-full mx-auto animate-pulse mb-4"></div>
                                <p class="text-indigo-400/50 font-black tracking-[0.4em] uppercase text-[9px]">{{ $t('Calculating load...') }}</p>
                            </div>

                            <div v-if="aiPlan" class="relative z-10">
                                <div class="p-5 bg-black/20 border border-white/5 rounded-2xl text-slate-400 text-sm leading-relaxed italic bidi-plaintext shadow-inner custom-scrollbar overflow-y-auto max-h-[300px]">
                                    {{ aiPlan }}
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(99, 102, 241, 0.2); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(99, 102, 241, 0.4); }

.bidi-plaintext {
    unicode-bidi: plaintext;
    text-align: start;
}
</style>
