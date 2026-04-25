<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    logs: Array
});

const today = new Date();
const selectedDay   = ref(today.getDate());
const selectedMonth = ref(today.getMonth() + 1);
const selectedYear  = ref(today.getFullYear());

const daysInMonth = computed(() => {
    return new Date(selectedYear.value, selectedMonth.value, 0).getDate();
});

const days    = computed(() => Array.from({ length: daysInMonth.value }, (_, i) => i + 1));
const months  = [
    { v: 1,  name: 'January' }, { v: 2,  name: 'February' }, { v: 3,  name: 'March' },
    { v: 4,  name: 'April' }, { v: 5,  name: 'May'   }, { v: 6,  name: 'June' },
    { v: 7,  name: 'July' }, { v: 8,  name: 'August' }, { v: 9,  name: 'September' },
    { v: 10, name: 'October'}, { v: 11, name: 'November' }, { v: 12, name: 'December' }
];
const years   = computed(() => {
    const y = [];
    for (let i = selectedYear.value; i >= selectedYear.value - 2; i--) y.push(i);
    return y;
});

const formattedDate = computed(() => {
    const m = String(selectedMonth.value).padStart(2, '0');
    const d = String(selectedDay.value).padStart(2, '0');
    return `${selectedYear.value}-${m}-${d}`;
});

const form = useForm({
    sleep_hours: 7,
    mood_score: 5,
    notes: ''
});

const submitLog = () => {
    form
        .transform(data => ({ ...data, log_date: formattedDate.value }))
        .post(route('health.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset('notes')
        });
};

const aiAnalysis = ref('');
const isAnalyzing = ref(false);

const getAnalysis = async () => {
    isAnalyzing.value = true;
    try {
        const response = await axios.post(route('health.analyze'));
        aiAnalysis.value = response.data.analysis;
    } catch(e) {
        aiAnalysis.value = trans('Diagnostic system offline.');
    } finally {
        isAnalyzing.value = false;
    }
};

const getMoodEmoji = (score) => {
    if (score <= 3) return `📉 ${trans('Exhausted')}`;
    if (score <= 6) return `〰️ ${trans('Normal')}`;
    if (score <= 8) return `📈 ${trans('Good')}`;
    return `🚀 ${trans('Excellent')}`;
};
</script>

