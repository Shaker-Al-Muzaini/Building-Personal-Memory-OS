<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';
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
        background: 'var(--c-surface)',
        color: 'var(--c-text)',
        customClass: { popup: 'border border-glass-border rounded-2xl shadow-2xl' }
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
            <h2 class="font-black text-2xl text-text-main leading-tight flex items-center gap-2">
                <span>👥</span> {{ $t('People Memory') }}
            </h2>
        </template>

        <div class="py-12 bg-surface min-h-screen text-text-main">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-8">
                
                <!-- AI Analysis -->
                <div class="bg-glass-bg border border-glass-border overflow-hidden shadow-2xl sm:rounded-2xl p-8 relative">
                    <div class="absolute -top-24 text-left -right-24 w-60 h-60 bg-secondary opacity-20 rounded-full blur-[80px]"></div>

                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-3xl font-bold text-text-main mb-2">{{ $t('How are your relations?') }}</h3>
                            <p class="text-text-muted text-lg">{{ $t('AI Relations Support') }}</p>
                        </div>
                        <button 
                            @click="generatePlan" 
                            :disabled="isGeneratingPlan"
                            class="rounded-full bg-accent px-8 py-3 text-white hover:bg-opacity-80 transition duration-300 font-bold shadow-[0_0_20px_rgba(6,155,255,0.3)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <span v-if="isGeneratingPlan" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                            <span v-if="!isGeneratingPlan">{{ $t('Analyze Relations (AI)') }}</span>
                            <span v-else>{{ $t('Thinking...') }}</span>
                        </button>
                    </div>

                    <!-- AI Response Area -->
                    <div v-if="aiPlanText" class="mt-8 p-6 bg-input-bg rounded-xl border border-border-subtle whitespace-pre-wrap leading-relaxed transition-all">
                       <div class="flex items-center gap-2 mb-4 text-accent">
                           <span class="text-xl">🤖</span>
                           <h4 class="font-bold text-xl">{{ $t('AI Advice on Relations:') }}</h4>
                       </div>
                       {{ aiPlanText }}
                    </div>
                </div>

                <!-- Add & List Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Add Person Form -->
                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl relative text-text-main">
                        <h3 class="text-xl font-bold mb-6">➕ {{ $t('Add Important Person') }}</h3>
                        <form @submit.prevent="addPerson" class="space-y-4">
                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Name') }}</label>
                                <input v-model="personForm.name" type="text" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent" required />
                            </div>
                            
                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Relation') }}</label>
                                <input v-model="personForm.relation" type="text" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent" />
                            </div>

                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Importance') }}</label>
                                <select v-model="personForm.importance" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent">
                                    <option value="high">{{ $t('Importance_High') }}</option>
                                    <option value="medium">{{ $t('Importance_Medium') }}</option>
                                    <option value="low">{{ $t('Importance_Low') }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Gifts & Notes (Optional)') }}</label>
                                <textarea v-model="personForm.gifts_notes" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent h-24"></textarea>
                            </div>
                            
                            <button type="submit" :disabled="personForm.processing" class="bg-accent text-white px-4 py-2 rounded-lg font-bold hover:bg-opacity-80 transition w-full disabled:opacity-50 mt-2">
                                {{ $t('Save to Memory') }}
                            </button>
                        </form>
                    </div>

                    <!-- List of People -->
                    <div class="md:col-span-2 bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl relative flex flex-col">
                        <h3 class="text-xl font-bold text-text-main mb-6">{{ $t('Registered People List') }}</h3>
                        
                        <div v-if="people.length === 0" class="text-center py-12 text-text-muted flex flex-col items-center">
                            <span class="text-5xl mb-4">📇</span>
                            <p>{{ $t('No people added yet.') }}</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-6 custom-scrollbar overflow-y-auto max-h-[600px] pr-2">
                            <div v-for="person in people" :key="person.id" 
                                :class="['bg-surface-2 border rounded-[30px] p-6 transition-all group relative overflow-hidden', 
                                person.bond_strength > 70 ? 'border-green-500/20 shadow-lg' : 
                                (person.bond_strength > 30 ? 'border-orange-500/20' : 'border-border-subtle opacity-80')]"
                            >
                                <!-- Thermal Pulse Background -->
                                <div :class="['absolute -inset-1 opacity-5 mix-blend-screen transition-opacity', person.bond_strength > 70 ? 'bg-green-500' : (person.bond_strength > 30 ? 'bg-orange-500' : 'bg-blue-500')]"></div>

                                <!-- Delete Button -->
                                <button @click="deletePerson(person.id)" class="absolute top-4 inset-inline-start-4 text-text-muted hover:text-red-500 transition opacity-0 group-hover:opacity-100 z-10">✖</button>
                                
                                <div class="flex items-start justify-between mb-4 relative z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-surface flex items-center justify-center text-xl font-black text-text-muted">
                                            {{ person.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <h4 class="font-black text-lg text-text-main leading-none mb-1">{{ person.name }}</h4>
                                            <span class="text-[10px] text-accent uppercase tracking-widest">{{ person.relation }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] font-mono text-text-muted mb-1">BOND_STRENGTH</div>
                                        <div :class="['text-sm font-black', person.bond_strength > 70 ? 'text-green-500' : (person.bond_strength > 30 ? 'text-orange-500' : 'text-text-muted')]">
                                            {{ Math.round(person.bond_strength) }}%
                                        </div>
                                    </div>
                                </div>

                                <!-- Bond Bar -->
                                <div class="w-full h-1 bg-border-subtle rounded-full mb-6 overflow-hidden relative">
                                    <div 
                                        :style="{ width: person.bond_strength + '%' }" 
                                        :class="['h-full transition-all duration-1000', person.bond_strength > 70 ? 'bg-green-500 shadow-lg' : (person.bond_strength > 30 ? 'bg-orange-500' : 'bg-gray-700')]"
                                    ></div>
                                </div>
                                
                                <p v-if="person.gifts_notes" class="text-sm text-text-muted mb-6 line-clamp-2 italic bidi-plaintext">
                                    "{{ person.gifts_notes }}"
                                </p>

                                <!-- AI Quick Advice Panel -->
                                <div v-if="individualAdvice[person.id]" class="mb-6 p-4 bg-accent/5 border border-accent/20 rounded-2xl animate-in slide-in-from-bottom-2 duration-500">
                                    <p class="text-xs text-accent font-bold bidi-plaintext leading-relaxed">🤖 {{ individualAdvice[person.id] }}</p>
                                </div>
                                
                                <div class="pt-4 border-t border-border-subtle flex items-center justify-between relative z-10">
                                    <button 
                                        @click="getPersonAdvice(person.id)" 
                                        :disabled="isLoadingAdvice[person.id]"
                                        class="text-[10px] font-black uppercase tracking-widest text-accent hover:text-text-main disabled:opacity-50 transition-colors"
                                    >
                                        {{ isLoadingAdvice[person.id] ? $t('Thinking...') : $t('Neural Advice') }}
                                    </button>
                                    
                                    <button @click="touchPerson(person.id)" class="bg-input-bg hover:bg-card-hover px-4 py-2 rounded-xl text-[10px] uppercase font-black tracking-widest transition-all">
                                        {{ $t('Updated Contact') }}
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
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: var(--c-accent); border-radius: 10px; opacity: 0.2; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
