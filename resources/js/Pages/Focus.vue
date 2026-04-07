<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed, onUnmounted } from 'vue';
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
    if (isFocusing.value) return; // don't adjust while running
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
        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <div class="mb-8 text-center bg-glass-bg border border-glass-border p-6 rounded-3xl">
                    <h1 class="text-3xl font-black text-text-main mb-2 tracking-tighter flex items-center justify-center gap-2">
                        <span class="text-accent">⏳</span> {{ $t('Deep Synthesis Room') }}
                    </h1>
                    <p class="text-text-muted max-w-3xl mx-auto font-light leading-relaxed">
                        {{ $t('Welcome to the Pomodoro execution room. We use this to force Deep Work blocks. Pick a pending task, start the timer, and ignore everything else. This builds discipline and prevents cognitive overload.') }}
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Timer Core (The Dark Room Vibe) -->
                    <div class="flex-1">
                        <div class="glass-container border border-glass-border rounded-3xl p-8 md:p-16 flex flex-col items-center justify-center min-h-[500px] relative overflow-hidden group transition-all duration-1000"
                             :class="isFocusing ? (isBreak ? 'shadow-lg ring-1 ring-emerald-500/20' : 'shadow-lg ring-1 ring-rose-500/20') : ''">
                            
                            <!-- Ambient Glow when active -->
                            <div class="absolute inset-0 z-0 opacity-0 group-hover:opacity-100 transition duration-1000">
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 blur-[120px] rounded-full"
                                     :class="isFocusing ? (isBreak ? 'bg-emerald-600/30' : 'bg-rose-600/30') : 'bg-blue-600/10'"></div>
                            </div>

                            <div class="relative z-10 w-full flex flex-col items-center">
                                <h3 class="text-text-muted tracking-[0.5em] uppercase text-sm mb-4 font-black">
                                    {{ isBreak ? $t('Rest Sequence') : $t('Deep Synthesis') }}
                                </h3>
                                
                                <div class="flex items-center gap-4 mb-2 opacity-50" v-if="!isFocusing">
                                    <button @click="adjustTime(5)" class="w-10 h-10 rounded-full bg-surface-2 hover:bg-card-hover text-text-main flex items-center justify-center font-bold text-xl transition-all">
                                        +
                                    </button>
                                    <span class="text-text-main text-xs tracking-widest font-mono">
                                        {{ isBreak ? $t('Rest Duration') : $t('Work Duration') }} ({{ isBreak ? breakDurationMinutes : workDurationMinutes }} {{ $t('min') }})
                                    </span>
                                    <button @click="adjustTime(-5)" class="w-10 h-10 rounded-full bg-surface-2 hover:bg-card-hover text-text-main flex items-center justify-center font-bold text-xl transition-all">
                                        -
                                    </button>
                                </div>
                                <div class="h-6 w-full" v-else></div>

                                <div class="text-[8rem] md:text-[12rem] font-mono leading-none tracking-tighter font-black text-transparent bg-clip-text"
                                     :class="isFocusing ? (isBreak ? 'bg-gradient-to-b from-emerald-400 to-emerald-600' : 'bg-gradient-to-b from-rose-400 to-rose-600') : 'bg-gradient-to-b from-text-main to-text-muted'">
                                    {{ formatTime(timerSeconds) }}
                                </div>

                                <div class="flex gap-6 mt-12">
                                    <button @click="toggleTimer" class="w-20 h-20 rounded-full flex items-center justify-center text-3xl hover:scale-105 active:scale-95 transition-all shadow-xl"
                                            :class="isFocusing ? 'bg-input-bg text-text-main border border-border-subtle' : 'bg-accent text-white'">
                                        {{ isFocusing ? '⏸' : '▶' }}
                                    </button>
                                    <button @click="resetTimer" class="w-20 h-20 rounded-full bg-surface-2 border border-border-subtle flex items-center justify-center text-xl text-text-muted hover:text-text-main hover:bg-card-hover transition">
                                        ⟲
                                    </button>
                                </div>
                                <p class="mt-8 text-text-muted text-sm tracking-widest">{{ $t('Total Focused Time:') }} {{ formatTime(totalFocusTime) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- AI Planner & Tasks -->
                    <div class="w-full lg:w-[450px] flex flex-col gap-6">
                        <div class="bg-glass-bg border border-glass-border rounded-3xl p-6">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold text-text-main flex items-center gap-2">
                                    <span class="text-blue-500">🎯</span> {{ $t('Target Tasks') }}
                                </h2>
                                <span class="text-xs bg-surface-2 px-2 py-1 rounded text-text-muted">{{ pendingTasks.length }} {{ $t('pending') }}</span>
                            </div>

                            <div class="space-y-3 custom-scroll max-h-[250px] overflow-y-auto pr-2">
                                <div v-for="t in pendingTasks" :key="t.id" class="p-3 bg-input-bg rounded-xl flex justify-between items-center group">
                                    <span class="text-text-main text-sm line-clamp-1">{{ t.title }}</span>
                                    <button @click="finishTask(t.id)" class="w-6 h-6 border-2 border-border-subtle rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 hover:border-emerald-500 hover:bg-emerald-500/20 transition cursor-pointer text-transparent hover:text-emerald-400">
                                        ✓
                                    </button>
                                </div>
                                <p v-if="pendingTasks.length === 0" class="text-text-muted text-sm text-center py-4">{{ $t('You are clear for today.') }}</p>
                            </div>
                        </div>

                        <div class="bg-gradient-to-br from-accent/10 to-surface border border-accent/20 rounded-3xl p-6 flex-1 flex flex-col">
                            <h2 class="text-lg font-bold text-accent flex items-center gap-2 mb-4">
                                <span class="text-2xl">🧠</span> {{ $t('AI Phase Planner') }}
                            </h2>
                            <button v-if="!aiPlan && !isLoadingPlan" @click="getAIPlan" class="w-full py-4 rounded-xl border border-accent/30 text-accent hover:bg-card-hover transition font-bold tracking-wider uppercase">
                                {{ $t('Generate Focus Plan') }}
                            </button>
                            <p v-if="isLoadingPlan" class="text-accent text-sm animate-pulse text-center my-auto">{{ $t('Analyzing cognitive load...') }}</p>
                            <div v-if="aiPlan" class="text-text-main/80 text-sm leading-relaxed bidi-plaintext custom-scroll overflow-y-auto flex-1">
                                {{ aiPlan }}
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.glass-container {
    background: var(--c-glass-bg);
    backdrop-filter: blur(20px);
}
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 4px; opacity: 0.2; }
</style>
