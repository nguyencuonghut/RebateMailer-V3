<script setup lang="ts">
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import AppLayout from '../layout/AppLayout.vue';

type OverviewCard = {
    label: string;
    value: number;
    caption: string;
    icon: string;
    severity: 'success' | 'info' | 'warn' | 'secondary';
};

type OperationalPanel = {
    title: string;
    status: string;
    lines: string[];
};

type QuickAction = {
    label: string;
    description: string;
    routeName: string;
};

type ImportBatchRow = {
    id: number;
    batchCode: string;
    batchName: string | null;
    status: string;
    statusLabel: string;
    aggregatedRecordCount: number;
    completedAt: string | null;
};

type CampaignRow = {
    id: number;
    name: string;
    status: string;
    statusLabel: string;
    batchCode: string | null;
    templateName: string | null;
    recipientCount: number;
    scheduledForAt: string | null;
    createdAt: string | null;
};

const props = defineProps<{
    title: string;
    subtitle: string;
    overviewCards: OverviewCard[];
    operationalPanels: OperationalPanel[];
    deliveryHealth: {
        queuedRecipients: number;
        sentRecipients: number;
        failedRecipients: number;
        failureRatePercent: number;
    };
    quickActions: QuickAction[];
    recentImportBatches: ImportBatchRow[];
    recentCampaigns: CampaignRow[];
}>();

const page = usePage<PageProps>();
const user = page.props.auth.user;

const formatDateTime = (value: string | null): string => {
    if (!value) {
        return 'Chưa có';
    }

    return new Date(value).toLocaleString('vi-VN');
};

const resolveBatchSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
    if (status === 'validated_ready' || status === 'aggregated') {
        return 'success';
    }

    if (status === 'validated_with_warnings') {
        return 'warn';
    }

    if (status === 'failed') {
        return 'danger';
    }

    return 'secondary';
};

const resolveCampaignSeverity = (status: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' => {
    if (status === 'completed') {
        return 'success';
    }

    if (status === 'dispatching' || status === 'scheduled') {
        return 'warn';
    }

    if (status === 'completed_with_failures' || status === 'cancelled') {
        return 'danger';
    }

    return 'secondary';
};
</script>

