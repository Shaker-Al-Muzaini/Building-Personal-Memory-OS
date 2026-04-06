<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const isOpen = ref(false);
const commandStr = ref('');
const isLoading = ref(false);
const messages = ref([
    { role: 'system', content: 'مرحباً، أنا العقل الموازي الخاص بك. كيف يمكنني إدارتك اليوم؟' }
]);
const chatScroll = ref(null);

const scrollToBottom = async () => {
    await nextTick();
    if (chatScroll.value) {
        chatScroll.value.scrollTop = chatScroll.value.scrollHeight;
    }
};

const sendCommand = async () => {
    if (!commandStr.value.trim() || isLoading.value) return;
    
    // Add user message
    const currentCmd = commandStr.value;
    messages.value.push({ role: 'user', content: currentCmd });
    commandStr.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await axios.post(route('dashboard.command'), { command: currentCmd });
        
        messages.value.push({ role: 'assistant', content: response.data.reply });
        
        if (response.data.type !== 'unknown') {
            router.reload({ preserveScroll: true }); // Reload Inertia props softly
        }
    } catch (e) {
        messages.value.push({ role: 'assistant', content: 'انقطع الاتصال بالشبكة العصبية، يرجى المحاولة لاحقاً.' });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

const toggleChat = () => {
    isOpen.value = !isOpen.value;
    if(isOpen.value) scrollToBottom();
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[200] font-cairo">
        
        <!-- Toggle Button -->
        <button 
            @click="toggleChat" 
            class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 to-indigo-500 shadow-[0_0_20px_rgba(79,70,229,0.5)] flex items-center justify-center text-white text-2xl hover:scale-110 active:scale-95 transition-all duration-300 relative group"
        >
            <span v-if="!isOpen">🤖</span>
            <span v-else>✖</span>
            
            <!-- Tooltip -->
            <div v-if="!isOpen" class="absolute -top-10 right-0 bg-surface/90 backdrop-blur border border-glass-border px-3 py-1 rounded-lg text-xs text-text-main opacity-0 group-hover:opacity-100 transition whitespace-nowrap pointer-events-none shadow-xl">
                {{ $t('OmniChat Assistant') }}
            </div>
        </button>

        <!-- Chat Window -->
        <Transition name="chat-slide">
            <div v-if="isOpen" class="absolute bottom-20 right-0 w-[350px] sm:w-[400px] h-[500px] max-h-[80vh] bg-glass-bg backdrop-blur-3xl border border-glass-border rounded-3xl shadow-2xl overflow-hidden flex flex-col">
                
                <!-- Chat Header -->
                <div class="p-4 border-b border-border-subtle bg-gradient-to-tr from-accent/5 to-transparent flex items-center justify-between pointer-events-none">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center border border-accent/30 text-accent">🧠</div>
                        <div>
                            <h3 class="font-bold text-text-main leading-none tracking-tight">OmniChat</h3>
                            <p class="text-[10px] text-text-muted mt-1 uppercase tracking-widest">Neural Companion</p>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div ref="chatScroll" class="flex-1 overflow-y-auto p-4 space-y-4 custom-scroll">
                    <div v-for="(msg, i) in messages" :key="i" class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                        <div 
                            class="max-w-[85%] rounded-2xl px-4 py-3 text-sm leading-relaxed"
                            :class="msg.role === 'user' ? 'bg-accent text-white rounded-br-none shadow-lg' : 'bg-surface-2 text-text-main rounded-bl-none border border-border-subtle shadow-sm'"
                        >
                            <p class="bidi-plaintext" style="word-break: break-word;">{{ msg.content }}</p>
                        </div>
                    </div>

                    <div v-if="isLoading" class="flex flex-col items-start">
                        <div class="bg-surface-2 border border-border-subtle rounded-2xl rounded-bl-none px-4 py-3 shadow-sm">
                            <div class="flex gap-1 items-center h-4">
                                <div class="w-1.5 h-1.5 rounded-full bg-accent animate-bounce" style="animation-delay: 0s"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-accent animate-bounce" style="animation-delay: 0.2s"></div>
                                <div class="w-1.5 h-1.5 rounded-full bg-accent animate-bounce" style="animation-delay: 0.4s"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-4 border-t border-border-subtle bg-surface/50 relative">
                    <form @submit.prevent="sendCommand" class="relative">
                        <input 
                            v-model="commandStr"
                            type="text" 
                            :disabled="isLoading"
                            :placeholder="$t('Type a command...')"
                            class="w-full bg-input-bg border border-border-subtle rounded-full py-3 ltr:pl-4 ltr:pr-12 rtl:pr-4 rtl:pl-12 text-sm text-text-main focus:ring-accent focus:border-accent transition-all font-cairo placeholder:text-text-muted disabled:opacity-50"
                        />
                        <button 
                            type="submit" 
                            :disabled="!commandStr.trim() || isLoading"
                            class="absolute ltr:right-2 rtl:left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-accent flex items-center justify-center text-white disabled:bg-surface-2 disabled:text-text-muted transition-colors shadow-md"
                        >
                            <span class="ltr:ml-1 rtl:mr-1">➤</span>
                        </button>
                    </form>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.chat-slide-enter-active, .chat-slide-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); transform-origin: bottom right; }
.chat-slide-enter-from, .chat-slide-leave-to { opacity: 0; transform: scale(0.9) translateY(20px); filter: blur(10px); }

.custom-scroll::-webkit-scrollbar { width: 4px; }
.custom-scroll::-webkit-scrollbar-track { background: transparent; }
.custom-scroll::-webkit-scrollbar-thumb { background: var(--c-accent); border-radius: 4px; opacity: 0.2; }
</style>
