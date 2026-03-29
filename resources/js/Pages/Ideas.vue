<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';

const props = defineProps({
    ideas: Array,
});

const ideaForm = useForm({
    content: ''
});

const saveIdea = () => {
    ideaForm.post(route('ideas.store'), {
        preserveScroll: true,
        onSuccess: () => ideaForm.reset(),
    });
};

const deleteIdea = (id) => {
    if(confirm("هل أنت متأكد من حذف هذه الفكرة؟")) {
        router.delete(route('ideas.delete', id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="ذاكرة الأفكار — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-2xl text-accent leading-tight flex items-center gap-2">
                <span>💡</span> ذاكرة الأفكار
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Add Idea -->
                <div class="bg-gray-900 border border-gray-800 rounded-2xl p-8 shadow-xl relative">
                    <div class="absolute -top-12 -left-12 w-40 h-40 bg-accent opacity-20 rounded-full blur-[60px]"></div>
                    <div class="relative z-10">
                        <h3 class="text-2xl font-bold text-white mb-2">ما الذي يدور في ذهنك؟</h3>
                        <p class="text-gray-400 mb-6">سجل الفكرة مهما كانت صغيرة، وسيتكفل المساعد الذكي بتحليلها واقتراح خطة لتطويرها.</p>
                        
                        <form @submit.prevent="saveIdea" class="flex flex-col gap-4">
                            <textarea
                                v-model="ideaForm.content"
                                class="w-full bg-black bg-opacity-30 border border-gray-700 rounded-lg px-4 py-3 text-white focus:ring-accent h-32 custom-scrollbar"
                                placeholder="اكتب فكرتك هنا... (مثال: أريد عمل تطبيق جديد لربط المهام بالذكاء الاصطناعي)"
                                required
                            ></textarea>
                            
                            <button
                                type="submit"
                                :disabled="ideaForm.processing"
                                class="bg-accent text-white px-8 py-3 rounded-xl font-bold hover:bg-opacity-80 transition disabled:opacity-50 self-end flex items-center gap-2"
                            >
                                <span v-if="ideaForm.processing" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full block"></span>
                                <span>{{ ideaForm.processing ? 'الذكاء يحلل الفكرة...' : 'حفظ وتحليل الفكرة 🧠' }}</span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Ideas List -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pb-20">
                    <div v-for="idea in ideas" :key="idea.id" class="bg-black bg-opacity-30 border border-gray-800 rounded-xl p-6 relative group transition duration-300 hover:border-gray-600">
                        <button @click="deleteIdea(idea.id)" class="absolute top-4 left-4 text-gray-600 hover:text-red-500 transition opacity-0 group-hover:opacity-100">✖</button>
                        
                        <div class="mb-4">
                            <h4 class="font-bold text-white text-lg mb-2 break-words">{{ idea.content }}</h4>
                            <span class="text-xs text-gray-500">{{ new Date(idea.created_at).toLocaleDateString() }}</span>
                        </div>
                        
                        <div class="p-4 bg-gray-900 border border-gray-800 rounded-lg mt-4" v-if="idea.ai_analysis">
                            <div class="flex items-center gap-2 mb-2 text-accent text-sm">
                                <span>🤖</span>
                                <span class="font-bold">تحليل تطويري:</span>
                            </div>
                            <p class="text-sm text-gray-300 leading-relaxed whitespace-pre-wrap">{{ idea.ai_analysis }}</p>
                        </div>
                    </div>

                    <div v-if="ideas.length === 0" class="col-span-1 md:col-span-2 text-center py-12 text-gray-500">
                        <span class="block text-5xl mb-4">💭</span>
                        لا يوجد أفكار مسجلة. ابدأ بكتابة فكرة واكتشف كيف يحللها الذكاء الاصطناعي!
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