<template>
    <Head title="Bảng điều khiển vận hành" />

    <AppLayout :app-name="page.props.appName">
        <div class="space-y-6">
            <Card class="sakai-panel overflow-hidden rounded-[2rem] border-0">
                <template #content>
                    <div class="space-y-6 p-1 sm:p-2">
                        <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-500">
                                    Điều hành production
                                </p>
                                <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ title }}
                                </h1>
                                <p class="mt-4 text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    {{ subtitle }}
                                </p>
                                <p class="mt-4 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    Tài khoản hiện tại: {{ user?.name ?? 'Không xác định' }}.
                                    Vai trò: {{ user?.roles?.join(', ') || 'Chưa gán vai trò' }}.
                                </p>
                            </div>

                            <div class="min-w-[18rem] rounded-[1.6rem] border p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                    Sức khỏe gửi mail
                                </p>
                                <div class="mt-4 space-y-4">
                                    <div class="flex items-center justify-between text-sm">
                                        <span :style="{ color: 'var(--dashboard-muted-text)' }">Đã gửi thành công</span>
                                        <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ deliveryHealth.sentRecipients }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span :style="{ color: 'var(--dashboard-muted-text)' }">Đang queued</span>
                                        <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ deliveryHealth.queuedRecipients }}</span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span :style="{ color: 'var(--dashboard-muted-text)' }">Lỗi gửi</span>
                                        <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ deliveryHealth.failedRecipients }}</span>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span :style="{ color: 'var(--dashboard-muted-text)' }">Tỷ lệ lỗi</span>
                                            <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ deliveryHealth.failureRatePercent }}%</span>
                                        </div>
                                        <ProgressBar :value="deliveryHealth.failureRatePercent" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <article
                                v-for="card in overviewCards"
                                :key="card.label"
                                class="rounded-[1.4rem] border p-5"
                                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ card.label }}
                                        </p>
                                        <p class="mt-3 text-3xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ card.value }}
                                        </p>
                                    </div>
                                    <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-950 text-white">
                                        <i :class="card.icon" />
                                    </div>
                                </div>
                                <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    {{ card.caption }}
                                </p>
                                <Tag :severity="card.severity" :value="card.label" rounded class="mt-4" />
                            </article>
                        </div>
                    </div>
                </template>
            </Card>

            <section class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                    Điều hướng nhanh
                                </p>
                                <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    Các khu vực nghiệp vụ đang mở cho tài khoản hiện tại
                                </h2>
                            </div>

                            <div v-if="quickActions.length === 0" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                                Tài khoản hiện tại chưa được cấp quyền vào module nghiệp vụ nào ngoài đăng nhập cơ bản.
                            </div>

                            <div v-else class="grid gap-3 lg:grid-cols-2">
                                <article
                                    v-for="action in quickActions"
                                    :key="action.routeName"
                                    class="rounded-[1.4rem] border p-5"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                                >
                                    <p class="text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ action.label }}
                                    </p>
                                    <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ action.description }}
                                    </p>
                                    <Button class="mt-4" size="small" outlined :label="action.label" @click="$inertia.get(route(action.routeName))" />
                                </article>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                    Điểm cần chú ý
                                </p>
                                <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    Tình trạng vận hành gần nhất
                                </h2>
                            </div>

                            <article
                                v-for="panel in operationalPanels"
                                :key="panel.title"
                                class="rounded-[1.4rem] border p-5"
                                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ panel.title }}
                                    </p>
                                    <Tag :value="panel.status" severity="secondary" rounded />
                                </div>

                                <ul class="mt-3 space-y-2">
                                    <li
                                        v-for="line in panel.lines"
                                        :key="line"
                                        class="text-sm leading-6"
                                        :style="{ color: 'var(--dashboard-muted-text)' }"
                                    >
                                        {{ line }}
                                    </li>
                                </ul>
                            </article>
                        </div>
                    </template>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                        Batch import gần đây
                                    </p>
                                    <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        5 batch xử lý mới nhất
                                    </h2>
                                </div>
                                <Button size="small" outlined label="Mở import dữ liệu" @click="$inertia.get(route('imports.index'))" />
                            </div>

                            <div v-if="recentImportBatches.length === 0" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                                Chưa có batch import nào được tạo.
                            </div>

                            <div v-else class="space-y-3">
                                <article
                                    v-for="batch in recentImportBatches"
                                    :key="batch.id"
                                    class="rounded-[1.4rem] border p-4"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ batch.batchCode }} - {{ batch.batchName || 'Chưa đặt tên' }}
                                            </p>
                                            <p class="mt-1 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                {{ batch.aggregatedRecordCount }} khách aggregate · Hoàn thành: {{ formatDateTime(batch.completedAt) }}
                                            </p>
                                        </div>
                                        <Tag :value="batch.statusLabel" :severity="resolveBatchSeverity(batch.status)" rounded />
                                    </div>
                                </article>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                        Chiến dịch gửi mail gần đây
                                    </p>
                                    <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        5 chiến dịch mới nhất
                                    </h2>
                                </div>
                                <Button size="small" outlined label="Mở điều phối gửi mail" @click="$inertia.get(route('mail.index'))" />
                            </div>

                            <div v-if="recentCampaigns.length === 0" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                                Chưa có chiến dịch gửi mail nào được tạo.
                            </div>

                            <div v-else class="space-y-3">
                                <article
                                    v-for="campaign in recentCampaigns"
                                    :key="campaign.id"
                                    class="rounded-[1.4rem] border p-4"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ campaign.name }}
                                            </p>
                                            <p class="mt-1 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Batch: {{ campaign.batchCode || 'Không có batch' }} · Template: {{ campaign.templateName || 'Không có template' }}
                                            </p>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                {{ campaign.recipientCount }} người nhận · Tạo lúc {{ formatDateTime(campaign.createdAt) }}
                                            </p>
                                            <p v-if="campaign.scheduledForAt" class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Lịch gửi: {{ formatDateTime(campaign.scheduledForAt) }}
                                            </p>
                                        </div>
                                        <Tag :value="campaign.statusLabel" :severity="resolveCampaignSeverity(campaign.status)" rounded />
                                    </div>
                                </article>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
