<script setup lang="ts">
import type { ImportWorkbookBoundary, ImportWorkbookBoundaryActionConfig } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import { useImportWorkbookBoundaryPreview } from '@/Services/imports/useImportWorkbookBoundaryPreview';
import type { ImportUploadReceipt } from '@/Services/imports/useImportUploadFlow';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import { toRef } from 'vue';

const props = defineProps<{
    receipt: ImportUploadReceipt | null;
    canManageImports: boolean;
    analysisPrep: ImportWorkbookBoundaryActionConfig;
    workbookBoundary: ImportWorkbookBoundary | null;
    isAnalyzingWorkbook: boolean;
    analysisErrorMessage: string;
    analysisStatusText: string;
}>();

const emit = defineEmits<{
    prepareAnalysis: [];
}>();

const { summaryItems, contractMessages, sheetDetails } = useImportWorkbookBoundaryPreview(
    toRef(props, 'workbookBoundary'),
);
</script>

<template>
    <div v-if="receipt" class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    File đã tiếp nhận
                </p>
                <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.originalFileName }}
                </p>
            </div>

            <Tag value="Upload thành công" severity="success" rounded />
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Dung lượng</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.size.toLocaleString('vi-VN') }} bytes
                </dd>
            </div>
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Thời điểm upload</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.uploadedAt }}
                </dd>
            </div>
        </dl>

        <div class="rounded-[1.2rem] border border-dashed p-4 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
            {{ receipt.nextStep }}
        </div>

        <div
            v-if="canManageImports"
            class="space-y-4 rounded-[1.4rem] border p-4"
            :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
        >
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Bước tiếp theo sau receipt upload
                    </p>
                    <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ analysisPrep.readyTitle }}
                    </p>
                    <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        {{ analysisPrep.helperText }}
                    </p>
                </div>

                <Tag :value="analysisStatusText" :severity="workbookBoundary ? 'success' : analysisErrorMessage ? 'danger' : 'warn'" rounded />
            </div>

            <Button
                :label="analysisErrorMessage ? 'Thử đọc workbook lại' : analysisPrep.actionLabel"
                :icon="analysisErrorMessage ? 'pi pi-refresh' : 'pi pi-arrow-right'"
                :loading="isAnalyzingWorkbook"
                :disabled="isAnalyzingWorkbook"
                @click="emit('prepareAnalysis')"
            />

            <Message v-if="analysisErrorMessage" severity="error" :closable="false">
                {{ analysisErrorMessage }}
            </Message>

            <Message v-if="workbookBoundary" severity="info" :closable="false">
                {{ analysisPrep.readyDescription }}
            </Message>

            <div
                v-if="workbookBoundary"
                class="space-y-4 rounded-[1.2rem] border border-dashed p-4"
                :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
            >
                <div class="space-y-2">
                    <p class="text-sm font-medium">Tổng quan workbook boundary</p>
                    <p class="text-sm">
                        Preview này gom toàn bộ thông tin boundary của workbook trước khi đi sang parser chi tiết từng sheet.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="item in summaryItems"
                        :key="item.label"
                        class="rounded-[1rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                    >
                        <p class="text-sm font-medium">{{ item.label }}</p>
                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ item.value }}
                        </p>
                        <p class="mt-2 text-sm">
                            {{ item.helper }}
                        </p>
                        <div class="mt-3">
                            <Tag :value="item.label" :severity="item.severity" rounded />
                        </div>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium">Danh sách sheet đã nhận diện</p>
                    <div class="flex flex-wrap gap-2">
                        <Tag
                            v-for="sheet in workbookBoundary.detectedSheets"
                            :key="sheet"
                            :value="sheet"
                            severity="info"
                            rounded
                        />
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium">Chuẩn hóa contract 4 sheet</p>
                    <Message
                        v-for="message in contractMessages"
                        :key="message.text"
                        :severity="message.severity"
                        :closable="false"
                    >
                        {{ message.text }}
                    </Message>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium">Chi tiết boundary theo từng sheet import hợp lệ</p>

                    <div class="grid gap-4">
                        <div
                            v-for="sheetDetail in sheetDetails"
                            :key="`headers-${sheetDetail.name}`"
                            class="rounded-[1rem] border p-4"
                            :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ sheetDetail.name }}
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            sheetDetail.hasHeader
                                                ? `Đã đọc ${sheetDetail.headerCount} cột ở line 1.`
                                                : 'Sheet này chưa có dữ liệu header để hiển thị ở boundary hiện tại.'
                                        }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{
                                            sheetDetail.isEmpty
                                                ? 'Sheet hiện không có dòng dữ liệu nào sau header.'
                                                : `Có ${sheetDetail.dataRowCount} dòng dữ liệu sau header.`
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Tag
                                        :value="sheetDetail.headerStatusLabel"
                                        :severity="sheetDetail.hasHeader ? 'success' : 'warn'"
                                        rounded
                                    />
                                    <Tag
                                        :value="sheetDetail.rowStatusLabel"
                                        :severity="sheetDetail.rowStatusSeverity"
                                        rounded
                                    />
                                </div>
                            </div>

                            <div
                                v-if="sheetDetail.hasHeader"
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                <Tag
                                    v-for="(header, headerIndex) in sheetDetail.headers"
                                    :key="`${sheetDetail.name}-${headerIndex}`"
                                    :value="header"
                                    severity="secondary"
                                    rounded
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-sm leading-6">
                    {{ workbookBoundary.nextStep }}
                </p>
            </div>
        </div>
    </div>

    <div v-else class="rounded-[1.2rem] border border-dashed p-4 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
        Chưa có receipt upload. Sau khi tải file thành công, khu vực này sẽ hiển thị thông tin file đã tiếp nhận.
    </div>
</template>
