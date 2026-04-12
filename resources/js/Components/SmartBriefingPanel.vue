<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
    briefing: { type: String, default: '' },
    balance:  { type: Number, default: 0 },
    tasks:    { type: Array, default: () => [] },
    harmony:  { type: Number, default: 0 },
});

// ─── Voice Oracle (Speech Recognition) ───
const isListening    = ref(false);
const voiceText      = ref('');
const voiceReply     = ref('');
const isProcessing   = ref(false);
const voiceSupported = ref(typeof window !== 'undefined' && ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window));

let recognition = null;

function startListening() {
    if (!voiceSupported.value) return;
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    recognition = new SpeechRecognition();
    recognition.lang     = 'ar-SA';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart  = () => { isListening.value = true; voiceText.value = ''; voiceReply.value = ''; };
    recognition.onresult = (e) => {
        voiceText.value = e.results[0][0].transcript;
        sendVoiceCommand();
    };
    recognition.onerror  = () => { isListening.value = false; };
    recognition.onend    = () => { isListening.value = false; };
    recognition.start();
}

function stopListening() {
    recognition?.stop();
    isListening.value = false;
}

async function sendVoiceCommand() {
    if (!voiceText.value) return;
    isProcessing.value = true;
    try {
        const res = await axios.post(route('dashboard.command'), { command: voiceText.value });
        voiceReply.value = res.data.reply || '';
        // Speak the reply back
        if ('speechSynthesis' in window && voiceReply.value) {
            const utt = new SpeechSynthesisUtterance(voiceReply.value);
            const savedDialect = localStorage.getItem('ar_voice_dialect') || 'ar-SA';
            utt.lang = savedDialect;
            
            // Try matching a voice
            const voices = window.speechSynthesis.getVoices();
            let voice = voices.find(v => v.lang === savedDialect) 
                     || voices.find(v => v.lang.startsWith(savedDialect)) 
                     || voices.find(v => v.lang.startsWith('ar'));
            if (voice) utt.voice = voice;
            
            utt.rate = 0.9;
            window.speechSynthesis.speak(utt);
        }
    } catch (e) {
        voiceReply.value = 'حدث خطأ في الاتصال.';
    } finally {
        isProcessing.value = false;
    }
}

// ─── Life Balance Guardian ───
const pendingTasks  = computed(() => props.tasks.filter(t => t.status === 'pending').length);
const guardianState = computed(() => {
    if (props.harmony < 30 || pendingTasks.value > 10) return 'critical';
    if (props.harmony < 55 || pendingTasks.value > 5)  return 'warning';
    return 'stable';
});

const guardianConfig = {
    critical: { icon: '🚨', label: 'حالة طوارئ', color: '#ef4444', bg: 'rgba(239,68,68,0.12)', msg: 'توقف عن القرارات الكبيرة الآن. التوازن خطير.' },
    warning:  { icon: '⚠️', label: 'تنبيه', color: '#f59e0b', bg: 'rgba(245,158,11,0.10)', msg: 'انتبه — ضغط متراكم. خذ استراحة قبل أي قرار.' },
    stable:   { icon: '✅', label: 'مستقر', color: '#10b981', bg: 'rgba(16,185,129,0.10)', msg: 'وضعك متوازن. الآن هو الوقت المثالي للعمل العميق.' },
};
</script>

