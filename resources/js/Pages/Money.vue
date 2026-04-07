<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

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
        aiPlanText.value = trans("Error connecting to AI advisor. Please try again later.");
    } finally {
        isGeneratingPlan.value = false;
    }
};

const transactionForm = useForm({
    type: 'expense',
    amount: '',
    category: trans('Food'),
    description: '',
});

const saveTransaction = () => {
    transactionForm.post(route('money.store'), {
        preserveScroll: true,
        onSuccess: () => transactionForm.reset('amount', 'description'),
    });
};

const deleteTransaction = async (id) => {
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
        router.delete(route('money.delete', id), { preserveScroll: true });
    }
};

// --- Chart Logic ---

const incomeVsExpenseSeries = computed(() => [
    Number(props.summary.income),
    Number(props.summary.expense)
]);

const incomeVsExpenseOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    labels: [trans('Income'), trans('Expense')],
    colors: ['#22c55e', '#ef4444'],
    theme: { mode: isDark.value ? 'dark' : 'light' },
    stroke: { show: false },
    legend: { position: 'bottom', labels: { colors: 'var(--c-text)' } },
    dataLabels: { enabled: false },
    tooltip: { theme: isDark.value ? 'dark' : 'light' }
}));

const categoryData = computed(() => {
    const expenses = props.transactions.filter(t => t.type === 'expense');
    const categories = {};
    expenses.forEach(t => {
        categories[t.category] = (categories[t.category] || 0) + Number(t.amount);
    });
    return categories;
});

const categorySeries = computed(() => Object.values(categoryData.value));

const categoryOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    labels: Object.keys(categoryData.value),
    theme: { mode: isDark.value ? 'dark' : 'light' },
    stroke: { show: false },
    legend: { position: 'bottom', labels: { colors: 'var(--c-text)' } },
    plotOptions: {
        pie: {
            donut: {
                size: '70%',
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: trans('Total'),
                        color: 'var(--c-text)',
                        formatter: (w) => `${w.globals.seriesTotals.reduce((a, b) => a + b, 0)} $`
                    }
                }
            }
        }
    },
    tooltip: { theme: isDark.value ? 'dark' : 'light' }
}));

const forecastData = ref([]);
const isForecasting = ref(false);

const getForecast = async () => {
    isForecasting.value = true;
    try {
        const response = await axios.get(route('money.forecast'));
        forecastData.value = response.data.forecast;
    } catch (e) {
        console.error("Forecast failed");
    } finally {
        isForecasting.value = false;
    }
};

