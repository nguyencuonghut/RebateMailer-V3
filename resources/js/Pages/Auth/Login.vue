<script setup lang="ts">
import type { PageProps } from '@/types';
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const page = usePage<PageProps>();
const isLocalEnvironment = ['local', 'development'].includes(page.props.appEnvironment);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Đăng nhập" />

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                    Truy cập an toàn
                </p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Đăng nhập hệ thống
                </h2>
                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Sử dụng tài khoản đã được cấp để truy cập đúng phạm vi nghiệp vụ của bạn trong {{ page.props.appName }}.
                </p>
            </div>

            <div
                v-if="status"
                class="rounded-2xl border px-4 py-3 text-sm font-medium text-emerald-700"
                :style="{ borderColor: 'rgba(16, 185, 129, 0.22)', background: 'rgba(236, 253, 245, 0.85)' }"
            >
                {{ status }}
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <InputLabel for="email" value="Email đăng nhập" class="text-sm font-medium" />

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <i class="pi pi-envelope text-sm" />
                        </span>
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full rounded-2xl border py-3 pl-11 pr-4 shadow-none"
                            :style="{
                                borderColor: 'var(--dashboard-panel-border)',
                                background: 'var(--dashboard-card-bg)',
                                color: 'var(--dashboard-strong-text)',
                            }"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />
                    </div>

                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <InputLabel for="password" value="Mật khẩu" class="text-sm font-medium" />

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                            <i class="pi pi-lock text-sm" />
                        </span>
                        <TextInput
                            id="password"
                            type="password"
                            class="block w-full rounded-2xl border py-3 pl-11 pr-4 shadow-none"
                            :style="{
                                borderColor: 'var(--dashboard-panel-border)',
                                background: 'var(--dashboard-card-bg)',
                                color: 'var(--dashboard-strong-text)',
                            }"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />
                    </div>

                    <InputError class="mt-1" :message="form.errors.password" />
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <label class="flex items-center gap-3">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Ghi nhớ đăng nhập</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-teal-700 underline decoration-transparent transition hover:decoration-current"
                    >
                        Quên mật khẩu?
                    </Link>
                </div>

                <PrimaryButton
                    class="w-full justify-center rounded-2xl border-0 bg-slate-950 px-5 py-3 text-sm tracking-[0.18em] text-white hover:bg-slate-800 focus:bg-slate-800 active:bg-black"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Đăng nhập vào hệ thống
                </PrimaryButton>
            </form>

            <div
                class="rounded-[1.4rem] border p-4 text-sm leading-6"
                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
            >
                <p class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Lưu ý bảo mật</p>
                <p class="mt-2">
                    Chỉ sử dụng tài khoản được cấp phát cho đúng phạm vi công việc. Mọi khu vực nghiệp vụ sẽ được mở hoặc khóa theo role và permission của phiên đăng nhập hiện tại.
                </p>

                <template v-if="isLocalEnvironment">
                    <p class="mt-4 font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Tài khoản mẫu cho môi trường phát triển</p>
                    <p class="mt-2 font-mono text-[13px]">admin@rebatemailer.test / password</p>
                    <p class="font-mono text-[13px]">user@rebatemailer.test / password</p>
                    <p class="font-mono text-[13px]">guest@rebatemailer.test / password</p>
                </template>
            </div>
        </div>
    </GuestLayout>
</template>
