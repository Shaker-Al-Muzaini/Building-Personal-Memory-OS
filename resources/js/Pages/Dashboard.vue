<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import { getActiveLanguage } from 'laravel-vue-i18n';

const props = defineProps({
    tasks: Array,
    habit: Object,
    goal: Object,
    overview: Object,
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const response = await axios.post(route('dashboard.generate-plan'), {
            locale: getActiveLanguage()
        });
        aiPlanText.value = response.data.plan;
    } catch (e) {
        aiPlanText.value = "Error connecting to AI advisor. Please try again later.";
    } finally {
        isGeneratingPlan.value = false;
    }
};

const taskForm = useForm({ title: '' });
const addTask = () => {
    taskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const toggleTask = (id) => {
    router.patch(route('tasks.toggle', id), {}, { preserveScroll: true });
};

const habitForm = useForm({
    name: props.habit ? props.habit.name : ''
});

const isEditingHabit = ref(!props.habit);
const saveHabit = () => {
    habitForm.post(route('habits.store'), {
        preserveScroll: true,
        onSuccess: () => { isEditingHabit.value = false; }
    });
};
</script>

<template>
    <Head :title="`${$t('Dashboard')} — Memory OS`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-3xl text-white tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-accent/20 flex items-center justify-center text-2xl">🧠</span>
                {{ $t('Your Personal  Brain') }}
            </h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-10">
            
            <!-- Hero AI Section -->
            <div class="relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-accent/20 to-purple-500/20 blur-[100px] -z-10 opacity-50 group-hover:opacity-100 transition-opacity duration-1000"></div>
                
                <div class="bg-black/40 backdrop-blur-2xl border border-white/5 p-10 rounded-[40px] shadow-2xl">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-10">
                        <div class="text-center lg:text-start flex-1">
                            <h3 class="text-4xl font-black text-white mb-4 leading-tight">
                                {{ $t('Smart Experience') }}
                            </h3>
                            <p class="text-gray-400 text-xl font-light">
                                {{ $t('Not just a website') }}
                            </p>
                        </div>
                        
                        <div class="flex-shrink-0">
                            <button 
                                @click="generatePlan" 
                                :disabled="isGeneratingPlan"
                                class="dashboard-ai-btn"
                            >
                                <span v-if="isGeneratingPlan" class="animate-spin w-6 h-6 border-4 border-white border-t-transparent rounded-full block"></span>
                                <span v-if="!isGeneratingPlan" class="flex items-center gap-3">
                                    <span class="text-2xl">✨</span>
                                    {{ $t('Analyze Day') }}
                                </span>
                                <span v-else>{{ $t('Thinking...') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- AI Result Box -->
                    <transition name="fade">
                        <div v-if="aiPlanText" class="mt-12 p-8 bg-black/60 rounded-3xl border border-accent/20 relative shadow-inner">
                            <div class="absolute top-0 right-0 p-4 opacity-10 text-6xl">🤖</div>
                            <h4 class="text-accent font-black text-xl mb-6 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-accent animate-pulse"></span>
                                AI Advisor:
                            </h4>
                            <div class="text-gray-300 leading-[1.8] text-lg whitespace-pre-wrap font-light">
                                {{ aiPlanText }}
                            </div>
                        </div>
                    </transition>
                </div>
            </div>

            <!-- Overview Section (Quick Access) -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Money Glance -->
                <div @click="router.visit(route('money.index'))" class="bg-gradient-to-br from-green-500/10 to-transparent border border-green-500/10 p-6 rounded-3xl cursor-pointer hover:border-green-500/30 transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-3xl">💰</span>
                        <span class="text-green-500 font-bold group-hover:scale-110 transition-transform">→</span>
                    </div>
                    <h4 class="text-gray-400 text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Wallet Balance') }}</h4>
                    <p class="text-2xl font-black text-white bidi-plaintext">{{ overview.balance }}</p>
                </div>

                <!-- Neural Logic Score -->
                <div @click="router.visit(route('decisions.index'))" class="bg-gradient-to-br from-blue-500/10 to-transparent border border-blue-500/10 p-6 rounded-3xl cursor-pointer hover:border-blue-500/30 transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-3xl">⚖️</span>
                        <span class="text-blue-500 font-bold group-hover:scale-110 transition-transform">→</span>
                    </div>
                    <h4 class="text-gray-400 text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Logic Avg') }}</h4>
                    <div class="flex items-baseline gap-2">
                        <p class="text-2xl font-black text-white">{{ overview.decision_logic_avg }}%</p>
                        <span class="text-[10px] text-gray-600 font-mono">({{ overview.sealed_decisions_count }} {{ $t('Sealed') }})</span>
                    </div>
                </div>

                <!-- Idea Reminder -->
                <div @click="router.visit(route('ideas.index'))" class="bg-gradient-to-br from-purple-500/10 to-transparent border border-purple-500/10 p-6 rounded-3xl cursor-pointer hover:border-purple-500/30 transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-3xl">💡</span>
                        <span class="text-purple-500 font-bold group-hover:scale-110 transition-transform">→</span>
                    </div>
                    <h4 class="text-gray-400 text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Last Idea') }}</h4>
                    <p class="text-white font-bold truncate bidi-plaintext">{{ overview.last_idea || $t('No ideas yet') }}</p>
                </div>

                <!-- Social Reminder -->
                <div @click="router.visit(route('people.index'))" class="bg-gradient-to-br from-orange-500/10 to-transparent border border-orange-500/10 p-6 rounded-3xl cursor-pointer hover:border-orange-500/30 transition-all group">
                    <div class="flex justify-between items-start mb-4">
                        <span class="text-3xl">🤝</span>
                        <span class="text-orange-500 font-bold group-hover:scale-110 transition-transform">→</span>
                    </div>
                    <h4 class="text-gray-400 text-sm mb-1 uppercase tracking-widest font-black">{{ $t('Reconnect with') }}</h4>
                    <p class="text-white font-bold bidi-plaintext">{{ overview.person_to_contact || $t('Add friends') }}</p>
                </div>
            </div>

            <!-- Dashboard Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <!-- Tasks List -->
                <div class="dashboard-card group">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-2xl font-black text-white flex items-center gap-3">
                            <span class="p-2 bg-blue-500/10 rounded-lg text-blue-400">📋</span>
                            {{ $t('Tasks of the Day') }}
                        </h3>
                    </div>

                    <form @submit.prevent="addTask" class="mb-8 relative flex items-center">
                        <input 
                            v-model="taskForm.title" 
                            type="text"
                            :placeholder="$t('Add a new task')"
                            class="dashboard-input w-full bidi-plaintext ltr:pl-14 rtl:pr-14"
                            required
                            dir="auto"
                        />
                        <button type="submit" class="absolute ltr:right-2 rtl:left-2 px-4 py-2 bg-accent text-white rounded-xl font-black hover:bg-accent/80 active:scale-95 transition-all">
                            +
                        </button>
                    </form>

                    <div class="space-y-4 max-h-[400px] overflow-y-auto ltr:pr-2 rtl:pl-2 custom-scroll">
                        <div v-for="task in tasks" :key="task.id" 
                            @click="toggleTask(task.id)"
                            :class="['task-pill group', task.status === 'completed' ? 'opacity-50' : '']"
                        >
                            <div :class="['w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all', task.status === 'completed' ? 'bg-accent border-accent text-white' : 'border-white/10 group-hover:border-accent']">
                                <span v-if="task.status === 'completed'">✓</span>
                            </div>
                            <span :class="['flex-1 transition-all bidi-plaintext', task.status === 'completed' ? 'line-through text-gray-600' : 'text-gray-200']">
                                {{ task.title }}
                            </span>
                        </div>
                        <div v-if="tasks.length === 0" class="text-center py-10 text-gray-600 italic">
                            {{ $t('No tasks yet') }}
                        </div>
                    </div>
                </div>

                <!-- Daily Goal -->
                <div class="dashboard-card">
                    <h3 class="text-2xl font-black text-white flex items-center gap-3 mb-8">
                        <span class="p-2 bg-red-500/10 rounded-lg text-red-400">🎯</span>
                        {{ $t('Goal of the Day') }}
                    </h3>
                    <div class="h-[300px] flex flex-col items-center justify-center bg-gradient-to-tr from-black/60 to-accent/5 rounded-[30px] border border-white/5 relative overflow-hidden group">
                        <div class="absolute inset-0 bg-accent/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        <span class="text-6xl mb-6 drop-shadow-2xl">🚀</span>
                        <h4 class="text-2xl font-black text-white text-center px-4">{{ goal?.title }}</h4>
                        <p class="text-gray-500 mt-4 text-sm uppercase tracking-widest">Focus Level: High</p>
                    </div>
                </div>

                <!-- Daily Habit -->
                <div class="dashboard-card px-0 overflow-hidden">
                    <div class="px-8 flex justify-between items-center mb-8">
                        <h3 class="text-2xl font-black text-white flex items-center gap-3">
                            <span class="p-2 bg-green-500/10 rounded-lg text-green-400">🔄</span>
                            {{ $t('Habit of the Day') }}
                        </h3>
                        <button v-if="!isEditingHabit && habit" @click="isEditingHabit = true" class="text-xs font-bold text-accent hover:underline uppercase tracking-widest">
                            {{ $t('Edit') }}
                        </button>
                    </div>

                    <div class="px-8 flex-1 flex flex-col items-center justify-center">
                        <div v-if="habit && !isEditingHabit" class="w-full h-full min-h-[200px] flex flex-col items-center justify-center bg-black/40 rounded-[30px] border border-white/5 hover:border-accent/40 transition-colors p-6">
                            <div class="w-20 h-20 rounded-full bg-accent/20 flex items-center justify-center text-4xl text-accent shadow-[0_0_30px_rgba(6,155,255,0.1)] mb-4 font-black border border-accent/30">
                                {{ habit.name.substring(0, 1) }}
                            </div>
                            <h4 class="text-xl font-black text-white text-center">{{ habit.name }}</h4>
                            <p class="text-sm text-gray-500 mt-3 text-center leading-relaxed">
                                {{ $t('Habit Subtitle') }}
                            </p>
                        </div>

                        <div v-else class="h-full flex flex-col items-center justify-center">
                            <form @submit.prevent="saveHabit" class="w-full space-y-4">
                                <input 
                                    v-model="habitForm.name" 
                                    type="text"
                                    placeholder="e.g. 20 mins reading"
                                    class="dashboard-input w-full text-center py-4"
                                    required
                                />
                                <button type="submit" class="w-full bg-accent text-white py-4 rounded-2xl font-black shadow-lg hover:brightness-110 active:scale-95 transition-all">
                                    {{ habit ? 'Update Habit' : 'Save Habit' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.dashboard-card {
    background: rgba(255, 255, 255, 0.02);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 40px;
    padding: 2.5rem;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.dashboard-card:hover {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(6, 155, 255, 0.2);
    transform: translateY(-8px);
}

.dashboard-ai-btn {
    background: rgba(255, 255, 255, 0.05);
    color: white;
    padding: 1.2rem 3rem;
    border-radius: 100px;
    font-weight: 700;
    font-size: 1.2rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dashboard-ai-btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(6, 155, 255, 0.5);
    box-shadow: 0 0 30px rgba(6, 155, 255, 0.15);
}

.dashboard-input {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    padding: 1rem 1.5rem;
    color: white;
    font-size: 1.1rem;
    transition: all 0.3s;
}

.dashboard-input:focus {
    outline: none;
    border-color: #069BFF;
    background: rgba(255, 255, 255, 0.05);
    box-shadow: 0 0 20px rgba(6, 155, 255, 0.2);
}

.task-pill {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    padding: 1.2rem 1.5rem;
    background: rgba(255, 255, 255, 0.02);
    border: 1px solid rgba(255, 255, 255, 0.03);
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.3s;
}

.task-pill:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(6, 155, 255, 0.4);
}

.custom-scroll::-webkit-scrollbar { width: 3px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(6, 155, 255, 0.3); border-radius: 10px; }

.fade-enter-active, .fade-leave-active { transition: opacity 0.5s, transform 0.5s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(20px); }
</style>
