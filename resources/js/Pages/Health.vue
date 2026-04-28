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
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Logger Form (4 Cols) -->
                <div class="lg:col-span-4">
                    <div class="n-card p-6 lg:p-8 sticky top-24">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-xl shadow-inner">🧬</div>
                            <h2 class="n-h2">{{ $t('Bio-Sync Portal') }}</h2>
                        </div>
                        
                        <form @submit.prevent="submitLog" class="space-y-6">
                            <!-- Temporal Marker -->
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Temporal Marker') }}</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <select v-model.number="selectedDay" class="n-input py-2 text-center text-xs">
                                        <option v-for="d in days" :key="d" :value="d">{{ d }}</option>
                                    </select>
                                    <select v-model.number="selectedMonth" class="n-input py-2 text-center text-xs">
                                        <option v-for="m in months" :key="m.v" :value="m.v">{{ $t(m.name) }}</option>
                                    </select>
                                    <select v-model.number="selectedYear" class="n-input py-2 text-center text-xs">
                                        <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                    </select>
                                </div>
                                <p class="text-emerald-500 font-black text-[9px] text-center uppercase mt-1">{{ formattedDate }}</p>
                            </div>

                            <!-- Sleep Range -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Hibernation (Hours)') }}</label>
                                    <span class="text-xs font-black text-emerald-500">{{ form.sleep_hours }}H</span>
                                </div>
                                <input type="range" v-model.number="form.sleep_hours" min="0" max="14" step="0.5" class="w-full accent-emerald-500" />
                            </div>

                            <!-- Mood Range -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Core Resonance') }}</label>
                                    <span class="text-xs font-black text-emerald-500">{{ form.mood_score }}/10</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="range" v-model.number="form.mood_score" min="1" max="10" step="1" class="w-full accent-emerald-500" />
                                    <span class="text-xl w-10 h-10 flex items-center justify-center rounded-lg bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-800">{{ getMoodEmoji(form.mood_score).split(' ')[0] }}</span>
                                </div>
                                <p class="text-[9px] text-center font-black uppercase text-slate-400 tracking-widest">{{ getMoodEmoji(form.mood_score).split(' ').slice(1).join(' ') }}</p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Telemetry Notes') }}</label>
                                <textarea v-model="form.notes" rows="3" class="n-input w-full" :placeholder="$t('Neural resonance observed...')"></textarea>
                            </div>

                            <button type="submit" :disabled="form.processing" class="w-full n-btn n-btn-primary bg-emerald-600 hover:bg-emerald-500">
                                {{ form.processing ? $t('Transmitting...') : $t('Sync Bio-Pattern') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- AI Analysis & History (8 Cols) -->
                <div class="lg:col-span-8 space-y-8">
                    
                    <!-- AI NEUROLOGICAL ANALYSIS -->
                    <div class="n-card p-8 lg:p-10 relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-transparent pointer-events-none -z-10"></div>
                        
                        <div class="flex items-center justify-between mb-8">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl shadow-inner animate-float">🧠</div>
                                <div>
                                    <h2 class="n-h2 text-2xl">{{ $t('Neural Synthesis') }}</h2>
                                    <p class="n-p text-[9px] uppercase tracking-widest font-black text-emerald-500">{{ $t('Cognitive_Audit.v1') }}</p>
                                </div>
                            </div>
                            <button v-if="aiAnalysis" @click="getAnalysis" :disabled="isAnalyzing" class="n-btn py-1.5 px-4 text-[9px] bg-emerald-500/10 text-emerald-500 border-emerald-500/20">{{ $t('Refresh') }}</button>
                        </div>

                        <div v-if="!aiAnalysis && !isAnalyzing" class="py-12 text-center space-y-6">
                            <p class="n-p text-lg max-w-md mx-auto opacity-60">{{ $t('Awaiting bio-data for neurological pattern analysis.') }}</p>
                            <button @click="getAnalysis" class="n-btn n-btn-primary bg-emerald-600 px-10">
                                {{ $t('Initialize Diagnostics') }}
                            </button>
                        </div>

                        <div v-if="isAnalyzing" class="py-12 text-center">
                            <div class="flex justify-center gap-1 mb-4">
                                <div v-for="i in 3" :key="i" class="w-2 h-2 bg-emerald-500 rounded-full animate-bounce" :style="{ animationDelay: (i*0.2)+'s' }"></div>
                            </div>
                            <p class="text-emerald-500 font-black tracking-widest uppercase text-[9px]">{{ $t('Processing bio-data nexus...') }}</p>
                        </div>

                        <div v-if="aiAnalysis" class="space-y-4 max-h-[400px] overflow-y-auto custom-scroll pr-2">
                            <template v-if="typeof aiAnalysis === 'string'">
                                <div v-for="(step, index) in aiAnalysis.split(/\d+\.\s+/).filter(s => s.trim())" :key="index"
                                     class="p-5 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-100 dark:border-slate-800 shadow-sm group/step hover:border-emerald-500/30 transition-all">
                                     <div class="flex items-start gap-4">
                                         <span class="w-8 h-8 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center text-xs font-black shrink-0 mt-0.5">
                                             {{ index + 1 }}
                                         </span>
                                         <p class="text-sm n-p leading-relaxed bidi-plaintext">{{ step.trim() }}</p>
                                     </div>
                                 </div>
                            </template>
                        </div>
                    </div>

                    <!-- BIOLOGICAL TIMELINE -->
                    <div class="n-card p-8">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="n-h2 text-xl">{{ $t('Biological Timeline') }}</h2>
                            <span class="px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-[10px] font-black text-slate-400">{{ logs.length }} {{ $t('Logged') }}</span>
                        </div>

                        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="log in logs" :key="log.id" 
                                class="flex flex-col md:flex-row items-center gap-6 p-6 rounded-3xl bg-slate-50/50 dark:bg-slate-900/30 border border-slate-100 dark:border-slate-800 hover:border-emerald-500/20 transition-all group">
                                
                                <div class="flex items-center gap-6 w-full md:w-auto">
                                    <div class="w-14 h-14 rounded-2xl flex flex-col items-center justify-center font-black border group-hover:scale-105 transition-transform"
                                         :class="log.mood_score >= 7 ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : (log.mood_score <= 4 ? 'bg-rose-500/10 text-rose-500 border-rose-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20')">
                                        <span class="text-xl">{{ log.mood_score }}</span>
                                        <span class="text-[7px] uppercase tracking-widest opacity-60">{{ $t('Mood') }}</span>
                                    </div>
                                    <div>
                                        <p class="text-lg font-black text-slate-800 dark:text-white">{{ log.log_date }}</p>
                                        <span class="text-[10px] font-black text-emerald-500 uppercase tracking-widest">
                                            {{ $t('Sleep') }}: {{ log.sleep_hours }}H
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="flex-1 w-full bg-white dark:bg-black/20 p-4 rounded-2xl border border-slate-100 dark:border-white/5">
                                    <p class="text-sm italic n-p bidi-plaintext">
                                        {{ log.notes || $t('No Telemetry Data Recorded.') }}
                                    </p>
                                </div>
                            </div>

                            <div v-if="logs.length === 0" class="py-20 text-center n-p opacity-40 italic">
                                {{ $t('Zero Biological resonance detected.') }}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
input[type=range] { -webkit-appearance: none; background: #e2e8f0; height: 4px; border-radius: 2px; }
.dark input[type=range] { background: #1e293b; }
input[type=range]::-webkit-slider-thumb { -webkit-appearance: none; width: 16px; height: 16px; border-radius: 50%; background: #10b981; cursor: pointer; border: 3px solid white; box-shadow: 0 0 10px rgba(16,185,129,0.3); }
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(16, 185, 129, 0.2); border-radius: 4px; }
</style>
