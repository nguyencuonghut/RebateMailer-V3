<script setup lang="ts">
import type { PageProps } from '@/types';
import type { ImportPageProps } from '@/Services/imports/useImportsIndexPage';
import ImportBatchHistoryCard from '@/Components/imports/ImportBatchHistoryCard.vue';
import ImportResultTabs from '@/Components/imports/ImportResultTabs.vue';
import ImportWorkbookBoundarySummary from '@/Components/imports/ImportWorkbookBoundarySummary.vue';
import { useAggregatePreviewFlow } from '@/Services/imports/useAggregatePreviewFlow';
import { useCamCaPreviewFlow } from '@/Services/imports/useCamCaPreviewFlow';
import { useImportUploadCard } from '@/Services/imports/useImportUploadCard';
import { useImportUploadFlow } from '@/Services/imports/useImportUploadFlow';
import { useImportBatchStatusMonitor } from '@/Services/imports/useImportBatchStatusMonitor';
import { useImportProcessBatchFlow } from '@/Services/imports/useImportProcessBatchFlow';
import { useImportWorkbookBoundaryFlow } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import { useImportsIndexPage } from '@/Services/imports/useImportsIndexPage';
import { useKeyAccountPreviewFlow } from '@/Services/imports/useKeyAccountPreviewFlow';
import { useKhoanNppPreviewFlow } from '@/Services/imports/useKhoanNppPreviewFlow';
import { useTongHopPreviewFlow } from '@/Services/imports/useTongHopPreviewFlow';
import { Head, router, usePage } from '@inertiajs/vue3';
import ImportUploadCard from '@/Components/imports/ImportUploadCard.vue';
import ImportPreviewShell from '@/Components/imports/ImportPreviewShell.vue';
import AppLayout from '@/layout/AppLayout.vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';
import { computed, watch } from 'vue';

const props = defineProps<ImportPageProps>();

const page = usePage<PageProps>();
const canOpenTemplates = computed(() => {
    const permissions = page.props.auth.user?.permissions ?? [];

    return permissions.includes('templates.view') || permissions.includes('templates.manage');
});
const { acceptedSheetTags, disabledActionMessage } = useImportsIndexPage(props);
const { inputId, selectedFile, inlineError, formattedFileSize, openFileDialog, clearSelection, onFileChange } = useImportUploadCard(
    props.uploadPolicy.acceptedExtension,
);
const { isUploading, uploadReceipt, uploadSelectedFile } = useImportUploadFlow(props.initialUploadReceipt);
const { workbookBoundary, resetWorkbookBoundary } = useImportWorkbookBoundaryFlow(props.initialWorkbookBoundary);
const { isLoadingTongHopPreview, tongHopPreview, tongHopErrorMessage, loadTongHopPreview } = useTongHopPreviewFlow(props.initialTongHopPreview);
const { isLoadingKhoanNppPreview, khoanNppPreview, khoanNppErrorMessage, loadKhoanNppPreview } = useKhoanNppPreviewFlow(props.initialKhoanNppPreview);
const { isLoadingCamCaPreview, camCaPreview, camCaErrorMessage, canPreviewCamCa, loadCamCaPreview } = useCamCaPreviewFlow(workbookBoundary, props.initialCamCaPreview);
const { isLoadingKeyAccountPreview, keyAccountPreview, keyAccountErrorMessage, canPreviewKeyAccount, loadKeyAccountPreview } = useKeyAccountPreviewFlow(workbookBoundary, props.initialKeyAccountPreview);
const { isLoadingAggregatePreview, aggregatePreview, aggregateErrorMessage, canPreviewAggregate, loadAggregatePreview } = useAggregatePreviewFlow(workbookBoundary, props.initialAggregatePreview);
const {
    isProcessing,
    processingError,
    processBatch,
    resetProcessBatch,
} = useImportProcessBatchFlow();
const {
    isBatchProcessing,
    processingError: batchProcessingError,
    startMonitoring,
} = useImportBatchStatusMonitor(props.initialBatchProcessingError);

const canShowReceiptShell = computed(() => props.canManageImports || uploadReceipt.value !== null);
const canShowResultTabs = computed(
    () =>
        aggregatePreview.value !== null
        || tongHopPreview.value !== null
        || khoanNppPreview.value !== null
        || camCaPreview.value !== null
        || keyAccountPreview.value !== null
        || isProcessing.value
        || isBatchProcessing.value
        || processingError.value !== ''
        || batchProcessingError.value !== '',
);

