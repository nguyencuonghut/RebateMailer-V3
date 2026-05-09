<script setup lang="ts">
import type { PageProps } from '@/types';
import MailRecipientEmailPreview from '@/Components/mail/MailRecipientEmailPreview.vue';
import DataTableGlobalFilterToolbar from '@/Components/common/DataTableGlobalFilterToolbar.vue';
import { useDataTableGlobalFilter } from '@/Services/useDataTableGlobalFilter';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import ProgressBar from 'primevue/progressbar';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '../../layout/AppLayout.vue';

type BatchOption = {
    batchId: number;
    batchCode: string;
    batchName: string;
    label: string;
};

type TemplateOption = {
    canvasId: number;
    name: string;
    isActive: boolean;
    partCount: number;
    label: string;
};

type CampaignOption = {
    campaignId: number;
    name: string;
    status: string;
    label: string;
};

type RecipientRow = {
    id: number;
    customerCode: string;
    customerFullName: string;
    recipientEmail: string | null;
    customerType: string;
    deliveryStatus: string;
    deliveryStatusLabel: string;
    latestErrorMessage: string | null;
    attemptsCount: number;
    canRetry: boolean;
    attemptLogs: Array<{
        id: number;
        eventType: string;
        eventLabel: string;
        status: string;
        statusLabel: string;
        message: string | null;
        createdAt: string | null;
        context: Record<string, unknown>;
    }>;
};

const props = defineProps<{
    title: string;
    description: string;
    canManageCampaigns: boolean;
    readOnlyNotice: string;
    batchOptions: BatchOption[];
    templateOptions: TemplateOption[];
    campaignOptions: CampaignOption[];
    selectedCampaignId: number | null;
    selectedRecipientId: number | null;
    selectedCampaign: {
        id: number;
        name: string;
        notes: string | null;
        status: string;
        statusLabel: string;
        scheduledAt: string | null;
        createdBy: string;
        createdAt: string | null;
        batch: {
            id: number | null;
            batchCode: string | null;
            batchName: string | null;
        };
        template: {
            id: number | null;
            name: string | null;
            isActive: boolean;
        };
        recipientSummary: {
            total: number;
            pending: number;
            queued: number;
            sent: number;
            failed: number;
        };
        progress: {
            batchId: number;
            batchCode: string | null;
            totalRecipients: number;
            processedRecipients: number;
            queuedRecipients: number;
            sentRecipients: number;
            failedRecipients: number;
            pendingRecipients: number;
            completionPercent: number;
            sentPercent: number;
            failedPercent: number;
            queuedPercent: number;
        };
    } | null;
    selectedRecipientPreview: {
        recipient: {
            id: number;
            customerCode: string;
            customerFullName: string;
            recipientEmail: string | null;
            customerType: string;
        };
        subject: {
            templateText: string;
            renderedText: string;
            errors: string[];
        } | null;
        greeting: {
            templateText: string;
            renderedText: string;
            errors: string[];
        } | null;
        tables: Array<{
            type: string;
            label: string;
            title: string;
            sourceSheet: string | null;
            rows: Array<Record<string, unknown>>;
            errors: string[];
        }>;
        errors: string[];
        html: string;
    } | null;
    recipientList: RecipientRow[];
}>();

const page = usePage<PageProps>();

const createForm = useForm({
    name: '',
    import_batch_id: props.batchOptions[0]?.batchId ?? null,
    mail_template_canvas_id: props.templateOptions[0]?.canvasId ?? null,
    notes: '',
});

const scheduleForm = useForm({
    scheduled_at: '',
});

const {
    filters: recipientFilters,
    globalFilterFields: recipientGlobalFilterFields,
    globalFilterValue: recipientGlobalFilterValue,
    clearGlobalFilter: clearRecipientGlobalFilter,
} = useDataTableGlobalFilter<RecipientRow>([
    'customerCode',
    'customerFullName',
    'recipientEmail',
    'customerType',
    'deliveryStatusLabel',
    'latestErrorMessage',
]);

