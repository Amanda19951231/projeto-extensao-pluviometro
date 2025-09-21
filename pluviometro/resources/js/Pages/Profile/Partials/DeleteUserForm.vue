<script setup>
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Deletar conta
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Uma vez que sua conta for deletada, todos os seus recursos e dados serão permanentemente deletados.
                Antes de deletar sua conta, por favor baixe quaisquer dados ou informações que você deseja manter.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Deletar conta</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-4 w-96 mx-auto"> <!-- p-4 diminui o padding, w-96 deixa mais estreito -->
                <h2 class="text-lg font-medium text-gray-900">
                    Você tem certeza que deseja deletar sua conta?
                </h2>

                <p class="mt-1 text-sm text-gray-600">
                    Uma vez que sua conta for deletada, todos os seus recursos e dados serão permanentemente deletados.
                    Por favor, insira sua senha para confirmar que você deseja deletar sua conta.
                </p>

                <div class="mt-4">
                    <InputLabel for="password" value="Senha" class="sr-only" />

                    <TextInput id="password" ref="passwordInput" v-model="form.password" type="password"
                        class="mt-1 block w-full" placeholder="Senha" @keyup.enter="deleteUser" />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-4 flex justify-end">
                    <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>

                    <DangerButton  class="ms-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing"
                        @click="deleteUser">
                        Deletar conta
                    </DangerButton>
                </div>
            </div>
        </Modal>

    </section>
</template>
