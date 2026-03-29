<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    decisions: Array,
});

const choices = ref({});

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
    router.patch(route('decisions.finalize', id), { final_decision: choice }, { preserveScroll: true });
};

const deleteDecision = (id) => {
    if(confirm("هل أنت متأكد من حذف هذا القرار؟")) {
        router.delete(route('decisions.delete', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="ذاكرة القرارات — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-accent leading-tight flex items-center gap-2">
                <span>⚖️</span> ذاكرة القرارات
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Ask Advice -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl relative">
                    <div class="absolute -top-12 -right-12 w-40 h-40 bg-accent opacity-20 rounded-full blur-[60px]"></div>
                    <div class="relative z-10">
                        <h3 class="text-3xl font-bold text-white mb-2">أنا محتار... ساعدني!</h3>
                        <p class="text-gray-400 mb-6">صف المشكلة أو الخيارات التي تفكر فيها وسيتولى مستشارك الذكي إعطاءك الإيجابيات والسلبيات لاتخاذ القرار النهائي.</p>
                        
                        <form @submit.prevent="saveProblem" class="flex flex-col gap-4">
                            <textarea
                                v-model="decisionForm.problem"
                                class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-accent h-32 custom-scrollbar"
                                placeholder="مثال: محتار بين دراسة البرمجة كمستقل أو التفرغ لتعلم الذكاء الاصطناعي؟"
                                required
                            ></textarea>
                            
                            <button
                                type="submit"
                                :disabled="decisionForm.processing"
                                class="bg-accent text-white px-8 py-3 rounded-full font-bold hover:bg-opacity-80 transition disabled:opacity-50 self-start flex items-center gap-2"
                            >
                                <span v-if="decisionForm.processing" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                                <span>{{ decisionForm.processing ? 'الذكاء يدرس الخيارات...' : 'اطلب الاستشارة ⚖️' }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Decisions List -->
                <div class="space-y-6 pb-20">
                    <div v-for="decision in decisions" :key="decision.id" class="bg-black bg-opacity-30 border border-gray-800 rounded-2xl p-6 relative group transition duration-300">
                        <button @click="deleteDecision(decision.id)" class="absolute top-4 left-4 text-gray-600 hover:text-red-500 transition opacity-0 group-hover:opacity-100">✖</button>
                        
                        <div class="mb-4">
                            <h4 class="font-bold text-white text-xl mb-2">{{ decision.problem }}</h4>
                            <span class="text-xs text-gray-500">{{ new Date(decision.created_at).toLocaleDateString() }}</span>
                        </div>
                        
                        <div class="p-4 bg-gray-900 border border-gray-800 rounded-xl mt-4" v-if="decision.ai_advice">
                            <div class="flex items-center gap-2 mb-2 text-accent text-lg">
                                <span>🤖</span>
                                <span class="font-bold">رأي المستشار الذكي:</span>
                            </div>
                            <p class="text-[15px] text-gray-300 leading-relaxed whitespace-pre-wrap">{{ decision.ai_advice }}</p>
                        </div>
                        
                        <!-- Final Choose -->
                        <div class="mt-6 pt-4 border-t border-gray-800" v-if="!decision.final_decision">
                            <label class="block text-sm text-gray-400 mb-2 font-bold">ما هو قرارك النهائي؟</label>
                            <div class="flex gap-2">
                                <input type="text" v-model="choices[decision.id]" placeholder="قراري هو..." class="flex-1 bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent text-sm" @keyup.enter="finalizeDecision(decision.id, choices[decision.id])" />
                                <button @click="finalizeDecision(decision.id, choices[decision.id])" class="bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm font-bold transition">حسم الأمر ✔</button>
                            </div>
                        </div>
                        
                        <div class="mt-6 p-4 bg-green-900 border border-green-800 rounded-xl text-green-100" v-else>
                            <span class="font-bold block mb-1">✅ القرار النهائي المحسوم:</span>
                            {{ decision.final_decision }}
                        </div>

                    </div>

                    <div v-if="decisions.length === 0" class="text-center py-12 text-gray-500">
                        <span class="block text-5xl mb-4">⚖️</span>
                        لا يوجد قرارات معلقة حالياً. عندما تحتار، عد إلى هنا وستجدني.
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
