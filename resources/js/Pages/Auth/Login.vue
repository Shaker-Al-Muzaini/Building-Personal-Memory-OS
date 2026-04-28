<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Log in')" />

        <div class="mb-8 text-center">
            <h1 class="n-h1 text-2xl mb-2">{{ $t('Welcome Back') }}</h1>
            <p class="n-p text-xs opacity-60 uppercase tracking-widest">{{ $t('Connect to your Neural Network') }}</p>
        </div>

        <div v-if="status" class="mb-6 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-sm font-medium text-emerald-600 dark:text-emerald-400">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">{{ $t('Email Address') }}</label>
                <input
                    id="email"
                    type="email"
                    class="n-input w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    :placeholder="$t('your@email.com')"
                />
                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center px-1">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Access Key') }}</label>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-[9px] font-black text-blue-500 uppercase tracking-widest hover:text-blue-600 transition-colors"
                    >
                        {{ $t('Lost key?') }}
                    </Link>
                </div>
                <input
                    id="password"
                    type="password"
                    class="n-input w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    :placeholder="$t('••••••••')"
                />
                <InputError class="mt-1" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center group cursor-pointer">
                    <Checkbox name="remember" v-model:checked="form.remember" class="border-slate-200 dark:border-slate-800" />
                    <span class="ms-2 text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-slate-600 dark:group-hover:text-slate-200 transition-colors">
                        {{ $t('Keep me connected') }}
                    </span>
                </label>
            </div>

            <button
                type="submit"
                class="w-full n-btn n-btn-primary py-4 text-sm"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                :disabled="form.processing"
            >
                {{ $t('Initiate Link') }}
            </button>

            <div class="pt-6 border-t border-slate-100 dark:border-slate-800 text-center">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">
                    {{ $t("Don't have an account?") }}
                </p>
                <Link
                    :href="route('register')"
                    class="text-xs font-black text-blue-500 hover:text-blue-600 uppercase tracking-widest transition-colors"
                >
                    {{ $t('Create New Node') }}
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
