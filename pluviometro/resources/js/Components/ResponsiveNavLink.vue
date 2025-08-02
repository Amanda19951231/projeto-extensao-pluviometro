<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() =>
    props.active
        ? 'd-block w-100 ps-3 pe-4 py-2 border-start border-4 border-primary text-start fs-5 fw-semibold text-primary bg-light'
        : 'd-block w-100 ps-3 pe-4 py-2 border-start border-4 border-transparent text-start fs-5 fw-normal text-secondary bg-white hover-bg-light'
);
</script>

<template>
    <Link :href="href" :class="classes">
    <slot />
    </Link>
</template>

<style scoped>
.hover-bg-light:hover {
    background-color: #f8f9fa;
    /* equivalente a bg-light no hover */
    color: #343a40 !important;
    /* cor cinza escura para hover */
    border-color: #dee2e6 !important;
    /* cor cinza clara para borda no hover */
    text-decoration: none;
}
</style>
