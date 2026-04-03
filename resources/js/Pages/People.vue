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
        aiPlanText.value = "حدث خطأ أثناء محاولة الاتصال بـ الذكاء الاصطناعي، يرجى المحاولة لاحقاً.";
    } finally {
        isGeneratingPlan.value = false;
    }
};

const personForm = useForm({
    name: '',
    relation: '',
    importance: 'متوسطة',
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
        background: '#0d1304',
        color: '#fff',
        customClass: { popup: 'border border-gray-800 rounded-2xl shadow-2xl' }
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
        individualAdvice.value[id] = " Neural logic error. Just say Hi!";
    } finally {
        isLoadingAdvice.value[id] = false;
    }
};
</script>

<template>
    <Head title="ذاكرة الناس — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-accent leading-tight flex items-center gap-2">
                <span>👥</span> {{ $t('People Memory') }}
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-8">
                
                <!-- AI Analysis -->
                <div class="bg-gray-900 border border-gray-800 overflow-hidden shadow-2xl sm:rounded-2xl p-8 relative">
                    <div class="absolute -top-24 text-left -right-24 w-60 h-60 bg-secondary opacity-20 rounded-full blur-[80px]"></div>

                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-3xl font-bold text-white mb-2">{{ $t('How are your relations?') }}</h3>
                            <p class="text-gray-400 text-lg">{{ $t('AI Relations Support') }}</p>
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
                    <div v-if="aiPlanText" class="mt-8 p-6 bg-black bg-opacity-40 rounded-xl border border-gray-800 whitespace-pre-wrap leading-relaxed transition-all">
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
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl relative text-white">
                        <h3 class="text-xl font-bold mb-6">➕ {{ $t('Add Important Person') }}</h3>
                        <form @submit.prevent="addPerson" class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">{{ $t('Name') }}</label>
                                <input v-model="personForm.name" type="text" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent" required />
                            </div>
                            
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">{{ $t('Relation') }}</label>
                                <input v-model="personForm.relation" type="text" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent" />
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">{{ $t('Importance') }}</label>
                                <select v-model="personForm.importance" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent">
                                    <option value="عالية">عالية جداً (كالعائلة والأصدقاء المقربين)</option>
                                    <option value="متوسطة">متوسطة</option>
                                    <option value="منخفضة">منخفضة / علاقة عابرة</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">{{ $t('Gifts & Notes (Optional)') }}</label>
                                <textarea v-model="personForm.gifts_notes" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent h-24"></textarea>
                            </div>
                            
                            <button type="submit" :disabled="personForm.processing" class="bg-accent text-white px-4 py-2 rounded-lg font-bold hover:bg-opacity-80 transition w-full disabled:opacity-50 mt-2">
                                {{ $t('Save to Memory') }}
                            </button>
                        </form>
                    </div>

                    <!-- List of People -->
                    <div class="md:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl relative flex flex-col">
                        <h3 class="text-xl font-bold text-white mb-6">{{ $t('Registered People List') }}</h3>
                        
                        <div v-if="people.length === 0" class="text-center py-12 text-gray-500 flex flex-col items-center">
                            <span class="text-5xl mb-4">📇</span>
                            <p>{{ $t('No people added yet.') }}</p>
                        </div>
                        
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-6 custom-scrollbar overflow-y-auto max-h-[600px] pr-2">
                            <div v-for="person in people" :key="person.id" 
                                :class="['bg-black border rounded-[30px] p-6 transition-all group relative overflow-hidden', 
                                person.bond_strength > 70 ? 'border-green-500/20 shadow-[0_0_20px_rgba(34,197,94,0.05)]' : 
                                (person.bond_strength > 30 ? 'border-orange-500/20' : 'border-gray-800 opacity-80')]"
                            >
                                <!-- Thermal Pulse Background -->
                                <div :class="['absolute -inset-1 opacity-5 mix-blend-screen transition-opacity', person.bond_strength > 70 ? 'bg-green-500' : (person.bond_strength > 30 ? 'bg-orange-500' : 'bg-blue-500')]"></div>

                                <!-- Delete Button -->
                                <button @click="deletePerson(person.id)" class="absolute top-4 left-4 text-gray-700 hover:text-red-500 transition opacity-0 group-hover:opacity-100 z-10">✖</button>
                                
                                <div class="flex items-start justify-between mb-4 relative z-10">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-2xl bg-white/5 flex items-center justify-center text-xl font-black text-gray-500">
                                            {{ person.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <h4 class="font-black text-lg text-white leading-none mb-1">{{ person.name }}</h4>
                                            <span class="text-[10px] text-accent uppercase tracking-widest">{{ person.relation }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] font-mono text-gray-500 mb-1">BOND_STRENGTH</div>
                                        <div :class="['text-sm font-black', person.bond_strength > 70 ? 'text-green-500' : (person.bond_strength > 30 ? 'text-orange-500' : 'text-gray-600')]">
                                            {{ Math.round(person.bond_strength) }}%
                                        </div>
                                    </div>
                                </div>

                                <!-- Bond Bar -->
                                <div class="w-full h-1 bg-white/5 rounded-full mb-6 overflow-hidden relative">
                                    <div 
                                        :style="{ width: person.bond_strength + '%' }" 
                                        :class="['h-full transition-all duration-1000', person.bond_strength > 70 ? 'bg-green-500 shadow-[0_0_10px_#22c55e]' : (person.bond_strength > 30 ? 'bg-orange-500' : 'bg-gray-700')]"
                                    ></div>
                                </div>
                                
                                <p v-if="person.gifts_notes" class="text-sm text-gray-500 mb-6 line-clamp-2 italic bidi-plaintext">
                                    "{{ person.gifts_notes }}"
                                </p>

                                <!-- AI Quick Advice Panel -->
                                <div v-if="individualAdvice[person.id]" class="mb-6 p-4 bg-accent/5 border border-accent/20 rounded-2xl animate-in slide-in-from-bottom-2 duration-500">
                                    <p class="text-xs text-accent font-bold bidi-plaintext leading-relaxed">🤖 {{ individualAdvice[person.id] }}</p>
                                </div>
                                
                                <div class="pt-4 border-t border-white/5 flex items-center justify-between relative z-10">
                                    <button 
                                        @click="getPersonAdvice(person.id)" 
                                        :disabled="isLoadingAdvice[person.id]"
                                        class="text-[10px] font-black uppercase tracking-widest text-accent hover:text-white disabled:opacity-50 transition-colors"
                                    >
                                        {{ isLoadingAdvice[person.id] ? $t('Thinking...') : 'Neural Advice' }}
                                    </button>
                                    
                                    <button @click="touchPerson(person.id)" class="bg-white/5 hover:bg-white/10 px-4 py-2 rounded-xl text-[10px] uppercase font-black tracking-widest transition-all">
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
:deep(.bg-white) { background-color: #0d1304 !important; border-color: #1f2937 !important; }
:deep(.text-gray-800) { color: #e2f0d5 !important; }
:deep(header) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
:deep(nav) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #062F69; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
