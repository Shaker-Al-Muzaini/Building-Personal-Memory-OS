<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="n-btn n-btn-secondary"
    >
        <slot />
    </button>
</template>
