<script setup>
import { Head, Link } from "@inertiajs/vue3";

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Personal Memory OS — ذاكرة الإنسان" />

    <div
        class="bg-primary min-h-screen text-memory-light selection:bg-accent selection:text-white font-sans flex flex-col justify-center items-center py-12 px-6"
        dir="rtl"
    >
        <div
            class="relative w-full max-w-4xl max-w-2xl px-6 lg:max-w-7xl relative z-10 flex flex-col items-center text-center"
        >
            <!-- Animated Background Glow effect -->
            <div
                class="absolute inset-0 flex justify-center items-center -z-10"
            >
                <div
                    class="w-72 h-72 bg-secondary opacity-30 rounded-full blur-[100px] animate-pulse"
                ></div>
            </div>

            <!-- Logo / Hero Title -->
            <h1
                class="text-5xl md:text-7xl font-bold bg-gradient-to-l from-accent to-[#5ab8ff] text-transparent bg-clip-text mb-6 tracking-tight"
            >
                Personal Memory OS
            </h1>

            <!-- Description -->
            <p
                class="text-lg md:text-2xl font-light text-gray-300 max-w-3xl leading-relaxed mb-10"
            >
                منصة ذكية تجمع ذاكرتك في مكان واحد وتحول أفكارك وأهدافك وعاداتك
                إلى نظام تشغيل ذكي لحياتك.<br />
                نصنع أول "عقل مساعد" يرافقك طوال يومك ويحلل كل ما تفعله.<br />
                الموقع = ذاكرة + مخطط + مساعد شخصي + عقل ثاني.
            </p>

            <!-- CTA Button -->
            <div class="flex gap-4" v-if="canLogin">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-full bg-accent px-8 py-4 text-white hover:bg-opacity-80 transition duration-300 font-semibold text-xl shadow-[0_0_20px_rgba(6,155,255,0.4)] hover:scale-105 transform"
                >
                    الذهاب للوحة التحكم
                </Link>
                <Link
                    v-else
                    :href="route('register')"
                    class="rounded-full bg-accent px-8 py-4 text-white hover:bg-opacity-80 transition duration-300 font-semibold text-xl shadow-[0_0_20px_rgba(6,155,255,0.4)] hover:scale-105 transform inline-block"
                >
                    ابدأ الآن
                </Link>

                <Link
                    v-if="!$page.props.auth.user && canLogin"
                    :href="route('login')"
                    class="rounded-full border border-gray-600 px-8 py-4 text-gray-300 hover:bg-gray-800 transition duration-300 font-semibold text-xl hover:scale-105 transform inline-block"
                >
                    تسجيل الدخول
                </Link>
            </div>

            <!-- Floating Data Features -->
            <div
                class="mt-20 grid grid-cols-2 lg:grid-cols-4 gap-6 text-sm text-gray-400"
            >
                <div
                    class="p-6 bg-[#001402] border border-gray-800 rounded-2xl shadow-xl"
                >
                    <span class="block text-2xl mb-2">💡</span>
                    <h3 class="font-bold text-white mb-1">ذاكرة الأفكار</h3>
                    <p>تحليل أفكارك وتطويرها.</p>
                </div>
                <div
                    class="p-6 bg-[#001402] border border-gray-800 rounded-2xl shadow-xl"
                >
                    <span class="block text-2xl mb-2">🎯</span>
                    <h3 class="font-bold text-white mb-1">ذاكرة الأهداف</h3>
                    <p>تقسيم الأهداف ووضع الخطط.</p>
                </div>
                <div
                    class="p-6 bg-[#001402] border border-gray-800 rounded-2xl shadow-xl"
                >
                    <span class="block text-2xl mb-2">🗓️</span>
                    <h3 class="font-bold text-white mb-1">ذاكرة اليوم</h3>
                    <p>تحليل مزاجك وتوقع يومك.</p>
                </div>
                <div
                    class="p-6 bg-[#001402] border border-gray-800 rounded-2xl shadow-xl"
                >
                    <span class="block text-2xl mb-2">👥</span>
                    <h3 class="font-bold text-white mb-1">ذاكرة الناس</h3>
                    <p>تحسين وإدارة علاقاتك.</p>
                </div>
            </div>
        </div>
    </div>
</template>
