<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => {
            form.reset();
        },
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
            <h2 class="text-lg font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                Xóa tài khoản
            </h2>

            <p class="mt-1 text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">
                Hành động này sẽ xóa vĩnh viễn tài khoản và không thể hoàn tác.
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">Xóa tài khoản</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6">
                <h2
                    class="text-lg font-medium"
                    :style="{ color: 'var(--dashboard-strong-text)' }"
                >
                    Bạn có chắc muốn xóa tài khoản này?
                </h2>

                <p class="mt-1 text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Vui lòng nhập mật khẩu hiện tại để xác nhận xóa vĩnh viễn tài khoản.
                </p>

                <div class="mt-6">
                    <InputLabel
                        for="password"
                        value="Mật khẩu"
                        class="sr-only"
                    />

                    <TextInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Mật khẩu hiện tại"
                        @keyup.enter="deleteUser"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal">
                        Hủy
                    </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Xóa tài khoản
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
