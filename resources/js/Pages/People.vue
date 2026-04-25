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
        <template #header>
            <div class="backdrop-blur-xl bg-slate-900/50 border-b border-white/5 px-6 py-8">
                <div class="max-w-[1600px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 flex items-center justify-center text-2xl shadow-xl shadow-orange-500/20 animate-float">
                            👥
                        </div>
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-white uppercase">{{ $t('People Memory') }}</h2>
                            <p class="text-[10px] text-orange-400 font-bold tracking-widest uppercase opacity-70 mt-1">✧ {{ $t('Social_Graph.v3') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-slate-950 text-slate-200 py-12 relative overflow-hidden">
            <!-- Background Social-Glow -->
            <div class="absolute top-[20%] left-[-10%] w-[40%] h-[40%] bg-orange-500/5 rounded-full blur-[120px] pointer-events-none"></div>
            <div class="absolute bottom-[20%] right-[-10%] w-[30%] h-[30%] bg-amber-500/5 rounded-full blur-[120px] pointer-events-none"></div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12 relative z-10">
                
                <!-- AI Relations Support Section -->
                <div class="neural-card-premium p-10 relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-orange-500/[0.03] to-transparent pointer-events-none"></div>
                    
                    <div class="relative z-10 flex flex-col lg:flex-row justify-between items-center gap-10">
                        <div class="max-w-2xl">
                            <span class="text-orange-400/60 tracking-[0.4em] uppercase text-[9px] font-black mb-3 block">{{ $t('Neural Analysis Active') }}</span>
                            <h3 class="text-4xl font-black text-white mb-4 tracking-tight uppercase">{{ $t('Social Bond Audit') }}</h3>
                            <p class="text-slate-400 text-lg font-medium opacity-80 leading-relaxed">{{ $t('Let the AI scan your social graph and suggest synchronization protocols for your most valuable human connections.') }}</p>
                        </div>
                        <button 
                            @click="generatePlan" 
                            :disabled="isGeneratingPlan"
                            class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-orange-600 to-amber-600 px-12 py-5 text-lg shadow-orange-500/20 shrink-0"
                        >
                            <span v-if="isGeneratingPlan" class="animate-spin w-6 h-6 border-4 border-white border-t-transparent rounded-full block"></span>
                            <span v-else class="flex items-center gap-3">
                                <span>⚡</span>
                                <span>{{ $t('Synthesize Social Strategy') }}</span>
                            </span>
                        </button>
                    </div>

                    <!-- AI Response Area -->
                    <Transition name="fade">
                        <div v-if="aiPlanText" class="mt-12 p-8 bg-slate-900/50 border border-white/5 rounded-[40px] shadow-inner relative group/advice overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent pointer-events-none"></div>
                            <div class="flex items-center gap-4 mb-6 relative z-10">
                                <div class="w-10 h-10 rounded-xl bg-orange-500/10 flex items-center justify-center text-xl">🤖</div>
                                <h4 class="font-black text-xl text-white uppercase tracking-tight">{{ $t('AI Strategic Directive') }}</h4>
                            </div>
                            <p class="text-slate-300 text-lg leading-relaxed italic bidi-plaintext relative z-10 selection:bg-orange-500/30">
                                {{ aiPlanText }}
                            </p>
                        </div>
                    </Transition>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
                    
                    <!-- Add Person Form (4 columns) -->
                    <div class="xl:col-span-4">
                        <div class="neural-card-premium p-8 sticky top-32">
                            <h3 class="text-xl font-black text-white uppercase tracking-widest mb-8 flex items-center gap-3">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-500 shadow-[0_0_15px_rgba(249,115,22,0.8)] animate-pulse"></span>
                                {{ $t('Protocol Enrollment') }}
                            </h3>
                            
                            <form @submit.prevent="addPerson" class="space-y-6">
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">{{ $t('Human Name') }}</label>
                                    <input v-model="personForm.name" type="text" class="neural-input-premium w-full text-white" :placeholder="$t('Full Name...')" required />
                                </div>
                                
                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">{{ $t('Connection Type') }}</label>
                                    <input v-model="personForm.relation" type="text" class="neural-input-premium w-full text-white" :placeholder="$t('e.g. Best Friend, Mentor...')" />
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">{{ $t('Strategic Value') }}</label>
                                    <select v-model="personForm.importance" class="neural-input-premium w-full text-white bg-slate-900 appearance-none">
                                        <option value="high">CORE (HIGH)</option>
                                        <option value="medium">STABLE (MEDIUM)</option>
                                        <option value="low">ORBITAL (LOW)</option>
                                    </select>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1">{{ $t('Neural Observation Notes') }}</label>
                                    <textarea v-model="personForm.gifts_notes" class="neural-input-premium w-full text-white h-32" :placeholder="$t('Gifts, interests, or shared memories...')"></textarea>
                                </div>
                                
                                <button type="submit" :disabled="personForm.processing" class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-orange-600 to-amber-600 w-full py-5 text-lg shadow-orange-500/20 active:scale-95 transition-all">
                                    {{ $t('Enroll in Memory') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- List of People (8 columns) -->
                    <div class="xl:col-span-8 space-y-6">
                        <div class="flex items-center justify-between mb-2 px-2">
                            <h3 class="text-2xl font-black text-white uppercase tracking-tight">{{ $t('Social Archive') }}</h3>
                            <span class="px-4 py-1 rounded-xl bg-slate-900 border border-white/5 text-[10px] font-black text-slate-500 tracking-[0.4em] shadow-inner uppercase">{{ people.length }} Active Nodes</span>
                        </div>
                        
                        <div v-if="people.length === 0" class="neural-card-premium py-32 text-center opacity-30">
                            <span class="text-8xl block mb-6 grayscale">📇</span>
                            <p class="text-xs font-black uppercase tracking-[0.6em]">{{ $t('No human nodes detected in archive.') }}</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6 custom-scrollbar overflow-y-auto max-h-[1000px] pr-4">
                            <div v-for="person in people" :key="person.id" 
                                class="neural-card-premium p-8 relative group hover:border-orange-500/30 transition-all duration-700 shadow-2xl overflow-hidden"
                            >
                                <!-- Status Glow Overlay -->
                                <div :class="['absolute top-0 right-0 w-32 h-32 blur-[60px] opacity-10 pointer-events-none transition-all duration-700 group-hover:opacity-20', 
                                    person.bond_strength > 70 ? 'bg-green-500' : (person.bond_strength > 30 ? 'bg-orange-500' : 'bg-blue-500')]"></div>

                                <!-- Delete Button -->
                                <button @click="deletePerson(person.id)" class="absolute top-4 inset-inline-start-4 w-8 h-8 rounded-lg bg-slate-900/50 flex items-center justify-center text-slate-500 hover:text-rose-500 transition-all active:scale-75 opacity-0 group-hover:opacity-100 border border-white/5 z-20">✕</button>
                                
                                <div class="flex items-start justify-between mb-6 relative z-10">
                                    <div class="flex items-center gap-4">
                                        <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-white/5 flex items-center justify-center text-2xl font-black text-slate-400 shadow-inner group-hover:scale-105 transition-transform duration-500 uppercase">
                                            {{ person.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <h4 class="font-black text-xl text-white tracking-tight uppercase mb-0.5 leading-none">{{ person.name }}</h4>
                                            <span class="text-[9px] font-black text-orange-400 uppercase tracking-[0.4em] opacity-80">{{ person.relation }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[8px] font-black text-slate-500 tracking-[0.3em] mb-1 uppercase">Bond_Flux</div>
                                        <div :class="['text-xl font-black tracking-tighter', 
                                            person.bond_strength > 70 ? 'text-green-500' : (person.bond_strength > 30 ? 'text-orange-500' : 'text-slate-500')]">
                                            {{ Math.round(person.bond_strength) }}%
                                        </div>
                                    </div>
                                </div>

                                <!-- Bond Bar Visual -->
                                <div class="w-full h-1 bg-slate-900 rounded-full mb-8 overflow-hidden relative shadow-inner">
                                    <div 
                                        :style="{ width: person.bond_strength + '%' }" 
                                        :class="['h-full transition-all duration-[2000ms] ease-out', 
                                            person.bond_strength > 70 ? 'bg-gradient-to-r from-emerald-500 to-green-500 shadow-[0_0_15px_rgba(16,185,129,0.5)]' : 
                                            (person.bond_strength > 30 ? 'bg-gradient-to-r from-orange-500 to-amber-500 shadow-[0_0_15px_rgba(249,115,22,0.5)]' : 'bg-slate-700')]"
                                    ></div>
                                </div>
                                
                                <div class="min-h-[50px] mb-8">
                                    <p v-if="person.gifts_notes" class="text-slate-400 text-sm italic bidi-plaintext line-clamp-2 leading-relaxed opacity-70 group-hover:opacity-100 transition-opacity">
                                        "{{ person.gifts_notes }}"
                                    </p>
                                    <p v-else class="text-slate-600 text-[10px] uppercase font-black tracking-[0.2em] italic">No telemetry data.</p>
                                </div>

                                <!-- AI Quick Advice Panel -->
                                <Transition name="slide-up">
                                    <div v-if="individualAdvice[person.id]" class="mb-8 p-5 bg-orange-500/5 border border-orange-500/20 rounded-[25px] shadow-inner relative overflow-hidden group/mini">
                                        <div class="absolute inset-0 bg-gradient-to-br from-orange-500/5 to-transparent pointer-events-none"></div>
                                        <p class="text-xs text-orange-400 font-bold bidi-plaintext leading-relaxed relative z-10">🤖 {{ individualAdvice[person.id] }}</p>
                                    </div>
                                </Transition>
                                
                                <div class="pt-6 border-t border-white/5 flex items-center justify-between relative z-10">
                                    <button 
                                        @click="getPersonAdvice(person.id)" 
                                        :disabled="isLoadingAdvice[person.id]"
                                        class="text-[9px] font-black uppercase tracking-[0.3em] text-orange-400 hover:text-white disabled:opacity-50 transition-all flex items-center gap-2 group/btn"
                                    >
                                        <span v-if="isLoadingAdvice[person.id]" class="animate-spin w-3 h-3 border-2 border-orange-400 border-t-transparent rounded-full"></span>
                                        <span v-else class="group-hover/btn:scale-110 transition-transform">🧠</span>
                                        <span>{{ isLoadingAdvice[person.id] ? $t('Thinking...') : $t('Advice') }}</span>
                                    </button>
                                    
                                    <button @click="touchPerson(person.id)" class="bg-slate-900 hover:bg-orange-500/10 border border-white/5 hover:border-orange-500/30 px-5 py-2.5 rounded-xl text-[9px] uppercase font-black tracking-[0.3em] text-slate-500 hover:text-orange-400 transition-all shadow-inner">
                                        {{ $t('Touch Protocol') }}
                                    </button>
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
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: rgba(249, 115, 22, 0.2); border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }

.bidi-plaintext {
    unicode-bidi: plaintext;
    text-align: start;
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active { transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1); }
.slide-up-enter-from { opacity: 0; transform: translateY(10px); }
</style>
