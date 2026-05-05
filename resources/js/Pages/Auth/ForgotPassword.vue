<script setup lang="ts">
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head title="Quên mật khẩu" />

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">Khôi phục truy cập</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Yêu cầu liên kết đặt lại mật khẩu
                </h2>
                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Nhập email đăng nhập để nhận liên kết đặt lại mật khẩu qua email.
                </p>
            </div>

            <div v-if="status" class="rounded-2xl border px-4 py-3 text-sm font-medium text-emerald-700" :style="{ borderColor: 'rgba(16, 185, 129, 0.22)', background: 'rgba(236, 253, 245, 0.85)' }">
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <InputLabel for="email" value="Email đăng nhập" class="text-sm font-medium" />
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full rounded-2xl border px-4 py-3 shadow-none"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                    />
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <PrimaryButton class="w-full justify-center rounded-2xl border-0 bg-slate-950 px-5 py-3 text-sm tracking-[0.18em] text-white hover:bg-slate-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Gửi liên kết đặt lại mật khẩu
                </PrimaryButton>
            </form>
        </div>
    </GuestLayout>
</template>
