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
        <template #header>
            <div class="backdrop-blur-xl bg-slate-900/50 border-b border-white/5 px-6 py-8">
                <div class="max-w-[1600px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-2xl shadow-xl shadow-indigo-500/20 animate-float">
                            ⚖️
                        </div>
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-white uppercase">{{ $t('Decision Neural Lab') }}</h2>
                            <p class="text-[10px] text-indigo-400 font-bold tracking-widest uppercase opacity-70 mt-1">✧ {{ $t('Logic_Synthesis.v4') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-slate-950 text-slate-200 py-12 relative overflow-hidden">
            <!-- Background Neural Glow -->
            <div class="absolute top-[-10%] left-[-10%] w-[40%] h-[40%] bg-indigo-500/10 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[-10%] right-[-10%] w-[40%] h-[40%] bg-purple-500/10 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-16 relative z-10">

                <!-- Decision Input Portal: The Dark Room -->
                <div class="relative group mt-10">
                    <div class="absolute -inset-1 bg-gradient-to-r from-indigo-500/30 to-purple-600/30 rounded-[40px] blur-2xl opacity-20 transition duration-1000 group-hover:opacity-40"></div>
                    <div class="neural-card-premium p-12 md:p-20 flex flex-col items-center justify-center min-h-[450px] overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-b from-indigo-500/[0.03] to-transparent pointer-events-none"></div>
                        
                        <div class="w-full max-w-4xl text-center relative z-10">
                            <span class="text-indigo-400/60 tracking-[0.6em] uppercase text-[10px] font-black mb-6 block animate-pulse">{{ $t('NEURAL_SENSING_ACTIVE') }}</span>
                            <h3 class="text-4xl md:text-6xl font-black text-white mb-6 tracking-tighter leading-none">{{ $t('I am confused... help me!') }}</h3>
                            <p class="text-slate-400 mb-12 text-lg md:text-xl font-medium leading-relaxed max-w-2xl mx-auto opacity-80">{{ $t('Describe the crossroad you are standing at, and let the AI analyze the neural weights of your choices.') }}</p>
                            
                            <form @submit.prevent="saveProblem" class="space-y-10 w-full">
                                <div class="relative">
                                    <textarea
                                        v-model="decisionForm.problem"
                                        class="w-full bg-transparent border-b-2 border-white/10 px-4 py-8 text-center text-white focus:ring-0 focus:border-indigo-500 text-2xl md:text-4xl font-light placeholder:text-slate-700 min-h-[120px] transition-all resize-none bidi-plaintext selection:bg-indigo-500/30"
                                        :placeholder="$t('Example: Should I leave my current job...?')"
                                        required
                                        dir="auto"
                                    ></textarea>
                                </div>
                                
                                <div class="flex justify-center">
                                    <button
                                        type="submit"
                                        :disabled="decisionForm.processing"
                                        class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-indigo-600 to-purple-600 px-16 py-6 text-xl shadow-indigo-500/20"
                                    >
                                        <span v-if="decisionForm.processing" class="animate-spin w-6 h-6 border-4 border-white border-t-transparent rounded-full block"></span>
                                        <span v-else class="text-2xl">⚡</span>
                                        <span>{{ decisionForm.processing ? $t('Analyzing Weights...') : $t('Initialize Socratic Analysis') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Archives/Pending Filter Tabs -->
                <div class="flex justify-center gap-4 mb-12 pt-10">
                    <button 
                        @click="activeFilter = 'pending'"
                        :class="['px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all duration-500 border',
                            activeFilter === 'pending' ? 'bg-indigo-600/20 text-indigo-400 border-indigo-500/40 shadow-lg shadow-indigo-500/10' : 'bg-slate-900/50 text-slate-500 border-white/5 hover:text-slate-300']"
                    >
                        {{ $t('Pending Contexts') }} ({{ props.decisions.filter(d => !d.final_decision).length }})
                    </button>
                    <button 
                        @click="activeFilter = 'sealed'"
                        :class="['px-10 py-4 rounded-2xl font-black text-xs uppercase tracking-widest transition-all duration-500 border',
                            activeFilter === 'sealed' ? 'bg-purple-600/20 text-purple-400 border-purple-500/40 shadow-lg shadow-purple-500/10' : 'bg-slate-900/50 text-slate-500 border-white/5 hover:text-slate-300']"
                    >
                        {{ $t('Archives of Wisdom') }} ({{ props.decisions.filter(d => d.final_decision).length }})
                    </button>
                </div>

                <!-- Decisions History -->
                <TransitionGroup name="list" tag="div" class="space-y-12 pb-32">
                    <div v-for="decision in filteredDecisions" :key="decision.id" 
                        class="neural-card-premium p-10 relative group hover:border-indigo-500/30 transition-all duration-700 shadow-2xl"
                    >
                        <button @click="deleteDecision(decision.id)" class="absolute top-8 inset-inline-start-8 w-10 h-10 rounded-xl bg-slate-900/50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition-all active:scale-75 opacity-0 group-hover:opacity-100 border border-white/5 shadow-lg">
                            <span class="text-xl">✕</span>
                        </button>
                        
                        <!-- Problem Header -->
                        <div class="mb-12 text-center max-w-4xl mx-auto">
                            <span class="text-[9px] uppercase tracking-[0.6em] font-black text-indigo-400 mb-4 block opacity-60">NEURAL_TRACE #{{ decision.id }}</span>
                            <h4 class="text-4xl font-black text-white mb-6 leading-tight bidi-plaintext tracking-tight">{{ decision.problem }}</h4>
                            <div class="flex justify-center items-center gap-4 text-[10px] text-slate-500 font-bold uppercase tracking-widest">
                                <span class="px-3 py-1 rounded-lg bg-white/5 border border-white/5">{{ new Date(decision.created_at).toLocaleDateString() }}</span>
                                <span class="px-3 py-1 rounded-lg bg-white/5 border border-white/5" :class="decision.final_decision ? 'text-purple-400 border-purple-500/20' : 'text-indigo-400 border-indigo-500/20'">{{ decision.final_decision ? $t('Sealed') : $t('Pending Analysis') }}</span>
                            </div>
                        </div>
                        
                        <!-- Visual Analysis Engine -->
                        <div v-if="decision.ai_advice" class="space-y-10 relative">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
                                <!-- OR Divider -->
                                <div class="hidden md:flex absolute inset-0 items-center justify-center pointer-events-none z-20">
                                    <div class="w-[1px] h-full bg-gradient-to-b from-transparent via-white/10 to-transparent"></div>
                                    <div class="w-14 h-14 rounded-full bg-slate-900 border border-white/10 flex items-center justify-center text-[10px] font-black text-indigo-400 shadow-2xl shadow-indigo-500/20 backdrop-blur-xl">{{ $t('VS') }}</div>
                                </div>

                                <!-- Pros -->
                                <div class="bg-emerald-500/[0.02] border border-emerald-500/10 p-10 rounded-[40px] space-y-6 hover:bg-emerald-500/[0.04] transition-colors shadow-inner">
                                    <h5 class="text-emerald-500 font-black text-xs uppercase tracking-[0.2em] flex items-center gap-3 mb-6 text-start">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)] animate-pulse"></span>
                                        {{ $t('Potential Rewards') }}
                                    </h5>
                                    <ul class="space-y-5 text-start">
                                        <li v-for="pro in parseAdvice(decision.ai_advice).pros" :key="pro" class="flex items-start justify-start gap-4 text-slate-300 font-medium leading-relaxed bidi-plaintext text-start group/li">
                                            <span class="text-emerald-500/60 mt-1.5 text-sm group-hover/li:translate-x-1 transition-transform">✓</span>
                                            <span class="text-base">{{ pro }}</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Cons -->
                                <div class="bg-rose-500/[0.02] border border-rose-500/10 p-10 rounded-[40px] space-y-6 hover:bg-rose-500/[0.04] transition-colors shadow-inner">
                                    <h5 class="text-rose-500 font-black text-xs uppercase tracking-[0.2em] flex items-center gap-3 mb-6 text-start">
                                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_15px_rgba(244,63,94,0.8)] animate-pulse"></span>
                                        {{ $t('Identified Risks') }}
                                    </h5>
                                    <ul class="space-y-5 text-start">
                                        <li v-for="con in parseAdvice(decision.ai_advice).cons" :key="con" class="flex items-start justify-start gap-4 text-slate-300 font-medium leading-relaxed bidi-plaintext text-start group/li">
                                            <span class="text-rose-500/60 mt-1.5 text-sm group-hover/li:translate-x-1 transition-transform">⚠</span>
                                            <span class="text-base">{{ con }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Suggestion & Analysis -->
                            <div class="bg-indigo-500/[0.03] border border-indigo-500/10 p-10 rounded-[45px] text-start flex flex-col md:flex-row gap-12 items-center shadow-inner relative overflow-hidden group">
                                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <!-- Strategic Score Gauge -->
                                <div class="relative w-40 h-40 flex-shrink-0 group/score">
                                    <svg class="w-full h-full transform -rotate-90">
                                        <circle cx="80" cy="80" r="72" stroke="currentColor" stroke-width="12" fill="transparent" class="text-slate-900" />
                                        <circle cx="80" cy="80" r="72" stroke="currentColor" stroke-width="12" fill="transparent" 
                                            :stroke-dasharray="452.4" 
                                            :stroke-dashoffset="452.4 - (452.4 * (parseAdvice(decision.ai_advice).score || 0) / 100)"
                                            stroke-linecap="round"
                                            class="text-indigo-500 transition-all duration-[2000ms] ease-out drop-shadow-[0_0_10px_rgba(99,102,241,0.5)]" 
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-4xl font-black text-white tracking-tighter">{{ parseAdvice(decision.ai_advice).score || 0 }}%</span>
                                        <span class="text-[9px] font-black text-indigo-400 uppercase tracking-[0.2em] mt-1">{{ $t('Logic Score') }}</span>
                                    </div>
                                    <!-- Tooltip logic explanation -->
                                    <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap bg-slate-900 border border-white/10 px-4 py-2 rounded-xl text-[9px] font-black text-slate-500 opacity-0 group-hover/score:opacity-100 transition-all translate-y-2 group-hover:translate-y-0 shadow-2xl">
                                        {{ $t('Weighted Analysis Matrix') }}
                                    </div>
                                </div>

                                <div class="flex-1 relative z-10">
                                    <div class="flex items-center justify-start gap-4 mb-6 text-start">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-500/10 flex items-center justify-center text-xl shadow-inner border border-indigo-500/20">🔮</div>
                                        <h5 class="text-indigo-400 font-black text-xs uppercase tracking-[0.3em]">{{ $t('Strategic Insight') }}</h5>
                                    </div>
                                    <p class="text-slate-300 font-medium italic text-lg leading-relaxed mb-8 bidi-plaintext opacity-90">"{{ parseAdvice(decision.ai_advice).analysis }}"</p>
                                    <div class="p-8 bg-indigo-600/10 border border-indigo-500/30 rounded-[30px] text-center shadow-inner relative overflow-hidden group/box">
                                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/5 to-purple-500/5 -z-10"></div>
                                        <span class="text-[10px] text-indigo-400 font-black block mb-2 uppercase tracking-[0.4em] opacity-70">{{ $t('Suggested Protocol') }}</span>
                                        <p class="text-indigo-400 font-black text-2xl italic bidi-plaintext group-hover/box:scale-105 transition-transform duration-500">"{{ parseAdvice(decision.ai_advice).suggestion }}"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decision Finalization Box -->
                        <div class="mt-16 pt-12 border-t border-white/5 relative z-10">
                            <div v-if="!decision.final_decision" class="max-w-2xl mx-auto space-y-8">
                                <label class="block text-center text-slate-500 font-black uppercase tracking-[0.4em] text-[10px] bidi-plaintext">{{ $t('COMMIT_CHOICE_TO_MEMORY') }}</label>
                                <div class="flex gap-4 p-3 bg-slate-950/50 border border-white/5 rounded-[35px] shadow-2xl backdrop-blur-xl group/input transition-all focus-within:border-indigo-500/30">
                                    <button @click="finalizeDecision(decision.id, choices[decision.id])" class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-indigo-600 to-purple-600 px-10 py-5 text-base shadow-indigo-500/20 active:scale-95 group/btn shrink-0">
                                        {{ $t('Seal Protocol') }}
                                        <span class="inline-block group-hover/btn:translate-x-1 transition-transform ms-2 text-sm">⚡</span>
                                    </button>
                                    <input 
                                        type="text" 
                                        v-model="choices[decision.id]" 
                                        :placeholder="$t('My Final Choice is...')" 
                                        class="flex-1 bg-transparent border-none rounded-full px-8 py-5 text-white focus:ring-0 text-xl font-light transition-all bidi-plaintext placeholder:text-slate-800" 
                                        @keyup.enter="finalizeDecision(decision.id, choices[decision.id])" 
                                        dir="auto"
                                    />
                                </div>
                            </div>
                            
                            <!-- Decision Seal -->
                            <div v-else class="flex justify-center">
                                <div class="relative py-10 px-20 bg-gradient-to-r from-purple-600/10 to-indigo-600/10 border border-purple-500/30 rounded-[40px] shadow-2xl shadow-purple-500/10 overflow-hidden group hover:scale-[1.02] transition-all duration-700">
                                    <div class="absolute inset-0 bg-gradient-to-b from-purple-500/[0.05] to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                                    <div class="relative z-10 flex flex-col items-center gap-2 text-center">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-purple-500 animate-ping"></span>
                                            <span class="text-[10px] font-black text-purple-400 uppercase tracking-[0.6em]">{{ $t('Neural Path Sealed') }}</span>
                                        </div>
                                        <p class="text-white text-3xl font-black bidi-plaintext tracking-tight uppercase">{{ decision.final_decision }}</p>
                                    </div>
                                    <!-- Seal Icon Bg -->
                                    <span class="absolute -right-6 -bottom-6 text-9xl opacity-5 blur-[2px] transform rotate-12 group-hover:rotate-0 transition-all duration-1000 grayscale select-none pointer-events-none">🖋️</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredDecisions.length === 0" class="text-center py-40 relative z-10" key="empty">
                        <div class="w-40 h-40 bg-slate-900 rounded-[40px] flex items-center justify-center mx-auto mb-10 text-7xl shadow-2xl border border-white/5 relative group overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 to-transparent"></div>
                            <span class="relative z-10 group-hover:scale-110 transition-transform duration-500">{{ activeFilter === 'pending' ? '⚖️' : '📚' }}</span>
                        </div>
                        <h4 class="text-3xl font-black text-white mb-4 text-center bidi-plaintext tracking-tight uppercase">
                            {{ activeFilter === 'pending' ? $t('Void detected in Neural Lab') : $t('Your archives are currently empty.') }}
                        </h4>
                        <p class="text-slate-500 font-medium text-lg text-center bidi-plaintext max-w-md mx-auto opacity-70">
                            {{ activeFilter === 'pending' ? $t('No active conflicts awaiting synthesis. Description logic.') : $t('Archives empty desc') }}
                        </p>
                    </div>
                </TransitionGroup>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #062F69; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

.bidi-plaintext {
    unicode-bidi: plaintext;
    text-align: start;
}

.list-enter-active,
.list-leave-active {
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(50px) scale(0.98);
}
</style>
