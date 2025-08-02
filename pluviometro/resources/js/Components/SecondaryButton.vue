<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button :type="type" class="btn btn-light btn-sm text-uppercase fw-semibold text-secondary shadow-sm"
        :disabled="$attrs.disabled" style="letter-spacing: 0.1em;">
        <slot />
    </button>
</template>
