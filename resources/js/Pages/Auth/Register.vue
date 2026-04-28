<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Register')" />

        <div class="mb-8 text-center">
            <h1 class="n-h1 text-2xl mb-2">{{ $t('Protocol Enrollment') }}</h1>
            <p class="n-p text-xs opacity-60 uppercase tracking-widest">{{ $t('Initialize your Neural Core') }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ $t('Human Name') }}</label>
                <input
                    id="name"
                    type="text"
                    class="n-input w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    :placeholder="$t('Full Name...')"
                />
                <InputError class="mt-1" :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ $t('Email Address') }}</label>
                <input
                    id="email"
                    type="email"
                    class="n-input w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    :placeholder="$t('your@email.com')"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ $t('Access Key') }}</label>
                    <input
                        id="password"
                        type="password"
                        class="n-input w-full"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        :placeholder="$t('••••••••')"
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ $t('Confirm Key') }}</label>
                    <input
                        id="password_confirmation"
                        type="password"
                        class="n-input w-full"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        :placeholder="$t('••••••••')"
                    />
                    <InputError class="mt-1" :message="form.errors.password_confirmation" />
                </div>
            </div>

            <button
                type="submit"
                class="w-full n-btn n-btn-primary py-4 text-sm mt-4"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                {{ $t('Establish Link') }}
            </button>

            <div class="pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                    {{ $t('Already enrolled?') }}
                </p>
                <Link
                    :href="route('login')"
                    class="text-xs font-black text-blue-500 hover:text-blue-600 uppercase tracking-widest transition-colors"
                >
                    {{ $t('Return to Hub') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
