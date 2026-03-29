<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    transactions: Array,
    summary: Object,
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const response = await axios.post(route('money.analyze'));
        aiPlanText.value = response.data.plan;
    } catch (e) {
        aiPlanText.value = "حدث خطأ أثناء الاتصال بالمستشار المالي، يرجى المحاولة لاحقاً.";
    } finally {
        isGeneratingPlan.value = false;
    }
};

const transactionForm = useForm({
    type: 'expense',
    amount: '',
    category: 'طعام',
    description: '',
});

const saveTransaction = () => {
    transactionForm.post(route('money.store'), {
        preserveScroll: true,
        onSuccess: () => transactionForm.reset('amount', 'description'),
    });
};

const deleteTransaction = (id) => {
    if(confirm("هل أنت متأكد من حذف هذه المعاملة؟")) {
        router.delete(route('money.delete', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="ذاكرة المال — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-accent leading-tight flex items-center gap-2">
                <span>💰</span> ذاكرة المال
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Summary Board -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl text-center">
                        <span class="text-gray-400 block mb-2 text-sm">مجموع الدخل 💵</span>
                        <h3 class="text-3xl font-bold text-green-500">{{ summary.income }} $</h3>
                    </div>
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl text-center">
                        <span class="text-gray-400 block mb-2 text-sm">مجموع المصروفات 💸</span>
                        <h3 class="text-3xl font-bold text-red-500">{{ summary.expense }} $</h3>
                    </div>
                    <div class="bg-gray-900 border border-t-[4px] border-accent rounded-2xl p-6 shadow-xl text-center relative overflow-hidden">
                        <div class="absolute inset-0 bg-accent opacity-5"></div>
                        <span class="text-gray-400 block mb-2 text-sm relative z-10">الرصيد المتبقي 💳</span>
                        <h3 :class="['text-4xl font-bold relative z-10', summary.balance >= 0 ? 'text-white' : 'text-red-500']">{{ summary.balance }} $</h3>
                    </div>
                </div>

                <!-- AI Button -->
                <div class="bg-gray-900 border border-gray-800 overflow-hidden shadow-2xl sm:rounded-2xl p-8 relative">
                    <div class="absolute -bottom-16 -right-16 w-40 h-40 bg-green-600 opacity-20 rounded-full blur-[60px]"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-2xl font-bold text-white mb-2">تسرب الأموال والميزانية؟</h3>
                            <p class="text-gray-400">دع مستشارك المالي الذكي يحلل أرقامك، دخلاتك، ومصاريفك ليعطيك خطة للتوفير.</p>
                        </div>
                        <button 
                            @click="generatePlan" 
                            :disabled="isGeneratingPlan"
                            class="rounded-full bg-accent px-8 py-3 text-white hover:bg-opacity-80 transition font-bold shadow-[0_0_20px_rgba(6,155,255,0.3)] disabled:opacity-50 flex items-center gap-2"
                        >
                            <span v-if="isGeneratingPlan" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                            <span>{{ isGeneratingPlan ? 'جاري التحليل المالي...' : '📈 تحليل الميزانية (AI)' }}</span>
                        </button>
                    </div>

                    <div v-if="aiPlanText" class="mt-8 p-6 bg-black bg-opacity-40 rounded-xl border border-gray-800 whitespace-pre-wrap leading-relaxed">
                       <div class="flex items-center gap-2 mb-4 text-accent">
                           <span class="text-xl">💰</span>
                           <h4 class="font-bold text-xl">نصيحة المستشار المالي:</h4>
                       </div>
                       {{ aiPlanText }}
                    </div>
                </div>

                <!-- Add Transaction Form -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-20">
                    <div class="md:col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl text-white">
                        <h3 class="text-xl font-bold mb-6">➕ حركة جديدة</h3>
                        <form @submit.prevent="saveTransaction" class="space-y-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">نوع الحركة</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="transactionForm.type = 'expense'" :class="['flex-1 py-2 rounded-lg font-bold transition', transactionForm.type === 'expense' ? 'bg-red-600' : 'bg-gray-800 text-gray-400']">مصروف 💸</button>
                                    <button type="button" @click="transactionForm.type = 'income'" :class="['flex-1 py-2 rounded-lg font-bold transition', transactionForm.type === 'income' ? 'bg-green-600' : 'bg-gray-800 text-gray-400']">دخل 💵</button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">المبلغ ($)</label>
                                <input v-model="transactionForm.amount" type="number" step="0.01" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent" required />
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">التصنيف</label>
                                <input v-model="transactionForm.category" type="text" placeholder="مثال: فاتورة، راتب، طعام..." class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent" required />
                            </div>

                            <div>
                                <label class="block text-sm text-gray-400 mb-1">وصف (اختياري)</label>
                                <input v-model="transactionForm.description" type="text" class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:ring-accent" />
                            </div>
                            
                            <button type="submit" :disabled="transactionForm.processing" class="bg-accent text-white px-4 py-2 rounded-lg font-bold hover:bg-opacity-80 transition w-full mt-2">
                                تسجيل الصرف
                            </button>
                        </form>
                    </div>

                    <!-- Transactions History -->
                    <div class="md:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl flex flex-col">
                        <h3 class="text-xl font-bold text-white mb-6">سجل حركة الأموال</h3>
                        
                        <div v-if="transactions.length === 0" class="text-center py-12 text-gray-500 font-medium">
                            لا يوجد سجلات مالية! قم بإضافة مصاريفك الأولى أو الراتب لتبدأ.
                        </div>
                        
                        <div v-else class="space-y-3 custom-scrollbar overflow-y-auto max-h-[400px] pr-2">
                            <div v-for="t in transactions" :key="t.id" class="flex items-center justify-between p-4 bg-black bg-opacity-30 border border-gray-800 rounded-xl relative group transition hover:border-accent">
                                <div class="flex items-center gap-4">
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-xl font-bold', t.type === 'income' ? 'bg-green-600/20 text-green-500' : 'bg-red-600/20 text-red-500']">
                                        {{ t.type === 'income' ? '+' : '-' }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-white">{{ t.category }}</h4>
                                        <span class="text-xs text-gray-500">{{ t.description || 'لا يوجد وصف' }} | {{ new Date(t.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <h3 :class="['text-xl font-bold', t.type === 'income' ? 'text-green-500' : 'text-red-500']">
                                        {{ t.amount }} $
                                    </h3>
                                    <button @click="deleteTransaction(t.id)" class="text-gray-600 hover:text-red-500 opacity-0 group-hover:opacity-100 transition px-2">✖</button>
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
