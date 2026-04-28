<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';
import VueApexCharts from 'vue3-apexcharts';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();
const lang = computed(() => getActiveLanguage());

const props = defineProps({
    transactions:      Array,
    summary:           Object,
    active_budget:     Object,
    budget_summary:    Object,
    today_plan:        Object,
    recurring_daily:   Array,
    recurring_monthly: Array,
    reports:           Object,
});

// ─── UI State ─────────────────────────────────────────────────────────────────
const showDailyModal    = ref(false);
const showMonthlyPlanModal = ref(false);
const showAddForm       = ref(false);
const activeReport      = ref('daily');   // 'daily' | 'weekly' | 'monthly'
const aiPlanText        = ref('');
const isGeneratingPlan  = ref(false);
const savingsTip        = ref('');
const isLoadingTip      = ref(false);

// ─── Daily Modal: AI suggestions ──────────────────────────────────────────────
const suggestions       = ref({});
const loadingSuggestion = ref({});

const fetchSuggestion = async (category, budget) => {
    const key = category;
    if (suggestions.value[key] || loadingSuggestion.value[key]) return;
    loadingSuggestion.value[key] = true;
    try {
        const res = await axios.post(route('money.ai.suggestions'), {
            category, budget, lang: lang.value
        });
        suggestions.value[key] = res.data.suggestions || [];
    } catch { suggestions.value[key] = []; }
    finally { loadingSuggestion.value[key] = false; }
};

// ─── 30-Day Monthly Projection ───────────────────────────────────────────────
const monthlyProjection = computed(() => {
    const projection = [];
    if (!props.budget_summary || !props.active_budget) return projection;

    let currentBalance = Number(props.budget_summary.total) || 0;
    
    let weeklyRecTotal = 0;
    let monthlyRecTotal = 0;
    if (props.recurring_monthly) {
        weeklyRecTotal = props.recurring_monthly.filter(x => x.frequency === 'weekly').reduce((sum, item) => sum + Number(item.amount), 0);
        monthlyRecTotal = props.recurring_monthly.filter(x => x.frequency === 'monthly').reduce((sum, item) => sum + Number(item.amount), 0);
    }
    
    // Deduct fixed monthly/weekly costs at start of projection
    currentBalance -= (monthlyRecTotal + (weeklyRecTotal * 4));

    const totalDailyCost = props.recurring_daily ? props.recurring_daily.reduce((sum, item) => sum + Number(item.amount), 0) : 0;

    for (let day = 1; day <= 30; day++) {
        currentBalance -= totalDailyCost;
        
        projection.push({
            day: day,
            label: trans('Daily Forecast'),
            dailyCost: Number(totalDailyCost),
            remaining: Math.max(0, currentBalance)
        });
    }

    return projection;
});


watch(showDailyModal, (val) => {
    if (val && props.today_plan?.daily_items) {
        props.today_plan.daily_items.forEach(item => {
            fetchSuggestion(item.category, item.amount);
        });
    }
});

// ─── Savings Tip ──────────────────────────────────────────────────────────────
const fetchSavingsTip = async () => {
    if (isLoadingTip.value) return;
    isLoadingTip.value = true;
    try {
        const res = await axios.post(route('money.savings.tip'), {
            saved: props.reports?.monthly_saved || 0,
            lang: lang.value,
        });
        savingsTip.value = res.data.tip || '';
    } catch { savingsTip.value = ''; }
    finally { isLoadingTip.value = false; }
};

// ─── AI Analyze ───────────────────────────────────────────────────────────────
const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const res = await axios.post(route('money.analyze'));
        aiPlanText.value = res.data.plan;
    } catch { aiPlanText.value = trans('Error connecting to AI advisor. Please try again later.'); }
    finally { isGeneratingPlan.value = false; }
};

// ─── Forms ────────────────────────────────────────────────────────────────────
const transactionForm = useForm({
    type:         'expense',
    amount:       '',
    category:     '',
    description:  '',
    is_recurring: false,
    frequency:    'monthly',
});

const saveTransaction = () => {
    transactionForm.post(route('money.store'), {
        preserveScroll: true,
        onSuccess: () => {
            transactionForm.reset('amount', 'description', 'is_recurring');
            showAddForm.value = false;
        },
    });
};

const budgetForm = useForm({
    amount:      '',
    period_type: 'monthly',
});

const saveBudget = () => {
    budgetForm.post(route('money.budget.store'), {
        preserveScroll: true,
        onSuccess: () => Swal.fire({
            title: trans('Budget Active'),
            text:  trans('Your neural budget has been initialized.'),
            icon:  'success',
            background: 'var(--c-surface)',
            color:      'var(--c-text)',
        }),
    });
};

