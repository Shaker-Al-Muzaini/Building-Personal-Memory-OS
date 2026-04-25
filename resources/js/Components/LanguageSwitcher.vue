<script setup>
import { loadLanguageAsync, getActiveLanguage } from 'laravel-vue-i18n';
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const currentLang = ref(getActiveLanguage() || 'ar');

const setLang = (lang, shouldReload = false) => {
    loadLanguageAsync(lang);
    currentLang.value = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = lang;
    localStorage.setItem('user_lang', lang);
    document.cookie = `user_lang=${lang}; path=/; max-age=31104000; samesite=lax`;
    
    if (shouldReload) {
        router.reload({ preserveScroll: true });
    }
};

onMounted(() => {
    const cookieValue = document.cookie.split('; ').find(row => row.startsWith('user_lang='))?.split('=')[1];
    const savedLang = cookieValue || localStorage.getItem('user_lang') || 'ar';
    setLang(savedLang);
});
</script>

<template>
    <div class="flex items-center p-1 bg-surface-2/50 backdrop-blur-md border border-border rounded-xl shadow-inner">
        <button 
            @click="setLang('en', true)" 
            :class="['px-4 py-1.5 text-[10px] font-black rounded-lg transition-all tracking-widest', currentLang === 'en' ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-500/20' : 'text-text-muted hover:text-text-main']"
        >
            EN
        </button>
        <button 
            @click="setLang('ar', true)" 
            :class="['px-4 py-1.5 text-[10px] font-black rounded-lg transition-all tracking-widest', currentLang === 'ar' ? 'bg-indigo-600 text-white shadow-xl shadow-indigo-500/20' : 'text-text-muted hover:text-text-main']"
        >
            AR
        </button>
    </div>
</template>
