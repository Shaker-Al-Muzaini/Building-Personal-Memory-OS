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
const monthlyMenu = [
    "مفتول", "مقلوبه", "مجدره", "كوبه", "فلافل", "كفته", 
    "طبخ كوسا", "طبيخ بطاطا", "طبيخ فاصوليا", "طبيخ بطاطا مع باذنجان", 
    "مسلفن رز", "مسلفن كبسها", "مسلفن بخاري", "كباب", "شورما", 
    "قدره", "رومانيها", "مخشي", "سلطه وقلي بطاطا", "فته", 
    "بطاطا صنيحه", "كوبه", "فلافل", "ملفوف محشي", "قلايه بنذوه برز واللحمه", 
    "طبيخ طحينها بلكفته", "بلازلاء ورز", "بامية", "سلطه مع بطاطا سلق", "بطاطا وعدس"
];

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
    
    currentBalance -= monthlyRecTotal + (weeklyRecTotal * 4);

    const totalDailyCost = props.recurring_daily ? props.recurring_daily.reduce((sum, item) => sum + Number(item.amount), 0) : 0;

    for (let day = 1; day <= 30; day++) {
        currentBalance -= totalDailyCost;
        
        projection.push({
            day: day,
            meal: monthlyMenu[day - 1] || "—",
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

// ─── Charts ───────────────────────────────────────────────────────────────────
const barOptions = computed(() => ({
    chart: { type: 'bar', toolbar: { show: false }, background: 'transparent' },
    colors: ['#ef4444', '#22c55e'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '50%' } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.reports?.daily?.map(d => d.label) || [],
        labels: { style: { colors: 'var(--c-text-muted)', fontSize: '10px' } },
    },
    yaxis: { labels: { style: { colors: 'var(--c-text-muted)', fontSize: '10px' } } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    legend: { labels: { colors: 'var(--c-text-muted)' } },
    grid: { borderColor: 'rgba(255,255,255,0.05)' },
}));

const barSeries = computed(() => [
    { name: trans('Expense'), data: props.reports?.daily?.map(d => d.expense) || [] },
    { name: trans('Income'),  data: props.reports?.daily?.map(d => d.income)  || [] },
]);

const donutOptions = computed(() => ({
    chart: { type: 'donut', background: 'transparent' },
    labels: props.reports?.category?.map(c => c.category) || [],
    colors: ['#6366f1','#f59e0b','#10b981','#ef4444','#3b82f6','#ec4899'],
    dataLabels: { enabled: false },
    legend: { position: 'bottom', labels: { colors: 'var(--c-text-muted)' } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    plotOptions: { pie: { donut: { size: '65%' } } },
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
    <Head :title="`${$t('Money Memory')} — Personal Memory`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl text-text-main flex items-center gap-2">
                💰 {{ $t('Money Memory') }}
            </h2>
        </template>

        <div class="py-8 bg-surface min-h-screen text-text-main">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

                <!-- ── TOP STATS ─────────────────────────────────────────── -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">

                    <!-- Income -->
                    <div class="bg-glass-bg border border-glass-border rounded-3xl p-5 flex flex-col gap-2 hover:-translate-y-1 transition-all">
                        <span class="text-[9px] font-black text-green-500 uppercase tracking-widest">{{ $t('Total Income') }}</span>
                        <span class="text-2xl font-black text-text-main">{{ summary.income }}<span class="text-xs text-text-muted ml-1">$</span></span>
                        <div class="h-1 bg-green-500/20 rounded-full"><div class="h-full bg-green-500 rounded-full w-full"></div></div>
                    </div>

                    <!-- Expenses & Commitments -->
                    <div class="bg-glass-bg border border-glass-border rounded-3xl p-5 flex flex-col gap-2 hover:-translate-y-1 transition-all">
                        <span class="text-[9px] font-black text-red-500 uppercase tracking-widest">{{ $t('Total Expenses & Commitments') }}</span>
                        <span class="text-2xl font-black text-text-main">
                            {{ budget_summary ? budget_summary.total_consumed : summary.expense }}
                            <span class="text-xs text-text-muted ml-1">$</span>
                        </span>
                        <div class="h-1 bg-red-500/20 rounded-full"><div class="h-full bg-red-500 rounded-full" :style="{ width: summary.income ? Math.min(100,((budget_summary ? budget_summary.total_consumed : summary.expense)/summary.income*100))+'%' : '0%' }"></div></div>
                    </div>

                    <!-- Today Card (clickable) -->
                    <div @click="showDailyModal = true" class="bg-gradient-to-br from-blue-600/20 to-indigo-600/20 border border-blue-500/30 rounded-3xl p-5 flex flex-col gap-2 cursor-pointer hover:-translate-y-1 active:scale-95 transition-all relative overflow-hidden group">
                        <div class="absolute -top-8 -right-8 w-20 h-20 bg-blue-500/20 rounded-full blur-xl group-hover:scale-150 transition-transform"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-[9px] font-black text-blue-400 uppercase tracking-widest">{{ $t("Today's Plan") }}</span>
                            <span class="text-[7px] bg-blue-500 text-white px-2 py-0.5 rounded-full font-black animate-pulse">LIVE</span>
                        </div>
                        <span class="text-2xl font-black text-text-main">{{ today_plan.remaining.toFixed(0) }}<span class="text-xs text-text-muted ml-1">$</span></span>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full transition-all duration-1000" :style="{ width: spendPct + '%' }"></div>
                        </div>
                        <span class="text-[8px] text-text-muted font-bold text-center">{{ $t('Click for detailed schedule') }} →</span>
                    </div>

                    <!-- Budget Health -->
                    <div v-if="budget_summary" class="bg-accent/10 border border-accent/30 rounded-3xl p-5 flex flex-col gap-2 hover:-translate-y-1 transition-all">
                        <span class="text-[9px] font-black text-accent uppercase tracking-widest">{{ $t('Budget Health') }}</span>
                        <div class="flex items-end gap-1">
                            <span class="text-2xl font-black text-text-main">{{ healthPct.toFixed(0) }}</span>
                            <span class="text-xs text-text-muted mb-0.5">%</span>
                        </div>
                        <div class="h-1 bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-1000"
                                :class="healthPct > 80 ? 'bg-red-500' : healthPct > 60 ? 'bg-yellow-500' : 'bg-accent'"
                                :style="{ width: healthPct + '%' }"></div>
                        </div>
                        <span class="text-[8px] text-text-muted font-bold">{{ budget_summary.days_left }} {{ $t('DAYS LEFT') }}</span>
                    </div>
                    <div v-else class="bg-surface-2 border border-dashed border-border-subtle rounded-3xl p-5 flex flex-col items-center justify-center gap-1">
                        <span class="text-2xl">🎯</span>
                        <span class="text-[9px] text-text-muted font-black uppercase">{{ $t('No Budget') }}</span>
                    </div>
                </div>

                <!-- ── MAIN GRID ──────────────────────────────────────────── -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                    <!-- LEFT: Smart Scheduler + Add Transaction -->
                    <div class="lg:col-span-1 space-y-4">

                        <!-- Budget Setup -->
                        <div class="bg-glass-bg border border-glass-border rounded-3xl p-6 relative overflow-hidden">
                            <div class="absolute -top-12 -right-12 w-28 h-28 bg-blue-600/10 rounded-full blur-2xl pointer-events-none"></div>
                            <h3 class="text-sm font-black text-text-main uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-7 h-7 bg-accent/20 rounded-xl flex items-center justify-center text-sm">⚖️</span>
                                {{ $t('Smart Scheduler') }}
                            </h3>

                            <!-- Mini stats if budget active -->
                            <div v-if="budget_summary" class="grid grid-cols-2 gap-2 mb-4">
                                <div class="bg-surface-2 rounded-2xl p-3 text-center">
                                    <p class="text-[7px] font-black text-text-muted uppercase tracking-widest mb-0.5">{{ $t('Daily Goal') }}</p>
                                    <p class="text-base font-black text-blue-400">{{ budget_summary.daily_allowance.toFixed(1) }}$</p>
                                </div>
                                <div class="bg-surface-2 rounded-2xl p-3 text-center">
                                    <p class="text-[7px] font-black text-text-muted uppercase tracking-widest mb-0.5">{{ $t('Today Remaining') }}</p>
                                    <p class="text-base font-black" :class="today_plan.remaining > 0 ? 'text-green-400' : 'text-red-400'">{{ today_plan.remaining.toFixed(1) }}$</p>
                                </div>
                                <div class="col-span-2">
                                    <button @click="showMonthlyPlanModal = true" type="button" class="w-full py-2.5 bg-accent/10 text-accent border border-accent/20 text-[9px] font-black uppercase tracking-widest rounded-xl hover:bg-accent hover:text-white transition-all shadow-md flex justify-center items-center gap-2">
                                        📅 خطة طعام 30 يوم
                                    </button>
                                </div>
                            </div>

                            <form @submit.prevent="saveBudget" class="space-y-3">
                                <div>
                                    <label class="block text-[8px] font-black text-text-muted uppercase tracking-widest mb-1">{{ $t('Spending Goal ($)') }}</label>
                                    <input v-model="budgetForm.amount" type="number" min="1" step="any"
                                        class="w-full bg-input-bg border border-border-subtle rounded-xl px-4 py-3 text-sm focus:ring-1 focus:ring-accent focus:outline-none"
                                        :placeholder="$t('e.g. 2000')" required />
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" @click="budgetForm.period_type = 'monthly'"
                                        :class="['py-2 rounded-xl text-[9px] font-black uppercase transition-all', budgetForm.period_type === 'monthly' ? 'bg-accent text-white' : 'bg-surface-2 text-text-muted hover:bg-white/5']">
                                        {{ $t('Monthly') }}
                                    </button>
                                    <button type="button" @click="budgetForm.period_type = 'weekly'"
                                        :class="['py-2 rounded-xl text-[9px] font-black uppercase transition-all', budgetForm.period_type === 'weekly' ? 'bg-accent text-white' : 'bg-surface-2 text-text-muted hover:bg-white/5']">
                                        {{ $t('Weekly') }}
                                    </button>
                                </div>
                                <button type="submit" class="w-full py-3 bg-white text-black text-[9px] font-black uppercase tracking-widest rounded-2xl hover:bg-accent hover:text-white transition-all duration-300 shadow-lg">
                                    {{ budget_summary ? $t('UPDATE PROTOCOL') : $t('INITIALIZE BUDGET') }}
                                </button>
                            </form>
                        </div>

                        <!-- Add Transaction Button -->
                        <button @click="showAddForm = !showAddForm"
                            class="w-full bg-accent text-white rounded-3xl py-4 font-black text-[11px] uppercase tracking-[0.3em] shadow-xl shadow-accent/20 hover:brightness-110 active:scale-95 transition-all flex items-center justify-center gap-2">
                            <span class="text-lg">{{ showAddForm ? '✖' : '＋' }}</span>
                            {{ showAddForm ? $t('Cancel') : $t('New Transaction') }}
                        </button>

                        <!-- Add Transaction Form (Collapsible) -->
                        <transition name="slide-down">
                            <div v-if="showAddForm" class="bg-glass-bg border border-glass-border rounded-3xl p-6 space-y-4">
                                <h3 class="text-sm font-black text-text-main uppercase tracking-widest">{{ $t('New Transaction') }}</h3>
                                <form @submit.prevent="saveTransaction" class="space-y-4">
                                    <!-- Type -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <button type="button" @click="transactionForm.type = 'expense'"
                                            :class="['py-2.5 rounded-xl text-[9px] font-black uppercase transition-all', transactionForm.type === 'expense' ? 'bg-red-600 text-white' : 'bg-surface-2 text-text-muted']">
                                            💸 {{ $t('Expense') }}
                                        </button>
                                        <button type="button" @click="transactionForm.type = 'income'"
                                            :class="['py-2.5 rounded-xl text-[9px] font-black uppercase transition-all', transactionForm.type === 'income' ? 'bg-green-600 text-white' : 'bg-surface-2 text-text-muted']">
                                            💵 {{ $t('Income') }}
                                        </button>
                                    </div>

                                    <!-- Amount -->
                                    <div>
                                        <label class="block text-[8px] font-black text-text-muted uppercase tracking-widest mb-1">{{ $t('Amount ($)') }}</label>
                                        <input v-model="transactionForm.amount" type="number" step="0.01" required
                                            class="w-full bg-input-bg border border-border-subtle rounded-xl px-4 py-3 text-text-main text-sm focus:ring-1 focus:ring-accent focus:outline-none" />
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label class="block text-[8px] font-black text-text-muted uppercase tracking-widest mb-1">{{ $t('Category') }}</label>
                                        <input v-model="transactionForm.category" type="text" required
                                            :placeholder="$t('e.g. Food, Electricity...')"
                                            class="w-full bg-input-bg border border-border-subtle rounded-xl px-4 py-3 text-text-main text-sm focus:ring-1 focus:ring-accent focus:outline-none" />
                                    </div>

                                    <!-- Recurring Toggle -->
                                    <div class="pt-3 border-t border-white/5 space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-[9px] font-black text-text-muted uppercase tracking-widest">{{ $t('Repeat Transaction') }}</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="transactionForm.is_recurring" class="sr-only peer">
                                                <div class="w-10 h-5 bg-surface-2 rounded-full peer peer-checked:bg-accent after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full ring-1 ring-white/5"></div>
                                            </label>
                                        </div>

                                        <transition name="slide-down">
                                            <div v-if="transactionForm.is_recurring" class="grid grid-cols-4 gap-1 p-1 bg-surface-2/50 rounded-xl border border-white/5">
                                                <button v-for="freq in ['daily','weekly','monthly','yearly']" :key="freq"
                                                    type="button" @click="transactionForm.frequency = freq"
                                                    :class="['py-2 text-[8px] font-black uppercase rounded-lg transition-all',
                                                        transactionForm.frequency === freq ? 'bg-accent text-white scale-105 shadow-lg shadow-accent/20' : 'text-text-muted hover:bg-white/5']">
                                                    {{ $t(freq.charAt(0).toUpperCase() + freq.slice(1)) }}
                                                </button>
                                            </div>
                                        </transition>
                                    </div>

                                    <button type="submit" :disabled="transactionForm.processing"
                                        class="w-full py-3.5 bg-accent text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:brightness-110 active:scale-95 transition-all shadow-lg shadow-accent/20 disabled:opacity-50">
                                        {{ $t('Sync to Neural Memory') }}
                                    </button>
                                </form>
                            </div>
                        </transition>

                        <!-- Monthly Recurring Expenses List -->
                        <div v-if="recurring_monthly.length > 0" class="bg-glass-bg border border-glass-border rounded-3xl p-6">
                            <h3 class="text-[9px] font-black text-text-muted uppercase tracking-widest mb-3 flex items-center gap-2">
                                📅 {{ $t('Monthly Fixed Expenses') }}
                            </h3>
                            <div class="space-y-2">
                                <div v-for="r in recurring_monthly" :key="r.id"
                                    class="flex justify-between items-center py-2 border-b border-white/5 last:border-0">
                                    <span class="text-xs font-bold text-text-main">{{ r.category }}</span>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono text-red-400">-{{ r.amount }}$</span>
                                        <span class="text-[7px] px-1.5 py-0.5 bg-purple-500/10 text-purple-400 rounded-md font-black uppercase">{{ $t(r.frequency.charAt(0).toUpperCase() + r.frequency.slice(1)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT: History + AI Advisor -->
                    <div class="lg:col-span-2 space-y-4">

                        <!-- AI Advisor -->
                        <div class="bg-glass-bg border border-glass-border rounded-3xl p-6 relative overflow-hidden">
                            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-green-600/10 rounded-full blur-2xl pointer-events-none"></div>
                            <div class="relative z-10">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-sm font-black text-text-main uppercase tracking-widest flex items-center gap-2">
                                        <span class="w-7 h-7 bg-green-500/20 rounded-xl flex items-center justify-center">🧠</span>
                                        {{ $t('Neural Advisor') }}
                                    </h3>
                                    <button @click="generatePlan" :disabled="isGeneratingPlan"
                                        class="px-4 py-2 bg-accent text-white text-[9px] font-black uppercase rounded-xl hover:brightness-110 transition disabled:opacity-50 flex items-center gap-1.5">
                                        <span v-if="isGeneratingPlan" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                        {{ isGeneratingPlan ? $t('SYNTHESIZING...') : $t('GENERATE AI STRATEGY') }}
                                    </button>
                                </div>
                                <div v-if="aiPlanText"
                                    class="p-4 bg-input-bg rounded-2xl border border-border-subtle text-xs leading-relaxed text-text-main whitespace-pre-line max-h-36 overflow-y-auto">
                                    {{ aiPlanText }}
                                </div>
                                <p v-else class="text-xs text-text-muted">{{ $t('Let AI analyze your numbers...') }}</p>
                            </div>
                        </div>

                        <!-- Transaction History -->
                        <div class="bg-glass-bg border border-glass-border rounded-3xl p-6">
                            <h3 class="text-sm font-black text-text-main uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-7 h-7 bg-white/5 rounded-xl flex items-center justify-center">📋</span>
                                {{ $t('Transactions History') }}
                            </h3>

                            <div v-if="transactions.length === 0" class="text-center py-8 text-text-muted">
                                <span class="text-4xl block mb-2">💸</span>
                                <p class="text-xs">{{ $t('No financial records!') }}</p>
                            </div>

                            <div v-else class="space-y-2 max-h-72 overflow-y-auto pr-1 custom-scrollbar">
                                <div v-for="t in transactions" :key="t.id"
                                    class="flex items-center justify-between p-3 bg-input-bg border border-border-subtle rounded-2xl group hover:border-accent/30 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-sm font-black shrink-0',
                                            t.type === 'income' ? 'bg-green-600/20 text-green-500' : 'bg-red-600/20 text-red-500']">
                                            {{ t.type === 'income' ? '+' : '−' }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-text-main">{{ t.category }}</p>
                                            <p class="text-[9px] text-text-muted">{{ new Date(t.created_at).toLocaleDateString(lang === 'ar' ? 'ar-EG' : 'en-US') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span :class="['text-sm font-black font-mono', t.type === 'income' ? 'text-green-500' : 'text-red-400']">
                                            {{ t.type === 'income' ? '+' : '−' }}{{ t.amount }}$
                                        </span>
                                        <button @click="deleteTransaction(t.id)"
                                            class="text-text-muted hover:text-red-500 opacity-0 group-hover:opacity-100 transition text-xs px-1">✖</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── REPORTS ────────────────────────────────────────────── -->
                <div class="bg-glass-bg border border-glass-border rounded-3xl p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <h3 class="text-sm font-black text-text-main uppercase tracking-widest flex items-center gap-2">
                            <span class="w-7 h-7 bg-purple-500/20 rounded-xl flex items-center justify-center">📊</span>
                            {{ $t('Reports') }}
                        </h3>
                        <!-- Tabs -->
                        <div class="flex gap-1 p-1 bg-surface-2 rounded-2xl w-fit">
                            <button v-for="tab in ['daily','weekly','monthly']" :key="tab"
                                @click="activeReport = tab"
                                :class="['px-4 py-2 text-[9px] font-black uppercase rounded-xl transition-all',
                                    activeReport === tab ? 'bg-accent text-white shadow-lg' : 'text-text-muted hover:text-text-main']">
                                {{ $t(tab.charAt(0).toUpperCase() + tab.slice(1)) }}
                            </button>
                        </div>
                    </div>

                    <!-- Daily Report: Bar Chart -->
                    <div v-if="activeReport === 'daily'">
                        <p class="text-[9px] text-text-muted font-bold uppercase tracking-widest mb-4">{{ $t('Last 7 Days') }}</p>
                        <VueApexCharts v-if="reports.daily.length" type="bar" height="200"
                            :options="barOptions" :series="barSeries" />
                        <p v-else class="text-center text-text-muted text-xs py-8">{{ $t('No data yet.') }}</p>
                    </div>

                    <!-- Weekly Report -->
                    <div v-else-if="activeReport === 'weekly'">
                        <p class="text-[9px] text-text-muted font-bold uppercase tracking-widest mb-4">{{ $t('Last 4 Weeks') }}</p>
                        <div class="grid grid-cols-4 gap-3">
                            <div v-for="w in reports.weekly" :key="w.label"
                                class="bg-surface-2 rounded-2xl p-4 text-center">
                                <p class="text-[8px] font-black text-text-muted uppercase mb-2">{{ w.label }}</p>
                                <p class="text-lg font-black text-red-400">{{ w.expense }}$</p>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly: Donut by Category + Savings Tip -->
                    <div v-else-if="activeReport === 'monthly'">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Donut -->
                            <div>
                                <p class="text-[9px] text-text-muted font-bold uppercase tracking-widest mb-4">{{ $t('Expenses by Category') }}</p>
                                <VueApexCharts v-if="reports.category.length" type="donut" height="200"
                                    :options="donutOptions" :series="donutSeries" />
                                <p v-else class="text-center text-text-muted text-xs py-8">{{ $t('No data yet.') }}</p>
                            </div>

                            <!-- Savings Tip -->
                            <div class="bg-surface-2 rounded-2xl p-5 flex flex-col gap-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-[9px] font-black text-yellow-400 uppercase tracking-widest">💡 {{ $t('Savings Ideas') }}</p>
                                    <button @click="fetchSavingsTip" :disabled="isLoadingTip"
                                        class="text-[8px] font-black text-accent hover:underline disabled:opacity-50 flex items-center gap-1">
                                        <span v-if="isLoadingTip" class="w-2.5 h-2.5 border border-accent border-t-transparent rounded-full animate-spin"></span>
                                        {{ $t('Generate') }}
                                    </button>
                                </div>
                                <div class="bg-yellow-500/5 border border-yellow-500/10 rounded-xl p-3 flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-[8px] font-black text-text-muted uppercase">{{ $t('Saved this month') }}</span>
                                        <span class="text-sm font-black text-green-400">{{ reports.monthly_saved }}$</span>
                                    </div>
                                    <p v-if="savingsTip" class="text-[10px] text-text-main leading-relaxed whitespace-pre-line">{{ savingsTip }}</p>
                                    <p v-else class="text-[10px] text-text-muted italic">{{ $t('Click Generate for smart saving ideas...') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── DAILY NEURAL SCHEDULE MODAL ──────────────────────────────── -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="showDailyModal" class="fixed inset-0 z-[100] flex items-start justify-center p-4 pt-16">
                    <div class="absolute inset-0 bg-black/70 backdrop-blur-lg" @click="showDailyModal = false"></div>

                    <div class="bg-glass-bg border border-white/10 w-full max-w-lg rounded-[28px] shadow-2xl relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 to-purple-600/5 pointer-events-none"></div>

                        <!-- Header -->
                        <div class="px-6 py-4 border-b border-white/5 flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-blue-500/20 rounded-xl flex items-center justify-center">📅</div>
                                <div>
                                    <h3 class="text-base font-black text-text-main">{{ $t('Neural Schedule') }}</h3>
                                    <p class="text-[8px] text-text-muted uppercase font-bold tracking-widest">
                                        {{ new Date().toLocaleDateString(lang === 'ar' ? 'ar-EG' : 'en-US', { weekday:'long', day:'numeric', month:'short' }) }}
                                    </p>
                                </div>
                            </div>
                            <button @click="showDailyModal = false"
                                class="w-8 h-8 bg-white/5 hover:bg-white/10 rounded-xl flex items-center justify-center text-[11px] text-text-muted transition">✖</button>
                        </div>

                        <!-- Stats Row -->
                        <div class="grid grid-cols-3 gap-2 px-6 py-4 border-b border-white/5 relative z-10">
                            <div class="bg-surface-2 rounded-2xl p-3 text-center">
                                <p class="text-[7px] font-black text-text-muted uppercase tracking-widest mb-0.5">{{ $t('Daily Goal') }}</p>
                                <p class="text-base font-black text-blue-400">{{ today_plan.allowance.toFixed(0) }}$</p>
                            </div>
                            <div class="bg-surface-2 rounded-2xl p-3 text-center">
                                <p class="text-[7px] font-black text-text-muted uppercase tracking-widest mb-0.5">{{ $t('Spent') }}</p>
                                <p class="text-base font-black text-red-400">{{ today_plan.actual_total.toFixed(0) }}$</p>
                            </div>
                            <div class="bg-surface-2 rounded-2xl p-3 text-center">
                                <p class="text-[7px] font-black text-text-muted uppercase tracking-widest mb-0.5">{{ $t('Remaining') }}</p>
                                <p class="text-base font-black" :class="today_plan.remaining > 0 ? 'text-green-400' : 'text-red-400'">{{ today_plan.remaining.toFixed(0) }}$</p>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="px-6 py-3 border-b border-white/5 relative z-10">
                            <div class="h-2 bg-white/5 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-1000"
                                    :class="spendPct > 90 ? 'bg-red-500' : spendPct > 70 ? 'bg-yellow-500' : 'bg-blue-500'"
                                    :style="{ width: spendPct + '%' }"></div>
                            </div>
                        </div>

                        <!-- Daily Items Table -->
                        <div class="px-6 py-4 relative z-10 max-h-96 overflow-y-auto custom-scrollbar">

                            <!-- Daily Recurring Items (with AI suggestions) -->
                            <p class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-3">{{ $t('Daily Expenses') }}</p>
                            <div v-if="today_plan.daily_items.length === 0" class="text-center py-6 text-text-muted">
                                <span class="text-3xl block mb-2">🌑</span>
                                <p class="text-[10px]">{{ $t('No activity detected.') }}</p>
                            </div>

                            <div v-for="item in today_plan.daily_items" :key="item.category" class="mb-4">
                                <!-- Row -->
                                <div class="flex items-center justify-between p-3 bg-surface-2 rounded-2xl border border-white/5">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-2 h-2 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)] shrink-0"></div>
                                        <span class="text-xs font-bold text-text-main">{{ item.category }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-mono font-bold text-red-400">-{{ item.amount }}$</span>
                                        <span class="text-[7px] px-2 py-0.5 bg-green-500/10 text-green-500 border border-green-500/20 rounded-md font-black uppercase">{{ $t('Daily') }}</span>
                                    </div>
                                </div>

                                <!-- AI Suggestions for this item -->
                                <div class="mt-1.5 ms-4 ps-2 border-s-2 border-blue-500/20">
                                    <div v-if="loadingSuggestion[item.category]" class="flex items-center gap-1.5 py-1">
                                        <span class="w-2.5 h-2.5 border border-blue-400 border-t-transparent rounded-full animate-spin"></span>
                                        <span class="text-[9px] text-text-muted italic">AI generating ideas...</span>
                                    </div>
                                    <div v-else-if="suggestions[item.category]?.length">
                                        <p v-for="(sug, si) in suggestions[item.category]" :key="si"
                                            class="text-[9px] text-blue-300 py-0.5">• {{ sug }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Today's Actual Spending (not recurring) -->
                            <div v-if="today_plan.actual_items.length > 0" class="mt-4 pt-4 border-t border-white/5">
                                <p class="text-[8px] font-black text-text-muted uppercase tracking-widest mb-2">{{ $t('Actual Today') }}</p>
                                <div v-for="item in today_plan.actual_items" :key="item.category + item.amount"
                                    class="flex justify-between items-center p-2.5 bg-surface-2/50 rounded-xl mb-1.5">
                                    <span class="text-[10px] font-bold text-text-main">{{ item.category }}</span>
                                    <span class="text-[10px] font-mono font-bold text-red-400">-{{ item.amount }}$</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-3 bg-white/5 text-center">
                            <p class="text-[7px] text-text-muted font-bold uppercase tracking-[0.25em] opacity-50">NEURAL_FINANCE // DAILY_PROTOCOL</p>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- ── MONTHLY FOOD PLAN MODAL ── -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="showMonthlyPlanModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0" @click="showMonthlyPlanModal = false"></div>
                    
                    <div class="relative bg-surface border border-glass-border w-full max-w-lg rounded-[2rem] shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
                        <!-- Dynamic Header -->
                        <div class="shrink-0 p-5 border-b border-white/5 flex items-center justify-between bg-white/[0.02]">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-accent/20 text-accent flex items-center justify-center text-xl shadow-inner">
                                    📆
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-text-main flex items-center gap-2">
                                        خطة طعام 30 يوم
                                    </h3>
                                    <p class="text-[8px] font-black text-text-muted uppercase tracking-widest mt-0.5">
                                        إسقاط للرصيد المتبقي مع وجباتك
                                    </p>
                                </div>
                            </div>
                            <button @click="showMonthlyPlanModal = false" class="w-8 h-8 rounded-full bg-white/5 text-text-muted hover:text-white flex items-center justify-center transition-colors">
                                ✖
                            </button>
                        </div>

                        <!-- Content -->
                        <div class="p-5 overflow-y-auto custom-scrollbar flex-1 space-y-4">
                            <div v-if="!budget_summary" class="text-center p-6 text-text-muted text-xs">
                                يرجى تفعيل ميزانية أولاً لرؤية الإسقاط.
                            </div>
                            <div v-else class="space-y-2">
                                <div v-for="projection in monthlyProjection" :key="projection.day" class="bg-surface-2/50 rounded-xl p-3 flex justify-between items-center border border-white/5 hover:bg-surface-2 transition-all">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-blue-500/10 text-blue-400 flex flex-col items-center justify-center ring-1 ring-blue-500/20">
                                            <span class="text-[8px] uppercase font-black uppercase">يوم</span>
                                            <span class="text-xs font-black">{{ projection.day }}</span>
                                        </div>
                                        <div>
                                            <p class="text-xs font-bold text-text-main">{{ projection.meal }}</p>
                                            <p class="text-[9px] text-red-400 font-mono">-{{ projection.dailyCost.toFixed(1) }}$</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-[8px] font-black text-text-muted uppercase tracking-wider mb-0.5">الرصيد</p>
                                        <p class="text-xs font-black" :class="projection.remaining > 0 ? 'text-green-400' : 'text-red-400'">{{ projection.remaining.toFixed(1) }}$</p>
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
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-10px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 4px; }
</style>
