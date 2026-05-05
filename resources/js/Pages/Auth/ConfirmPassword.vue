<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Xác nhận mật khẩu" />

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">Bảo vệ thao tác nhạy cảm</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Xác nhận lại mật khẩu
                </h2>
                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Khu vực này yêu cầu xác thực lại để tiếp tục thao tác an toàn.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <InputLabel for="password" value="Mật khẩu hiện tại" class="text-sm font-medium" />
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full rounded-2xl border px-4 py-3 shadow-none"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        autofocus
                    />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <PrimaryButton class="w-full justify-center rounded-2xl border-0 bg-slate-950 px-5 py-3 text-sm tracking-[0.18em] text-white hover:bg-slate-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Xác nhận và tiếp tục
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
