<script setup lang="ts">
import AppLayout from '../layout/AppLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps<{
    appName: string;
}>();

const statCards = [
    { label: 'Luồng import dữ liệu', value: 'Sẵn sàng', icon: 'pi pi-file-import', severity: 'success' as const },
    { label: 'Trình dựng mẫu email', value: 'Đang xếp hàng', icon: 'pi pi-objects-column', severity: 'warn' as const },
    { label: 'Bộ điều phối gửi mail', value: 'Đã lên kế hoạch', icon: 'pi pi-send', severity: 'info' as const },
    { label: 'Theo dõi trạng thái', value: 'Đã lên kế hoạch', icon: 'pi pi-chart-line', severity: 'secondary' as const },
];
</script>

<template>
    <AppLayout :app-name="appName">
        <section class="grid gap-4 sm:gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(21rem,1fr)]">
            <Card class="sakai-panel overflow-hidden rounded-[2rem] border-0">
                <template #content>
                    <div class="flex flex-col gap-6 p-1 sm:gap-8 sm:p-2">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                                    Nhiệm vụ 0.3
                                </p>
                                <h2 class="mt-4 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl xl:text-5xl">
                                    Giao diện điều hành bằng PrimeVue và Sakai
                                </h2>
                                <p class="mt-4 text-base leading-7 text-slate-600">
                                    Nền UI đã được nâng từ Inertia shell lên bảng điều khiển quản trị có thanh bên,
                                    thanh trên và các thành phần PrimeVue, sẵn sàng cho các lát cắt nghiệp vụ tiếp theo.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3">
                                <Avatar label="AI" shape="circle" class="bg-teal-600 text-white" size="large" />
                                <div class="rounded-2xl bg-slate-950 px-4 py-3 text-sm text-slate-100 shadow-lg">
                                    Bản dựng responsive
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <article
                                v-for="card in statCards"
                                :key="card.label"
                                class="rounded-[1.4rem] border border-slate-200 bg-white/80 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium text-slate-500">
                                            {{ card.label }}
                                        </p>
                                        <p class="mt-3 text-2xl font-semibold text-slate-950">
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
                        Lộ trình dự án
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-950">0.1 Hạ tầng nền</p>
                                <p class="mt-1 text-sm text-slate-600">Laravel 13, PostgreSQL, Redis</p>
                            </div>
                            <div class="rounded-2xl bg-teal-50 p-4 ring-1 ring-teal-100">
                                <p class="text-sm font-semibold text-teal-900">0.2 Frontend cốt lõi</p>
                                <p class="mt-1 text-sm text-teal-700">InertiaJS, Vue 3, TypeScript</p>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <p class="text-sm font-semibold text-slate-950">0.3 Giao diện dashboard</p>
                                <p class="mt-1 text-sm text-slate-600">PrimeVue v4, bố cục Sakai</p>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Bước tiếp theo
                    </template>
                    <template #content>
                        <div class="space-y-3 text-sm leading-6 text-slate-600">
                            <p>Bước tiếp theo hợp lý là màn upload file Excel và preview parse cho Slice 1.</p>
                            <Button label="Bắt đầu Slice 1" icon="pi pi-arrow-right" />
                        </div>
                    </template>
                </Card>
            </div>
        </section>
    </AppLayout>
</template>
