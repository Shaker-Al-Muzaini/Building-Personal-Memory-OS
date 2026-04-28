<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';

const props = defineProps({
    people: Array,
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const response = await axios.post(route('people.generate-plan'));
        aiPlanText.value = response.data.plan;
    } catch (e) {
        aiPlanText.value = trans("Error connecting to AI advisor. Please try again later.");
    } finally {
        isGeneratingPlan.value = false;
    }
};

const personForm = useForm({
    name: '',
    relation: '',
    importance: 'medium',
    gifts_notes: '',
});

const addPerson = () => {
    personForm.post(route('people.store'), {
        preserveScroll: true,
        onSuccess: () => personForm.reset(),
    });
};

const touchPerson = (id) => {
    router.patch(route('people.touch', id), {}, { preserveScroll: true });
};

const deletePerson = async (id) => {
    const result = await Swal.fire({
        title: trans('Are you sure?'),
        text: trans("You won't be able to revert this!"),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#4b5563',
        confirmButtonText: trans('Yes, delete it!'),
        cancelButtonText: trans('Cancel'),
        background: '#0f172a',
        color: '#f1f5f9',
        customClass: { popup: 'border border-white/5 rounded-[30px] shadow-2xl backdrop-blur-xl' }
    });

    if (result.isConfirmed) {
        router.delete(route('people.delete', id), { preserveScroll: true });
    }
};

const individualAdvice = ref({});
const isLoadingAdvice = ref({});

const getPersonAdvice = async (id) => {
    isLoadingAdvice.value[id] = true;
    try {
        const response = await axios.get(route('people.advice', id));
        individualAdvice.value[id] = response.data.advice;
    } catch (e) {
        individualAdvice.value[id] = "Neural logic error. Just say Hi!";
    } finally {
        isLoadingAdvice.value[id] = false;
    }
};
</script>

