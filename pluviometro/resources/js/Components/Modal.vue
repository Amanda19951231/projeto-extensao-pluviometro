<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['close']);
const showSlot = ref(props.show);

watch(
    () => props.show,
    (val) => {
        if (val) {
            document.body.style.overflow = 'hidden';
            showSlot.value = true;
        } else {
            document.body.style.overflow = '';
            setTimeout(() => {
                showSlot.value = false;
            }, 200);
        }
    }
);

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e) => {
    if (e.key === 'Escape' && props.show) {
        e.preventDefault();
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => {
    // Mapeando para classes Bootstrap container max-width
    return {
        sm: 'modal-sm',
        md: 'modal-md',
        lg: 'modal-lg',
        xl: 'modal-xl',
        '2xl': 'modal-xl', // Bootstrap não tem 2xl, usa XL
    }[props.maxWidth] || 'modal-xl';
});
</script>

<template>
    <div class="modal fade" tabindex="-1" role="dialog" :class="{ show: show }"
        :style="{ display: show ? 'block' : 'none', backgroundColor: 'rgba(0,0,0,0.5)' }" @click.self="close">
        <div class="modal-dialog modal-dialog-centered" :class="maxWidthClass" role="document">
            <div class="modal-content">
                <slot v-if="showSlot" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal.show {
    opacity: 1;
    transition: opacity 0.3s ease-out;
}

.modal {
    opacity: 0;
    transition: opacity 0.2s ease-in;
}
</style>
