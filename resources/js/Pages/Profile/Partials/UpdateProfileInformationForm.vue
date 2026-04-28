<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header class="mb-8">
            <h2 class="n-h2 text-xl mb-2">
                {{ $t('Profile Information') }}
            </h2>

            <p class="n-p text-sm opacity-60 italic">
                {{ $t("Update your account's profile information and email address.") }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-6"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Human Name') }}</label>

                    <input
                        id="name"
                        type="text"
                        class="n-input w-full"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                    />

                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $t('Email Address') }}</label>

                    <input
                        id="email"
                        type="email"
                        class="n-input w-full font-mono"
                        v-model="form.email"
                        required
                        autocomplete="username"
                    />

                    <InputError class="mt-2" :message="form.errors.email" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-amber-600 font-bold">
                    ⚠️ {{ $t('Your email address is unverified.') }}
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="ms-2 underline text-blue-500 hover:text-blue-600 transition-colors"
                    >
                        {{ $t('Click here to re-send the verification email.') }}
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-xs font-black text-emerald-500 uppercase tracking-widest"
                >
                    {{ $t('A new verification link has been sent to your email address.') }}
                </div>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                <button type="submit" :disabled="form.processing" class="n-btn n-btn-primary min-w-[120px]">
                    {{ $t('Save') }}
                </button>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-xs font-bold text-emerald-500 uppercase"
                    >
                        ✓ {{ $t('Synchronized') }}
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