<template>
    <div class="briefing-shell">

        <!-- ─── Morning Briefing ─── -->
        <div class="briefing-card">
            <div class="briefing-header">
                <span class="briefing-icon">🌅</span>
                <div>
                    <p class="briefing-label">الإحاطة الصباحية</p>
                    <p class="briefing-text bidi-plaintext">{{ briefing || 'جاري تحليل يومك...' }}</p>
                </div>
            </div>
            <div class="briefing-stats">
                <div class="stat-pill" :class="balance >= 0 ? 'green' : 'red'">
                    💰 {{ balance >= 0 ? '+' : '' }}{{ balance }}$
                </div>
                <div class="stat-pill yellow">
                    📋 {{ pendingTasks }} مهمة معلقة
                </div>
                <div class="stat-pill blue">
                    🧠 توافق {{ harmony }}%
                </div>
            </div>
        </div>

        <!-- ─── Life Balance Guardian ─── -->
        <div class="guardian-card" :style="{ background: guardianConfig[guardianState].bg, borderColor: guardianConfig[guardianState].color + '44' }">
            <span class="guardian-icon">{{ guardianConfig[guardianState].icon }}</span>
            <div class="guardian-body">
                <p class="guardian-label" :style="{ color: guardianConfig[guardianState].color }">
                    حارس التوازن الحيوي · {{ guardianConfig[guardianState].label }}
                </p>
                <p class="guardian-msg">{{ guardianConfig[guardianState].msg }}</p>
            </div>
            <div class="guardian-bar">
                <div class="guardian-bar-fill"
                    :style="{ width: harmony + '%', background: guardianConfig[guardianState].color }">
                </div>
            </div>
        </div>

        <!-- ─── Voice Oracle ─── -->
        <div class="voice-oracle-card">
            <div class="voice-header">
                <span class="voice-title">🎙️ Oracle الصوتي</span>
                <span class="voice-sub">تكلم، النظام يسمع ويتصرف</span>
            </div>

            <div class="voice-body">
                <!-- Mic button -->
                <button
                    @mousedown="startListening"
                    @mouseup="stopListening"
                    @touchstart.prevent="startListening"
                    @touchend.prevent="stopListening"
                    class="mic-btn"
                    :class="{ listening: isListening }"
                    :disabled="!voiceSupported">
                    <span class="mic-rings" v-if="isListening">
                        <span></span><span></span><span></span>
                    </span>
                    🎙️
                </button>

                <div class="voice-status">
                    <p v-if="isListening" class="voice-listening-label">أنا أسمعك...</p>
                    <p v-else-if="isProcessing" class="voice-processing-label animate-pulse">جاري التحليل...</p>
                    <p v-else-if="!voiceSupported" class="voice-error">المتصفح لا يدعم الصوت</p>
                    <p v-else class="voice-hint">اضغط مع الاستمرار للكلام</p>
                </div>
            </div>

            <!-- Voice transcript -->
            <div v-if="voiceText" class="voice-transcript">
                <p class="v-label">قلت:</p>
                <p class="v-text bidi-plaintext">{{ voiceText }}</p>
            </div>

            <!-- Voice reply -->
            <div v-if="voiceReply" class="voice-reply">
                <p class="v-label">Oracle:</p>
                <p class="v-text bidi-plaintext">{{ voiceReply }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.briefing-shell {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Morning Briefing */
.briefing-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
}
.briefing-header { display: flex; gap: 1rem; align-items: flex-start; margin-bottom: 1rem; }
.briefing-icon { font-size: 2rem; }
.briefing-label { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--c-accent); margin-bottom: 0.3rem; }
.briefing-text { font-size: 0.875rem; color: var(--c-text); line-height: 1.65; }
.briefing-stats { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.stat-pill {
    font-size: 0.72rem; font-weight: 800;
    padding: 0.25rem 0.75rem; border-radius: 100px;
    border: 1px solid;
}
.stat-pill.green { color: #10b981; border-color: #10b98133; background: #10b98111; }
.stat-pill.red   { color: #ef4444; border-color: #ef444433; background: #ef444411; }
.stat-pill.yellow{ color: #f59e0b; border-color: #f59e0b33; background: #f59e0b11; }
.stat-pill.blue  { color: #3b82f6; border-color: #3b82f633; background: #3b82f611; }

/* Guardian */
.guardian-card {
    border: 1px solid;
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    flex-direction: column;
}
.guardian-icon { font-size: 2rem; }
.guardian-body { flex: 1; }
.guardian-label { font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.2rem; }
.guardian-msg { font-size: 0.875rem; color: var(--c-text); }
.guardian-bar { width: 100%; height: 4px; background: var(--c-border); border-radius: 100px; overflow: hidden; margin-top: 0.75rem; }
.guardian-bar-fill { height: 100%; border-radius: 100px; transition: width 0.8s ease; }

/* Voice Oracle */
.voice-oracle-card {
    background: var(--c-surface);
    border: 1px solid var(--c-border);
    border-radius: 20px;
    padding: 1.25rem 1.5rem;
}
.voice-header { margin-bottom: 1rem; }
.voice-title { font-size: 0.9rem; font-weight: 800; color: var(--c-text); display: block; }
.voice-sub { font-size: 0.72rem; color: var(--c-text-muted); }
.voice-body { display: flex; align-items: center; gap: 1.25rem; margin-bottom: 0.75rem; }

.mic-btn {
    width: 60px; height: 60px; border-radius: 50%;
    background: var(--c-accent-bg);
    border: 2px solid var(--c-accent);
    font-size: 1.6rem;
    cursor: pointer; position: relative;
    transition: all 0.2s;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.mic-btn.listening { background: var(--c-accent); animation: mic-pulse 1s infinite; }
.mic-btn:disabled  { opacity: 0.4; cursor: not-allowed; }

@keyframes mic-pulse {
    0%, 100% { box-shadow: 0 0 0 0 var(--c-accent); }
    50%       { box-shadow: 0 0 0 12px transparent; }
}

.mic-rings { position: absolute; inset: -6px; border-radius: 50%; }
.mic-rings span {
    position: absolute; inset: 0; border-radius: 50%;
    border: 2px solid var(--c-accent);
    animation: ring-expand 1.5s ease-out infinite;
    opacity: 0;
}
.mic-rings span:nth-child(2) { animation-delay: 0.5s; }
.mic-rings span:nth-child(3) { animation-delay: 1s; }
@keyframes ring-expand {
    0%   { transform: scale(1);   opacity: 0.7; }
    100% { transform: scale(2.2); opacity: 0; }
}

.voice-status p { font-size: 0.8rem; }
.voice-listening-label  { color: var(--c-accent); font-weight: 700; }
.voice-processing-label { color: #f59e0b; font-weight: 700; }
.voice-hint  { color: var(--c-text-muted); }
.voice-error { color: #ef4444; }

.voice-transcript, .voice-reply {
    background: var(--c-surface-2, var(--c-border));
    border-radius: 12px;
    padding: 0.75rem 1rem;
    margin-top: 0.5rem;
}
.v-label { font-size: 0.65rem; font-weight: 800; color: var(--c-accent); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.2rem; }
.v-text  { font-size: 0.85rem; color: var(--c-text); }
</style>