<template>
    <Head :title="$t('Health & Mood')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="backdrop-blur-xl bg-slate-900/50 border-b border-white/5 px-6 py-8">
                <div class="max-w-[1600px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-2xl shadow-xl shadow-emerald-500/20 animate-float">
                            🧬
                        </div>
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-white uppercase">{{ $t('Health Matrix') }}</h2>
                            <p class="text-[10px] text-emerald-400 font-bold tracking-widest uppercase opacity-70 mt-1">✧ {{ $t('Biological_Sync.v2') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-slate-950 text-slate-200 py-12 relative overflow-hidden">
            <!-- Background Bio-Glow -->
            <div class="absolute top-[-5%] right-[-5%] w-[35%] h-[35%] bg-emerald-500/10 rounded-full blur-[100px] pointer-events-none animate-pulse"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 relative z-10">
                
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Logger Form -->
                    <div class="w-full lg:w-[400px] shrink-0">
                        <div class="neural-card-premium p-8 sticky top-32">
                            <h2 class="text-lg font-black text-white uppercase tracking-widest mb-8 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                {{ $t('Bio-Sync Portal') }}
                            </h2>
                            
                            <form @submit.prevent="submitLog" class="space-y-8">
                                <!-- Custom Date Selector -->
                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $t('Temporal Marker') }}</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select v-model.number="selectedDay" class="bg-slate-900 border border-white/5 rounded-xl px-2 py-4 text-white text-center font-bold focus:ring-emerald-500/50 focus:border-emerald-500/50 appearance-none cursor-pointer shadow-inner">
                                            <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                                        </select>
                                        <select v-model.number="selectedMonth" class="bg-slate-900 border border-white/5 rounded-xl px-2 py-4 text-white text-center font-bold focus:ring-emerald-500/50 focus:border-emerald-500/50 appearance-none cursor-pointer shadow-inner">
                                            <option v-for="m in months" :key="m.v" :value="m.v">{{ $t(m.name) }}</option>
                                        </select>
                                        <select v-model.number="selectedYear" class="bg-slate-900 border border-white/5 rounded-xl px-2 py-4 text-white text-center font-bold focus:ring-emerald-500/50 focus:border-emerald-500/50 appearance-none cursor-pointer shadow-inner">
                                            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                        </select>
                                    </div>
                                    <p class="text-emerald-500/60 text-[9px] font-black tracking-widest mt-1 text-center uppercase">{{ formattedDate }}</p>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest flex justify-between">
                                        <span>{{ $t('Hibernation (Hours)') }}</span>
                                        <span class="text-emerald-400 font-black">{{ form.sleep_hours }}H</span>
                                    </label>
                                    <input type="range" v-model.number="form.sleep_hours" min="0" max="14" step="0.5" class="w-full h-1.5 bg-slate-900 rounded-full appearance-none accent-emerald-500 cursor-pointer" />
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest flex justify-between">
                                        <span>{{ $t('Core Resonance') }}</span>
                                        <span class="text-emerald-400 font-black">{{ form.mood_score }}/10</span>
                                    </label>
                                    <div class="flex items-center gap-3">
                                        <input type="range" v-model.number="form.mood_score" min="1" max="10" step="1" class="w-full h-1.5 bg-slate-900 rounded-full appearance-none accent-emerald-500 cursor-pointer" />
                                        <span class="text-xl shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-white/5 border border-white/5">{{ getMoodEmoji(form.mood_score).split(' ')[0] }}</span>
                                    </div>
                                    <p class="text-[9px] text-center font-black uppercase text-emerald-500/60 tracking-widest">{{ getMoodEmoji(form.mood_score).split(' ').slice(1).join(' ') }}</p>
                                </div>

                                <div class="space-y-4">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ $t('Telemetry Notes') }}</label>
                                    <textarea v-model="form.notes" rows="3" class="neural-input-premium w-full text-white" :placeholder="$t('Neural resonance observed...')"></textarea>
                                </div>

                                <button type="submit" :disabled="form.processing" class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-emerald-600 to-teal-600 w-full py-5 text-lg shadow-emerald-500/20">
                                    {{ form.processing ? $t('Transmitting...') : $t('Sync Bio-Pattern') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- AI Analysis & History -->
                    <div class="flex-1 space-y-8">
                        
                        <!-- AI Neurological Analysis -->
                        <div class="neural-card-premium p-10 relative overflow-hidden group">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent pointer-events-none"></div>
                            
                            <div class="flex items-center justify-between mb-8 relative z-10">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl shadow-inner border border-emerald-500/20 animate-float">🧠</div>
                                    <div>
                                        <h2 class="text-2xl font-black text-white uppercase tracking-tight">{{ $t('Neural Synthesis') }}</h2>
                                        <p class="text-[9px] text-emerald-400 font-bold uppercase tracking-[0.4em] mt-1 opacity-60">Cognitive_Audit.v1</p>
                                    </div>
                                </div>
                                <button v-if="aiAnalysis" @click="getAnalysis" :disabled="isAnalyzing" class="text-[10px] font-black text-emerald-400 hover:text-emerald-300 transition-colors uppercase tracking-widest border border-emerald-500/20 px-4 py-2 rounded-xl bg-emerald-500/5 shadow-inner">Refresh</button>
                            </div>

                            <div v-if="!aiAnalysis && !isAnalyzing" class="relative z-10 py-10 text-center">
                                <p class="text-slate-500 text-lg font-medium mb-8 max-w-md mx-auto">{{ $t('Awaiting bio-data for neurological pattern analysis.') }}</p>
                                <button @click="getAnalysis" class="btn-neural-premium px-10 py-4 bg-emerald-600/20 text-emerald-400 border-emerald-500/30 hover:bg-emerald-600/30">
                                    {{ $t('Initialize Diagnostics') }}
                                </button>
                            </div>

                            <div v-if="isAnalyzing" class="relative z-10 py-20 text-center">
                                <div class="flex justify-center gap-1 mb-6">
                                    <div v-for="i in 3" :key="i" class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" :style="{ animationDelay: (i*0.2)+'s' }"></div>
                                </div>
                                <p class="text-emerald-500/50 font-black tracking-[0.4em] uppercase text-xs">{{ $t('Processing bio-data nexus...') }}</p>
                            </div>

                            <div v-if="aiAnalysis" class="relative z-10">
                                <div class="p-8 bg-slate-900/40 border border-white/5 rounded-[40px] text-slate-300 leading-relaxed text-lg italic bidi-plaintext shadow-inner selection:bg-emerald-500/30">
                                    {{ aiAnalysis }}
                                </div>
                            </div>
                        </div>

                        <!-- Logs History -->
                        <div class="neural-card-premium p-10">
                            <div class="flex items-center justify-between mb-10">
                                <h2 class="text-2xl font-black text-white uppercase tracking-tight">{{ $t('Biological Timeline') }}</h2>
                                <span class="px-4 py-1 rounded-xl bg-slate-900 border border-white/5 text-[10px] font-black text-slate-500 tracking-[0.3em] shadow-inner">{{ logs.length }} ENTRIES</span>
                            </div>

                            <div class="space-y-4 overflow-y-auto max-h-[600px] pr-2 custom-scrollbar">
                                <div v-for="log in logs" :key="log.id" 
                                    class="group p-6 bg-slate-900/30 border border-white/5 rounded-[30px] flex flex-col md:flex-row justify-between items-center gap-8 hover:bg-slate-900/60 hover:border-emerald-500/20 transition-all duration-500 shadow-lg">
                                    
                                    <div class="flex items-center gap-6 w-full md:w-auto">
                                        <div class="w-16 h-16 rounded-2xl flex flex-col items-center justify-center font-black shadow-xl border relative overflow-hidden group-hover:scale-105 transition-transform"
                                             :class="log.mood_score >= 7 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 shadow-emerald-500/10' : (log.mood_score <= 4 ? 'bg-rose-500/10 text-rose-400 border-rose-500/20 shadow-rose-500/10' : 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20 shadow-indigo-500/10')">
                                            <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent"></div>
                                            <span class="text-2xl relative z-10">{{ log.mood_score }}</span>
                                            <span class="text-[8px] uppercase tracking-widest opacity-60 relative z-10">{{ $t('Mood') }}</span>
                                        </div>
                                        <div>
                                            <p class="text-xl font-black text-white uppercase tracking-tight">{{ log.log_date }}</p>
                                            <div class="flex gap-4 mt-1">
                                                <span class="text-[10px] text-emerald-400 font-bold tracking-widest uppercase flex items-center gap-1.5">
                                                    <span class="w-1 h-1 rounded-full bg-emerald-500"></span>
                                                    {{ $t('Sleep') }}: {{ log.sleep_hours }}H
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 w-full md:w-auto">
                                        <div class="p-4 bg-black/20 border border-white/5 rounded-2xl relative group/note">
                                            <p class="text-slate-400 text-sm leading-relaxed italic bidi-plaintext line-clamp-2 md:line-clamp-1 text-end rtl:text-left group-hover/note:line-clamp-none transition-all">
                                                {{ log.notes || $t('No Telemetry Data Recorded.') }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="logs.length === 0" class="py-32 text-center opacity-10">
                                    <span class="text-6xl block mb-4">🌑</span>
                                    <p class="text-xs font-black uppercase tracking-[0.5em]">{{ $t('Zero Biological resonance detected.') }}</p>
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
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: rgba(16, 185, 129, 0.4); }

.bidi-plaintext {
    unicode-bidi: plaintext;
}
</style>
