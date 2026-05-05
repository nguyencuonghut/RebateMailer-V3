<script setup lang="ts">
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Xác minh email" />

        <div class="space-y-6">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">Hoàn tất xác thực</p>
                <h2 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Xác minh địa chỉ email
                </h2>
                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Trước khi bắt đầu sử dụng hệ thống, vui lòng xác minh email bằng liên kết đã được gửi đến hộp thư của bạn.
                </p>
            </div>

            <div v-if="verificationLinkSent" class="rounded-2xl border px-4 py-3 text-sm font-medium text-emerald-700" :style="{ borderColor: 'rgba(16, 185, 129, 0.22)', background: 'rgba(236, 253, 245, 0.85)' }">
                Liên kết xác minh mới đã được gửi đến địa chỉ email của bạn.
            </div>

            <form @submit.prevent="submit">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <PrimaryButton class="justify-center rounded-2xl border-0 bg-slate-950 px-5 py-3 text-sm tracking-[0.18em] text-white hover:bg-slate-800" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Gửi lại email xác minh
                    </PrimaryButton>

                    <Link :href="route('logout')" method="post" as="button" class="text-sm font-medium text-teal-700 underline decoration-transparent transition hover:decoration-current">
                        Đăng xuất
                    </Link>
                </div>
            </form>
        </div>
    </GuestLayout>
</template>
