<script setup>
import { ref, defineExpose } from 'vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const showingNavDropdown = ref(false);

const toggleNavDropdown = () => {
    showingNavDropdown.value = !showingNavDropdown.value;
};

const closeNavDropdown = () => {
    showingNavDropdown.value = false;
};

const clickOutside = {
    beforeMount(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener('click', el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener('click', el.clickOutsideEvent);
    },
};

defineExpose({
    directives: {
        clickOutside,
    },
});
</script>
<template>
    <div>
        <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom fixed-top">
            <div class="container-fluid">
                <Link :href="route('dashboard')" class="navbar-brand">
                <ApplicationLogo class="mb-3" style="height: 30px; width: 30px; fill: currentColor; color: #6c757d;" />
                </Link>

                <button class="navbar-toggler" type="button" @click="toggleNavDropdown"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" :class="{ show: showingNavDropdown }" id="navbarSupportedContent"
                    v-click-outside="closeNavDropdown">

                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                        <li class="nav-item">
                            <Link :href="route('dashboard')" class="nav-link"
                                :class="{ active: route().current('dashboard') }" @click="closeNavDropdown">
                            Dashboard
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link :href="route('pluviometros')" class="nav-link"
                                :class="{ active: route().current('pluviometros') }" @click="closeNavDropdown">
                            Pluviômetros
                            </Link>
                        </li>

                        <li class="nav-item dropdown" :class="{ show: showingNavDropdown }">
                            <a href="#" class="nav-link dropdown-toggle" role="button"
                                @click.prevent="toggleNavDropdown" :aria-expanded="showingNavDropdown.toString()">
                                {{ $page.props.auth.user.name }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" :class="{ show: showingNavDropdown }">
                                <li>
                                    <Link :href="route('profile.edit')" class="dropdown-item" @click="closeNavDropdown">
                                    Perfil
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('logout')" method="post" as="button" class="dropdown-item"
                                        @click="closeNavDropdown">
                                    Logout
                                    </Link>
                                </li>
                            </ul>

                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <header class="pt-5 mt-4 bg-light border-bottom">
            <div class="container">
                <slot name="header" />
            </div>
        </header>

        <main class="container py-4">
            <slot />
        </main>
    </div>
</template>
