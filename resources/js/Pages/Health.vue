<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

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
    { v: 1,  ar: 'يناير' }, { v: 2,  ar: 'فبراير' }, { v: 3,  ar: 'مارس' },
    { v: 4,  ar: 'أبريل' }, { v: 5,  ar: 'مايو'   }, { v: 6,  ar: 'يونيو' },
    { v: 7,  ar: 'يوليو' }, { v: 8,  ar: 'أغسطس' }, { v: 9,  ar: 'سبتمبر' },
    { v: 10, ar: 'أكتوبر'}, { v: 11, ar: 'نوفمبر' }, { v: 12, ar: 'ديسمبر' }
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
        aiAnalysis.value = 'النظام الشتخيصي خارج الخدمة.';
    } finally {
        isAnalyzing.value = false;
    }
};

const getMoodEmoji = (score) => {
    if (score <= 3) return '📉 مرهق';
    if (score <= 6) return '〰️ عادي';
    if (score <= 8) return '📈 جيد';
    return '🚀 ممتاز';
};
</script>

<template>
    <Head :title="$t('Health & Mood')" />

    <AuthenticatedLayout>
        <div class="py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                
                <!-- Page Header -->
                <div class="mb-10 text-center md:text-right">
                    <h1 class="text-3xl md:text-5xl font-black text-white tracking-tighter mb-4 flex justify-center md:justify-start items-center gap-3">
                        <span class="text-emerald-500">🧬</span> {{ $t('Health & Mood Matrix') }}
                    </h1>
                    <p class="text-emerald-100/60 max-w-2xl mx-auto md:mx-0 text-lg">
                        {{ $t('Track your biological energy to understand how your sleep and mood affect your financial decisions and time management.') }}
                    </p>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- Logger Form -->
                    <div class="w-full lg:w-1/3">
                        <div class="bg-black/60 backdrop-blur-3xl border border-white/5 rounded-3xl p-6 md:p-8 relative overflow-hidden shadow-[0_0_50px_rgba(16,185,129,0.05)]">
                            <div class="absolute inset-0 bg-gradient-to-tr from-emerald-500/5 to-transparent -z-10"></div>
                            
                            <h2 class="text-xl font-bold text-white mb-6">{{ $t('Daily Bio-Log') }}</h2>
                            
                            <form @submit.prevent="submitLog" class="space-y-6">
                                <!-- Custom Date Selector -->
                                <div>
                                    <label class="block text-sm font-bold text-white/50 mb-3">{{ $t('Date') }}</label>
                                    <div class="grid grid-cols-3 gap-2">
                                        <select v-model.number="selectedDay" class="bg-white/5 border border-white/10 rounded-xl px-2 py-3 text-white text-center font-mono focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="d in days" :key="d" :value="d" class="bg-gray-900">{{ d }}</option>
                                        </select>
                                        <select v-model.number="selectedMonth" class="bg-white/5 border border-white/10 rounded-xl px-2 py-3 text-white text-center focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="m in months" :key="m.v" :value="m.v" class="bg-gray-900">{{ m.ar }}</option>
                                        </select>
                                        <select v-model.number="selectedYear" class="bg-white/5 border border-white/10 rounded-xl px-2 py-3 text-white text-center font-mono focus:ring-emerald-500 focus:border-emerald-500 appearance-none cursor-pointer">
                                            <option v-for="y in years" :key="y" :value="y" class="bg-gray-900">{{ y }}</option>
                                        </select>
                                    </div>
                                    <p class="text-emerald-400/60 text-xs font-mono mt-1 text-center">{{ formattedDate }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-white/50 mb-2 flex justify-between">
                                        <span>{{ $t('Sleep Hours') }}</span>
                                        <span class="text-emerald-400 font-mono">{{ form.sleep_hours }} h</span>
                                    </label>
                                    <input type="range" v-model.number="form.sleep_hours" min="0" max="14" step="0.5" class="w-full accent-emerald-500" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-white/50 mb-2 flex justify-between">
                                        <span>{{ $t('Mood Score') }}</span>
                                        <span class="text-emerald-400 font-mono">{{ form.mood_score }}/10 ({{ getMoodEmoji(form.mood_score) }})</span>
                                    </label>
                                    <input type="range" v-model.number="form.mood_score" min="1" max="10" step="1" class="w-full accent-emerald-500" />
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-white/50 mb-2">{{ $t('Notes (Optional)') }}</label>
                                    <textarea v-model="form.notes" rows="2" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:ring-emerald-500 focus:border-emerald-500" placeholder="e.g. Worked out, stressed at work..."></textarea>
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
                        <div class="bg-gradient-to-br from-emerald-950/40 to-black border border-emerald-500/20 rounded-3xl p-6 flex flex-col min-h-[250px]">
                            <h2 class="text-lg font-bold text-emerald-400 flex items-center gap-2 mb-4">
                                <span class="text-2xl">🧠</span> {{ $t('Neural Health Analysis') }}
                            </h2>
                            <button v-if="!aiAnalysis && !isAnalyzing" @click="getAnalysis" class="w-full py-4 rounded-xl border border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/10 transition font-bold tracking-wider mb-4">
                                {{ $t('Diagnose My Patterns') }}
                            </button>
                            <div v-if="isAnalyzing" class="my-auto text-center text-emerald-500/50 animate-pulse font-mono tracking-widest text-sm">
                                {{ $t('Processing bio-data...') }}
                            </div>
                            <div v-if="aiAnalysis" class="text-emerald-50/80 leading-relaxed text-sm bidi-plaintext custom-scroll overflow-y-auto pr-2">
                                {{ aiAnalysis }}
                            </div>
                        </div>

                        <!-- Logs History -->
                        <div class="bg-black/40 border border-white/5 rounded-3xl p-6 flex-1 max-h-[400px] flex flex-col">
                            <h2 class="text-lg font-bold text-white mb-4">{{ $t('Recent Logs') }}</h2>
                            <div class="space-y-3 overflow-y-auto custom-scroll pr-2 flex-1">
                                <div v-for="log in logs" :key="log.id" class="p-4 bg-white/5 border border-white/5 rounded-2xl flex flex-col md:flex-row justify-between items-start md:items-center gap-4 hover:bg-white/10 transition">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-xl flex items-center justify-center font-bold text-lg"
                                             :class="log.mood_score >= 7 ? 'bg-emerald-500/20 text-emerald-400' : (log.mood_score <= 4 ? 'bg-rose-500/20 text-rose-400' : 'bg-blue-500/20 text-blue-400')">
                                            {{ log.mood_score }}
                                        </div>
                                        <div>
                                            <p class="text-white font-bold">{{ log.log_date }}</p>
                                            <p class="text-xs text-white/40 font-mono">{{ $t('Sleep') }}: {{ log.sleep_hours }}h</p>
                                        </div>
                                    </div>
                                    <div class="flex-1 md:text-right">
                                        <p class="text-white/60 text-sm line-clamp-2 md:line-clamp-1 italic text-right rtl:text-left">{{ log.notes || $t('No notes provided.') }}</p>
                                    </div>
                                </div>
                                <p v-if="logs.length === 0" class="text-center text-white/30 py-8">{{ $t('No biological logs found.') }}</p>
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
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
</style>