const forecastOptions = computed(() => ({
    chart: { type: 'line', toolbar: { show: false }, background: 'transparent' },
    stroke: { curve: 'smooth', dashArray: [0, 8] },
    colors: ['#22c55e', '#069BFF'],
    xaxis: { 
        categories: [trans('Month 1'), trans('Month 2'), trans('Month 3')],
        labels: { style: { colors: 'var(--c-text-muted)' } }
    },
    yaxis: { labels: { style: { colors: 'var(--c-text-muted)' } } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    markers: { size: 4 },
    grid: { borderColor: 'var(--c-border-subtle)' }
}));

const forecastSeries = computed(() => [
    { name: trans('Balance Projection'), data: forecastData.value }
]);
</script>

<template>
    <Head :title="`${$t('Money Memory')} — Personal Memory`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-2xl text-text-main leading-tight flex items-center gap-2">
                <span>💰</span> {{ $t('Money Memory') }}
            </h2>
        </template>

        <div class="py-12 bg-surface min-h-screen text-text-main">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <!-- Summary Board -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl text-center transition-all hover:translate-y-[-4px]">
                        <span class="text-text-muted block mb-2 text-sm">{{ $t('Total Income') }}</span>
                        <h3 class="text-3xl font-bold text-green-500">{{ summary.income }} $</h3>
                    </div>
                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl text-center transition-all hover:translate-y-[-4px]">
                        <span class="text-text-muted block mb-2 text-sm">{{ $t('Total Expenses') }}</span>
                        <h3 class="text-3xl font-bold text-red-500">{{ summary.expense }} $</h3>
                    </div>
                    <div class="bg-glass-bg border border-t-[4px] border-accent rounded-2xl p-6 shadow-xl text-center relative overflow-hidden transition-all hover:translate-y-[-4px]">
                        <div class="absolute inset-0 bg-accent opacity-5"></div>
                        <span class="text-text-muted block mb-2 text-sm relative z-10">{{ $t('Remaining Balance') }}</span>
                        <h3 :class="['text-4xl font-bold relative z-10', summary.balance >= 0 ? 'text-text-main' : 'text-red-500']">{{ summary.balance }} $</h3>
                    </div>
                </div>

                <!-- AI Button -->
                <div class="bg-glass-bg border border-glass-border overflow-hidden shadow-2xl sm:rounded-2xl p-8 relative">
                    <div class="absolute -bottom-16 -right-16 w-40 h-40 bg-green-600 opacity-20 rounded-full blur-[60px]"></div>
                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-2xl font-bold text-text-main mb-2">{{ $t('Budget Leaking?') }}</h3>
                            <p class="text-text-muted">{{ $t('Let AI analyze your numbers...') }}</p>
                        </div>
                        <button 
                            @click="generatePlan" 
                            :disabled="isGeneratingPlan"
                            class="rounded-full bg-accent px-8 py-3 text-white hover:bg-opacity-80 transition font-bold shadow-[0_0_20px_rgba(6,155,255,0.3)] disabled:opacity-50 flex items-center gap-2"
                        >
                            <span v-if="isGeneratingPlan" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                            <span>{{ isGeneratingPlan ? $t('Thinking...') : $t('Analyze Budget (AI)') }}</span>
                        </button>
                    </div>

                    <div v-if="aiPlanText" class="mt-8 p-6 bg-input-bg rounded-xl border border-border-subtle whitespace-pre-wrap leading-relaxed">
                       <div class="flex items-center gap-2 mb-4 text-accent">
                           <span class="text-xl">💰</span>
                           <h4 class="font-bold text-xl">{{ $t('Financial Advisor Tip:') }}</h4>
                       </div>
                       {{ aiPlanText }}
                    </div>
                </div>

                <!-- Financial Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl">
                        <h4 class="text-lg font-bold text-text-main mb-6 flex items-center justify-between">
                             <div class="flex items-center gap-2">
                                <span class="w-2 h-2 bg-accent rounded-full"></span>
                                {{ $t('Income vs Expenses') }}
                             </div>
                        </h4>
                        <div class="flex justify-center">
                            <VueApexCharts width="300" :options="incomeVsExpenseOptions" :series="incomeVsExpenseSeries" />
                        </div>
                    </div>

                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl">
                        <h4 class="text-lg font-bold text-text-main mb-6 flex items-center gap-2">
                             <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                             {{ $t('Expenses by Category') }}
                        </h4>
                        <div class="flex justify-center">
                            <VueApexCharts width="300" :options="categoryOptions" :series="categorySeries" />
                        </div>
                    </div>

                    <!-- AI PROPHET FORECAST CARD -->
                    <div class="bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                        <div class="absolute -top-10 -left-10 w-32 h-32 bg-accent/20 rounded-full blur-3xl opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <h4 class="text-lg font-black text-text-main mb-6 flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-2 font-black">
                                <span class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></span>
                                {{ $t('Money Forecast (AI)') }}
                             </div>
                             <button @click="getForecast" :disabled="isForecasting" class="text-xs text-accent hover:underline uppercase tracking-widest font-black">
                                {{ isForecasting ? '...' : 'REFRESH' }}
                             </button>
                        </h4>
                        <div v-if="forecastData.length > 0" class="relative z-10 h-40">
                             <VueApexCharts height="160" :options="forecastOptions" :series="forecastSeries" />
                        </div>
                        <div v-else class="h-40 flex flex-col items-center justify-center text-text-muted text-xs text-center px-4 relative z-10 border border-dashed border-border-subtle rounded-xl">
                            <span class="text-2xl mb-2">🔮</span>
                            {{ $t('Activate AI Projection to see your future balance.') }}
                        </div>
                    </div>
                </div>

                <!-- Add Transaction Form -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pb-20">
                    <div class="md:col-span-1 bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl text-text-main">
                        <h3 class="text-xl font-bold mb-6">➕ {{ $t('New Transaction') }}</h3>
                        <form @submit.prevent="saveTransaction" class="space-y-4">
                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Transaction Type') }}</label>
                                <div class="flex gap-2">
                                    <button type="button" @click="transactionForm.type = 'expense'" :class="['flex-1 py-2 rounded-lg font-bold transition', transactionForm.type === 'expense' ? 'bg-red-600 text-white' : 'bg-surface-2 text-text-muted']">{{ $t('Expense') }}</button>
                                    <button type="button" @click="transactionForm.type = 'income'" :class="['flex-1 py-2 rounded-lg font-bold transition', transactionForm.type === 'income' ? 'bg-green-600 text-white' : 'bg-surface-2 text-text-muted']">{{ $t('Income') }}</button>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Amount ($)') }}</label>
                                <input v-model="transactionForm.amount" type="number" step="0.01" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent" required />
                            </div>

                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Category') }}</label>
                                <input v-model="transactionForm.category" type="text" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent" required />
                            </div>

                            <div>
                                <label class="block text-sm text-text-muted mb-1">{{ $t('Description (Optional)') }}</label>
                                <input v-model="transactionForm.description" type="text" class="w-full bg-input-bg border border-border-subtle rounded-lg px-3 py-2 text-text-main focus:ring-accent" />
                            </div>
                            
                            <button type="submit" :disabled="transactionForm.processing" class="bg-accent text-white px-4 py-2 rounded-lg font-bold hover:bg-opacity-80 transition w-full mt-2">
                                {{ $t('Record Transaction') }}
                            </button>
                        </form>
                    </div>

                    <!-- Transactions History -->
                    <div class="md:col-span-2 bg-glass-bg border border-glass-border rounded-2xl p-6 shadow-xl flex flex-col">
                        <h3 class="text-xl font-bold text-text-main mb-6">{{ $t('Transactions History') }}</h3>
                        
                        <div v-if="transactions.length === 0" class="text-center py-12 text-text-muted font-medium">
                            {{ $t('No financial records!') }}
                        </div>
                        
                        <div v-else class="space-y-3 custom-scrollbar overflow-y-auto max-h-[400px] pr-2">
                            <div v-for="t in transactions" :key="t.id" class="flex items-center justify-between p-4 bg-input-bg border border-border-subtle rounded-xl relative group transition hover:border-accent">
                                <div class="flex items-center gap-4">
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-xl font-bold', t.type === 'income' ? 'bg-green-600/20 text-green-500' : 'bg-red-600/20 text-red-500']">
                                        {{ t.type === 'income' ? '+' : '-' }}
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-text-main">{{ t.category }}</h4>
                                        <span class="text-xs text-text-muted">{{ t.description || $t('No description') }} | {{ new Date(t.created_at).toLocaleDateString() }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <h3 :class="['text-xl font-bold', t.type === 'income' ? 'text-green-500' : 'text-red-500']">
                                        {{ t.amount }} $
                                    </h3>
                                    <button @click="deleteTransaction(t.id)" class="text-text-muted hover:text-red-500 opacity-0 group-hover:opacity-100 transition px-2">✖</button>
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
