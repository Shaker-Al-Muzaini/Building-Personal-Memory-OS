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
    // Set a cookie that Laravel can read
    document.cookie = `user_lang=${lang}; path=/; max-age=31104000; samesite=lax`;
    
    if (shouldReload) {
        router.reload({ preserveScroll: true });
    }
};

onMounted(() => {
    // Priority: Cookie > LocalStorage > Default (ar)
    const cookieValue = document.cookie.split('; ').find(row => row.startsWith('user_lang='))?.split('=')[1];
    const savedLang = cookieValue || localStorage.getItem('user_lang') || 'ar';
    setLang(savedLang);
});
</script>

<template>
    <div class="flex items-center gap-2">
        <button 
            @click="setLang('en', true)" 
            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', currentLang === 'en' ? 'bg-accent text-white shadow-lg' : 'text-gray-500 hover:text-white']"
        >
            EN
        </button>
        <div class="w-[1px] h-4 bg-white/10"></div>
        <button 
            @click="setLang('ar', true)" 
            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', currentLang === 'ar' ? 'bg-accent text-white shadow-lg' : 'text-gray-500 hover:text-white']"
        >
            AR
        </button>
    </div>
</template>
