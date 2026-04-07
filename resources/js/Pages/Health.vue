<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';

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
        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Page Header -->
                <div class="mb-10 text-center" :class="getActiveLanguage() === 'ar' ? 'md:text-right' : 'md:text-left'">
                    <h1 class="text-3xl md:text-5xl font-black text-text-main tracking-tighter mb-4 flex justify-center items-center gap-3" :class="getActiveLanguage() === 'ar' ? 'md:justify-start' : 'md:justify-start'">
                        <span class="text-emerald-500">🧬</span> {{ $t('Health & Mood Matrix') }}
                    </h1>
                    <p class="text-text-muted max-w-2xl mx-auto text-lg" :class="getActiveLanguage() === 'ar' ? 'md:mx-0' : 'md:mx-0'">
                        {{ $t('Track your biological energy to understand how your sleep and mood affect your financial decisions and time management.') }}
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Logger Form -->
                    <div class="w-full lg:w-1/3">
                        <div class="bg-glass-bg backdrop-blur-3xl border border-glass-border rounded-3xl p-6 md:p-8 relative overflow-hidden shadow-lg">
                            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/5 to-transparent -z-10"></div>
                            
                            <h2 class="text-xl font-bold text-text-main mb-6">{{ $t('Daily Bio-Log') }}</h2>
                            
                            <form @submit.prevent="submitLog" class="space-y-6">
                                <!-- Custom Date Selector -->
                                <div>
                                    <label class="block text-sm font-bold text-text-muted mb-3">{{ $t('Date') }}</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select v-model.number="selectedDay" class="bg-surface-2 border border-border-subtle rounded-xl px-2 py-3 text-text-main text-center font-mono focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="d in days" :key="d" :value="d" class="bg-surface-2">{{ d }}</option>
                                        </select>
                                        <select v-model.number="selectedMonth" class="bg-surface-2 border border-border-subtle rounded-xl px-2 py-3 text-text-main text-center focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="m in months" :key="m.v" :value="m.v" class="bg-surface-2">{{ $t(m.name) }}</option>
                                        </select>
                                        <select v-model.number="selectedYear" class="bg-surface-2 border border-border-subtle rounded-xl px-2 py-3 text-text-main text-center font-mono focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="y in years" :key="y" :value="y" class="bg-surface-2">{{ y }}</option>
                                        </select>
                                    </div>
                                    <p class="text-emerald-500/60 text-xs font-mono mt-1 text-center">{{ formattedDate }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-text-muted mb-2 flex justify-between">
                                        <span>{{ $t('Sleep Hours') }}</span>
                                        <span class="text-emerald-500 font-mono">{{ form.sleep_hours }} h</span>
                                    </label>
                                    <input type="range" v-model.number="form.sleep_hours" min="0" max="14" step="0.5" class="w-full accent-emerald-500" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-text-muted mb-2 flex justify-between">
                                        <span>{{ $t('Mood Score') }}</span>
                                        <span class="text-emerald-500 font-mono">{{ form.mood_score }}/10 ({{ getMoodEmoji(form.mood_score) }})</span>
                                    </label>
                                    <input type="range" v-model.number="form.mood_score" min="1" max="10" step="1" class="w-full accent-emerald-500" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-text-muted mb-2">{{ $t('Notes (Optional)') }}</label>
                                    <textarea v-model="form.notes" rows="2" class="w-full bg-input-bg border border-border-subtle rounded-xl px-4 py-3 text-text-main focus:ring-emerald-500 focus:border-emerald-500" :placeholder="$t('Mood Notes Placeholder')"></textarea>
                                </div>

                                <button type="submit" :disabled="form.processing" class="w-full py-4 rounded-xl font-bold text-white text-lg hover:scale-[1.02] active:scale-95 transition-all shadow-xl disabled:opacity-50"
                                        :class="form.processing ? 'bg-emerald-800' : 'bg-gradient-to-r from-emerald-500 to-teal-400'">
                                    {{ form.processing ? $t('Saving...') : $t('Sync Biological Pattern') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- AI Analysis & History -->
                    <div class="flex-1 flex flex-col gap-8">
                        
                        <!-- AI Neurological Analysis -->
                        <div class="bg-gradient-to-br from-emerald-500/10 to-surface border border-emerald-500/20 rounded-3xl p-6 flex flex-col min-h-[250px]">
                            <h2 class="text-lg font-bold text-emerald-500 flex items-center gap-2 mb-4">
                                <span class="text-2xl">🧠</span> {{ $t('Neural Health Analysis') }}
                            </h2>
                            <button v-if="!aiAnalysis && !isAnalyzing" @click="getAnalysis" class="w-full py-4 rounded-xl border border-emerald-500/30 text-emerald-500 hover:bg-emerald-500/10 transition font-bold tracking-wider mb-4">
                                {{ $t('Diagnose My Patterns') }}
                            </button>
                            <div v-if="isAnalyzing" class="my-auto text-center text-emerald-500/50 animate-pulse font-mono tracking-widest text-sm">
                                {{ $t('Processing bio-data...') }}
                            </div>
                            <div v-if="aiAnalysis" class="text-text-main/80 leading-relaxed text-sm bidi-plaintext custom-scroll overflow-y-auto pr-2">
                                {{ aiAnalysis }}
                            </div>
                        </div>

                        <!-- Logs History -->
                        <div class="bg-glass-bg border border-glass-border rounded-3xl p-6 flex-1 max-h-[400px] flex flex-col">
                            <h2 class="text-lg font-bold text-text-main mb-4">{{ $t('Recent Logs') }}</h2>
                            <div class="space-y-3 overflow-y-auto custom-scroll pr-2 flex-1">
                                <div v-for="log in logs" :key="log.id" class="p-4 bg-input-bg border border-border-subtle rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-card-hover transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg"
                                             :class="log.mood_score >= 7 ? 'bg-emerald-500/20 text-emerald-500' : (log.mood_score <= 4 ? 'bg-rose-500/20 text-rose-500' : 'bg-blue-500/20 text-blue-500')">
                                            {{ log.mood_score }}
                                        </div>
                                        <div>
                                            <p class="text-text-main font-bold">{{ log.log_date }}</p>
                                            <p class="text-xs text-text-muted font-mono">{{ $t('Sleep') }}: {{ log.sleep_hours }}h</p>
                                        </div>
                                    </div>
                                    <div class="flex-1 md:text-right">
                                        <p class="text-text-muted text-sm line-clamp-2 md:line-clamp-1 italic text-right rtl:text-left">{{ log.notes || $t('No notes provided.') }}</p>
                                    </div>
                                </div>
                                <p v-if="logs.length === 0" class="text-center text-text-muted py-8">{{ $t('No biological logs found.') }}</p>
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 4px; opacity: 0.2; }
</style>
