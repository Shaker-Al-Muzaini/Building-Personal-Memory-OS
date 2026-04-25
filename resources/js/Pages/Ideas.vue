<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';
import Swal from 'sweetalert2';

const isRecording = ref(false);
let recognition = null;

onMounted(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = document.documentElement.lang === 'ar' ? 'ar-SA' : 'en-US';

        recognition.onstart = () => { isRecording.value = true; };
        recognition.onend = () => { isRecording.value = false; };
        recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            ideaForm.content += (ideaForm.content ? ' ' : '') + transcript;
        };
    }
});

const startVoice = () => {
    if (!recognition) {
        Swal.fire({
            title: trans('Not Supported'),
            text: trans('Your browser does not support voice recognition.'),
            icon: 'error',
            background: 'var(--c-surface)',
            color: 'var(--c-text)'
        });
        return;
    }
    if (isRecording.value) {
        recognition.stop();
    } else {
        recognition.start();
    }
};

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
        background: 'var(--c-surface)',
        color: 'var(--c-text)',
        customClass: { popup: 'border border-glass-border rounded-2xl shadow-2xl' }
    });

    if (result.isConfirmed) {
        router.delete(route('ideas.delete', id), { preserveScroll: true });
    }
};

const statusColumns = [
    { id: 'draft', label: trans('Draft'), icon: '📝', color: 'border-blue-500/30' },
    { id: 'developing', label: trans('Developing'), icon: '🧪', color: 'border-yellow-500/30' },
    { id: 'ready', label: trans('Ready'), icon: '🚀', color: 'border-green-500/30' }
];

const getIdeasByStatus = (status) => {
    return props.ideas.filter(idea => idea.status === status);
};
</script>

<template>
    <Head :title="`${$t('Ideas Memory')} — Personal Memory`" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1400px] mx-auto p-4 lg:p-6 space-y-6">

            <!-- PAGE HEADER -->
            <div class="ai-briefing-compact n-card">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 flex items-center justify-center text-2xl shadow-inner">
                        💡
                    </div>
                    <div>
                        <h2 class="n-h1 text-2xl">{{ $t('Idea Lab') }}</h2>
                        <p class="n-p text-[10px] uppercase tracking-widest font-bold">{{ $t('Neural_Incubator.v9') }}</p>
                    </div>
                </div>
            </div>

            <!-- CAPTURE SECTION -->
            <div class="n-card">
                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    <div class="flex-1 w-full">
                        <h3 class="n-h3 mb-2">{{ $t("Capture Neural Spark") }}</h3>
                        <p class="n-p mb-4 text-xs italic">{{ $t('Record your vision before it fades into the void...') }}</p>
                        
                        <form @submit.prevent="saveIdea" class="space-y-4">
                            <div class="relative group/input">
                                <textarea
                                    v-model="ideaForm.content"
                                    class="n-input min-h-[100px] text-base font-medium resize-none"
                                    :placeholder="trans('Describe your idea...')"
                                    required
                                ></textarea>
                                
                                <button 
                                    type="button"
                                    @click="startVoice"
                                    :class="['absolute bottom-3 ltr:right-3 rtl:left-3 w-9 h-9 rounded-lg flex items-center justify-center transition-all', isRecording ? 'bg-red-500 text-white animate-pulse shadow-lg shadow-red-500/40' : 'bg-slate-500/10 text-slate-500 hover:text-blue-500']"
                                    :title="isRecording ? $t('Stop Recording') : $t('Voice Dictation')"
                                >
                                    <span v-if="!isRecording" class="text-base">🎤</span>
                                    <span v-else class="text-base">⏹</span>
                                </button>
                            </div>
                            
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="ideaForm.processing"
                                    class="n-btn n-btn-primary min-w-[150px] gap-2"
                                >
                                    <span v-if="ideaForm.processing" class="animate-spin w-4 h-4 border-2 border-white/30 border-t-white rounded-full"></span>
                                    <span v-else>✨</span>
                                    <span>{{ ideaForm.processing ? $t('Synthesizing...') : $t('Initialize Idea') }}</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- KANBAN BOARD -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div v-for="col in statusColumns" :key="col.id" class="flex flex-col gap-4">
                    <!-- Column Header -->
                    <div class="flex items-center justify-between px-2 pb-2 border-b-2 border-slate-100 dark:border-slate-800">
                        <h3 class="n-h3 flex items-center gap-2">
                            <span class="w-7 h-7 rounded bg-slate-500/5 flex items-center justify-center text-sm">{{ col.icon }}</span>
                            {{ col.label }}
                        </h3>
                        <span class="text-[10px] font-black bg-blue-500/10 text-blue-500 px-2 py-0.5 rounded-full border border-blue-500/20">
                            {{ getIdeasByStatus(col.id).length }}
                        </span>
                    </div>

                    <!-- Column Content -->
                    <div class="space-y-4 min-h-[400px]">
                        <div v-for="idea in getIdeasByStatus(col.id)" :key="idea.id" 
                            class="n-card group/card hover:border-blue-500/30 transition-all p-5"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <span class="px-2 py-0.5 bg-blue-500/5 text-blue-500 text-[9px] uppercase font-black rounded border border-blue-500/10">
                                    {{ idea.category || $t('Neural_Node') }}
                                </span>
                                <button @click="deleteIdea(idea.id)" class="text-slate-300 hover:text-red-500 transition-all text-xs">
                                    ✖
                                </button>
                            </div>

                            <p class="n-h2 text-sm leading-relaxed mb-4 break-words">
                                {{ idea.content }}
                            </p>

                            <!-- AI Analysis Preview -->
                            <div v-if="idea.ai_analysis" class="mb-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-[11px] n-p italic relative">
                                <span class="text-blue-500 font-black block mb-1 uppercase text-[8px]">✧ Oracle Synthesis</span>
                                {{ idea.ai_analysis }}
                            </div>

                            <!-- Controls -->
                            <div class="flex items-center gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                                <div class="flex items-center gap-1">
                                    <button 
                                        v-for="target in statusColumns" 
                                        :key="target.id"
                                        v-show="target.id !== idea.status"
                                        @click="updateStatus(idea.id, target.id)"
                                        class="w-7 h-7 bg-slate-500/5 hover:bg-blue-500/10 text-slate-400 hover:text-blue-500 rounded border border-slate-100 dark:border-slate-800 transition-all text-[10px] flex items-center justify-center"
                                        :title="`${$t('Transition to')} ${target.label}`"
                                    >
                                        {{ target.icon }}
                                    </button>
                                </div>
                                <span class="ms-auto text-[8px] n-p font-bold">
                                    {{ new Date(idea.created_at).toLocaleDateString() }}
                                </span>
                            </div>
                        </div>

                        <div v-if="getIdeasByStatus(col.id).length === 0" class="flex flex-col items-center justify-center py-12 opacity-20">
                            <span class="text-2xl">📡</span>
                            <p class="text-[8px] font-black uppercase tracking-widest mt-2">{{ $t('Node_Empty') }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Specific styles if needed */
</style>
    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 10px; opacity: 0.1; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
</style>
