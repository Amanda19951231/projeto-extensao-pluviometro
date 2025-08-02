<script setup>
import { computed, onMounted, onUnmounted, ref, nextTick } from 'vue';

const props = defineProps({
    align: {
        type: String,
        default: 'right',
    },
    width: {
        type: String,
        default: '200px', // agora string de CSS para maior flexibilidade
    },
    contentClasses: {
        type: String,
        default: 'py-1 bg-white border rounded shadow-sm',
    },
});

const open = ref(false);

const closeOnEscape = (e) => {
    if (open.value && e.key === 'Escape') {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => document.removeEventListener('keydown', closeOnEscape));

const alignmentClasses = computed(() => {
    if (props.align === 'left') {
        return 'start-0'; // Bootstrap utilitário para left 0
    } else if (props.align === 'right') {
        return 'end-0'; // right 0
    }
    return '';
});
</script>

<template>
    <div class="position-relative d-inline-block">
        <!-- Gatilho -->
        <div @click="open = !open" style="cursor: pointer;">
            <slot name="trigger" />
        </div>

        <!-- Overlay fullscreen para fechar ao clicar fora -->
        <div v-show="open" class="position-fixed top-0 start-0 w-100 h-100" style="z-index: 1040;"
            @click="open = false"></div>

        <!-- Dropdown -->
        <transition name="fade" appear>
            <div v-show="open" class="position-absolute mt-2 shadow-sm bg-white border rounded"
                :class="alignmentClasses" :style="{ minWidth: props.width, zIndex: 1050 }" @click.stop>
                <div :class="contentClasses">
                    <slot name="content" />
                </div>
            </div>
        </transition>
    </div>
</template>

<style>
/* Transição fade simples para dropdown */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-to,
.fade-leave-from {
    opacity: 1;
}
</style>
