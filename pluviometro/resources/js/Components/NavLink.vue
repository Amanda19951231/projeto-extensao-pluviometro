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
    ? 'd-inline-flex align-items-center pb-1 border-bottom border-3 border-primary text-sm fw-medium text-primary'
    : 'd-inline-flex align-items-center pb-1 border-bottom border-3 border-transparent text-sm fw-medium text-secondary text-decoration-none hover-text-primary hover-border-primary'
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>

<style scoped>
.hover-text-primary:hover {
  color: #0d6efd !important; /* azul bootstrap */
}
.hover-border-primary:hover {
  border-color: #0d6efd !important;
}
</style>

