<script setup>
import { ref } from 'vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
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

            <!-- Page Content -->
            <main class="relative">
                <slot />
            </main>
        </div>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;700;900&display=swap');
.font-cairo { font-family: 'Cairo', sans-serif; }
</style>