watch(
    () => uploadReceipt.value?.importBatch.status,
    (status) => {
        if (!props.canManageImports || !uploadReceipt.value) {
            return;
        }

        if (!['queued', 'processing'].includes(status ?? '')) {
            return;
        }

        startMonitoring(
            uploadReceipt.value.importBatch.id,
            route('imports.batch-status', uploadReceipt.value.importBatch.id),
        );
    },
    { immediate: true },
);

const submitUpload = async (): Promise<void> => {
    resetWorkbookBoundary();
    resetProcessBatch();

    const errorMessage = await uploadSelectedFile(selectedFile.value, route('imports.upload'));

    if (errorMessage) {
        inlineError.value = errorMessage;
        return;
    }

    inlineError.value = '';

    // Auto-trigger pipeline ngay sau upload thành công
    const processError = await processBatch(uploadReceipt.value, route('imports.process-batch'));

    if (processError) {
        inlineError.value = processError;
        return;
    }

    router.get(
        route('imports.index'),
        {
            batch: uploadReceipt.value?.importBatch.id,
        },
        {
            preserveScroll: true,
            preserveState: false,
        },
    );
};

const loadTongHopTab = async (): Promise<void> => {
    if (!uploadReceipt.value) {
        return;
    }

    const errorMessage = await loadTongHopPreview(uploadReceipt.value, route('imports.preview-tong-hop'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};

const loadKhoanNppTab = async (): Promise<void> => {
    if (!uploadReceipt.value) {
        return;
    }

    const errorMessage = await loadKhoanNppPreview(uploadReceipt.value, route('imports.preview-khoan-npp'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};

const loadCamCaTab = async (): Promise<void> => {
    if (!uploadReceipt.value) {
        return;
    }

    const errorMessage = await loadCamCaPreview(uploadReceipt.value, route('imports.preview-cam-ca'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};

const loadKeyAccountTab = async (): Promise<void> => {
    if (!uploadReceipt.value) {
        return;
    }

    const errorMessage = await loadKeyAccountPreview(uploadReceipt.value, route('imports.preview-key-account'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};

const loadAggregateTab = async (): Promise<void> => {
    if (!uploadReceipt.value) {
        return;
    }

    const errorMessage = await loadAggregatePreview(uploadReceipt.value, route('imports.preview-aggregated'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};

</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <Toast
            position="top-right"
            :breakpoints="{
                '960px': {
                    width: 'calc(100vw - 2rem)',
                    right: '1rem',
                    left: '1rem',
                },
            }"
            :pt="{
                root: { class: '!top-24 md:!top-28 !right-4 md:!right-6' },
                message: { class: 'import-page-toast max-w-[32rem] !rounded-[1.4rem] !border !shadow-[0_24px_60px_rgba(15,23,42,0.55)]' },
                messageContent: { class: '!items-start !gap-3 !px-5 !py-4' },
                messageText: { class: '!gap-2' },
                summary: { class: '!text-base !font-semibold !leading-6' },
                detail: { class: '!mt-1 !text-sm !font-medium !leading-7 break-words' },
                closeButton: { class: 'hover:!bg-white/10' },
                closeIcon: { class: '!text-base' },
            }"
        />

        <div class="space-y-6">
            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-2">
                            <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ title }}
                            </h1>
                            <p class="text-sm leading-6 sm:text-base" :style="{ color: 'var(--dashboard-muted-text)' }">
                                {{ description }}
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="sheet in acceptedSheetTags"
                                :key="sheet.label"
                                :value="sheet.label"
                                :severity="sheet.severity"
                                rounded
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Tải file dữ liệu
                </template>
                <template #content>
                    <ImportUploadCard
                        :can-manage-imports="canManageImports"
                        :accepted-extension="uploadPolicy.acceptedExtension"
                        :accepted-mime-label="uploadPolicy.acceptedMimeLabel"
                        :input-id="inputId"
                        :selected-file="selectedFile"
                        :formatted-file-size="formattedFileSize"
                        :inline-error="inlineError"
                        :disabled-action-message="disabledActionMessage"
                        :is-uploading="isUploading || isProcessing"
                        @open="openFileDialog"
                        @clear="clearSelection"
                        @select="onFileChange"
                        @upload="submitUpload"
                    />
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Lịch sử nhập dữ liệu
                </template>
                <template #content>
                    <ImportBatchHistoryCard
                        :history="importHistory"
                        :active-batch-id="activeBatchId"
                        :can-open-templates="canOpenTemplates"
                    />
                </template>
            </Card>

            <Card v-if="canShowReceiptShell" class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Thông tin file đã nhận
                </template>
                <template #content>
                    <ImportPreviewShell
                        :receipt="uploadReceipt"
                        :can-manage-imports="canManageImports"
                        :can-open-templates="canOpenTemplates"
                        :analysis-prep="analysisPrep"
                        :workbook-boundary="workbookBoundary"
                        :is-analyzing-workbook="false"
                        analysis-error-message=""
                        analysis-status-text=""
                    />
                </template>
            </Card>

            <Card v-if="workbookBoundary" class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Cấu trúc tệp Excel
                </template>
                <template #content>
                    <ImportWorkbookBoundarySummary
                        :workbook-boundary="workbookBoundary"
                    />
                </template>
            </Card>

            <!-- Kết quả import: TabView full-width -->
            <Card v-if="canShowResultTabs || props.canManageImports" class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Kết quả import
                </template>
                <template #content>
                    <ImportResultTabs
                        :aggregate-preview="aggregatePreview"
                        :tong-hop-preview="tongHopPreview"
                        :khoan-npp-preview="khoanNppPreview"
                        :cam-ca-preview="camCaPreview"
                        :key-account-preview="keyAccountPreview"
                        :is-loading-aggregate-preview="isLoadingAggregatePreview"
                        :is-loading-tong-hop-preview="isLoadingTongHopPreview"
                        :is-loading-khoan-npp-preview="isLoadingKhoanNppPreview"
                        :is-loading-cam-ca-preview="isLoadingCamCaPreview"
                        :is-loading-key-account-preview="isLoadingKeyAccountPreview"
                        :aggregate-error-message="aggregateErrorMessage"
                        :tong-hop-error-message="tongHopErrorMessage"
                        :khoan-npp-error-message="khoanNppErrorMessage"
                        :cam-ca-error-message="camCaErrorMessage"
                        :key-account-error-message="keyAccountErrorMessage"
                        :can-preview-aggregate="canManageImports && canPreviewAggregate"
                        :can-preview-tong-hop="canManageImports && !!uploadReceipt && !!workbookBoundary?.sheets.some((sheet) => sheet.name === 'Tổng hợp' && sheet.present)"
                        :can-preview-khoan-npp="canManageImports && !!uploadReceipt && !!workbookBoundary?.sheets.some((sheet) => sheet.name === 'Khoán NPP' && sheet.present)"
                        :can-preview-cam-ca="canManageImports && canPreviewCamCa"
                        :can-preview-key-account="canManageImports && canPreviewKeyAccount"
                        :is-processing="isProcessing || isBatchProcessing"
                        :processing-error="processingError || batchProcessingError"
                        @load-aggregate="loadAggregateTab"
                        @load-tong-hop="loadTongHopTab"
                        @load-khoan-npp="loadKhoanNppTab"
                        @load-cam-ca="loadCamCaTab"
                        @load-key-account="loadKeyAccountTab"
                    />
                </template>
            </Card>
        </div>
    </AppLayout>
</template>

<style scoped>
:deep(.import-page-toast.p-toast-message-success) {
    border-color: rgb(16 185 129) !important;
    background: linear-gradient(180deg, rgb(220 252 231), rgb(209 250 229)) !important;
    color: rgb(6 78 59) !important;
}

:deep(.import-page-toast.p-toast-message-error) {
    border-color: rgb(248 113 113) !important;
    background: linear-gradient(180deg, rgb(127 29 29), rgb(69 10 10)) !important;
    color: rgb(255 255 255) !important;
}

:deep(.import-page-toast.p-toast-message-warn) {
    border-color: rgb(251 191 36) !important;
    background: linear-gradient(180deg, rgb(120 53 15), rgb(113 63 18)) !important;
    color: rgb(255 255 255) !important;
}

:deep(.import-page-toast.p-toast-message-success .p-toast-summary),
:deep(.import-page-toast.p-toast-message-success .p-toast-detail),
:deep(.import-page-toast.p-toast-message-success .p-toast-close-button),
:deep(.import-page-toast.p-toast-message-success .p-toast-message-icon) {
    color: rgb(6 78 59) !important;
}

:deep(.import-page-toast.p-toast-message-error .p-toast-summary),
:deep(.import-page-toast.p-toast-message-error .p-toast-detail),
:deep(.import-page-toast.p-toast-message-error .p-toast-close-button),
:deep(.import-page-toast.p-toast-message-error .p-toast-message-icon),
:deep(.import-page-toast.p-toast-message-warn .p-toast-summary),
:deep(.import-page-toast.p-toast-message-warn .p-toast-detail),
:deep(.import-page-toast.p-toast-message-warn .p-toast-close-button),
:deep(.import-page-toast.p-toast-message-warn .p-toast-message-icon) {
    color: rgb(255 255 255) !important;
}
</style>
