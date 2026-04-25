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
    chart: { type: 'bar', toolbar: { show: false }, background: 'transparent' },
    colors: ['#ef4444', '#22c55e'],
    plotOptions: { bar: { borderRadius: 10, columnWidth: '40%' } },
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.reports?.daily?.map(d => d.label) || [],
        labels: { style: { colors: 'var(--c-text-muted)', fontSize: '10px', fontWeight: 600 } },
    },
    yaxis: { labels: { style: { colors: 'var(--c-text-muted)', fontSize: '10px' } } },
    theme: { mode: isDark.value ? 'dark' : 'light' },
    grid: { borderColor: 'var(--c-border)', strokeDashArray: 4 },
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
    plotOptions: { pie: { donut: { size: '75%', labels: { show: true, name: { show: false }, value: { color: 'var(--c-text)', fontSize: '14px', fontWeight: 900 } } } } },
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
        <template #header>
            <div class="backdrop-blur-xl bg-slate-900/50 border-b border-white/5 px-6 py-8">
                <div class="max-w-[1600px] mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-2xl shadow-xl shadow-emerald-500/20 animate-float">
                            💰
                        </div>
                        <div>
                            <h2 class="text-3xl font-black tracking-tight text-white uppercase">{{ $t('Smart Budget') }}</h2>
                            <p class="text-[10px] text-emerald-400 font-bold tracking-widest uppercase opacity-70 mt-1">✧ {{ $t('Finance_Protocol.v9') }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="showAddForm = !showAddForm" class="btn-neural-premium btn-neural-primary bg-gradient-to-r from-emerald-600 to-teal-600 shadow-emerald-500/20">
                            <span class="text-lg">{{ showAddForm ? '✕' : '＋' }}</span>
                            {{ showAddForm ? $t('Close Protocol') : $t('New Injection') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="min-h-screen bg-slate-950 text-slate-200 py-10">
            <div class="max-w-[1600px] mx-auto px-4 lg:px-8 space-y-10">
                
                <!-- ── CORE STATS BENTO ────────────────────────────────────────── -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Income Card -->
                    <div class="neural-card-premium p-6 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest">{{ $t('Total Inflow') }}</span>
                            <span class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">📈</span>
                        </div>
                        <div class="flex items-baseline gap-1 relative z-10">
                            <span class="text-4xl font-black tracking-tighter text-white">{{ summary.income }}</span>
                            <span class="text-xs font-bold text-slate-500">$</span>
                        </div>
                        <div class="mt-4 h-1 bg-white/5 rounded-full overflow-hidden relative z-10">
                            <div class="h-full bg-emerald-500 rounded-full w-full opacity-50 animate-pulse"></div>
                        </div>
                    </div>

                    <!-- Expenses Card -->
                    <div class="neural-card-premium p-6 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-rose-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <span class="text-[9px] font-black text-rose-500 uppercase tracking-widest">{{ $t('Committed Costs') }}</span>
                            <span class="w-8 h-8 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">📉</span>
                        </div>
                        <div class="flex items-baseline gap-1 relative z-10">
                            <span class="text-4xl font-black tracking-tighter text-white">
                                {{ budget_summary ? budget_summary.total_consumed : summary.expense }}
                            </span>
                            <span class="text-xs font-bold text-slate-500">$</span>
                        </div>
                        <div class="mt-4 h-1 bg-white/5 rounded-full overflow-hidden relative z-10">
                            <div class="h-full bg-rose-500 rounded-full" :style="{ width: summary.income ? Math.min(100,((budget_summary ? budget_summary.total_consumed : summary.expense)/summary.income*100))+'%' : '0%' }"></div>
                        </div>
                    </div>

                    <!-- Daily Target Node -->
                    <div @click="showDailyModal = true" class="neural-card-premium p-6 group cursor-pointer hover:border-emerald-500/30">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-emerald-400 uppercase tracking-widest">{{ $t("Neural Allowance") }}</span>
                                <span class="text-[7px] text-emerald-500/60 font-bold animate-pulse uppercase">Protocol_Active</span>
                            </div>
                            <span class="w-8 h-8 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">🎯</span>
                        </div>
                        <div class="flex items-baseline gap-1 relative z-10">
                            <span class="text-4xl font-black tracking-tighter text-white">{{ today_plan.remaining.toFixed(0) }}</span>
                            <span class="text-xs font-bold text-slate-500">$</span>
                        </div>
                        <div class="mt-4 h-1 bg-white/5 rounded-full overflow-hidden relative z-10">
                            <div class="h-full bg-emerald-500 rounded-full transition-all duration-1000" :style="{ width: spendPct + '%' }"></div>
                        </div>
                    </div>

                    <!-- Budget Health -->
                    <div v-if="budget_summary" class="neural-card-premium p-6 group">
                        <div class="absolute inset-0 bg-gradient-to-br from-amber-500/5 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <div class="flex justify-between items-start mb-4 relative z-10">
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black text-amber-500 uppercase tracking-widest">{{ $t('Stability Score') }}</span>
                                <span class="text-[7px] text-amber-600 font-bold uppercase tracking-widest">{{ budget_summary.days_left }} {{ $t('Cycles Remaining') }}</span>
                            </div>
                            <span class="w-8 h-8 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500">🧬</span>
                        </div>
                        <div class="flex items-baseline gap-1 relative z-10">
                            <span class="text-4xl font-black tracking-tighter text-white">{{ (100 - healthPct).toFixed(0) }}</span>
                            <span class="text-xs font-bold text-slate-500">%</span>
                        </div>
                        <div class="mt-4 h-1 bg-white/5 rounded-full overflow-hidden relative z-10">
                            <div class="h-full rounded-full transition-all duration-1000"
                                :class="healthPct > 80 ? 'bg-rose-500' : healthPct > 60 ? 'bg-amber-500' : 'bg-emerald-500'"
                                :style="{ width: (100 - healthPct) + '%' }"></div>
                        </div>
                    </div>
                    <div v-else class="neural-card-premium p-6 flex flex-col items-center justify-center border-dashed border-white/10">
                        <p class="text-[10px] font-black text-slate-600 uppercase tracking-widest">{{ $t('Protocol Offline') }}</p>
                    </div>
                </div>

                <!-- ── MAIN INTERFACE ────────────────────────────────────────── -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    
                    <!-- Sidebar: Controls & Recurrings -->
                    <div class="lg:col-span-4 space-y-8">
                        
                        <!-- Quick Add Form (Neural) -->
                        <transition name="oracle-slide">
                            <div v-if="showAddForm" class="neural-card-premium p-8 border-emerald-500/30">
                                <h3 class="text-xs font-black uppercase tracking-[0.3em] mb-6 flex items-center gap-2 text-emerald-400">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ $t('Financial Injection') }}
                                </h3>
                                <form @submit.prevent="saveTransaction" class="space-y-6">
                                    <div class="grid grid-cols-2 gap-2 p-1 bg-slate-900/50 rounded-2xl border border-white/5">
                                        <button type="button" @click="transactionForm.type = 'expense'"
                                            :class="['py-3 rounded-xl text-[10px] font-black uppercase transition-all', transactionForm.type === 'expense' ? 'bg-rose-600 text-white shadow-lg shadow-rose-500/20' : 'text-slate-500 hover:text-slate-300']">
                                            💸 {{ $t('Expense') }}
                                        </button>
                                        <button type="button" @click="transactionForm.type = 'income'"
                                            :class="['py-3 rounded-xl text-[10px] font-black uppercase transition-all', transactionForm.type === 'income' ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-500/20' : 'text-slate-500 hover:text-slate-300']">
                                            💵 {{ $t('Income') }}
                                        </button>
                                    </div>

                                    <div class="space-y-4">
                                        <input v-model="transactionForm.amount" type="number" step="0.01" required
                                            class="neural-input-premium w-full text-xl font-black text-white" :placeholder="$t('Quantum Amount ($)')" />
                                        
                                        <input v-model="transactionForm.category" type="text" required
                                            class="neural-input-premium w-full" :placeholder="$t('Neural Category (e.g. Subsistence)')" />
                                    </div>

                                    <div class="pt-4 border-t border-white/5">
                                        <div class="flex items-center justify-between mb-4">
                                            <span class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ $t('Temporal Cycle') }}</span>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" v-model="transactionForm.is_recurring" class="sr-only peer">
                                                <div class="w-11 h-6 bg-slate-800 rounded-full peer peer-checked:bg-emerald-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full shadow-inner"></div>
                                            </label>
                                        </div>

                                        <transition name="slide-down">
                                            <div v-if="transactionForm.is_recurring" class="grid grid-cols-4 gap-1">
                                                <button v-for="freq in ['daily','weekly','monthly','yearly']" :key="freq"
                                                    type="button" @click="transactionForm.frequency = freq"
                                                    :class="['py-2 rounded-lg text-[8px] font-black uppercase transition-all',
                                                        transactionForm.frequency === freq ? 'bg-emerald-600 text-white shadow-lg' : 'bg-slate-800 text-slate-500 hover:text-slate-300']">
                                                    {{ $t(freq) }}
                                                </button>
                                            </div>
                                        </transition>
                                    </div>

                                    <button type="submit" :disabled="transactionForm.processing"
                                        class="w-full btn-neural-premium btn-neural-primary bg-gradient-to-r from-emerald-600 to-teal-600 py-4 shadow-emerald-500/20">
                                        {{ $t('Synchronize Nexus') }}
                                    </button>
                                </form>
                            </div>
                        </transition>

                        <!-- Smart Scheduler Portal -->
                        <div class="neural-card-premium p-8">
                            <div class="flex items-center justify-between mb-8">
                                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-emerald-400">Quantum_Nexus</h3>
                                <button @click="showMonthlyPlanModal = true" class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center hover:scale-110 transition-transform shadow-inner">📅</button>
                            </div>
                            
                            <div v-if="budget_summary" class="grid grid-cols-2 gap-3 mb-8">
                                <div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 text-center shadow-inner">
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">{{ $t('Cycle Target') }}</p>
                                    <p class="text-xl font-black text-emerald-500">{{ budget_summary.daily_allowance.toFixed(1) }}$</p>
                                </div>
                                <div class="p-4 rounded-2xl bg-slate-900/50 border border-white/5 text-center shadow-inner">
                                    <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-1">{{ $t('Available') }}</p>
                                    <p class="text-xl font-black" :class="today_plan.remaining > 0 ? 'text-emerald-500' : 'text-rose-500'">{{ today_plan.remaining.toFixed(1) }}$</p>
                                </div>
                            </div>

                            <form @submit.prevent="saveBudget" class="space-y-4">
                                <input v-model="budgetForm.amount" type="number" min="1" step="any"
                                    class="neural-input-premium w-full text-center font-bold" :placeholder="$t('Initialize Budget Goal ($)')" required />
                                
                                <div class="grid grid-cols-2 gap-2 p-1 bg-slate-900/50 rounded-2xl border border-white/5">
                                    <button type="button" @click="budgetForm.period_type = 'monthly'"
                                        :class="['py-2.5 rounded-xl text-[9px] font-black uppercase transition-all', budgetForm.period_type === 'monthly' ? 'bg-emerald-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300']">
                                        {{ $t('Monthly') }}
                                    </button>
                                    <button type="button" @click="budgetForm.period_type = 'weekly'"
                                        :class="['py-2.5 rounded-xl text-[9px] font-black uppercase transition-all', budgetForm.period_type === 'weekly' ? 'bg-emerald-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-300']">
                                        {{ $t('Weekly') }}
                                    </button>
                                </div>
                                <button type="submit" class="w-full btn-neural-premium btn-neural-primary bg-gradient-to-r from-slate-800 to-slate-900 text-white py-4 hover:brightness-125 border border-white/5">
                                    {{ budget_summary ? $t('Re-initialize Protocol') : $t('Boot Nexus') }}
                                </button>
                            </form>
                        </div>

                        <!-- Recurring Commitments Hub -->
                        <div v-if="recurring_monthly.length > 0" class="neural-card-premium p-8">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xs font-black uppercase tracking-[0.3em] text-rose-500">{{ $t('Fixed Entropies') }}</h3>
                                <button @click="clearAllRecurring" class="text-[8px] font-black text-rose-500/60 hover:text-rose-500 transition-colors uppercase tracking-widest">
                                    {{ $t('Purge All') }}
                                </button>
                            </div>
                            <div class="space-y-3">
                                <div v-for="r in recurring_monthly" :key="r.id"
                                    class="group flex items-center justify-between p-4 rounded-2xl bg-slate-900/50 border border-white/5 hover:border-rose-500/30 transition-all duration-300">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-black text-white uppercase tracking-tight">{{ r.category }}</span>
                                        <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ $t(r.frequency) }}</span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm font-black text-rose-500 font-mono">{{ r.amount }}$</span>
                                        <button @click="deleteRecurringTransaction(r.id)" class="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-rose-500 transition-all p-1">✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Section: History & Analytics -->
                    <div class="lg:col-span-8 space-y-8">
                        
                        <!-- Analytics Dashboard -->
                        <div class="neural-card-premium p-10">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10 relative z-10">
                                <div class="space-y-1">
                                    <h3 class="text-2xl font-black tracking-tighter uppercase text-white">{{ $t('Quantum Flow') }}</h3>
                                    <p class="text-[10px] text-emerald-400 font-black uppercase tracking-[0.4em] opacity-60">Deep_Synthesis // Tier_1</p>
                                </div>
                                <div class="flex gap-1 p-1 bg-slate-900/50 rounded-2xl border border-white/5">
                                    <button v-for="tab in ['daily','weekly','monthly']" :key="tab"
                                        @click="activeReport = tab"
                                        :class="['px-6 py-2.5 text-[9px] font-black uppercase rounded-xl transition-all',
                                            activeReport === tab ? 'bg-emerald-600 text-white shadow-xl shadow-emerald-500/20' : 'text-slate-500 hover:text-slate-300']">
                                        {{ $t(tab) }}
                                    </button>
                                </div>
                            </div>

                            <div class="min-h-[300px] relative z-10">
                                <div v-if="activeReport === 'daily'">
                                    <VueApexCharts v-if="reports.daily.length" type="bar" height="300" :options="barOptions" :series="barSeries" />
                                    <div v-else class="h-full flex items-center justify-center opacity-10 py-20">🕳️</div>
                                </div>

                                <div v-else-if="activeReport === 'weekly'" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div v-for="w in reports.weekly" :key="w.label"
                                        class="neural-card-premium p-6 text-center border-white/5 hover:border-emerald-500/20 transition-all">
                                        <p class="text-[8px] font-black text-slate-500 uppercase mb-4 tracking-widest">{{ w.label }}</p>
                                        <p class="text-2xl font-black text-rose-500 font-mono">{{ w.expense }}$</p>
                                    </div>
                                </div>

                                <div v-else-if="activeReport === 'monthly'" class="grid grid-cols-1 md:grid-cols-12 gap-8 items-center">
                                    <div class="md:col-span-7">
                                        <VueApexCharts v-if="reports.category.length" type="donut" height="320" :options="donutOptions" :series="donutSeries" />
                                    </div>
                                    <div class="md:col-span-5 space-y-6">
                                        <div class="p-6 rounded-[2rem] bg-emerald-500/5 border border-emerald-500/20 relative overflow-hidden group">
                                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                            <div class="flex items-center justify-between mb-4 relative z-10">
                                                <span class="text-[10px] font-black text-emerald-400 uppercase tracking-widest">💡 {{ $t('Strategy Node') }}</span>
                                                <button @click="fetchSavingsTip" :disabled="isLoadingTip" class="text-[10px] font-black text-emerald-400/60 hover:text-emerald-400 transition-colors uppercase tracking-widest">Synth</button>
                                            </div>
                                            <p v-if="savingsTip" class="text-[11px] leading-relaxed font-medium italic bidi-plaintext text-slate-300 relative z-10">{{ savingsTip }}</p>
                                            <p v-else class="text-[10px] text-slate-600 font-light relative z-10">{{ $t('Awaiting strategy synthesis...') }}</p>
                                        </div>
                                        <div class="p-8 rounded-[2rem] bg-emerald-600/10 border border-emerald-500/30 text-center shadow-inner">
                                            <p class="text-[8px] font-black text-emerald-500 uppercase tracking-widest mb-1">{{ $t('Conserved Value') }}</p>
                                            <p class="text-4xl font-black text-emerald-500 font-mono">{{ reports.monthly_saved }}$</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- History Node -->
                        <div class="neural-card-premium p-10">
                            <div class="flex items-center justify-between mb-10 relative z-10">
                                <h3 class="text-2xl font-black tracking-tighter uppercase text-white">{{ $t('Temporal Log') }}</h3>
                                <span class="px-4 py-1 rounded-full bg-slate-900/50 border border-white/5 text-[10px] font-black text-slate-500 tracking-widest">{{ transactions.length }} VESTIGES</span>
                            </div>

                            <div v-if="transactions.length === 0" class="py-20 text-center opacity-10 relative z-10">
                                <span class="text-6xl block mb-4">🌑</span>
                                <p class="text-xs font-black uppercase tracking-[0.5em]">{{ $t('Zero Resonance') }}</p>
                            </div>

                            <div v-else class="space-y-4 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar relative z-10">
                                <div v-for="t in transactions" :key="t.id"
                                    class="group flex items-center justify-between p-6 rounded-[2rem] bg-slate-900/30 border border-white/5 hover:bg-slate-900/60 hover:border-emerald-500/20 transition-all duration-500">
                                    <div class="flex items-center gap-6">
                                        <div :class="['w-14 h-14 rounded-2xl flex items-center justify-center text-xl shadow-xl transition-transform group-hover:scale-110 shadow-black/40',
                                            t.type === 'income' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20']">
                                            {{ t.type === 'income' ? '＋' : '－' }}
                                        </div>
                                        <div>
                                            <p class="text-lg font-black text-white uppercase tracking-tight">{{ t.category }}</p>
                                            <p class="text-[10px] text-slate-500 font-bold tracking-[0.2em] uppercase mt-0.5">{{ new Date(t.created_at).toLocaleDateString(lang === 'ar' ? 'ar-EG' : 'en-US', { day:'numeric', month:'short' }) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-8">
                                        <span :class="['text-2xl font-black font-mono tracking-tighter', t.type === 'income' ? 'text-emerald-400' : 'text-rose-400']">
                                            {{ t.type === 'income' ? '+' : '−' }}{{ t.amount }}$
                                        </span>
                                        <button @click="deleteTransaction(t.id)"
                                            class="opacity-0 group-hover:opacity-100 w-10 h-10 rounded-xl flex items-center justify-center bg-slate-800 text-slate-500 hover:text-rose-500 transition-all border border-white/5">✕</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── MODALS (Premium Redesign) ────────────────────────────────── -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="showDailyModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 backdrop-blur-3xl bg-slate-950/90">
                    <div class="neural-card-premium w-full max-w-2xl bg-slate-900 border-white/10 relative shadow-2xl shadow-emerald-500/10">
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/5 to-teal-500/5 -z-10"></div>
                        
                        <div class="p-8 border-b border-white/5 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl shadow-inner border border-emerald-500/20">🛰️</div>
                                <div>
                                    <h3 class="text-2xl font-black tracking-tighter uppercase text-white">{{ $t('Cycle Synthesis') }}</h3>
                                    <p class="text-[9px] text-emerald-400 font-bold uppercase tracking-[0.4em] mt-1">{{ new Date().toLocaleDateString(lang === 'ar' ? 'ar-EG' : 'en-US', { weekday:'long', day:'numeric', month:'long' }) }}</p>
                                </div>
                            </div>
                            <button @click="showDailyModal = false" class="w-10 h-10 rounded-xl bg-white/5 text-slate-500 hover:text-white flex items-center justify-center transition-all border border-white/10">✕</button>
                        </div>

                        <div class="grid grid-cols-3 gap-4 p-8 bg-black/20">
                            <div class="neural-card-premium p-6 text-center border-white/5 shadow-inner">
                                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">{{ $t('Cycle Target') }}</p>
                                <p class="text-3xl font-black text-emerald-400 font-mono">{{ today_plan.allowance.toFixed(0) }}$</p>
                            </div>
                            <div class="neural-card-premium p-6 text-center border-white/5 shadow-inner">
                                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">{{ $t('Dissipated') }}</p>
                                <p class="text-3xl font-black text-rose-500 font-mono">{{ today_plan.actual_total.toFixed(0) }}$</p>
                            </div>
                            <div class="neural-card-premium p-6 text-center border-white/5 shadow-inner">
                                <p class="text-[8px] font-black text-slate-500 uppercase tracking-widest mb-2">{{ $t('Potential') }}</p>
                                <p class="text-3xl font-black font-mono" :class="today_plan.remaining > 0 ? 'text-emerald-500' : 'text-rose-500'">{{ today_plan.remaining.toFixed(0) }}$</p>
                            </div>
                        </div>

                        <div class="p-8 space-y-6 max-h-[500px] overflow-y-auto custom-scrollbar">
                            <div v-for="item in today_plan.daily_items" :key="item.category" class="group relative">
                                <div class="flex items-center justify-between p-6 rounded-[2rem] bg-slate-800/30 border border-white/5 group-hover:border-emerald-500/40 transition-all duration-500">
                                    <div class="flex items-center gap-6">
                                        <div class="w-3 h-3 rounded-full bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.8)]"></div>
                                        <span class="text-lg font-black text-white uppercase tracking-tight">{{ item.category }}</span>
                                    </div>
                                    <div class="flex items-center gap-6">
                                        <span class="text-xl font-black text-rose-500 font-mono">-{{ item.amount }}$</span>
                                        <button @click="deleteRecurringTransaction(item.id)" class="opacity-0 group-hover:opacity-100 text-slate-600 hover:text-rose-500 p-1">✕</button>
                                    </div>
                                </div>
                                <div class="mt-4 ms-12 grid grid-cols-1 gap-3">
                                    <div v-for="(sug, si) in suggestions[item.category]" :key="si"
                                        class="text-[11px] text-emerald-300/60 font-medium bidi-plaintext flex items-center gap-3 p-3 rounded-xl bg-white/5 border border-white/5 italic">
                                        <span class="w-1 h-1 rounded-full bg-emerald-500/40"></span>
                                        {{ sug }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>
        </Teleport>

        <!-- 30-Day Projection (Neural) -->
        <Teleport to="body">
            <transition name="fade">
                <div v-if="showMonthlyPlanModal" class="fixed inset-0 bg-slate-950/95 backdrop-blur-3xl z-[100] flex items-center justify-center p-4">
                    <div class="neural-card-premium w-full max-w-2xl bg-slate-900 border-white/10 max-h-[85vh] flex flex-col shadow-2xl">
                        <div class="p-8 border-b border-white/5 flex items-center justify-between relative z-10">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-3xl shadow-inner border border-emerald-500/20">🗓️</div>
                                <div>
                                    <h3 class="text-2xl font-black tracking-tighter uppercase text-white">Temporal_Projection</h3>
                                    <p class="text-[9px] text-emerald-400 font-black uppercase tracking-[0.4em] mt-1">30_Cycle_Simulation</p>
                                </div>
                            </div>
                            <button @click="showMonthlyPlanModal = false" class="w-10 h-10 rounded-xl bg-white/5 text-slate-500 hover:text-white flex items-center justify-center transition-all border border-white/10">✕</button>
                        </div>

                        <div class="p-8 overflow-y-auto custom-scrollbar flex-1 relative z-10">
                            <div v-if="!budget_summary" class="text-center py-20 opacity-10 font-black tracking-[0.5em] text-white">PROTOCOL_OFFLINE</div>
                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4 pb-10">
                                <div v-for="projection in monthlyProjection" :key="projection.day" 
                                    class="p-6 rounded-[2rem] bg-slate-800/20 border border-white/5 hover:bg-slate-800/40 hover:border-emerald-500/30 transition-all duration-500 flex justify-between items-center group">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-slate-900 border border-white/10 text-emerald-400 flex flex-col items-center justify-center font-black shadow-inner">
                                            <span class="text-[6px] opacity-40">CYC</span>
                                            <span class="text-sm leading-none">{{ projection.day }}</span>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-black text-white group-hover:text-emerald-400 transition-colors uppercase tracking-tight">{{ projection.meal }}</span>
                                            <span class="text-[8px] font-mono text-rose-500/60 font-bold">-{{ projection.dailyCost.toFixed(1) }}$</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-black font-mono tracking-tighter" :class="projection.remaining > 0 ? 'text-emerald-400' : 'text-rose-400'">{{ projection.remaining.toFixed(1) }}$</p>
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
.oracle-slide-enter-active, .oracle-slide-leave-active { transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.oracle-slide-enter-from, .oracle-slide-leave-to { opacity: 0; transform: translateY(-20px); }

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.05); border-radius: 10px; }

.bidi-plaintext { unicode-bidi: plaintext; text-align: start; }
</style>
