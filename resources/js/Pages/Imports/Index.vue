<script setup lang="ts">
import type { PageProps } from '@/types';
import type { ImportPageProps } from '@/Services/imports/useImportsIndexPage';
import { useImportUploadCard } from '@/Services/imports/useImportUploadCard';
import { useImportWorkbookBoundaryFlow } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import { useImportUploadFlow } from '@/Services/imports/useImportUploadFlow';
import { useImportsIndexPage } from '@/Services/imports/useImportsIndexPage';
import { Head, usePage } from '@inertiajs/vue3';
import ImportUploadCard from '@/Components/imports/ImportUploadCard.vue';
import ImportPreviewShell from '@/Components/imports/ImportPreviewShell.vue';
import AppLayout from '@/layout/AppLayout.vue';
import Card from 'primevue/card';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';

const props = defineProps<ImportPageProps>();

const page = usePage<PageProps>();
const { acceptedSheetTags, uploadReadinessItems, disabledActionMessage } = useImportsIndexPage(props);
const { inputId, selectedFile, inlineError, formattedFileSize, openFileDialog, clearSelection, onFileChange } = useImportUploadCard(
    props.uploadPolicy.acceptedExtension,
);
const { isUploading, uploadReceipt, uploadSelectedFile } = useImportUploadFlow();
const { isAnalyzingWorkbook, workbookBoundary, analysisStatusText, analyzeWorkbook, resetWorkbookBoundary } = useImportWorkbookBoundaryFlow();

const submitUpload = async (): Promise<void> => {
    resetWorkbookBoundary();

    const errorMessage = await uploadSelectedFile(selectedFile.value, route('imports.upload'));

    if (errorMessage) {
        inlineError.value = errorMessage;
        return;
    }

    inlineError.value = '';
};

const prepareWorkbookBoundary = async (): Promise<void> => {
    inlineError.value = '';

    const errorMessage = await analyzeWorkbook(uploadReceipt.value, route('imports.analyze-workbook'));

    if (errorMessage) {
        inlineError.value = errorMessage;
    }
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <Toast position="top-right" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(22rem,1fr)]">
            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                                Slice {{ currentSlice.code }}
                            </p>
                            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ title }}
                            </h1>
                            <p class="max-w-3xl text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                {{ description }}
                            </p>
                        </div>

                        <Message severity="info" :closable="false">
                            {{ currentSlice.label }}. Sau khi có receipt upload, người dùng có quyền thao tác sẽ thấy rõ bước kế tiếp để chuyển sang đọc cấu trúc workbook.
                        </Message>

                        <div class="rounded-[1.6rem] border p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        Chính sách tiếp nhận file
                                    </p>
                                    <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ uploadPolicy.acceptedMimeLabel }}
                                    </p>
                                </div>

                                <Tag :value="uploadPolicy.acceptedExtension" severity="success" rounded />
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <Tag
                                    v-for="sheet in acceptedSheetTags"
                                    :key="sheet.label"
                                    :value="sheet.label"
                                    :severity="sheet.severity"
                                    rounded
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid gap-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Sẵn sàng triển khai
                    </template>
                    <template #content>
                        <ul class="space-y-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            <li v-for="item in uploadReadinessItems" :key="item" class="flex gap-3">
                                <i class="pi pi-check-circle mt-1 text-teal-600" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Khung thao tác upload
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
                            :is-uploading="isUploading"
                            @open="openFileDialog"
                            @clear="clearSelection"
                            @select="onFileChange"
                            @upload="submitUpload"
                        />
                    </template>
                </Card>

                <Card v-if="canManageImports" class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Preview receipt upload
                    </template>
                    <template #content>
                        <ImportPreviewShell
                            :receipt="uploadReceipt"
                            :can-manage-imports="canManageImports"
                            :analysis-prep="analysisPrep"
                            :workbook-boundary="workbookBoundary"
                            :is-analyzing-workbook="isAnalyzingWorkbook"
                            :analysis-status-text="analysisStatusText"
                            @prepare-analysis="prepareWorkbookBoundary"
                        />
                    </template>
                </Card>

                <Card v-else class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Quyền truy cập hiện tại
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <Message severity="warn" :closable="false">
                                Tài khoản hiện tại chỉ có quyền xem khu vực import dữ liệu.
                            </Message>

                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                Chức năng tải file lên và xem receipt upload chỉ mở cho người dùng được cấp quyền thao tác import.
                            </p>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
