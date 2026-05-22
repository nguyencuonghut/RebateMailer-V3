<script setup lang="ts">
import type { PageProps } from '@/types';
import MailRecipientEmailPreview from '@/Components/mail/MailRecipientEmailPreview.vue';
import DataTableGlobalFilterToolbar from '@/Components/common/DataTableGlobalFilterToolbar.vue';
import { useDataTableGlobalFilter } from '@/Services/useDataTableGlobalFilter';
import axios from 'axios';
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
    errorCount: number;
    hasErrors: boolean;
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
    createdBy: string;
    createdAt: string | null;
    label: string;
};

type RecipientRow = {
    id: number;
    customerCode: string;
    customerFullName: string;
    recipientEmail: string | null;
    sourceSheets: string[];
    sourceSheetsLabel: string;
    deliveryStatus: string;
    deliveryStatusLabel: string;
    latestErrorMessage: string | null;
    latestFriendlyMessage: string | null;
    attemptsCount: number;
    canRetry: boolean;
    canResend: boolean;
    attemptLogs: Array<{
        id: number;
        eventType: string;
        eventLabel: string;
        status: string;
        statusLabel: string;
        message: string | null;
        friendlyMessage: string;
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
        dispatchTrigger: string | null;
        scheduledAt: string | null;
        scheduledForAt: string | null;
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
        canRequestPdfExport: boolean;
        pdfExportDisabledReason: string | null;
        latestPdfExport: {
            id: number;
            status: string;
            statusLabel: string;
            totalRecipients: number;
            exportedRecipients: number;
            requestedAt: string | null;
            startedAt: string | null;
            completedAt: string | null;
            failedAt: string | null;
            errorMessage: string | null;
            fileName: string | null;
        } | null;
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
    'sourceSheetsLabel',
    'deliveryStatusLabel',
    'latestErrorMessage',
]);
const selectedRecipientDeliveryStatus = ref<string | null>(null);
const recipientDeliveryStatusOptions = [
    { label: 'Tất cả trạng thái', value: null },
    { label: 'Chưa gửi', value: 'pending' },
    { label: 'Đã vào hàng đợi', value: 'queued' },
    { label: 'Đang gửi', value: 'sending' },
    { label: 'Đã gửi', value: 'sent' },
    { label: 'Lỗi gửi', value: 'failed' },
];
const filteredRecipientList = computed(() =>
    selectedRecipientDeliveryStatus.value
        ? props.recipientList.filter((recipient) => recipient.deliveryStatus === selectedRecipientDeliveryStatus.value)
        : props.recipientList,
);
const failedRecipientCount = computed(() =>
    props.recipientList.filter((r) => r.deliveryStatus === 'failed').length,
);
const isFilteringFailed = computed(() => selectedRecipientDeliveryStatus.value === 'failed');
const exportFailedUrl = computed(() =>
    props.selectedCampaignId
        ? route('mail.campaigns.recipients.export-failed', { mailCampaign: props.selectedCampaignId })
        : null,
);
const exportAggregatedUrl = computed(() =>
    props.selectedCampaignId
        ? route('mail.campaigns.recipients.export-aggregated', { mailCampaign: props.selectedCampaignId })
        : null,
);
const requestPdfExport = (): void => {
    if (!props.selectedCampaignId || !props.canManageCampaigns) {
        return;
    }

    router.post(route('mail.campaigns.exports.pdf.store', { mailCampaign: props.selectedCampaignId }), {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const toggleFailedFilter = (): void => {
    selectedRecipientDeliveryStatus.value = isFilteringFailed.value ? null : 'failed';
};

const selectedCampaignOption = computed(() =>
    props.campaignOptions.find((campaign) => campaign.campaignId === props.selectedCampaignId)?.campaignId ?? null,
);
const isPreviewDialogVisible = computed(() => props.selectedRecipientPreview !== null);
const isCreateDialogVisible = ref(false);
const canDispatchSelectedCampaign = computed(() =>
    props.canManageCampaigns
    && !!props.selectedCampaign
    && ['draft', 'scheduled'].includes(props.selectedCampaign.status),
);
const showScheduleControls = computed(() =>
    props.canManageCampaigns
    && !!props.selectedCampaign
    && ['draft', 'scheduled'].includes(props.selectedCampaign.status),
);
const showScheduledDispatchInfo = computed(() =>
    !!props.selectedCampaign
    && (
        props.selectedCampaign.dispatchTrigger === 'scheduled'
        || !!props.selectedCampaign.scheduledForAt
        || !!props.selectedCampaign.scheduledAt
    )
    && ['dispatching', 'completed', 'completed_with_failures'].includes(props.selectedCampaign.status),
);
const scheduledDispatchDisplayAt = computed(() =>
    props.selectedCampaign?.scheduledForAt
    ?? props.selectedCampaign?.scheduledAt
    ?? null,
);
const showLegacyMissingScheduleInfo = computed(() =>
    !!props.selectedCampaign
    && ['dispatching', 'completed', 'completed_with_failures'].includes(props.selectedCampaign.status)
    && props.selectedCampaign.dispatchTrigger === null
    && !props.selectedCampaign.scheduledForAt
    && !props.selectedCampaign.scheduledAt,
);
const shouldAutoRefreshCampaign = computed(() =>
    !!props.selectedCampaign
    && ['scheduled', 'dispatching'].includes(props.selectedCampaign.status),
);
const resolveSourceSheetSeverity = (sheet: string): 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast' => {
    if (sheet === 'Tổng hợp') {
        return 'info';
    }

    if (sheet === 'Khoán NPP') {
        return 'contrast';
    }

    if (sheet === 'Cám cá') {
        return 'success';
    }

    if (sheet === 'Key Account') {
        return 'warn';
    }

    return 'secondary';
};
const selectedRecipientErrorRow = ref<RecipientRow | null>(null);
const isErrorDialogVisible = computed(() => selectedRecipientErrorRow.value !== null);

const submitCreate = (): void => {
    createForm.post(route('mail.campaigns.store'), {
        preserveScroll: true,
        onSuccess: () => {
            isCreateDialogVisible.value = false;
            createForm.reset('name', 'notes');
            createForm.clearErrors();
        },
    });
};

const openCreateCampaignDialog = (): void => {
    if (!props.canManageCampaigns) {
        return;
    }

    isCreateDialogVisible.value = true;
};

const closeCreateCampaignDialog = (): void => {
    isCreateDialogVisible.value = false;
    createForm.clearErrors();
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

type PreviewErrorEntry = { section: string; error: string };

const collectPreviewErrors = (preview: typeof props.selectedRecipientPreview): PreviewErrorEntry[] => {
    if (!preview) return [];
    const result: PreviewErrorEntry[] = [];
    for (const err of preview.errors) result.push({ section: 'Chung', error: err });
    for (const err of preview.subject?.errors ?? []) result.push({ section: 'Tiêu đề', error: err });
    for (const err of preview.greeting?.errors ?? []) result.push({ section: 'Lời chào', error: err });
    for (const table of preview.tables) {
        for (const err of table.errors) result.push({ section: table.label, error: err });
    }
    return result;
};

const previewErrorsCache = ref<Map<number, PreviewErrorEntry[]>>(new Map());

const currentPreviewErrors = computed(() => collectPreviewErrors(props.selectedRecipientPreview));

watch(
    () => props.selectedRecipientPreview,
    (preview) => {
        if (preview) {
            const errors = collectPreviewErrors(preview);
            previewErrorsCache.value = new Map(previewErrorsCache.value).set(preview.recipient.id, errors);
        }
    },
    { immediate: true },
);

const expandedTechnicalIds = ref<number[]>([]);
const isTechnicalExpanded = (id: number): boolean => expandedTechnicalIds.value.includes(id);
const toggleTechnicalDetail = (id: number): void => {
    const idx = expandedTechnicalIds.value.indexOf(id);
    if (idx >= 0) {
        expandedTechnicalIds.value.splice(idx, 1);
    } else {
        expandedTechnicalIds.value.push(id);
    }
};

const openRecipientErrorDialog = (recipient: RecipientRow): void => {
    selectedRecipientErrorRow.value = recipient;
    expandedTechnicalIds.value = [];
};

const closeRecipientErrorDialog = (): void => {
    selectedRecipientErrorRow.value = null;
    expandedTechnicalIds.value = [];
};

type SampleSendResult = {
    label: string;
    customerCode: string;
    customerFullName: string;
    status: 'sent' | 'error' | 'skipped';
    message: string;
};

const isSampleDialogVisible = ref(false);
const sampleEmail = ref('');
const sampleEmailError = ref('');
const isSampleSending = ref(false);
const sampleResults = ref<SampleSendResult[]>([]);

const openSampleDialog = (): void => {
    sampleEmail.value = '';
    sampleEmailError.value = '';
    sampleResults.value = [];
    isSampleDialogVisible.value = true;
};

const closeSampleDialog = (): void => {
    isSampleDialogVisible.value = false;
};

const submitSampleSend = async (): Promise<void> => {
    if (!props.selectedCampaignId) return;

    sampleEmailError.value = '';
    if (!sampleEmail.value.trim()) {
        sampleEmailError.value = 'Vui lòng nhập địa chỉ email nhận.';
        return;
    }

    isSampleSending.value = true;
    sampleResults.value = [];

    try {
        const response = await axios.post<{ status: string; message: string; results: SampleSendResult[] }>(
            route('mail.campaigns.send-sample', { mailCampaign: props.selectedCampaignId }),
            { target_email: sampleEmail.value.trim() },
        );
        sampleResults.value = response.data.results;
    } catch (err) {
        if (axios.isAxiosError(err) && err.response?.status === 422) {
            const errors = err.response.data?.errors as Record<string, string[]> | undefined;
            sampleEmailError.value = errors?.target_email?.[0] ?? 'Email không hợp lệ.';
        } else {
            sampleResults.value = [{ label: '', customerCode: '', customerFullName: '', status: 'error', message: 'Gửi mẫu thất bại. Kiểm tra lại cấu hình mail.' }];
        }
    } finally {
        isSampleSending.value = false;
    }
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

const resendConfirmRecipient = ref<RecipientRow | null>(null);

const openResendConfirm = (recipient: RecipientRow): void => {
    resendConfirmRecipient.value = recipient;
};

const closeResendConfirm = (): void => {
    resendConfirmRecipient.value = null;
};

const confirmResend = (): void => {
    if (!resendConfirmRecipient.value || !props.selectedCampaignId || !props.canManageCampaigns) {
        return;
    }

    router.post(route('mail.campaigns.recipients.retry', {
        mailCampaign: props.selectedCampaignId,
        mailCampaignRecipient: resendConfirmRecipient.value.id,
    }), {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => closeResendConfirm(),
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
watch(() => props.selectedCampaignId, () => {
    selectedRecipientDeliveryStatus.value = null;
});
onBeforeUnmount(() => {
    if (autoRefreshTimer) {
        clearInterval(autoRefreshTimer);
    }
});
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
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
                                Tạo chiến dịch từ batch dữ liệu đã aggregate và template email đã sẵn sàng, sau đó rà soát danh sách người nhận, xem trước nội dung email đầy đủ và điều phối gửi hàng loạt.
                            </template>
                            <template v-else>
                                {{ readOnlyNotice }}
                            </template>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div class="space-y-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                    Chiến dịch đang xem
                                </p>
                                <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    Quản lý chiến dịch gửi mail
                                </h2>
                            </div>

                            <Button
                                v-if="canManageCampaigns"
                                type="button"
                                label="Tạo chiến dịch"
                                icon="pi pi-plus"
                                :disabled="batchOptions.length === 0 || templateOptions.length === 0"
                                @click="openCreateCampaignDialog"
                            />
                        </div>

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
                            Chưa có chiến dịch nào được tạo. Hãy tạo chiến dịch đầu tiên để materialize danh sách người nhận từ batch dữ liệu đã aggregate.
                        </div>

                        <template v-else>
                            <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ selectedCampaign.name }}
                                        </h2>
                                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Template: {{ selectedCampaign.template.name }}
                                        </p>
                                        <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Người tạo: {{ selectedCampaign.createdBy }}
                                        </p>
                                        <p v-if="selectedCampaign.createdAt" class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Thời gian tạo: {{ new Date(selectedCampaign.createdAt).toLocaleString('vi-VN') }}
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
                            </div>
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
                                                Tên chiến dịch: {{ selectedCampaign.name }}
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
                            <div v-if="showScheduleControls" class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
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
                                            label="Export PDF mail đã gửi"
                                            severity="contrast"
                                            outlined
                                            icon="pi pi-file-pdf"
                                            :disabled="!selectedCampaign.canRequestPdfExport"
                                            @click="requestPdfExport"
                                        />
                                        <Button
                                            type="button"
                                            label="Gửi mẫu"
                                            severity="secondary"
                                            outlined
                                            icon="pi pi-send"
                                            @click="openSampleDialog"
                                        />
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

                            <div
                                v-else-if="showScheduledDispatchInfo"
                                class="rounded-[1.4rem] border p-4"
                                :style="{ borderColor: 'rgba(20, 184, 166, 0.32)', background: 'rgba(20, 184, 166, 0.08)' }"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            Chiến dịch này đã được điều phối bằng lịch gửi
                                        </p>
                                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Thời điểm lịch đã cấu hình:
                                            {{ scheduledDispatchDisplayAt ? new Date(scheduledDispatchDisplayAt).toLocaleString('vi-VN') : 'Không còn dữ liệu lịch gửi' }}.
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        label="Export PDF mail đã gửi"
                                        severity="contrast"
                                        outlined
                                        size="small"
                                        icon="pi pi-file-pdf"
                                        :disabled="!selectedCampaign.canRequestPdfExport"
                                        @click="requestPdfExport"
                                    />
                                    <Button
                                        type="button"
                                        label="Gửi mẫu"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        icon="pi pi-send"
                                        @click="openSampleDialog"
                                    />
                                </div>
                            </div>

                            <div
                                v-else-if="showLegacyMissingScheduleInfo"
                                class="rounded-[1.4rem] border p-4"
                                :style="{ borderColor: 'rgba(245, 158, 11, 0.32)', background: 'rgba(245, 158, 11, 0.08)' }"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            Không còn dữ liệu lịch gửi của chiến dịch này
                                        </p>
                                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Các campaign cũ được gửi trước khi hệ thống lưu vĩnh viễn metadata lịch gửi có thể đã mất thông tin này sau lúc mở dispatch.
                                        </p>
                                    </div>
                                    <Button
                                        type="button"
                                        label="Export PDF mail đã gửi"
                                        severity="contrast"
                                        outlined
                                        size="small"
                                        icon="pi pi-file-pdf"
                                        :disabled="!selectedCampaign.canRequestPdfExport"
                                        @click="requestPdfExport"
                                    />
                                    <Button
                                        type="button"
                                        label="Gửi mẫu"
                                        severity="secondary"
                                        outlined
                                        size="small"
                                        icon="pi pi-send"
                                        @click="openSampleDialog"
                                    />
                                </div>
                            </div>

                            <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            Export PDF mail đã gửi gần nhất
                                        </h3>
                                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Có thể tạo yêu cầu export PDF cho toàn bộ người nhận của campaign đã chọn. Slice hiện tại mới ghi nhận yêu cầu và trạng thái chờ xử lý.
                                        </p>
                                        <p
                                            v-if="!selectedCampaign.canRequestPdfExport && selectedCampaign.pdfExportDisabledReason"
                                            class="mt-2 text-sm leading-6 text-amber-600"
                                        >
                                            {{ selectedCampaign.pdfExportDisabledReason }}
                                        </p>
                                    </div>

                                    <Button
                                        v-if="!showScheduleControls && !showScheduledDispatchInfo && !showLegacyMissingScheduleInfo"
                                        type="button"
                                        label="Export PDF mail đã gửi"
                                        severity="contrast"
                                        outlined
                                        icon="pi pi-file-pdf"
                                        :disabled="!selectedCampaign.canRequestPdfExport"
                                        @click="requestPdfExport"
                                    />
                                </div>

                                <div
                                    v-if="selectedCampaign.latestPdfExport"
                                    class="mt-4 rounded-[1rem] border p-4"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-app-bg)' }"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                Trạng thái: {{ selectedCampaign.latestPdfExport.statusLabel }}
                                            </p>
                                            <p
                                                v-if="selectedCampaign.latestPdfExport.requestedAt"
                                                class="mt-1 text-sm leading-6"
                                                :style="{ color: 'var(--dashboard-muted-text)' }"
                                            >
                                                Yêu cầu lúc: {{ new Date(selectedCampaign.latestPdfExport.requestedAt).toLocaleString('vi-VN') }}
                                            </p>
                                        </div>

                                        <Tag :value="selectedCampaign.latestPdfExport.status" severity="info" rounded />
                                    </div>

                                    <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                        <div class="rounded-[0.9rem] border px-4 py-3" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Tổng người nhận</p>
                                            <p class="mt-1 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ selectedCampaign.latestPdfExport.totalRecipients }}
                                            </p>
                                        </div>
                                        <div class="rounded-[0.9rem] border px-4 py-3" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Đã export</p>
                                            <p class="mt-1 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ selectedCampaign.latestPdfExport.exportedRecipients }}
                                            </p>
                                        </div>
                                        <div
                                            v-if="selectedCampaign.latestPdfExport.errorMessage"
                                            class="rounded-[0.9rem] border px-4 py-3"
                                            :style="{ borderColor: 'rgba(239, 68, 68, 0.24)', background: 'rgba(239, 68, 68, 0.06)' }"
                                        >
                                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Lỗi gần nhất</p>
                                            <p class="mt-1 text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ selectedCampaign.latestPdfExport.errorMessage }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="mt-4 rounded-[1rem] border px-4 py-3 text-sm leading-6"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-app-bg)', color: 'var(--dashboard-muted-text)' }"
                                >
                                    Campaign này chưa có yêu cầu export PDF nào được tạo.
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                Danh sách người nhận đã aggregate
                            </p>
                            <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                Người nhận của chiến dịch đã chọn
                            </h2>
                        </div>

                        <div v-if="!selectedCampaign" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                            Chọn một chiến dịch ở card phía trên để xem danh sách người nhận đã aggregate.
                        </div>

                        <div v-else class="space-y-3">
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <DataTableGlobalFilterToolbar
                                    v-model="recipientGlobalFilterValue"
                                    placeholder="Tìm theo mã số, khách hàng, email, nguồn dữ liệu, trạng thái"
                                    @clear="clearRecipientGlobalFilter"
                                />

                                <div class="flex w-full flex-col gap-3 lg:w-auto lg:flex-row lg:items-end">
                                    <div class="w-full lg:max-w-xs">
                                        <label class="mb-2 block text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Trạng thái gửi
                                        </label>
                                        <Select
                                            v-model="selectedRecipientDeliveryStatus"
                                            :options="recipientDeliveryStatusOptions"
                                            option-label="label"
                                            option-value="value"
                                            fluid
                                            placeholder="Lọc theo trạng thái gửi"
                                            show-clear
                                        />
                                    </div>

                                    <div class="flex shrink-0 gap-2">
                                        <Button
                                            type="button"
                                            :label="isFilteringFailed ? 'Xem tất cả' : `Chỉ xem lỗi${failedRecipientCount > 0 ? ` (${failedRecipientCount})` : ''}`"
                                            :severity="isFilteringFailed ? 'secondary' : 'danger'"
                                            :outlined="!isFilteringFailed"
                                            :disabled="failedRecipientCount === 0"
                                            @click="toggleFailedFilter"
                                        />
                                        <a
                                            v-if="exportFailedUrl && failedRecipientCount > 0"
                                            :href="exportFailedUrl"
                                            download
                                        >
                                            <Button
                                                type="button"
                                                label="Export lỗi"
                                                severity="danger"
                                                icon="pi pi-download"
                                            />
                                        </a>
                                        <a
                                            v-if="exportAggregatedUrl"
                                            :href="exportAggregatedUrl"
                                            download
                                        >
                                            <Button
                                                type="button"
                                                label="Export dữ liệu aggregate"
                                                severity="secondary"
                                                icon="pi pi-download"
                                            />
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <DataTable
                                v-model:filters="recipientFilters"
                                :value="filteredRecipientList"
                                :global-filter-fields="recipientGlobalFilterFields"
                                paginator
                                :rows="10"
                                paginator-template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                                current-page-report-template="Hiển thị {first} đến {last} trong tổng số {totalRecords}"
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
                                <Column header="Nguồn dữ liệu">
                                    <template #body="{ data }">
                                        <div class="flex flex-wrap gap-2">
                                            <Tag
                                                v-for="sheet in data.sourceSheets"
                                                :key="`${data.customerCode}-${sheet}`"
                                                :value="sheet"
                                                :severity="resolveSourceSheetSeverity(sheet)"
                                                rounded
                                            />
                                            <Tag
                                                v-if="data.sourceSheets.length === 0"
                                                value="Không xác định"
                                                severity="secondary"
                                                rounded
                                            />
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Trạng thái gửi">
                                    <template #body="{ data }">
                                        <Tag :value="data.deliveryStatusLabel" :severity="data.deliveryStatus === 'failed' ? 'danger' : data.deliveryStatus === 'sent' ? 'success' : 'warn'" rounded />
                                    </template>
                                </Column>
                                <Column header="Lỗi gần nhất">
                                    <template #body="{ data }">
                                        <div class="space-y-1">
                                            <span :style="{ color: 'var(--dashboard-muted-text)' }">
                                                {{ data.latestFriendlyMessage || 'Không có' }}
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
                                                :severity="previewErrorsCache.get(data.id)?.length ? 'danger' : undefined"
                                                :icon="previewErrorsCache.get(data.id)?.length ? 'pi pi-exclamation-circle' : undefined"
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
                                                label="Thử lại"
                                                size="small"
                                                severity="warn"
                                                @click="retryRecipient(data)"
                                            />
                                            <Button
                                                v-if="canManageCampaigns && data.canResend"
                                                type="button"
                                                label="Gửi lại"
                                                size="small"
                                                severity="secondary"
                                                outlined
                                                @click="openResendConfirm(data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <Dialog
            :visible="resendConfirmRecipient !== null"
            modal
            :style="{ width: 'min(480px, 96vw)' }"
            header="Xác nhận gửi lại"
            @update:visible="(visible) => { if (!visible) closeResendConfirm() }"
        >
            <p class="mb-4">
                Khách hàng <strong>{{ resendConfirmRecipient?.customerFullName }}</strong>
                (<code>{{ resendConfirmRecipient?.recipientEmail }}</code>)
                đã nhận được mail này rồi.
            </p>
            <p class="mb-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                Bạn có chắc muốn gửi lại? Khách hàng sẽ nhận được email trùng.
            </p>
            <div class="flex justify-end gap-2">
                <Button
                    label="Hủy"
                    severity="secondary"
                    outlined
                    @click="closeResendConfirm"
                />
                <Button
                    label="Gửi lại"
                    severity="secondary"
                    @click="confirmResend"
                />
            </div>
        </Dialog>

        <Dialog
            :visible="isCreateDialogVisible"
            modal
            :style="{ width: 'min(720px, 96vw)' }"
            header="Tạo chiến dịch gửi mail"
            @update:visible="(visible) => { if (!visible) closeCreateCampaignDialog() }"
        >
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
                        option-disabled="hasErrors"
                        filter
                        fluid
                        :disabled="!canManageCampaigns"
                        placeholder="Chọn batch đã aggregate"
                    >
                        <template #option="{ option }: { option: BatchOption }">
                            <div class="flex w-full items-center justify-between gap-3">
                                <span>{{ option.label }}</span>
                                <span
                                    v-if="option.hasErrors"
                                    class="shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold"
                                    style="background: rgba(239,68,68,0.12); color: var(--p-red-500);"
                                >
                                    {{ option.errorCount }} lỗi
                                </span>
                            </div>
                        </template>
                    </Select>
                    <small v-if="createForm.errors.import_batch_id" class="text-red-500">{{ createForm.errors.import_batch_id }}</small>
                    <small v-if="batchOptions.some((b) => b.hasErrors)" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Một số batch bị vô hiệu hóa do có lỗi dữ liệu ở cột Email, Tổng cộng hoặc Bằng chữ.
                    </small>
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

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Đóng" severity="secondary" text @click="closeCreateCampaignDialog" />
                    <Button
                        type="submit"
                        label="Tạo chiến dịch"
                        :loading="createForm.processing"
                        :disabled="!canManageCampaigns || batchOptions.length === 0 || templateOptions.length === 0"
                    />
                </div>
            </form>
        </Dialog>

        <Dialog
            :visible="isPreviewDialogVisible"
            modal
            maximizable
            :style="{ width: 'min(1120px, 96vw)' }"
            header="Xem trước toàn bộ email"
            @update:visible="(visible) => { if (!visible) closeRecipientPreview() }"
        >
            <div v-if="currentPreviewErrors.length > 0" class="mb-4 rounded-[1.2rem] border p-4" :style="{ borderColor: 'rgba(239,68,68,0.28)', background: 'rgba(239,68,68,0.08)' }">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-500">
                    Lỗi preview — {{ currentPreviewErrors.length }} vấn đề cần xử lý
                </p>
                <ul class="mt-2 space-y-1.5">
                    <li
                        v-for="(entry, i) in currentPreviewErrors"
                        :key="i"
                        class="text-sm leading-6"
                    >
                        <span class="font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">{{ entry.section }}:</span>
                        <span :style="{ color: 'var(--dashboard-strong-text)' }"> {{ entry.error }}</span>
                    </li>
                </ul>
            </div>
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
                            {{ attempt.friendlyMessage || 'Không có thông tin chi tiết.' }}
                        </p>

                        <div v-if="attempt.message || Object.keys(attempt.context).length > 0" class="mt-3">
                            <button
                                type="button"
                                class="flex items-center gap-1 text-xs"
                                :style="{ color: 'var(--dashboard-muted-text)' }"
                                @click="toggleTechnicalDetail(attempt.id)"
                            >
                                <span>{{ isTechnicalExpanded(attempt.id) ? 'Ẩn chi tiết kỹ thuật' : 'Xem chi tiết kỹ thuật' }}</span>
                                <i :class="isTechnicalExpanded(attempt.id) ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" class="text-[10px]" />
                            </button>

                            <div v-if="isTechnicalExpanded(attempt.id)" class="mt-2 rounded-2xl border px-4 py-3" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                                <p v-if="attempt.message" class="break-all font-mono text-xs leading-5" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    {{ attempt.message }}
                                </p>
                                <div v-if="Object.keys(attempt.context).length > 0" :class="attempt.message ? 'mt-2 pt-2 border-t' : ''" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                                    <p
                                        v-for="[key, value] in Object.entries(attempt.context)"
                                        :key="`${attempt.id}-${key}`"
                                        class="break-all font-mono text-xs leading-5"
                                        :style="{ color: 'var(--dashboard-muted-text)' }"
                                    >
                                        {{ key }}: {{ String(value) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Dialog>

        <Dialog
            v-model:visible="isSampleDialogVisible"
            modal
            :style="{ width: 'min(520px, 96vw)' }"
            header="Gửi mail mẫu"
            @hide="closeSampleDialog"
        >
            <div class="space-y-4">
                <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Hệ thống sẽ chọn tối đa 3 khách hàng đại diện (Tổng hợp/Khoán NPP, Cám cá, Key Account) và gửi mail mẫu đến email bạn nhập bên dưới.
                </p>

                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Email nhận</label>
                    <InputText
                        v-model="sampleEmail"
                        type="email"
                        placeholder="example@email.com"
                        fluid
                        :disabled="isSampleSending"
                        @keyup.enter="submitSampleSend"
                    />
                    <small v-if="sampleEmailError" class="text-red-500">{{ sampleEmailError }}</small>
                </div>

                <div v-if="sampleResults.length > 0" class="space-y-2">
                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Kết quả gửi</p>
                    <div
                        v-for="(result, idx) in sampleResults"
                        :key="idx"
                        class="flex items-start gap-3 rounded-2xl border px-4 py-3"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <Tag
                            :value="result.status === 'sent' ? 'Đã gửi' : result.status === 'skipped' ? 'Bỏ qua' : 'Lỗi'"
                            :severity="result.status === 'sent' ? 'success' : result.status === 'skipped' ? 'warn' : 'danger'"
                            rounded
                            class="shrink-0"
                        />
                        <div class="min-w-0 flex-1">
                            <p v-if="result.label" class="text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ result.label }}
                                <span v-if="result.customerCode" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    — {{ result.customerCode }} {{ result.customerFullName }}
                                </span>
                            </p>
                            <p class="mt-0.5 text-xs leading-5" :style="{ color: 'var(--dashboard-muted-text)' }">{{ result.message }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <Button type="button" label="Đóng" severity="secondary" text @click="closeSampleDialog" />
                    <Button
                        type="button"
                        label="Gửi mẫu"
                        icon="pi pi-send"
                        :loading="isSampleSending"
                        :disabled="isSampleSending"
                        @click="submitSampleSend"
                    />
                </div>
            </div>
        </Dialog>
    </AppLayout>
</template>
