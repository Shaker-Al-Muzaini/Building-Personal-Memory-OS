<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head :title="$t('Profile Settings')" />

    <AuthenticatedLayout>
        <main class="relative z-10 max-w-[1200px] mx-auto p-4 lg:p-6 space-y-8">
            
            <div class="mb-8">
                <h1 class="n-h1 text-3xl mb-2">{{ $t('Neural Configuration') }}</h1>
                <p class="n-p text-sm opacity-60 uppercase tracking-widest">{{ $t('Manage your personal data and security protocols') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-8">
                <div class="n-card p-6 lg:p-10">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-2xl"
                    />
                </div>

                <div class="n-card p-6 lg:p-10">
                    <UpdatePasswordForm class="max-w-2xl" />
                </div>

                <div class="n-card p-6 lg:p-10 border-rose-500/10 dark:border-rose-500/20">
                    <DeleteUserForm class="max-w-2xl" />
                </div>
            </div>

        </main>
    </AuthenticatedLayout>
</template>
