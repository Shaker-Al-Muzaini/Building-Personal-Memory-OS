<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
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
    <Head title="محلل القرارات الذكي — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-3xl text-text-main tracking-tight flex items-center gap-3">
                <span class="w-12 h-12 rounded-2xl bg-accent/20 flex items-center justify-center text-3xl border border-accent/20 shadow-lg">⚖️</span>
                {{ $t('Decision Neural Lab') }}
            </h2>
        </template>

        <div class="py-12 bg-surface min-h-screen text-text-main relative overflow-hidden" dir="rtl">
            <!-- Cinematic Vignette & Grain -->
            <div class="absolute inset-0 z-0 pointer-events-none opacity-10 bg-[radial-gradient(ellipse_at_center,_transparent_0%,_var(--c-bg)_100%)]"></div>
            <div class="absolute top-0 left-0 w-full h-[500px] bg-gradient-to-b from-accent/5 to-transparent pointer-events-none"></div>

            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-16 relative z-10">

                <!-- Decision Input Portal: The Dark Room -->
                <div class="relative group mt-10">
                    <div class="absolute -inset-1 bg-gradient-to-r from-accent/20 to-purple-600/20 rounded-[40px] blur-xl opacity-50 transition duration-1000"></div>
                    <div class="relative bg-glass-bg border border-glass-border p-12 md:p-20 rounded-[40px] shadow-2xl overflow-hidden flex flex-col items-center justify-center min-h-[400px]">
                        
                        <div class="w-full max-w-4xl text-center">
                            <span class="text-accent/50 tracking-[0.5em] uppercase text-xs font-black mb-6 block">{{ $t('The Dark Room') }}</span>
                            <h3 class="text-3xl md:text-5xl font-black text-text-main mb-6 tracking-tighter">{{ $t('I am confused... help me!') }}</h3>
                            <p class="text-text-muted mb-12 text-lg md:text-xl font-light leading-relaxed max-w-2xl mx-auto">{{ $t('Describe the crossroad you are standing at, and let the AI analyze the neural weights of your choices.') }}</p>
                            
                            <form @submit.prevent="saveProblem" class="space-y-8 w-full">
                                <div class="relative">
                                    <textarea
                                        v-model="decisionForm.problem"
                                        class="w-full bg-transparent border-b-2 border-border-main px-4 py-6 text-center text-text-main focus:ring-0 focus:border-accent text-2xl md:text-3xl font-light placeholder:text-text-muted min-h-[100px] transition-all resize-none bidi-plaintext"
                                        :placeholder="$t('Example: Should I leave my current job...?')"
                                        required
                                        dir="auto"
                                    ></textarea>
                                </div>
                                
                                <div class="flex justify-center">
                                    <button
                                        type="submit"
                                        :disabled="decisionForm.processing"
                                        class="bg-input-bg border border-border-main text-text-main px-12 py-5 rounded-full font-black text-lg hover:bg-card-hover hover:border-accent active:scale-95 transition-all disabled:opacity-50 flex items-center gap-4 shadow-2xl"
                                    >
                                        <span v-if="decisionForm.processing" class="animate-spin w-6 h-6 border-4 border-text-main border-t-transparent rounded-full block"></span>
                                        <span v-else class="text-accent text-2xl">⚡</span>
                                        <span>{{ decisionForm.processing ? $t('Analyzing Neural Weights...') : $t('Initialize Socratic Analysis') }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Archives/Pending Filter Tabs -->
                <div class="flex justify-center gap-6 mb-12 pt-10">
                    <button 
                        @click="activeFilter = 'pending'"
                        class="px-8 py-3 rounded-full font-black text-sm uppercase tracking-widest transition-all duration-500"
                        :class="activeFilter === 'pending' ? 'bg-accent/20 text-accent border border-accent/40 shadow-lg' : 'bg-input-bg text-text-muted hover:text-text-main border border-border-subtle'"
                    >
                        {{ $t('Pending Contexts') }} ({{ props.decisions.filter(d => !d.final_decision).length }})
                    </button>
                    <button 
                        @click="activeFilter = 'sealed'"
                        class="px-8 py-3 rounded-2xl font-black text-sm uppercase tracking-widest transition-all duration-500"
                        :class="activeFilter === 'sealed' ? 'bg-accent/20 text-accent border border-accent/40 shadow-lg' : 'bg-input-bg text-text-muted hover:text-text-main border border-border-subtle'"
                    >
                        {{ $t('Archives of Wisdom') }} ({{ props.decisions.filter(d => d.final_decision).length }})
                    </button>
                </div>

                <!-- Decisions History -->
                <TransitionGroup name="list" tag="div" class="space-y-12 pb-32">
                    <div v-for="decision in filteredDecisions" :key="decision.id" 
                        class="bg-glass-bg border border-glass-border rounded-[50px] p-10 relative group hover:border-accent/30 transition-all duration-700 shadow-2xl"
                    >
                        <button @click="deleteDecision(decision.id)" class="absolute top-8 left-8 text-text-muted hover:text-red-500 transition-all active:scale-75 opacity-0 group-hover:opacity-100">
                            <span class="text-2xl">✖</span>
                        </button>
                        
                        <!-- Problem Header -->
                        <div class="mb-10 text-center max-w-4xl mx-auto">
                            <span class="text-[10px] uppercase tracking-[0.4em] font-black text-accent/60 mb-3 block">Neural Input #{{ decision.id }}</span>
                            <h4 class="text-3xl font-black text-text-main mb-4 leading-tight bidi-plaintext">{{ decision.problem }}</h4>
                            <div class="flex justify-center items-center gap-4 text-xs text-text-muted font-mono">
                                <span>{{ new Date(decision.created_at).toLocaleDateString() }}</span>
                                <span class="w-1 h-1 rounded-full bg-border-subtle"></span>
                                <span class="uppercase">{{ decision.final_decision ? $t('Sealed') : $t('Pending') }}</span>
                            </div>
                        </div>
                        
                        <!-- Visual Analysis Engine -->
                        <div v-if="decision.ai_advice" class="space-y-8">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
                                <!-- OR Divider -->
                                <div class="hidden md:flex absolute inset-0 items-center justify-center pointer-events-none">
                                    <div class="w-[1px] h-full bg-gradient-to-b from-transparent via-border-main to-transparent"></div>
                                    <div class="w-12 h-12 rounded-full bg-surface border border-border-subtle flex items-center justify-center text-[10px] font-black text-text-muted shadow-2xl z-10">VS</div>
                                </div>

                                <!-- Pros -->
                                <div class="bg-green-500/[0.03] border border-green-500/10 p-8 rounded-[35px] space-y-4">
                                    <h5 class="text-green-500 font-black text-xs uppercase tracking-widest flex items-center gap-2 mb-4 text-right">
                                        <span class="w-2 h-2 rounded-full bg-green-500 shadow-[0_0_10px_rgba(34,197,94,1)]"></span>
                                        {{ $t('Potential Rewards') }}
                                    </h5>
                                    <ul class="space-y-4 text-right">
                                        <li v-for="pro in parseAdvice(decision.ai_advice).pros" :key="pro" class="flex items-start justify-end gap-3 text-text-main font-light leading-relaxed bidi-plaintext">
                                            <span>{{ pro }}</span>
                                            <span class="text-green-500/60 mt-1">✓</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Cons -->
                                <div class="bg-red-500/[0.03] border border-red-500/10 p-8 rounded-[35px] space-y-4">
                                    <h5 class="text-red-500 font-black text-xs uppercase tracking-widest flex items-center gap-2 mb-4 text-right">
                                        <span class="w-2 h-2 rounded-full bg-red-500 shadow-[0_0_10px_rgba(239,68,68,1)]"></span>
                                        {{ $t('Identified Risks') }}
                                    </h5>
                                    <ul class="space-y-4 text-right">
                                        <li v-for="con in parseAdvice(decision.ai_advice).cons" :key="con" class="flex items-start justify-end gap-3 text-text-main font-light leading-relaxed bidi-plaintext">
                                            <span>{{ con }}</span>
                                            <span class="text-red-500/60 mt-1">⚠</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Suggestion & Analysis -->
                            <div class="bg-accent/[0.03] border border-accent/10 p-8 rounded-[35px] text-right flex flex-col md:flex-row gap-10 items-center">
                                <!-- Strategic Score Gauge -->
                                <div class="relative w-32 h-32 flex-shrink-0 group/score">
                                    <svg class="w-full h-full transform -rotate-90">
                                        <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" class="text-border-subtle" />
                                        <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" 
                                            :stroke-dasharray="364.4" 
                                            :stroke-dashoffset="364.4 - (364.4 * (parseAdvice(decision.ai_advice).score || 0) / 100)"
                                            class="text-accent transition-all duration-[2000ms] ease-out" 
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                                        <span class="text-2xl font-black text-text-main">{{ parseAdvice(decision.ai_advice).score || 0 }}%</span>
                                        <span class="text-[8px] font-black text-accent uppercase tracking-widest">{{ $t('Logic Score') }}</span>
                                    </div>
                                    <!-- Tooltip logic explanation -->
                                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 whitespace-nowrap bg-surface border border-border-main px-3 py-1 rounded-full text-[8px] text-text-muted opacity-0 group-hover/score:opacity-100 transition-opacity">
                                        {{ $t('Based on Money & Tasks') }}
                                    </div>
                                </div>

                                <div class="flex-1">
                                    <div class="flex items-center justify-end gap-3 mb-4">
                                        <h5 class="text-accent font-black text-xs uppercase tracking-widest">{{ $t('Strategic Insight') }}</h5>
                                        <span class="text-2xl">🔮</span>
                                    </div>
                                    <p class="text-text-main font-light italic leading-relaxed mb-6 bidi-plaintext">{{ parseAdvice(decision.ai_advice).analysis }}</p>
                                    <div class="p-5 bg-accent/10 border border-accent/20 rounded-2xl text-center">
                                        <span class="text-[10px] text-accent font-black block mb-1 uppercase tracking-widest opacity-60">{{ $t('Suggested Action') }}</span>
                                        <p class="text-accent font-black text-xl italic bidi-plaintext">"{{ parseAdvice(decision.ai_advice).suggestion }}"</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Decision Finalization Box -->
                        <div class="mt-12 pt-10 border-t border-border-subtle">
                            <div v-if="!decision.final_decision" class="max-w-2xl mx-auto space-y-6">
                                <label class="block text-center text-text-muted font-black uppercase tracking-[0.2em] text-xs bidi-plaintext">{{ $t('Seal conflict in your memory') }}</label>
                                <div class="flex gap-4 p-2 bg-input-bg border border-border-subtle rounded-[30px] shadow-inner">
                                    <button @click="finalizeDecision(decision.id, choices[decision.id])" class="bg-accent hover:bg-accent/80 text-white px-8 py-4 rounded-[22px] font-black shadow-xl transition-all active:scale-95 group/btn">
                                        {{ $t('Seal It') }}
                                        <span class="inline-block group-hover/btn:translate-x-1 transition-transform mr-1 text-xs">⚡</span>
                                    </button>
                                    <input 
                                        type="text" 
                                        v-model="choices[decision.id]" 
                                        :placeholder="$t('My Final Choice is...')" 
                                        class="flex-1 bg-transparent border-none rounded-full px-6 py-4 text-text-main focus:ring-0 text-lg font-light transition-all bidi-plaintext" 
                                        @keyup.enter="finalizeDecision(decision.id, choices[decision.id])" 
                                        dir="auto"
                                    />
                                </div>
                            </div>
                            
                            <!-- Decision Seal -->
                            <div v-else class="flex justify-center animate-in fade-in zoom-in duration-1000">
                                <div class="relative py-8 px-16 bg-gradient-to-r from-accent/20 to-purple-500/20 border border-accent/40 rounded-[30px] shadow-[0_0_50px_rgba(6,155,255,0.1)] overflow-hidden group">
                                    <div class="absolute inset-x-0 bottom-0 h-[2px] bg-accent"></div>
                                    <div class="relative z-10 flex flex-col items-center gap-1 text-center">
                                        <span class="text-[10px] font-black text-accent uppercase tracking-[0.5em] mb-2">{{ $t('Neural Decision Sealed') }}</span>
                                        <p class="text-text-main text-2xl font-black bidi-plaintext">{{ decision.final_decision }}</p>
                                    </div>
                                    <!-- Seal Icon Bg -->
                                    <span class="absolute -right-8 -bottom-8 text-8xl opacity-10 blur-sm transform rotate-12 group-hover:rotate-0 transition-transform duration-1000">🖋️</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Empty State -->
                    <div v-if="filteredDecisions.length === 0" class="text-center py-32" key="empty">
                        <div class="w-32 h-32 bg-input-bg rounded-full flex items-center justify-center mx-auto mb-8 text-6xl shadow-2xl border border-border-subtle text-center">
                            {{ activeFilter === 'pending' ? '⚖️' : '📚' }}
                        </div>
                        <h4 class="text-2xl font-black text-text-main mb-2 text-center bidi-plaintext">
                            {{ activeFilter === 'pending' ? $t('No decisions are waiting in the lab.') : $t('Your archives are currently empty.') }}
                        </h4>
                        <p class="text-text-muted font-light text-center bidi-plaintext">
                            {{ activeFilter === 'pending' ? $t('Use advisor to start analysis.') : $t('Archives empty desc') }}
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