const deleteTransaction = async (id) => {
    const result = await Swal.fire({
        title:              trans('Are you sure?'),
        text:               trans("You won't be able to revert this!"),
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor:  '#4b5563',
        confirmButtonText:  trans('Yes, delete it!'),
        cancelButtonText:   trans('Cancel'),
        background:         'var(--c-surface)',
        color:              'var(--c-text)',
    });
    if (result.isConfirmed) {
        router.delete(route('money.delete', id), { preserveScroll: true });
    }
};

const deleteRecurringTransaction = async (id) => {
    const result = await Swal.fire({
        title:              trans('Are you sure?'),
        text:               trans("This will stop the future recurring schedule for this item."),
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor:  '#4b5563',
        confirmButtonText:  trans('Yes, stop it!'),
        cancelButtonText:   trans('Cancel'),
        background:         'var(--c-surface)',
        color:              'var(--c-text)',
    });
    if (result.isConfirmed) {
        router.delete(route('money.recurring.delete', id), { preserveScroll: true });
    }
};

const clearAllRecurring = async () => {
    const result = await Swal.fire({
        title:              trans('Clear everything?'),
        text:               trans("This will delete all your recurring daily and monthly schedules."),
        icon:               'warning',
        showCancelButton:   true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor:  '#4b5563',
        confirmButtonText:  trans('Yes, clear all!'),
        cancelButtonText:   trans('Cancel'),
        background:         'var(--c-surface)',
        color:              'var(--c-text)',
    });
    if (result.isConfirmed) {
        router.delete(route('money.recurring.clear'), { preserveScroll: true });
    }
};

// ─── Charts ───────────────────────────────────────────────────────────────────
const barOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, background: 'transparent', zoom: { enabled: false } },
    colors: ['#f43f5e', '#10b981'], // n-danger and n-success
    plotOptions: { 
        bar: { 
            borderRadius: 6, 
            columnWidth: '35%',
            dataLabels: { position: 'top' }
        } 
    },
    dataLabels: { 
        enabled: false,
    },
    xaxis: {
        categories: props.reports?.daily?.map(d => d.label) || [],
        axisBorder: { show: false },
        axisTicks: { show: false },
        labels: { style: { colors: 'var(--text-p)', fontSize: '9px', fontWeight: 700 } },
    },
    yaxis: { 
        show: true,
        labels: { style: { colors: 'var(--text-p)', fontSize: '9px' } } 
    },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: 'var(--border)', strokeDashArray: 2, padding: { left: 10, right: 10 } },
    tooltip: { theme: isDark.value ? 'dark' : 'light' }
}));

const barSeries = computed(() => [
    { name: trans('Expense'), data: props.reports?.daily?.map(d => d.expense) || [] },
    { name: trans('Income'),  data: props.reports?.daily?.map(d => d.income)  || [] },
]);

const donutOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    labels: props.reports?.category?.map(c => c.category) || [],
    colors: ['#3b82f6','#6366f1','#06b6d4','#10b981','#f43f5e','#f59e0b'],
    dataLabels: { enabled: false },
    legend: { 
        position: 'bottom', 
        labels: { colors: 'var(--text-p)', useSeriesColors: false },
        itemMargin: { horizontal: 10, vertical: 5 }
    },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    plotOptions: { 
        pie: { 
            donut: { 
                size: '78%', 
                labels: { 
                    show: true, 
                    name: { show: false }, 
                    value: { color: 'var(--text-h)', fontSize: '18px', fontWeight: 900 } 
                } 
            } 
        } 
    },
    stroke: { show: false }
}));

const donutSeries = computed(() =>
    props.reports?.category?.map(c => c.total) || []
);

// ─── Spend progress ───────────────────────────────────────────────────────────
const spendPct = computed(() => {
    if (!props.today_plan?.allowance) return 0;
    return Math.min(100, (props.today_plan.actual_total / props.today_plan.allowance) * 100);
});

const healthPct = computed(() => {
    if (!props.budget_summary) return 0;
    return Math.min(100, (props.budget_summary.total_consumed / props.budget_summary.total) * 100);
});
</script>

