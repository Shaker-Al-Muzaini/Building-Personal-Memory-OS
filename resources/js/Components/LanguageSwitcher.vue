<script setup>
import { loadLanguageAsync, getActiveLanguage } from 'laravel-vue-i18n';
import { ref, onMounted } from 'vue';

const currentLang = ref(getActiveLanguage() || 'ar');

const setLang = (lang) => {
    loadLanguageAsync(lang);
    currentLang.value = lang;
    document.documentElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
    document.documentElement.lang = lang;
    localStorage.setItem('user_lang', lang);
};

onMounted(() => {
    const savedLang = localStorage.getItem('user_lang') || 'ar';
    setLang(savedLang);
});
</script>

<template>
    <div class="flex items-center gap-2">
        <button 
            @click="setLang('en')" 
            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', currentLang === 'en' ? 'bg-accent text-white shadow-lg' : 'text-gray-500 hover:text-white']"
        >
            EN
        </button>
        <div class="w-[1px] h-4 bg-white/10"></div>
        <button 
            @click="setLang('ar')" 
            :class="['px-3 py-1 text-xs font-bold rounded-lg transition-all', currentLang === 'ar' ? 'bg-accent text-white shadow-lg' : 'text-gray-500 hover:text-white']"
        >
            AR
        </button>
    </div>
</template>
