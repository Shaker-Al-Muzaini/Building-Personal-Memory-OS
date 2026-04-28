<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';

const props = defineProps({
    decisions: Array,
});

const choices = ref({});
const activeFilter = ref('pending'); // 'pending' or 'sealed'

const filteredDecisions = computed(() => {
    if (activeFilter.value === 'sealed') {
        return props.decisions.filter(d => d.final_decision);
    }
    return props.decisions.filter(d => !d.final_decision);
});

const decisionForm = useForm({
    problem: ''
});

const saveProblem = () => {
    decisionForm.post(route('decisions.store'), {
        preserveScroll: true,
        onSuccess: () => decisionForm.reset(),
    });
};

const finalizeDecision = (id, choice) => {
    if (!choice) return;
    router.patch(route('decisions.finalize', id), { final_decision: choice }, { 
        preserveScroll: true,
        onSuccess: () => {
            choices.value[id] = '';
            Swal.fire({
                title: trans('Decision Sealed!'),
                text: trans('Your final choice has been recorded in your neural memory.'),
                icon: 'success',
                timer: 3000,
                showConfirmButton: false,
                background: 'var(--c-surface)',
                color: 'var(--c-text)',
                customClass: { popup: 'border border-glass-border rounded-[30px] shadow-2xl' }
            });
        }
    });
};

const deleteDecision = async (id) => {
    const result = await Swal.fire({
        title: trans('Are you sure?'),
        text: trans("This memory will be erased forever."),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#4b5563',
        confirmButtonText: trans('Delete'),
        cancelButtonText: trans('Keep'),
        background: 'var(--c-surface)',
        color: 'var(--c-text)',
    });

    if (result.isConfirmed) {
        router.delete(route('decisions.delete', id), { preserveScroll: true });
    }
};

const parseAdvice = (advice) => {
    try {
        return JSON.parse(advice);
    } catch (e) {
        return { 
            pros: [], 
            cons: [], 
            analysis: advice, 
            suggestion: trans('Analysis pending or in legacy format.') 
        };
    }
};
</script>

