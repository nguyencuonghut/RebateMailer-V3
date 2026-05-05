<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Đặt lại mật khẩu" />

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">Thiết lập thông tin xác thực</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Tạo mật khẩu mới
                </h2>
                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Hoàn tất quá trình đặt lại bằng cách xác nhận email và nhập mật khẩu mới.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <InputLabel for="email" value="Email đăng nhập" class="text-sm font-medium" />
                    <TextInput id="email" type="email" class="block w-full rounded-2xl border px-4 py-3 shadow-none" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }" v-model="form.email" required autofocus autocomplete="username" />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password" value="Mật khẩu mới" class="text-sm font-medium" />
                    <TextInput id="password" type="password" class="block w-full rounded-2xl border px-4 py-3 shadow-none" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }" v-model="form.password" required autocomplete="new-password" />
                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password_confirmation" value="Xác nhận mật khẩu mới" class="text-sm font-medium" />
                    <TextInput id="password_confirmation" type="password" class="block w-full rounded-2xl border px-4 py-3 shadow-none" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }" v-model="form.password_confirmation" required autocomplete="new-password" />
                    <InputError class="mt-1" :message="form.errors.password_confirmation" />
                </div>

                <PrimaryButton class="w-full justify-center rounded-2xl border-0 bg-slate-950 px-5 py-3 text-sm tracking-[0.18em] text-white hover:bg-slate-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Cập nhật mật khẩu
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
