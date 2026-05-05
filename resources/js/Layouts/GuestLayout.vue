<script setup lang="ts">
import type { PageProps } from '@/types';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppLogo from '../layout/AppLogo.vue';
import { useLayout } from '../layout/composables/useLayout';

const { isDarkMode, initializeTheme, toggleTheme } = useLayout();
const page = usePage<PageProps>();
const isLocalEnvironment = ['local', 'development'].includes(page.props.appEnvironment);

onMounted(() => {
    initializeTheme();
});
</script>

<template>
    <div class="relative flex min-h-screen items-center px-4 py-8 sm:px-6 lg:px-8">
        <div class="absolute right-4 top-4 sm:right-6 sm:top-6">
            <Button
                :icon="isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"
                rounded
                text
                severity="secondary"
                :aria-label="isDarkMode ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'"
                @click="toggleTheme"
            />
        </div>

        <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 lg:grid lg:grid-cols-[minmax(0,1.15fr)_minmax(24rem,28rem)] lg:items-center lg:gap-10">
            <section class="sakai-panel relative overflow-hidden rounded-[2rem] border-0 p-6 sm:p-8 lg:p-10">
                <div class="absolute inset-x-0 top-0 h-32 bg-gradient-to-r from-teal-400/15 via-transparent to-cyan-400/15" />

                <Link href="/" class="relative inline-flex items-center gap-4">
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-[1.35rem]"
                        :style="{
                            background: 'var(--brand-logo-bg)',
                            color: 'var(--brand-logo-fg)',
                            boxShadow: 'inset 0 0 0 1px var(--brand-logo-ring)',
                        }"
                    >
                        <AppLogo class="h-8 w-8" />
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.34em]" style="color: var(--dashboard-muted-text)">
                            RebateMailer
                        </p>
                        <p class="text-xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                            Trung tâm điều phối rebate
                        </p>
                    </div>
                </Link>

                <div class="relative mt-10 max-w-2xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                        Đăng nhập nội bộ
                    </p>
                    <h1 class="mt-4 text-4xl font-semibold tracking-tight sm:text-5xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Không gian đăng nhập dành cho người vận hành và quản trị hệ thống
                    </h1>
                    <p class="mt-5 max-w-xl text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Truy cập nền tảng điều phối rebate để xử lý import dữ liệu, quản lý template email,
                        vận hành chiến dịch gửi mail và phân quyền theo vai trò người dùng.
                    </p>
                </div>

                <div class="relative mt-10 grid gap-3 sm:grid-cols-3">
                    <div
                        class="rounded-[1.25rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Xác thực nội bộ</p>
                        <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Laravel Breeze</p>
                    </div>
                    <div
                        class="rounded-[1.25rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Phân quyền</p>
                        <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Spatie Roles</p>
                    </div>
                    <div
                        class="rounded-[1.25rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">UI đồng nhất</p>
                        <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">PrimeVue + Sakai</p>
                    </div>
                </div>

                <div class="relative mt-8 flex flex-wrap items-center gap-3">
                    <Tag severity="contrast" rounded :value="page.props.appName" />
                    <Tag v-if="isLocalEnvironment" severity="info" rounded value="Môi trường phát triển" />
                    <span class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Phiên đăng nhập này được bảo vệ bởi xác thực ứng dụng và kiểm soát truy cập theo vai trò.
                    </span>
                </div>
            </section>

            <div class="sakai-panel rounded-[2rem] border-0 p-5 sm:p-7">
                <slot />
            </div>
        </div>
    </div>
</template>
