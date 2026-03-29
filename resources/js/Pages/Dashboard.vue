<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({
    tasks: Array,
    habit: Object,
    goal: Object,
});

const isGeneratingPlan = ref(false);
const aiPlanText = ref(null);

const generatePlan = async () => {
    isGeneratingPlan.value = true;
    try {
        const response = await axios.post(route('dashboard.generate-plan'));
        aiPlanText.value = response.data.plan;
    } catch (e) {
        aiPlanText.value = "حدث خطأ أثناء محاولة الاتصال بـ الذكاء الاصطناعي، يرجى المحاولة لاحقاً.";
    } finally {
        isGeneratingPlan.value = false;
    }
};

// Form for Adding Task
const taskForm = useForm({
    title: ''
});

const addTask = () => {
    taskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const toggleTask = (id) => {
    router.patch(route('tasks.toggle', id), {}, { preserveScroll: true });
};

// Form for Editing Habit
const habitForm = useForm({
    name: props.habit ? props.habit.name : ''
});

const isEditingHabit = ref(!props.habit);

const saveHabit = () => {
    habitForm.post(route('habits.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isEditingHabit.value = false;
        }
    });
};
</script>

<template>
    <Head title="لوحة التحكم — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-accent leading-tight flex items-center gap-2">
                <span>🧠</span> عقل المساعد الشخصي الخاص بك
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col gap-8">
                
                <!-- Welcome & AI Plan Generation -->
                <div class="bg-gray-900 border border-gray-800 overflow-hidden shadow-2xl sm:rounded-2xl p-8 relative">
                    <div class="absolute -top-24 -right-24 w-60 h-60 bg-secondary opacity-20 rounded-full blur-[80px]"></div>

                    <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                        <div>
                            <h3 class="text-3xl font-bold text-white mb-2">كيف هو مزاجك اليوم؟</h3>
                            <p class="text-gray-400 text-lg">أنا هنا لتحليل يومك وتنظيمه لتكون أفضل نسخة من نفسك.</p>
                        </div>
                        <button 
                            @click="generatePlan" 
                            :disabled="isGeneratingPlan"
                            class="rounded-full bg-accent px-8 py-3 text-white hover:bg-opacity-80 transition duration-300 font-bold shadow-[0_0_20px_rgba(6,155,255,0.3)] disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            <span v-if="isGeneratingPlan" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                            <span v-if="!isGeneratingPlan">✨ تحليّل يومي (AI)</span>
                            <span v-else>جاري التحليل...</span>
                        </button>
                    </div>

                    <!-- AI Response Area -->
                    <div v-if="aiPlanText" class="mt-8 p-6 bg-black bg-opacity-40 rounded-xl border border-gray-800 whitespace-pre-wrap leading-relaxed">
                       <div class="flex items-center gap-2 mb-4 text-accent">
                           <span class="text-xl">🤖</span>
                           <h4 class="font-bold text-xl">خطة ومشورة الذكاء الاصطناعي لليوم:</h4>
                       </div>
                       {{ aiPlanText }}
                    </div>
                </div>

                <!-- Three main sections -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    
                    <!-- Today's Tasks -->
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl relative flex flex-col">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-white">📋 مهام اليوم</h3>
                        </div>
                        
                        <!-- Add Task Form -->
                        <form @submit.prevent="addTask" class="mb-4 flex gap-2">
                            <input 
                                v-model="taskForm.title" 
                                type="text"
                                placeholder="إضافة مهمة جديدة..."
                                class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-accent focus:ring-1 focus:ring-accent"
                                required
                            />
                            <button 
                                type="submit" 
                                :disabled="taskForm.processing"
                                class="bg-accent text-white px-4 py-2 rounded-lg font-bold hover:bg-opacity-80 transition disabled:opacity-50"
                            >
                                +
                            </button>
                        </form>

                        <div class="overflow-y-auto flex-1 max-h-60 pr-2 custom-scrollbar">
                            <ul v-if="tasks.length > 0" class="space-y-3">
                                <li v-for="task in tasks" :key="task.id" 
                                    class="flex items-center gap-3 p-3 bg-black bg-opacity-30 rounded-lg cursor-pointer hover:bg-opacity-50 transition"
                                    @click="toggleTask(task.id)">
                                    <input type="checkbox" :checked="task.status === 'completed'" class="rounded border-gray-700 text-accent focus:ring-accent bg-gray-900 pointer-events-none">
                                    <span :class="{'line-through text-gray-500': task.status === 'completed'}">{{ task.title }}</span>
                                </li>
                            </ul>
                            <div v-else class="text-center py-6 text-gray-500 text-sm">
                                لا يوجد مهام مسجلة حتى الآن! اكتب مهمتك الأولى في الأعلى.
                            </div>
                        </div>
                    </div>

                    <!-- Goal -->
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl hover:shadow-[0_0_20px_rgba(6,155,255,0.1)] transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-white">🎯 هدف اليوم</h3>
                        </div>
                        <div v-if="goal" class="p-6 bg-gradient-to-br from-secondary to-gray-900 rounded-xl border border-gray-700 text-center h-[180px] flex flex-col justify-center">
                            <span class="block text-4xl mb-3">🚀</span>
                            <h4 class="font-bold text-lg mb-1">{{ goal.title }}</h4>
                            <span class="text-sm text-gray-400 block mt-2">نرجو أن تكون متحمساً لتحقيقه!</span>
                        </div>
                    </div>

                    <!-- Habit -->
                    <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-xl hover:shadow-[0_0_20px_rgba(6,155,255,0.1)] transition duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-white">🔄 عادة واحدة</h3>
                            <button v-if="!isEditingHabit && habit" @click="isEditingHabit = true" class="text-xs text-accent hover:underline">
                                تعديل
                            </button>
                        </div>
                        
                        <!-- Show Habit -->
                        <div v-if="habit && !isEditingHabit" class="flex flex-col items-center justify-center py-6 bg-black bg-opacity-30 rounded-xl h-[180px]">
                            <div class="w-16 h-16 rounded-full bg-accent flex items-center justify-center text-white text-2xl shadow-lg shadow-accent/50 mb-4 font-bold">
                                {{ habit.name.substring(0, 1) }}
                            </div>
                            <h4 class="font-bold text-lg text-white">{{ habit.name }}</h4>
                            <span class="text-sm text-gray-400 text-center mt-1">المواظبة تقود للنجاح</span>
                        </div>
                        
                        <!-- Edit/Create Habit Form -->
                        <div v-if="isEditingHabit || !habit" class="flex flex-col items-center justify-center py-6 bg-black bg-opacity-30 rounded-xl h-[180px] px-4">
                            <form @submit.prevent="saveHabit" class="w-full text-center">
                                <label class="block text-sm text-gray-400 mb-2">ما هي العادة التي تريد بناؤها؟</label>
                                <input 
                                    v-model="habitForm.name" 
                                    type="text"
                                    placeholder="مثال: القراءة 20 دقيقة"
                                    class="w-full bg-gray-900 border border-gray-700 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-accent text-center mb-4"
                                    required
                                />
                                <button 
                                    type="submit" 
                                    :disabled="habitForm.processing"
                                    class="bg-accent text-white px-6 py-2 rounded-full text-sm font-bold hover:bg-opacity-80 transition w-full"
                                >
                                    {{ habit ? 'تحديث العادة' : 'احفظ العادة' }}
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
:deep(.bg-white) { background-color: #0d1304 !important; border-color: #1f2937 !important; }
:deep(.text-gray-800) { color: #e2f0d5 !important; }
:deep(header) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
:deep(nav) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #062F69; border-radius: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
</style>