<template>
    <Head :title="`${$t('People Memory')} — Personal Memory`" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-8">
            
            <!-- AI RELATIONS SUPPORT (Compact Style like Dashboard) -->
            <div class="ai-briefing-compact n-card">
                <div class="flex-shrink-0 flex items-center gap-3 border-r border-slate-200 dark:border-slate-700 pr-6">
                    <div class="w-10 h-10 rounded-full bg-blue-500/10 flex items-center justify-center text-xl">👤</div>
                    <div>
                        <h2 class="n-h3 leading-none">{{ $t('Social Bond Audit') }}</h2>
                        <p class="text-[9px] font-bold text-blue-500 uppercase mt-1">{{ $t('Neural Analysis Active') }}</p>
                    </div>
                </div>
                
                <div class="flex-1 min-w-0">
                    <p class="n-p truncate italic text-blue-600/80 dark:text-blue-400/80 bidi-plaintext">
                        {{ $t('Let the AI scan your social graph and suggest synchronization protocols for your most valuable human connections.') }}
                    </p>
                </div>

                <button 
                    @click="generatePlan" 
                    :disabled="isGeneratingPlan"
                    :class="['flex-shrink-0 w-10 h-10 rounded-xl flex items-center justify-center transition-all shadow-lg', isGeneratingPlan ? 'bg-slate-500/10' : 'bg-blue-600 hover:bg-blue-500 text-white shadow-blue-500/20']"
                >
                    <span v-if="isGeneratingPlan" class="animate-spin w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full"></span>
                    <span v-else>⚡</span>
                </button>
            </div>

            <Transition name="fade">
                <div v-if="aiPlanText" class="n-card border-blue-500/20 bg-blue-500/5 p-6 lg:p-8 italic n-p text-lg leading-relaxed bidi-plaintext">
                    "{{ aiPlanText }}"
                </div>
            </Transition>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                
                <!-- Add Person Form (4 columns) -->
                <div class="xl:col-span-4">
                    <div class="n-card p-6 lg:p-8 sticky top-24">
                        <div class="flex items-center gap-3 mb-8">
                            <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center text-xl shadow-inner">👤</div>
                            <h2 class="n-h2 text-xl">{{ $t('Protocol Enrollment') }}</h2>
                        </div>
                        
                        <form @submit.prevent="addPerson" class="space-y-5">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Human Name') }}</label>
                                <input v-model="personForm.name" type="text" class="n-input w-full" :placeholder="$t('Full Name...')" required />
                            </div>
                            
                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Connection Type') }}</label>
                                <input v-model="personForm.relation" type="text" class="n-input w-full" :placeholder="$t('e.g. Best Friend, Mentor...')" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Strategic Value') }}</label>
                                <select v-model="personForm.importance" class="n-input w-full appearance-none">
                                    <option value="high">{{ $t('Importance_High') }}</option>
                                    <option value="medium">{{ $t('Importance_Medium') }}</option>
                                    <option value="low">{{ $t('Importance_Low') }}</option>
                                </select>
                            </div>

                            <div class="space-y-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Neural Observation Notes') }}</label>
                                <textarea v-model="personForm.gifts_notes" class="n-input w-full min-h-[100px]" :placeholder="$t('Gifts, interests, or shared memories...')"></textarea>
                            </div>
                            
                            <button type="submit" :disabled="personForm.processing" class="w-full n-btn n-btn-primary">
                                {{ $t('Enroll in Memory') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- List of People (8 columns) -->
                <div class="xl:col-span-8 space-y-6">
                    <div class="flex items-center justify-between px-2">
                        <h3 class="n-h2 text-2xl">{{ $t('Social Archive') }}</h3>
                        <span class="px-3 py-1 rounded-full bg-blue-500/10 text-blue-500 text-[10px] font-black uppercase border border-blue-500/20">{{ people.length }} {{ $t('Active Targets') }}</span>
                    </div>
                    
                    <div v-if="people.length === 0" class="n-card py-32 text-center opacity-40 italic">
                        {{ $t('No human nodes detected in archive.') }}
                    </div>
                    
                    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div v-for="person in people" :key="person.id" class="n-card p-6 relative group">
                            
                            <button @click="deletePerson(person.id)" class="absolute top-3 end-3 text-slate-300 hover:text-rose-500 transition-all opacity-0 group-hover:opacity-100">✕</button>
                            
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-xl font-black text-slate-400 shadow-inner group-hover:scale-105 transition-transform duration-500">
                                        {{ person.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div>
                                        <h4 class="n-h3 text-lg">{{ person.name }}</h4>
                                        <span class="text-[9px] font-black text-blue-500 uppercase tracking-widest opacity-80">{{ person.relation }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-[8px] font-black text-slate-400 uppercase block tracking-widest">{{ $t('Bond') }}</span>
                                    <span :class="['text-lg font-black', person.bond_strength > 70 ? 'text-emerald-500' : (person.bond_strength > 30 ? 'text-orange-500' : 'text-slate-400')]">
                                        {{ Math.round(person.bond_strength) }}%
                                    </span>
                                </div>
                            </div>

                            <!-- Bond Progress Bar -->
                            <div class="w-full h-1 bg-slate-100 dark:bg-slate-800 rounded-full mb-4 overflow-hidden shadow-inner">
                                <div 
                                    :style="{ width: person.bond_strength + '%' }" 
                                    :class="['h-full transition-all duration-1000', 
                                        person.bond_strength > 70 ? 'bg-emerald-500' : (person.bond_strength > 30 ? 'bg-blue-500' : 'bg-slate-400')]"
                                ></div>
                            </div>
                            
                            <p class="text-xs n-p bidi-plaintext line-clamp-2 italic mb-4 min-h-[32px] opacity-70">
                                "{{ person.gifts_notes || $t('No telemetry data.') }}"
                            </p>

                            <!-- AI Advice (Compact) -->
                            <div v-if="individualAdvice[person.id]" class="mb-4 p-3 rounded-xl bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/50">
                                <p class="text-[10px] text-blue-600 dark:text-blue-400 font-bold bidi-plaintext">🤖 {{ individualAdvice[person.id] }}</p>
                            </div>
                            
                            <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                                <button 
                                    @click="getPersonAdvice(person.id)" 
                                    :disabled="isLoadingAdvice[person.id]"
                                    class="text-[9px] font-black uppercase text-blue-500 hover:text-blue-600 flex items-center gap-2"
                                >
                                    <span v-if="isLoadingAdvice[person.id]" class="animate-spin w-3 h-3 border-2 border-blue-500 border-t-transparent rounded-full"></span>
                                    <span v-else>🧠</span>
                                    <span>{{ isLoadingAdvice[person.id] ? $t('Thinking...') : $t('Advice') }}</span>
                                </button>
                                
                                <button @click="touchPerson(person.id)" class="px-4 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-[9px] uppercase font-black text-slate-500 hover:bg-blue-500/10 hover:text-blue-500 transition-all border border-transparent hover:border-blue-500/20">
                                    {{ $t('Touch Protocol') }}
                                </button>
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
.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
.slide-up-enter-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from { opacity: 0; transform: translateY(10px); }
</style>
