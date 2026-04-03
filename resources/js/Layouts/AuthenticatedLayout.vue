<script setup>
import { ref, watch } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';

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
        oracleReply.value = "Neural overflow. System rejected.";
    } finally {
        isOracleLoading.value = false;
    }
};

window.addEventListener('keydown', (e) => {
    if (e.key === '/' && !showOracle.value && e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
        showOracle.value = true;
    }
    if (e.key === 'Escape') showOracle.value = false;
});
</script>

<template>
    <div class="font-cairo selection:bg-accent/40 overflow-x-hidden">
        <div class="min-h-screen bg-[#050905]">
            <nav class="border-b border-white/5 bg-black/80 backdrop-blur-md sticky top-0 z-50">
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center gap-3">
                                <Link :href="route('dashboard')" class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-accent to-purple-500 flex items-center justify-center text-lg">
                                        🧠
                                    </div>
                                    <span class="font-black text-xl tracking-tighter text-white">{{ $t('Memory OS') }}</span>
                                </Link>
                            </div>

                            <!-- Navigation Links (Bilingual) -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex lg:gap-4 lg:space-x-0">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    {{ $t('Dashboard') }}
                                </NavLink>
                                <NavLink :href="route('people.index')" :active="route().current('people.*')">
                                    {{ $t('People') }}
                                </NavLink>
                                <NavLink :href="route('ideas.index')" :active="route().current('ideas.*')">
                                    {{ $t('Ideas') }}
                                </NavLink>
                                <NavLink :href="route('decisions.index')" :active="route().current('decisions.*')">
                                    {{ $t('Decisions') }}
                                </NavLink>
                                <NavLink :href="route('money.index')" :active="route().current('money.*')">
                                    {{ $t('Money') }}
                                </NavLink>
                            </div>
                        </div>

                        <!-- Universal Search Bar -->
                        <div class="hidden lg:flex flex-1 max-w-md mx-8 items-center relative">
                            <div class="w-full relative group">
                                <span class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 text-gray-500 group-focus-within:text-accent transition-colors">🔍</span>
                                <input 
                                    v-model="searchQuery"
                                    type="text" 
                                    :placeholder="$t('Search your entire memory...')"
                                    class="w-full bg-white/5 border border-white/5 rounded-2xl py-2 ltr:pl-12 ltr:pr-4 rtl:pr-12 rtl:pl-4 text-sm text-white focus:ring-accent focus:border-accent focus:bg-white/10 transition-all"
                                />
                                
                                <!-- Search Results Dropdown -->
                                <div v-if="searchResults.length > 0 || isSearching" class="absolute top-[calc(100%+8px)] left-0 right-0 bg-[#0d1304] border border-white/10 rounded-2xl shadow-2xl z-50 overflow-hidden backdrop-blur-3xl">
                                    <div v-if="isSearching" class="p-4 text-center text-sm text-gray-500 animate-pulse">
                                        {{ $t('Searching everywhere...') }}
                                    </div>
                                    <div v-else class="max-h-[350px] overflow-y-auto custom-scroll">
                                        <div 
                                            v-for="item in searchResults" 
                                            :key="item.type + item.id"
                                            @click="navigateTo(item)"
                                            class="p-4 border-b border-white/5 hover:bg-white/5 cursor-pointer transition-colors flex items-start gap-3"
                                        >
                                            <span class="text-lg">
                                                {{ item.type === 'ideas' ? '💡' : (item.type === 'people' ? '🤝' : (item.type === 'tasks' ? '📋' : '⚖️')) }}
                                            </span>
                                            <div class="flex-1 overflow-hidden">
                                                <p class="text-white text-sm font-bold truncate">{{ item.title }}</p>
                                                <p class="text-[10px] text-accent uppercase tracking-tighter">{{ $t(item.type) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center gap-6">
                            
                            <LanguageSwitcher />

                            <!-- Settings Dropdown -->
                            <div class="relative ms-3 border-l border-white/10 ps-6">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center rounded-md border border-transparent bg-white/5 px-4 py-2 text-sm font-medium leading-4 text-gray-300 transition duration-150 ease-in-out hover:text-white hover:bg-white/10 focus:outline-none">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <div class="bg-gray-900 border border-white/10 rounded-lg shadow-2xl overflow-hidden">
                                            <DropdownLink :href="route('profile.edit')" class="text-gray-300 hover:bg-white/5">{{ $t('Profile') }}</DropdownLink>
                                            <DropdownLink :href="route('logout')" method="post" as="button" class="text-gray-300 hover:bg-white/5">{{ $t('Log Out') }}</DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-white/10 hover:text-white focus:outline-none">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown}" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-black border-t border-white/5">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            {{ $t('Dashboard') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('people.index')" :active="route().current('people.*')">
                            {{ $t('People') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('ideas.index')" :active="route().current('ideas.*')">
                            {{ $t('Ideas') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('decisions.index')" :active="route().current('decisions.*')">
                            {{ $t('Decisions') }}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('money.index')" :active="route().current('money.*')">
                            {{ $t('Money') }}
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="border-t border-white/5 pb-1 pt-4">
                        <div class="px-4 flex justify-between items-center mb-4">
                            <div>
                                <div class="text-base font-medium text-white">{{ $page.props.auth.user.name }}</div>
                                <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                            </div>
                            <LanguageSwitcher />
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">{{ $t('Profile') }}</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button">{{ $t('Log Out') }}</ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header class="bg-black/40 border-b border-white/5" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Dashboard Oracle/Shadow Prediction floating card could go here, but let's keep it near main -->
            
            <!-- Page Content -->
            <main class="relative">
                <slot />

                <!-- The Oracle Console (Global Command) -->
                <transition name="oracle-slide">
                    <div v-if="showOracle" class="fixed bottom-10 left-1/2 -translate-x-1/2 w-full max-w-2xl z-[100] px-4">
                        <div class="bg-black/90 backdrop-blur-3xl border-2 border-accent/40 rounded-[30px] p-2 shadow-[0_0_50px_rgba(6,155,255,0.4)] relative overflow-hidden">
                            <div class="absolute inset-0 bg-gradient-to-tr from-accent/5 to-purple-500/5 -z-10"></div>
                            
                            <form @submit.prevent="runOracle" class="flex items-center gap-3">
                                <span class="ps-4 text-accent font-mono animate-pulse">#oracle ></span>
                                <input 
                                    v-model="oracleCommand"
                                    type="text" 
                                    placeholder="Execute command (e.g. Add task, Record expense...)"
                                    class="flex-1 bg-transparent border-none focus:ring-0 text-white font-mono placeholder:text-gray-700 py-4"
                                    autofocus
                                />
                                <button type="button" @click="showOracle = false" class="pe-4 text-gray-500 hover:text-white transition-all">esc</button>
                            </form>
                            
                            <div v-if="oracleReply || isOracleLoading" class="border-t border-white/5 p-4 bg-black/40">
                                <p v-if="isOracleLoading" class="text-accent text-sm font-mono animate-pulse">Processing context...</p>
                                <p v-else class="text-gray-300 text-sm font-mono leading-relaxed bidi-plaintext flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-accent rounded-full"></span>
                                    {{ oracleReply }}
                                </p>
                            </div>
                        </div>
                    </div>
                </transition>
            </main>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700;900&display=swap');
.font-cairo { font-family: 'Cairo', sans-serif; }

.oracle-slide-enter-active, .oracle-slide-leave-active { transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.oracle-slide-enter-from, .oracle-slide-leave-to { transform: translate(-50%, 100px); opacity: 0; }

</style>