const selectedCampaignOption = computed(() =>
    props.campaignOptions.find((campaign) => campaign.campaignId === props.selectedCampaignId)?.campaignId ?? null,
);
const isPreviewDialogVisible = computed(() => props.selectedRecipientPreview !== null);
const canDispatchSelectedCampaign = computed(() =>
    props.canManageCampaigns
    && !!props.selectedCampaign
    && ['draft', 'scheduled'].includes(props.selectedCampaign.status),
);
const shouldAutoRefreshCampaign = computed(() =>
    !!props.selectedCampaign
    && ['scheduled', 'dispatching'].includes(props.selectedCampaign.status),
);
const selectedRecipientErrorRow = ref<RecipientRow | null>(null);
const isErrorDialogVisible = computed(() => selectedRecipientErrorRow.value !== null);

const submitCreate = (): void => {
    createForm.post(route('mail.campaigns.store'), {
        preserveScroll: true,
    });
};

const startDispatchNow = (): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.post(route('mail.campaigns.dispatch', props.selectedCampaignId), {}, {
        preserveScroll: true,
    });
};

const scheduleDispatch = (): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    scheduleForm.post(route('mail.campaigns.schedule', props.selectedCampaignId), {
        preserveScroll: true,
    });
};

const openCampaign = (campaignId: number | null): void => {
    router.get(
        route('mail.index'),
        campaignId ? { campaign: campaignId } : {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const openRecipientPreview = (recipientId: number): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.get(
        route('mail.index'),
        {
            campaign: props.selectedCampaignId,
            recipient: recipientId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const closeRecipientPreview = (): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.get(
        route('mail.index'),
        {
            campaign: props.selectedCampaignId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const openRecipientErrorDialog = (recipient: RecipientRow): void => {
    selectedRecipientErrorRow.value = recipient;
};

const closeRecipientErrorDialog = (): void => {
    selectedRecipientErrorRow.value = null;
};

const retryRecipient = (recipient: RecipientRow): void => {
    if (!props.selectedCampaignId || !props.canManageCampaigns) {
        return;
    }

    router.post(route('mail.campaigns.recipients.retry', {
        mailCampaign: props.selectedCampaignId,
        mailCampaignRecipient: recipient.id,
    }), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

let autoRefreshTimer: ReturnType<typeof setInterval> | null = null;

const refreshCampaignDashboard = (): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.reload({
        only: ['campaignOptions', 'selectedCampaign', 'recipientList', 'selectedRecipientPreview', 'selectedRecipientId'],
        data: {
            campaign: props.selectedCampaignId,
            ...(props.selectedRecipientId ? { recipient: props.selectedRecipientId } : {}),
        },
    });
};

const syncAutoRefresh = (): void => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
        autoRefreshTimer = null;
    }

    if (!shouldAutoRefreshCampaign.value) {
        return;
    }

    autoRefreshTimer = setInterval(refreshCampaignDashboard, 5000);
};

onMounted(syncAutoRefresh);
watch(() => [props.selectedCampaignId, props.selectedCampaign?.status, props.selectedRecipientId], syncAutoRefresh);
onBeforeUnmount(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
    }
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1.5fr)]">
            <div class="space-y-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-500">
                                        Gửi mail chiết khấu
                                    </p>
                                    <h1 class="mt-4 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ title }}
                                    </h1>
                                    <p class="mt-4 text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ description }}
                                    </p>
                                </div>

                                <Tag :value="canManageCampaigns ? 'Có quyền điều phối' : 'Chỉ xem'" :severity="canManageCampaigns ? 'success' : 'warn'" rounded />
                            </div>

                            <div
                                class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                                :style="{
                                    borderColor: canManageCampaigns ? 'rgba(20, 184, 166, 0.32)' : 'rgba(245, 158, 11, 0.32)',
                                    background: canManageCampaigns ? 'rgba(20, 184, 166, 0.08)' : 'rgba(245, 158, 11, 0.08)',
                                    color: 'var(--dashboard-muted-text)',
                                }"
                            >
                                <template v-if="canManageCampaigns">
                                    Tạo chiến dịch từ batch dữ liệu đã aggregate và template email đã sẵn sàng, sau đó hệ thống sẽ materialize danh sách người nhận để rà soát trước khi bước sang preview đầy đủ và dispatch hàng loạt.
                                </template>
                                <template v-else>
                                    {{ readOnlyNotice }}
                                </template>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Tạo chiến dịch gửi mail
                    </template>
                    <template #content>
                        <form class="space-y-4" @submit.prevent="submitCreate">
                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên chiến dịch</label>
                                <InputText v-model="createForm.name" fluid :disabled="!canManageCampaigns" />
                                <small v-if="createForm.errors.name" class="text-red-500">{{ createForm.errors.name }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Batch nhập liệu</label>
                                <Select
                                    v-model="createForm.import_batch_id"
                                    :options="batchOptions"
                                    option-label="label"
                                    option-value="batchId"
                                    filter
                                    fluid
                                    :disabled="!canManageCampaigns"
                                    placeholder="Chọn batch đã aggregate"
                                />
                                <small v-if="createForm.errors.import_batch_id" class="text-red-500">{{ createForm.errors.import_batch_id }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Template email</label>
                                <Select
                                    v-model="createForm.mail_template_canvas_id"
                                    :options="templateOptions"
                                    option-label="label"
                                    option-value="canvasId"
                                    filter
                                    fluid
                                    :disabled="!canManageCampaigns"
                                    placeholder="Chọn template canvas"
                                />
                                <small v-if="createForm.errors.mail_template_canvas_id" class="text-red-500">{{ createForm.errors.mail_template_canvas_id }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Ghi chú</label>
                                <Textarea v-model="createForm.notes" rows="4" fluid :disabled="!canManageCampaigns" />
                                <small v-if="createForm.errors.notes" class="text-red-500">{{ createForm.errors.notes }}</small>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    label="Tạo chiến dịch"
                                    :loading="createForm.processing"
                                    :disabled="!canManageCampaigns || batchOptions.length === 0 || templateOptions.length === 0"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>

            <div class="space-y-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Chiến dịch đang xem
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Chọn chiến dịch</label>
                                <Select
                                    :model-value="selectedCampaignOption"
                                    :options="campaignOptions"
                                    option-label="label"
                                    option-value="campaignId"
                                    filter
                                    show-clear
                                    fluid
                                    placeholder="Chọn chiến dịch đã tạo"
                                    @update:model-value="openCampaign"
                                />
                            </div>

                            <div
                                v-if="!selectedCampaign"
                                class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                            >
                                Chưa có chiến dịch nào được tạo. Sau khi tạo chiến dịch đầu tiên, danh sách người nhận đã aggregate sẽ xuất hiện tại đây.
                            </div>

                            <template v-else>
                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Tổng người nhận</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.total }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Chưa gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.pending }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Đã vào hàng đợi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.queued }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Đã gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.sent }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Lỗi gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.failed }}</p>
                                    </div>
                                </div>

                                <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                Tiến độ gửi theo batch
                                            </h3>
                                            <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Batch ID: {{ selectedCampaign.progress.batchId }}<span v-if="selectedCampaign.progress.batchCode"> - {{ selectedCampaign.progress.batchCode }}</span>
                                            </p>
                                        </div>

                                        <Tag
                                            :value="shouldAutoRefreshCampaign ? 'Tự động làm mới mỗi 5 giây' : 'Dữ liệu đã ổn định'"
                                            :severity="shouldAutoRefreshCampaign ? 'info' : 'secondary'"
                                            rounded
                                        />
                                    </div>

                                    <div class="mt-4 space-y-4">
                                        <div class="space-y-2">
                                            <div class="flex items-center justify-between gap-3 text-sm">
                                                <span :style="{ color: 'var(--dashboard-muted-text)' }">Hoàn thành tổng</span>
                                                <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                    {{ selectedCampaign.progress.processedRecipients }}/{{ selectedCampaign.progress.totalRecipients }} ({{ selectedCampaign.progress.completionPercent }}%)
                                                </span>
                                            </div>
                                            <ProgressBar :value="selectedCampaign.progress.completionPercent" />
                                        </div>

                                        <div class="grid gap-4 xl:grid-cols-3">
                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span :style="{ color: 'var(--dashboard-muted-text)' }">Đã vào hàng đợi</span>
                                                    <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        {{ selectedCampaign.progress.queuedRecipients }} ({{ selectedCampaign.progress.queuedPercent }}%)
                                                    </span>
                                                </div>
                                                <ProgressBar :value="selectedCampaign.progress.queuedPercent" />
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span :style="{ color: 'var(--dashboard-muted-text)' }">Đã gửi thành công</span>
                                                    <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        {{ selectedCampaign.progress.sentRecipients }} ({{ selectedCampaign.progress.sentPercent }}%)
                                                    </span>
                                                </div>
                                                <ProgressBar :value="selectedCampaign.progress.sentPercent" />
                                            </div>

                                            <div class="space-y-2">
                                                <div class="flex items-center justify-between gap-3 text-sm">
                                                    <span :style="{ color: 'var(--dashboard-muted-text)' }">Lỗi gửi</span>
                                                    <span class="font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        {{ selectedCampaign.progress.failedRecipients }} ({{ selectedCampaign.progress.failedPercent }}%)
                                                    </span>
                                                </div>
                                                <ProgressBar :value="selectedCampaign.progress.failedPercent" />
                                            </div>
                                        </div>

                                        <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Còn lại {{ selectedCampaign.progress.pendingRecipients }} người nhận chưa vào hàng đợi hoặc chưa được xử lý xong.
                                        </p>
                                    </div>
                                </div>

                                <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ selectedCampaign.name }}
                                            </h2>
                                            <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Batch: {{ selectedCampaign.batch.batchCode }} - {{ selectedCampaign.batch.batchName || 'Chưa đặt tên' }}
                                            </p>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Template: {{ selectedCampaign.template.name }}
                                            </p>
                                            <p v-if="selectedCampaign.scheduledAt" class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Lịch gửi: {{ new Date(selectedCampaign.scheduledAt).toLocaleString('vi-VN') }}
                                            </p>
                                        </div>

                                        <Tag :value="selectedCampaign.statusLabel" severity="info" rounded />
                                    </div>

                                    <p v-if="selectedCampaign.notes" class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ selectedCampaign.notes }}
                                    </p>

                                    <div v-if="canManageCampaigns" class="mt-4 border-t pt-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                                        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                                            <div class="space-y-2">
                                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Lên lịch gửi</label>
                                                <input
                                                    v-model="scheduleForm.scheduled_at"
                                                    type="datetime-local"
                                                    class="w-full rounded-2xl border px-4 py-3 shadow-none xl:w-[20rem]"
                                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-strong-text)' }"
                                                    :disabled="!canDispatchSelectedCampaign"
                                                >
                                                <small v-if="scheduleForm.errors.scheduled_at" class="text-red-500">{{ scheduleForm.errors.scheduled_at }}</small>
                                            </div>

                                            <div class="flex flex-wrap justify-end gap-3">
                                                <Button
                                                    type="button"
                                                    label="Lên lịch gửi"
                                                    severity="secondary"
                                                    outlined
                                                    :disabled="!canDispatchSelectedCampaign || scheduleForm.processing || !scheduleForm.scheduled_at"
                                                    @click="scheduleDispatch"
                                                />
                                                <Button
                                                    type="button"
                                                    label="Gửi ngay"
                                                    :disabled="!canDispatchSelectedCampaign"
                                                    @click="startDispatchNow"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Danh sách người nhận đã aggregate
                    </template>
                    <template #content>
                        <div v-if="!selectedCampaign" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                            Chọn một chiến dịch để xem danh sách người nhận.
                        </div>

                        <div v-else class="space-y-3">
                            <DataTableGlobalFilterToolbar
                                v-model="recipientGlobalFilterValue"
                                placeholder="Tìm theo mã số, khách hàng, email, loại khách, trạng thái"
                                @clear="clearRecipientGlobalFilter"
                            />

                            <DataTable
                                v-model:filters="recipientFilters"
                                :value="recipientList"
                                :global-filter-fields="recipientGlobalFilterFields"
                                paginator
                                :rows="10"
                                responsive-layout="scroll"
                                class="p-datatable-sm"
                            >
                                <Column field="customerCode" header="Mã số" />
                                <Column field="customerFullName" header="Mã & tên khách hàng" />
                                <Column header="Email">
                                    <template #body="{ data }">
                                        <span :style="{ color: data.recipientEmail ? 'var(--dashboard-strong-text)' : 'var(--dashboard-muted-text)' }">
                                            {{ data.recipientEmail || 'Chưa có email' }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="customerType" header="Loại khách" />
                                <Column header="Trạng thái gửi">
                                    <template #body="{ data }">
                                        <Tag :value="data.deliveryStatusLabel" :severity="data.deliveryStatus === 'failed' ? 'danger' : data.deliveryStatus === 'sent' ? 'success' : 'warn'" rounded />
                                    </template>
                                </Column>
                                <Column header="Lỗi gần nhất">
                                    <template #body="{ data }">
                                        <div class="space-y-1">
                                            <span :style="{ color: 'var(--dashboard-muted-text)' }">
                                                {{ data.latestErrorMessage || 'Không có' }}
                                            </span>
                                            <p class="text-xs" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Lịch sử: {{ data.attemptLogs.length }} bản ghi
                                            </p>
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Thao tác">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                label="Xem trước email"
                                                size="small"
                                                outlined
                                                @click="openRecipientPreview(data.id)"
                                            />
                                            <Button
                                                type="button"
                                                label="Chi tiết lỗi"
                                                size="small"
                                                severity="secondary"
                                                outlined
                                                @click="openRecipientErrorDialog(data)"
                                            />
                                            <Button
                                                v-if="canManageCampaigns && data.canRetry"
                                                type="button"
                                                label="Retry"
                                                size="small"
                                                severity="warn"
                                                @click="retryRecipient(data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <Dialog
            :visible="isPreviewDialogVisible"
            modal
            maximizable
            :style="{ width: 'min(1120px, 96vw)' }"
            header="Xem trước toàn bộ email"
            @update:visible="(visible) => { if (!visible) closeRecipientPreview() }"
        >
            <MailRecipientEmailPreview
                v-if="selectedRecipientPreview"
                :preview="selectedRecipientPreview"
            />
        </Dialog>

        <Dialog
            :visible="isErrorDialogVisible"
            modal
            :style="{ width: 'min(760px, 96vw)' }"
            header="Chi tiết lỗi gửi mail"
            @update:visible="(visible) => { if (!visible) closeRecipientErrorDialog() }"
        >
            <div v-if="selectedRecipientErrorRow" class="space-y-4">
                <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ selectedRecipientErrorRow.customerFullName }}
                    </p>
                    <p class="mt-1 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Mã số: {{ selectedRecipientErrorRow.customerCode }} | Email: {{ selectedRecipientErrorRow.recipientEmail || 'Chưa có email' }}
                    </p>
                    <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Trạng thái hiện tại: {{ selectedRecipientErrorRow.deliveryStatusLabel }}
                    </p>
                </div>

                <div v-if="selectedRecipientErrorRow.attemptLogs.length === 0" class="rounded-[1.25rem] border p-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                    Chưa có bản ghi lỗi hoặc lịch sử retry cho người nhận này.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="attempt in selectedRecipientErrorRow.attemptLogs"
                        :key="attempt.id"
                        class="rounded-[1.25rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ attempt.eventLabel }}
                                </p>
                                <p class="mt-1 text-xs leading-5" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    {{ attempt.createdAt ? new Date(attempt.createdAt).toLocaleString('vi-VN') : 'Không có thời điểm' }}
                                </p>
                            </div>

                            <Tag :value="attempt.statusLabel" :severity="attempt.status === 'failed' ? 'danger' : attempt.status === 'queued' ? 'warn' : 'success'" rounded />
                        </div>

                        <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ attempt.message || 'Không có thông điệp chi tiết.' }}
                        </p>

                        <div v-if="Object.keys(attempt.context).length > 0" class="mt-3 rounded-2xl border px-4 py-3 text-xs leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
                            <p
                                v-for="[key, value] in Object.entries(attempt.context)"
                                :key="`${attempt.id}-${key}`"
                            >
                                {{ key }}: {{ String(value) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