<template>
    <Head :title="`${$t('Decision Neural Lab')} — Personal Memory`" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1200px] mx-auto p-4 lg:p-6 space-y-8">
            
            <!-- HEADER SECTION -->
            <div class="n-card text-center p-8 lg:p-12 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 to-purple-500/5 -z-10"></div>
                <div class="w-16 h-16 rounded-2xl bg-blue-500/10 flex items-center justify-center text-3xl mx-auto mb-6 shadow-inner">
                    ⚖️
                </div>
                <h1 class="n-h1 text-3xl md:text-5xl mb-4">{{ $t('Decision Neural Lab') }}</h1>
                <p class="n-p text-lg max-w-2xl mx-auto opacity-70">
                    {{ $t('Describe the crossroad you are standing at, and let the AI analyze the neural weights of your choices.') }}
                </p>

                <form @submit.prevent="saveProblem" class="mt-8 max-w-3xl mx-auto space-y-4">
                    <textarea
                        v-model="decisionForm.problem"
                        class="n-input w-full text-center text-xl md:text-2xl min-h-[100px] py-6"
                        :placeholder="$t('Example: Should I leave my current job...?')"
                        required
                        dir="auto"
                    ></textarea>
                    <button
                        type="submit"
                        :disabled="decisionForm.processing"
                        class="n-btn n-btn-primary px-12 py-4 text-lg"
                    >
                        <span>{{ decisionForm.processing ? $t('Analyzing Weights...') : $t('Initialize Socratic Analysis') }}</span>
                    </button>
                </form>
            </div>

            <!-- FILTER TABS -->
            <div class="flex justify-center gap-2">
                <button 
                    @click="activeFilter = 'pending'"
                    :class="['n-btn gap-2', activeFilter === 'pending' ? 'n-btn-primary' : 'bg-slate-100 dark:bg-slate-800 text-slate-500']"
                >
                    {{ $t('Pending Contexts') }}
                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">{{ props.decisions.filter(d => !d.final_decision).length }}</span>
                </button>
                <button 
                    @click="activeFilter = 'sealed'"
                    :class="['n-btn gap-2', activeFilter === 'sealed' ? 'n-btn-primary bg-purple-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-500']"
                >
                    {{ $t('Archives of Wisdom') }}
                    <span class="text-[10px] bg-white/20 px-2 py-0.5 rounded-full">{{ props.decisions.filter(d => d.final_decision).length }}</span>
                </button>
            </div>

            <!-- HISTORY LIST -->
            <TransitionGroup name="fade" tag="div" class="space-y-6 pb-20">
                <div v-for="decision in filteredDecisions" :key="decision.id" class="n-card p-6 lg:p-8 relative group">
                    <button @click="deleteDecision(decision.id)" class="absolute top-4 end-4 text-slate-300 hover:text-rose-500 transition-all">
                        ✕
                    </button>

                    <!-- Problem Text -->
                    <div class="mb-8 max-w-4xl mx-auto text-center">
                        <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest block mb-2 opacity-60">{{ $t('Neural Trace') }} #{{ decision.id }}</span>
                        <h4 class="n-h2 text-2xl lg:text-3xl bidi-plaintext">{{ decision.problem }}</h4>
                        <p class="text-[9px] font-bold text-slate-400 mt-2 uppercase tracking-widest">
                            {{ new Date(decision.created_at).toLocaleDateString() }} // {{ decision.final_decision ? $t('Sealed') : $t('Pending Analysis') }}
                        </p>
                    </div>

                    <!-- AI Advice Content -->
                    <div v-if="decision.ai_advice" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Pros/Cons -->
                        <div class="lg:col-span-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="p-5 rounded-2xl bg-emerald-50/50 dark:bg-emerald-900/10 border border-emerald-100/50 dark:border-emerald-800/50">
                                <h5 class="text-emerald-500 font-black text-[10px] uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    {{ $t('Potential Rewards') }}
                                </h5>
                                <ul class="space-y-3">
                                    <li v-for="pro in parseAdvice(decision.ai_advice).pros" :key="pro" class="text-xs n-p bidi-plaintext flex items-start gap-2">
                                        <span class="text-emerald-500">✓</span> {{ pro }}
                                    </li>
                                </ul>
                            </div>

                            <div class="p-5 rounded-2xl bg-rose-50/50 dark:bg-rose-900/10 border border-rose-100/50 dark:border-rose-800/50">
                                <h5 class="text-rose-500 font-black text-[10px] uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                    {{ $t('Identified Risks') }}
                                </h5>
                                <ul class="space-y-3">
                                    <li v-for="con in parseAdvice(decision.ai_advice).cons" :key="con" class="text-xs n-p bidi-plaintext flex items-start gap-2">
                                        <span class="text-rose-500">⚠</span> {{ con }}
                                    </li>
                                </ul>
                            </div>

                            <!-- Insight Box -->
                            <div class="md:col-span-2 p-6 rounded-2xl bg-blue-50/50 dark:bg-blue-900/10 border border-blue-100/50 dark:border-blue-800/50">
                                <p class="text-sm font-medium italic n-p mb-4 bidi-plaintext">"{{ parseAdvice(decision.ai_advice).analysis }}"</p>
                                <div class="p-4 bg-white/50 dark:bg-slate-900/50 rounded-xl border border-blue-100 dark:border-blue-800 text-center">
                                    <span class="text-[9px] font-black text-blue-500 uppercase block mb-1 opacity-60">{{ $t('Suggested Protocol') }}</span>
                                    <p class="n-h3 text-blue-600 dark:text-blue-400">"{{ parseAdvice(decision.ai_advice).suggestion }}"</p>
                                </div>
                            </div>
                        </div>

                        <!-- Score Circle -->
                        <div class="lg:col-span-4 flex flex-col items-center justify-center p-6 border-s border-slate-100 dark:border-slate-800">
                            <div class="relative w-32 h-32 mb-4">
                                <svg class="w-full h-full transform -rotate-90">
                                    <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                    <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" 
                                        :stroke-dasharray="364.4" 
                                        :stroke-dashoffset="364.4 - (364.4 * (parseAdvice(decision.ai_advice).score || 0) / 100)"
                                        stroke-linecap="round"
                                        class="text-blue-500 transition-all duration-1000" 
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-3xl font-black text-slate-800 dark:text-white">{{ parseAdvice(decision.ai_advice).score || 0 }}%</span>
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Logic Score') }}</span>
                                </div>
                            </div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">{{ $t('Weighted Analysis Matrix') }}</p>
                        </div>
                    </div>

                    <!-- Final Decision Action -->
                    <div class="mt-8 pt-8 border-t border-slate-100 dark:border-slate-800">
                        <div v-if="!decision.final_decision" class="max-w-xl mx-auto">
                            <div class="flex gap-2 p-1 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden focus-within:ring-2 focus-within:ring-blue-500/20">
                                <input 
                                    type="text" 
                                    v-model="choices[decision.id]" 
                                    :placeholder="$t('My Final Choice is...')" 
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-sm bidi-plaintext p-3" 
                                    @keyup.enter="finalizeDecision(decision.id, choices[decision.id])" 
                                />
                                <button @click="finalizeDecision(decision.id, choices[decision.id])" class="n-btn n-btn-primary px-6 py-2">
                                    {{ $t('Seal Protocol') }}
                                </button>
                            </div>
                        </div>
                        <div v-else class="flex justify-center">
                            <div class="px-8 py-3 bg-purple-500/10 border border-purple-500/20 rounded-full flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                <p class="text-sm font-black text-purple-600 dark:text-purple-400 uppercase bidi-plaintext">{{ decision.final_decision }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredDecisions.length === 0" class="text-center py-32 n-p opacity-40">
                    <span class="text-5xl block mb-4">{{ activeFilter === 'pending' ? '⚖️' : '📚' }}</span>
                    <p class="text-xl font-bold uppercase tracking-widest">{{ $t('Void detected in Neural Lab') }}</p>
                </div>
            </TransitionGroup>
        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
.fade-enter-active, .fade-leave-active { transition: all 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(20px); }
</style>
