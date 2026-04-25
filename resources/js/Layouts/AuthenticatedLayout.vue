<script setup>
import { ref, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import OmniChat from '@/Components/OmniChat.vue';
import GlowingTubesCursor from '@/Components/GlowingTubesCursor.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { useTheme } from '@/Composables/useTheme';
import { onMounted, onUnmounted } from 'vue';
import { trans, getActiveLanguage } from 'laravel-vue-i18n';

const { isDark } = useTheme();

const showingNavigationDropdown = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const isSearching = ref(false);

let searchTimeout = null;

watch(searchQuery, (newVal) => {
    if (searchTimeout) clearTimeout(searchTimeout);
    if (!newVal) {
        searchResults.value = [];
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        isSearching.value = true;
        try {
            const response = await axios.get(route('dashboard.search'), { params: { q: newVal } });
            searchResults.value = response.data;
        } catch (e) {
            console.error('Search error', e);
        } finally {
            isSearching.value = false;
        }
    }, 300);
});

const navigateTo = (item) => {
    searchQuery.value = '';
    searchResults.value = [];
    router.visit(route(`${item.type}.index`));
};

const showOracle = ref(false);
const oracleCommand = ref('');
const oracleReply = ref('');
const isOracleLoading = ref(false);

const runOracle = async () => {
    if (!oracleCommand.value) return;
    isOracleLoading.value = true;
    try {
        const response = await axios.post(route('dashboard.command'), { command: oracleCommand.value });
        oracleReply.value = response.data.reply;
        oracleCommand.value = '';
        // تحديث الصفحة لو تمت حركة مالية أو إضافة مهمة
        if (response.data.type !== 'unknown') {
            router.reload({ preserveScroll: true });
        }
    } catch (e) {
        oracleReply.value = trans("Neural overflow. System rejected.");
    } finally {
        isOracleLoading.value = false;
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
});

const handleKeydown = (e) => {
    if (e.key === '/' && !showOracle.value && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        showOracle.value = true;
    }
    if (e.key === 'Escape') showOracle.value = false;
};
</script>

