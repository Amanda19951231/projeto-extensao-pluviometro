<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link :href="href" class="d-block w-100 px-3 py-2 text-start small text-body transition" style="cursor: pointer;">
    <slot />
    </Link>
</template>
