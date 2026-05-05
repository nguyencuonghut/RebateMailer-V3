<script setup lang="ts">
import type { PageProps } from '@/types';
import AppLayout from '@/layout/AppLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import Card from 'primevue/card';
import { Head, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();

const page = usePage<PageProps>();
</script>

<template>
    <Head title="Hồ sơ cá nhân" />

    <AppLayout :app-name="page.props.appName">
        <div class="space-y-6">
            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-500">Thiết lập tài khoản</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                            Hồ sơ cá nhân
                        </h1>
                        <p class="mt-3 max-w-3xl text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Cập nhật thông tin nhận diện, mật khẩu và các thiết lập bảo mật của tài khoản đang đăng nhập.
                        </p>
                    </div>
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-2xl" />
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <UpdatePasswordForm class="max-w-2xl" />
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <DeleteUserForm class="max-w-2xl" />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>
