<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="d-flex flex-column min-vh-100 align-items-center bg-light pt-4 pt-sm-0 justify-content-sm-center"
    >
        <div>
            <Link href="/">
                <ApplicationLogo class="mb-3" style="height: 80px; width: 80px; fill: currentColor; color: #6c757d;" />
            </Link>
        </div>

        <div
            class="mt-3 w-100 bg-white p-4 shadow-sm rounded-sm"
            style="max-width: 400px;"
        >
            <slot />
        </div>
    </div>
</template>
