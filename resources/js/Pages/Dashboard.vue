<script setup lang="ts">
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import AppLayout from '../layout/AppLayout.vue';

const page = usePage<PageProps>();

const statCards = [
    { label: 'Luồng import dữ liệu', value: 'Sẵn sàng', icon: 'pi pi-file-import', severity: 'success' as const },
    { label: 'Thiết kế mẫu email', value: 'Đang chờ triển khai', icon: 'pi pi-pencil', severity: 'warn' as const },
    { label: 'Điều phối gửi mail', value: 'Mailpit đã bật', icon: 'pi pi-send', severity: 'info' as const },
    { label: 'Theo dõi trạng thái', value: 'RBAC đã sẵn sàng', icon: 'pi pi-shield', severity: 'secondary' as const },
];

const quickActions = [
    { label: 'Import dữ liệu', routeName: 'imports.index', permission: 'imports.view' },
    { label: 'Điều phối gửi mail', routeName: 'mail.index', permission: 'mail.view' },
    { label: 'Quản lý người dùng', routeName: 'users.index', permission: 'users.view' },
];

const user = page.props.auth.user;

const visibleActions = quickActions.filter((item) => user?.permissions.includes(item.permission));
</script>

<template>
    <Head title="Bảng điều khiển" />

    <AppLayout :app-name="page.props.appName">
        <section class="grid gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(21rem,1fr)]">
            <Card class="sakai-panel overflow-hidden rounded-[2rem] border-0">
                <template #content>
                    <div class="flex flex-col gap-6 p-1 sm:gap-8 sm:p-2">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                                    Hạ tầng quản trị
                                </p>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl xl:text-5xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    Bảng điều hành RebateMailerV3 đã có xác thực và phân quyền
                                </h2>
                                <p class="mt-4 text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    Tài khoản hiện tại đăng nhập với vai trò {{ user?.roles?.[0] ?? 'nội bộ' }},
                                    các khu vực nghiệp vụ sẽ mở hoặc khóa theo permission thực tế của người dùng.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Avatar label="RBAC" shape="circle" class="bg-teal-600 text-white" size="large" />
                                <div class="rounded-2xl px-4 py-3 text-sm shadow-lg" :style="{ background: 'var(--dashboard-chip-bg)', color: 'var(--dashboard-chip-text)' }">
                                    Spatie Permission + Breeze
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <article
                                v-for="card in statCards"
                                :key="card.label"
                                class="rounded-[1.4rem] border p-5"
                                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ card.label }}
                                        </p>
                                        <p class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ card.value }}
                                        </p>
                                    </div>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-white">
                                        <i :class="card.icon" />
                                    </div>
                                </div>
                                <Tag :severity="card.severity" :value="card.value" rounded class="mt-4" />
                            </article>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid gap-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Điều hướng theo quyền
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            <p>Những khu vực dưới đây đang mở cho tài khoản hiện tại.</p>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    v-for="action in visibleActions"
                                    :key="action.routeName"
                                    :label="action.label"
                                    size="small"
                                    outlined
                                    @click="$inertia.get(route(action.routeName))"
                                />
                            </div>
                            <p v-if="visibleActions.length === 0">
                                Tài khoản này chưa được cấp quyền thao tác nào ngoài đăng nhập cơ bản.
                            </p>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Vai trò hiện tại
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <Tag
                                v-for="role in user?.roles ?? []"
                                :key="role"
                                :value="role"
                                severity="contrast"
                                rounded
                            />
                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                Quyền CRUD người dùng chỉ mở cho vai trò Admin. Vai trò Khách chỉ xem được
                                import dữ liệu và điều phối mail.
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </section>
    </AppLayout>
</template>