<template>
    <Head :title="`${$t('Money Memory')} — Neural OS`" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-6">

            <!-- PAGE HEADER -->
            <div class="ai-briefing-compact n-card">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-2xl shadow-inner">
                            💰
                        </div>
                        <div>
                            <h2 class="n-h1 text-2xl">{{ $t('Smart Budget') }}</h2>
                            <p class="n-p text-[10px] uppercase tracking-widest font-bold">{{ $t('Finance_Protocol.v9') }}</p>
                        </div>
                    </div>
                    <button @click="showAddForm = !showAddForm" class="n-btn n-btn-primary gap-2">
                        <span>{{ showAddForm ? '✕' : '＋' }}</span>
                        <span>{{ showAddForm ? $t('Close Protocol') : $t('New Injection') }}</span>
                    </button>
                </div>
            </div>

            <!-- CORE STATS GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="n-card">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">{{ $t('Total Inflow') }}</span>
                        <span class="text-emerald-500">📈</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-800 dark:text-white">{{ summary.income || 0 }}</span>
                        <span class="text-xs font-bold text-slate-400">$</span>
                    </div>
                </div>

                <div class="n-card">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest">{{ $t('Committed Costs') }}</span>
                        <span class="text-rose-500">📉</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-800 dark:text-white">
                            {{ budget_summary ? budget_summary.total_consumed : (summary.expense || 0) }}
                        </span>
                        <span class="text-xs font-bold text-slate-400">$</span>
                    </div>
                </div>

                <div @click="showDailyModal = true" class="n-card cursor-pointer hover:border-emerald-500/30 group">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest">{{ $t("Neural Allowance") }}</span>
                        <span class="text-emerald-400 group-hover:animate-bounce">🎯</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-800 dark:text-white">{{ today_plan ? today_plan.remaining.toFixed(0) : 0 }}</span>
                        <span class="text-xs font-bold text-slate-400">$</span>
                    </div>
                    <p class="text-[8px] text-slate-400 mt-2 font-bold uppercase tracking-tighter opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ $t('Neural Allowance Explain') }}
                    </p>
                </div>

                <div v-if="budget_summary" class="n-card group">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest">{{ $t('Stability Score') }}</span>
                        <span class="text-amber-500">🧬</span>
                    </div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-3xl font-black text-slate-800 dark:text-white">{{ (100 - healthPct).toFixed(0) }}</span>
                        <span class="text-xs font-bold text-slate-400">%</span>
                    </div>
                    <p class="text-[8px] text-slate-400 mt-2 font-bold uppercase tracking-tighter opacity-0 group-hover:opacity-100 transition-opacity">
                        {{ $t('Stability Score Explain') }}
                    </p>
                </div>
            </div>

            <!-- MAIN CONTENT -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                
                <!-- SIDEBAR CONTROLS (4 Cols) -->
                <div class="lg:col-span-4 space-y-6">
                    <transition name="fade">
                        <div v-if="showAddForm" class="n-card border-emerald-500/30">
                            <h3 class="n-h3 mb-4 text-emerald-500">{{ $t('Financial Injection') }}</h3>
                            <form @submit.prevent="saveTransaction" class="space-y-4">
                                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                                    <button type="button" @click="transactionForm.type = 'expense'"
                                        :class="['py-2 rounded-lg text-[10px] font-black transition-all', transactionForm.type === 'expense' ? 'bg-rose-500 text-white shadow-lg' : 'text-slate-400']">
                                        💸 {{ $t('Expense') }}
                                    </button>
                                    <button type="button" @click="transactionForm.type = 'income'"
                                        :class="['py-2 rounded-lg text-[10px] font-black transition-all', transactionForm.type === 'income' ? 'bg-emerald-500 text-white shadow-lg' : 'text-slate-400']">
                                        💵 {{ $t('Income') }}
                                    </button>
                                </div>

                                <input v-model="transactionForm.amount" type="number" step="0.01" required
                                    class="n-input text-xl font-black" :placeholder="$t('Amount ($)')" />
                                
                                <input v-model="transactionForm.category" type="text" required
                                    class="n-input" :placeholder="$t('Category')" />

                                <div class="flex items-center justify-between p-2 border-t border-slate-100 dark:border-slate-800">
                                    <span class="text-[10px] font-black uppercase text-slate-400">{{ $t('Temporal Cycle') }}</span>
                                    <input type="checkbox" v-model="transactionForm.is_recurring" class="w-4 h-4 rounded text-blue-500" />
                                </div>

                                <div v-if="transactionForm.is_recurring" class="grid grid-cols-4 gap-1">
                                    <button v-for="freq in ['daily','weekly','monthly','yearly']" :key="freq"
                                        type="button" @click="transactionForm.frequency = freq"
                                        :class="['py-1.5 rounded text-[8px] font-black transition-all border', transactionForm.frequency === freq ? 'bg-blue-500 text-white' : 'border-slate-100 dark:border-slate-800 text-slate-400']">
                                        {{ $t(freq) }}
                                    </button>
                                </div>

                                <button type="submit" :disabled="transactionForm.processing" class="w-full n-btn n-btn-primary">
                                    {{ $t('Synchronize Nexus') }}
                                </button>
                            </form>
                        </div>
                    </transition>

                    <div class="n-card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="n-h3 text-emerald-500">{{ $t('Quantum_Nexus') }}</h3>
                            <button @click="showMonthlyPlanModal = true" class="text-xl">📅</button>
                        </div>
                        <form @submit.prevent="saveBudget" class="space-y-3">
                            <input v-model="budgetForm.amount" type="number" min="1" step="any"
                                class="n-input text-center font-bold" :placeholder="$t('Initialize Budget Goal ($)')" required />
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" @click="budgetForm.period_type = 'monthly'"
                                    :class="['py-2 rounded-lg text-[9px] font-black transition-all border', budgetForm.period_type === 'monthly' ? 'bg-emerald-500 text-white' : 'border-slate-100 dark:border-slate-800 text-slate-400']">
                                    {{ $t('Monthly') }}
                                </button>
                                <button type="button" @click="budgetForm.period_type = 'weekly'"
                                    :class="['py-2 rounded-lg text-[9px] font-black transition-all border', budgetForm.period_type === 'weekly' ? 'bg-emerald-500 text-white' : 'border-slate-100 dark:border-slate-800 text-slate-400']">
                                    {{ $t('Weekly') }}
                                </button>
                            </div>
                            <button type="submit" class="w-full n-btn n-btn-primary bg-slate-800 dark:bg-slate-700">
                                {{ budget_summary ? $t('Re-initialize Protocol') : $t('Boot Nexus') }}
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ANALYTICS & LOG (8 Cols) -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="n-card">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
                            <h3 class="n-h2">{{ $t('Quantum Flow') }}</h3>
                            <div class="flex gap-1 p-1 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                                <button v-for="tab in ['daily','weekly','monthly']" :key="tab"
                                    @click="activeReport = tab"
                                    :class="['px-4 py-1.5 text-[9px] font-black uppercase rounded-lg transition-all',
                                        activeReport === tab ? 'bg-blue-500 text-white' : 'text-slate-400']">
                                    {{ $t(tab) }}
                                </button>
                            </div>
                        </div>

                        <div class="min-h-[300px]">
                            <div v-if="activeReport === 'daily'">
                                <VueApexCharts v-if="reports.daily.length" type="bar" height="300" :options="barOptions" :series="barSeries" />
                                <div v-else class="py-20 text-center n-p italic">{{ $t('No resonance detected.') }}</div>
                            </div>
                            <div v-else-if="activeReport === 'weekly'" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div v-for="w in reports.weekly" :key="w.label" class="n-card border-slate-50 dark:border-slate-900 p-4 text-center">
                                    <p class="text-[8px] font-black text-slate-400 uppercase mb-2">{{ w.label }}</p>
                                    <p class="text-xl font-black text-rose-500">{{ w.expense }}$</p>
                                </div>
                            </div>
                            <div v-else-if="activeReport === 'monthly'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <VueApexCharts v-if="reports.category.length" type="donut" height="280" :options="donutOptions" :series="donutSeries" />
                                <div class="space-y-4">
                                    <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-100 dark:border-emerald-800">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-[9px] font-black text-emerald-500 uppercase">💡 {{ $t('Strategy Node') }}</span>
                                            <button @click="fetchSavingsTip" :disabled="isLoadingTip" class="text-[8px] font-black text-emerald-500 uppercase">{{ $t('Refresh') }}</button>
                                        </div>
                                        <p class="text-[11px] n-p italic bidi-plaintext">{{ savingsTip || $t('Awaiting strategy synthesis...') }}</p>
                                    </div>
                                    <div class="n-card text-center py-6">
                                        <p class="text-[8px] font-black text-emerald-500 uppercase mb-1">{{ $t('Conserved Value') }}</p>
                                        <p class="text-3xl font-black text-emerald-500">{{ reports.monthly_saved }}$</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TEMPORAL LOG -->
                    <div class="n-card">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="n-h2">{{ $t('Temporal Log') }}</h3>
                            <span class="px-3 py-1 rounded-full bg-slate-50 dark:bg-slate-900 text-[10px] font-black text-slate-400">{{ transactions.length }} {{ $t('Vestiges') }}</span>
                        </div>

                        <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="t in transactions" :key="t.id"
                                class="flex items-center justify-between p-4 rounded-xl bg-slate-50/50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-blue-500/20 transition-all">
                                <div class="flex items-center gap-4">
                                    <div :class="['w-10 h-10 rounded-lg flex items-center justify-center text-sm',
                                        t.type === 'income' ? 'bg-emerald-500/10 text-emerald-500' : 'bg-rose-500/10 text-rose-500']">
                                        {{ t.type === 'income' ? '＋' : '－' }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 dark:text-white uppercase">{{ t.category }}</p>
                                        <p class="text-[9px] text-slate-400 font-bold uppercase">{{ new Date(t.created_at).toLocaleDateString() }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span :class="['text-lg font-black', t.type === 'income' ? 'text-emerald-500' : 'text-rose-500']">
                                        {{ t.type === 'income' ? '+' : '−' }}{{ t.amount }}$
                                    </span>
                                    <button @click="deleteTransaction(t.id)" class="text-slate-300 hover:text-rose-500 transition-all">✕</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODALS -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="showDailyModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div class="n-card w-full max-w-xl bg-white dark:bg-slate-900 p-8 shadow-2xl">
                        <div class="flex justify-between items-center mb-8">
                            <h3 class="n-h1 text-xl uppercase tracking-tighter">{{ $t('Cycle Synthesis') }}</h3>
                            <button @click="showDailyModal = false" class="text-xl">✕</button>
                        </div>

                        <div class="grid grid-cols-3 gap-4 mb-8">
                            <div class="n-card bg-slate-50 dark:bg-slate-800 text-center p-4">
                                <p class="text-[8px] font-black text-slate-400 uppercase mb-1">{{ $t('Cycle Target') }}</p>
                                <p class="text-2xl font-black text-emerald-500">{{ today_plan.allowance.toFixed(0) }}$</p>
                            </div>
                            <div class="n-card bg-slate-50 dark:bg-slate-800 text-center p-4">
                                <p class="text-[8px] font-black text-slate-400 uppercase mb-1">{{ $t('Dissipated') }}</p>
                                <p class="text-2xl font-black text-rose-500">{{ today_plan.actual_total.toFixed(0) }}$</p>
                            </div>
                            <div class="n-card bg-slate-50 dark:bg-slate-800 text-center p-4">
                                <p class="text-[8px] font-black text-slate-400 uppercase mb-1">{{ $t('Potential') }}</p>
                                <p class="text-2xl font-black" :class="today_plan.remaining > 0 ? 'text-emerald-500' : 'text-rose-500'">{{ today_plan.remaining.toFixed(0) }}$</p>
                            </div>
                        </div>

                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 custom-scroll">
                            <div v-for="item in today_plan.daily_items" :key="item.category" class="space-y-2">
                                <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50/50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700">
                                    <span class="text-sm font-black uppercase">{{ item.category }}</span>
                                    <span class="text-lg font-black text-rose-500">-{{ item.amount }}$</span>
                                </div>
                                <div v-for="(sug, si) in suggestions[item.category]" :key="si"
                                    class="text-[10px] n-p italic p-2 border-l-2 border-emerald-500 bg-emerald-50/50 dark:bg-emerald-900/10">
                                    {{ sug }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <Teleport to="body">
            <transition name="fade">
                <div v-if="showMonthlyPlanModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
                    <div class="n-card w-full max-w-2xl bg-white dark:bg-slate-900 p-8 shadow-2xl max-h-[90vh] flex flex-col">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="n-h1 text-xl uppercase tracking-tighter">{{ $t('Temporal_Projection') }}</h3>
                            <button @click="showMonthlyPlanModal = false" class="text-xl">✕</button>
                        </div>
                        <div class="overflow-y-auto flex-1 pr-2 custom-scroll">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div v-for="proj in monthlyProjection" :key="proj.day" 
                                    class="p-4 rounded-xl border border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50/30 dark:bg-slate-800/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-500/10 text-blue-500 flex flex-col items-center justify-center border border-blue-500/20">
                                            <span class="text-[7px] font-black">{{ $t('DAY') }}</span>
                                            <span class="text-xs font-black">{{ proj.day < 10 ? '0' + proj.day : proj.day }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-[10px] font-black uppercase text-slate-400">{{ proj.label }}</span>
                                            <span class="text-[11px] text-rose-500 font-black">−{{ proj.dailyCost.toFixed(1) }}$</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[8px] font-black text-slate-400 uppercase block mb-1">{{ $t('Balance') }}</span>
                                        <span class="text-sm font-black" :class="proj.remaining > 0 ? 'text-emerald-500' : 'text-rose-500'">{{ proj.remaining.toFixed(1) }}$</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