<template>
    <div class="min-h-screen font-cairo overflow-x-hidden" :class="isDark ? 'os-dark' : 'os-light'" :dir="getActiveLanguage() === 'ar' ? 'rtl' : 'ltr'">
        <div class="min-h-screen" style="background: var(--c-bg); color: var(--c-text)">
            <nav class="sticky top-0 z-50 backdrop-blur-xl bg-white/70 dark:bg-slate-900/80 border-b border-slate-200 dark:border-cyan-500/20 shadow-sm dark:shadow-cyan-500/5 transition-colors duration-500">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between items-center">
                        <div class="flex items-center gap-8">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center gap-3">
                                <Link :href="route('dashboard')" class="flex items-center gap-3 group">
                                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cyan-500 via-blue-600 to-purple-700 flex items-center justify-center shadow-xl shadow-cyan-500/20 group-hover:scale-110 transition-transform duration-300">
                                        <span class="text-xl group-hover:rotate-12 transition-transform">🧠</span>
                                    </div>
                                    <span class="font-black text-xl tracking-tighter bg-clip-text text-transparent bg-gradient-to-r from-cyan-400 to-blue-500 uppercase">{{ $t('Memory OS') }}</span>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-1 sm:-my-px sm:flex lg:gap-1">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Dashboard') }}</span>
                                </NavLink>
                                <NavLink :href="route('people.index')" :active="route().current('people.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('People') }}</span>
                                </NavLink>
                                <NavLink :href="route('ideas.index')" :active="route().current('ideas.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Ideas') }}</span>
                                </NavLink>
                                <NavLink :href="route('decisions.index')" :active="route().current('decisions.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Decisions') }}</span>
                                </NavLink>
                                <NavLink :href="route('money.index')" :active="route().current('money.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Money') }}</span>
                                </NavLink>
                                <NavLink :href="route('focus.index')" :active="route().current('focus.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Focus') }}</span>
                                </NavLink>
                                <NavLink :href="route('health.index')" :active="route().current('health.*')">
                                    <span class="px-3 py-1 rounded-lg transition-all">{{ $t('Health') }}</span>
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-6">
                            <div class="flex items-center gap-2 bg-slate-800/50 p-1.5 rounded-xl border border-white/5">
                                <ThemeToggle />
                                <LanguageSwitcher />
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3 border-l border-white/10 ps-6">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button type="button" class="flex items-center gap-2 group px-3 py-2 rounded-xl hover:bg-white/5 transition-all">
                                            <div class="w-8 h-8 rounded-lg bg-slate-800 border border-white/10 flex items-center justify-center text-xs font-black text-cyan-400 group-hover:border-cyan-500/50 transition-all">
                                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-sm font-bold text-slate-300 group-hover:text-white transition-all">{{ $page.props.auth.user.name }}</span>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="bg-slate-900 border border-white/10 rounded-xl shadow-2xl overflow-hidden min-w-[200px]">
                                            <DropdownLink :href="route('profile.edit')" class="text-slate-300 hover:bg-white/5 hover:text-cyan-400 px-4 py-3">{{ $t('Profile') }}</DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-slate-300 hover:bg-white/5 hover:text-red-400 px-4 py-3 w-full text-left">{{ $t('Log Out') }}</DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white transition-all">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-slate-900 border-t border-white/5">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">{{ $t('Dashboard') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('people.index')" :active="route().current('people.*')">{{ $t('People') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('ideas.index')" :active="route().current('ideas.*')">{{ $t('Ideas') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('decisions.index')" :active="route().current('decisions.*')">{{ $t('Decisions') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('money.index')" :active="route().current('money.*')">{{ $t('Money') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('focus.index')" :active="route().current('focus.*')">{{ $t('Focus') }}</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('health.index')" :active="route().current('health.*')">{{ $t('Health') }}</ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-white/5 pb-1 pt-4">
                        <div class="px-4 flex justify-between items-center mb-4">
                            <div>
                                <div class="text-base font-bold text-white">{{ $page.props.auth.user.name }}</div>
                                <div class="text-sm font-medium text-slate-400">{{ $page.props.auth.user.email }}</div>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">{{ $t('Profile') }}</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">{{ $t('Log Out') }}</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="relative z-10">
                <slot name="header" />
            </header>

            <!-- Page Content -->
            <main class="relative z-10">
                <slot />

                <!-- The Oracle Console (Global Command) -->
                <transition name="oracle-slide">
                    <div v-if="showOracle" class="fixed bottom-10 left-1/2 -translate-x-1/2 w-full max-w-2xl z-[100] px-4">
                        <div class="bg-glass-bg backdrop-blur-3xl border-2 border-accent/40 rounded-[30px] p-2 shadow-2xl relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-tr from-accent/5 to-purple-500/5 -z-10"></div>
                            
                            <form @submit.prevent="runOracle" class="flex items-center gap-3">
                                <span class="ps-4 text-accent font-mono animate-pulse">#oracle ></span>
                                <input 
                                    v-model="oracleCommand"
                                    type="text" 
                                    :placeholder="$t('Execute command... (e.g. Add task, Record expense)')"
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-text-main font-mono placeholder:text-text-muted py-4 shadow-none"
                                    autofocus
                                />
                                <button type="button" @click="showOracle = false" class="pe-4 text-text-muted hover:text-text-main transition-all">esc</button>
                            </form>
                            
                            <div v-if="oracleReply || isOracleLoading" class="border-t border-border-subtle p-4 bg-surface shadow-inner">
                                <p v-if="isOracleLoading" class="text-accent text-sm font-mono animate-pulse tracking-widest">{{ $t('PROCESSING_CONTEXT...') }}</p>
                                <p v-else class="text-text-main text-sm font-mono leading-relaxed bidi-plaintext flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-accent rounded-full shadow-[0_0_8px_var(--c-accent)]"></span>
                                    {{ oracleReply }}
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>
            </main>
            
            <OmniChat />
            <GlowingTubesCursor />
            
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700;900&display=swap');
.font-cairo { font-family: 'Cairo', sans-serif; }

.oracle-slide-enter-active, .oracle-slide-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.oracle-slide-enter-from, .oracle-slide-leave-to { transform: translate(-50%, 100px); opacity: 0; }

</style>
