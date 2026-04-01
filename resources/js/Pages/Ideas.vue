<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { trans } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';

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

const updateStatus = (id, status) => {
    router.patch(route('ideas.status', id), { status }, { preserveScroll: true });
};

const deleteIdea = async (id) => {
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
        router.delete(route('ideas.delete', id), { preserveScroll: true });
    }
};

const statusColumns = [
    { id: 'draft', label: 'مسودة', icon: '📝', color: 'border-blue-500/30' },
    { id: 'developing', label: 'قيد التطوير', icon: '🧪', color: 'border-yellow-500/30' },
    { id: 'ready', label: 'جاهز للتنفيذ', icon: '🚀', color: 'border-green-500/30' }
];

const getIdeasByStatus = (status) => {
    return props.ideas.filter(idea => idea.status === status);
};
</script>

<template>
    <Head title="ذاكرة الأفكار — Personal Memory" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-3xl text-white tracking-tight flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl bg-accent/20 flex items-center justify-center text-2xl">💡</span>
                {{ $t('Ideas Memory') }}
            </h2>
        </template>

        <div class="py-12 bg-primary min-h-screen text-memory-light" dir="rtl">
            <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8 space-y-12">

                <!-- Add Idea Section -->
                <div class="bg-black/40 backdrop-blur-2xl border border-white/5 p-8 rounded-[40px] shadow-2xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-accent/10 to-purple-500/10 blur-[100px] -z-10 opacity-50"></div>
                    
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black text-white mb-2">{{ $t("What's on your mind?") }}</h3>
                        <p class="text-gray-400 mb-8 font-light text-lg">{{ $t('Record any idea...') }}</p>
                        
                        <form @submit.prevent="saveIdea" class="space-y-4">
                            <textarea
                                v-model="ideaForm.content"
                                class="w-full bg-black/60 border border-white/5 rounded-[30px] px-8 py-6 text-white focus:ring-accent focus:border-accent text-xl font-light placeholder:text-gray-700 min-h-[150px] transition-all resize-none"
                                :placeholder="trans('Record any idea...')"
                                required
                            ></textarea>
                            
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="ideaForm.processing"
                                    class="bg-accent text-white px-10 py-4 rounded-2xl font-black text-lg hover:brightness-110 active:scale-95 transition-all disabled:opacity-50 flex items-center gap-3 shadow-[0_0_30px_rgba(6,155,255,0.2)]"
                                >
                                    <span v-if="ideaForm.processing" class="animate-spin w-6 h-6 border-4 border-white border-t-transparent rounded-full block"></span>
                                    <span v-else>✨</span>
                                    <span>{{ ideaForm.processing ? $t('Thinking...') : $t('Save and Analyze Idea') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Kanban Board -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 pb-32">
                    <div v-for="col in statusColumns" :key="col.id" class="flex flex-col h-full min-h-[600px]">
                        <!-- Column Header -->
                        <div class="flex items-center justify-between mb-6 px-4">
                            <h3 class="text-xl font-black text-white flex items-center gap-3">
                                <span class="p-2 bg-white/5 rounded-xl">{{ col.icon }}</span>
                                {{ col.label }}
                                <span class="text-sm bg-accent/20 text-accent px-3 py-1 rounded-full">{{ getIdeasByStatus(col.id).length }}</span>
                            </h3>
                        </div>

                        <!-- Column Content -->
                        <div class="flex-1 space-y-6 p-4 bg-black/20 rounded-[40px] border border-white/5 custom-scroll overflow-y-auto max-h-[800px]">
                            <div v-for="idea in getIdeasByStatus(col.id)" :key="idea.id" 
                                class="bg-black/40 border border-white/5 p-6 rounded-[30px] group hover:border-accent/40 transition-all duration-500 relative shadow-xl"
                            >
                                <!-- Card Header -->
                                <div class="flex justify-between items-start mb-4">
                                    <span class="px-3 py-1 bg-accent/10 text-accent text-[10px] uppercase tracking-widest font-black rounded-lg">
                                        {{ idea.category || 'عام' }}
                                    </span>
                                    <button @click="deleteIdea(idea.id)" class="text-gray-700 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-all active:scale-75">
                                        ✖
                                    </button>
                                </div>

                                <!-- Idea Content -->
                                <p class="text-white text-lg font-bold leading-relaxed mb-4 break-words">
                                    {{ idea.content }}
                                </p>

                                <!-- AI Analysis Preview -->
                                <div v-if="idea.ai_analysis" class="mb-6 p-4 bg-black/40 rounded-2xl border border-white/5 text-sm text-gray-400 font-light leading-relaxed whitespace-pre-wrap italic">
                                    <span class="text-accent font-black block mb-2 text-xs">AI INSIGHT:</span>
                                    {{ idea.ai_analysis }}
                                </div>

                                <!-- Card Footer: Status Controls -->
                                <div class="flex items-center gap-2 mt-auto pt-4 border-t border-white/5">
                                    <button 
                                        v-for="target in statusColumns" 
                                        :key="target.id"
                                        v-show="target.id !== idea.status"
                                        @click="updateStatus(idea.id, target.id)"
                                        class="p-2 bg-white/5 hover:bg-accent/20 text-gray-500 hover:text-accent rounded-lg transition-all text-xs font-bold"
                                        :title="`نقل إلى ${target.label}`"
                                    >
                                        {{ target.icon }}
                                    </button>
                                    <span class="ms-auto text-[10px] text-gray-700 font-mono">
                                        {{ new Date(idea.created_at).toLocaleDateString() }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="getIdeasByStatus(col.id).length === 0" class="flex flex-col items-center justify-center py-20 text-gray-700 opacity-40">
                                <span class="text-4xl mb-4">🌑</span>
                                <p class="font-bold">فارغ حالياً</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: rgba(6, 155, 255, 0.1); border-radius: 10px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }

/* Ensure consistent backgrounds during transitions */
:deep(.bg-white) { background-color: #0d1304 !important; border-color: #1f2937 !important; }
:deep(.text-gray-800) { color: #e2f0d5 !important; }
:deep(header) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
:deep(nav) { background-color: #0d1304 !important; border-bottom: 1px solid #1f2937 !important; }
</style>
