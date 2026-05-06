<script setup lang="ts">
import type { ImportWorkbookBoundary, ImportWorkbookBoundaryActionConfig } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import type { ImportUploadReceipt } from '@/Services/imports/useImportUploadFlow';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    receipt: ImportUploadReceipt | null;
    canManageImports: boolean;
    analysisPrep: ImportWorkbookBoundaryActionConfig;
    workbookBoundary: ImportWorkbookBoundary | null;
    isAnalyzingWorkbook: boolean;
    analysisStatusText: string;
}>();

const emit = defineEmits<{
    prepareAnalysis: [];
}>();
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

                <Tag :value="analysisStatusText" :severity="workbookBoundary ? 'success' : 'warn'" rounded />
            </div>

            <Button
                :label="analysisPrep.actionLabel"
                icon="pi pi-arrow-right"
                :loading="isAnalyzingWorkbook"
                :disabled="isAnalyzingWorkbook"
                @click="emit('prepareAnalysis')"
            />

            <Message v-if="workbookBoundary" severity="info" :closable="false">
                {{ analysisPrep.readyDescription }}
            </Message>

            <div
                v-if="workbookBoundary"
                class="space-y-4 rounded-[1.2rem] border border-dashed p-4"
                :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-medium">Danh sách sheet đã nhận diện</p>
                        <p class="mt-1 text-sm">
                            Hệ thống đã đọc được {{ workbookBoundary.sheetCount }} sheet từ workbook đã tải lên.
                        </p>
                    </div>

                    <Tag :value="`${workbookBoundary.sheetCount} sheet`" severity="contrast" rounded />
                </div>

                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="sheet in workbookBoundary.detectedSheets"
                        :key="sheet"
                        :value="sheet"
                        severity="info"
                        rounded
                    />
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="space-y-2">
                        <p class="text-sm font-medium">4 sheet import hợp lệ</p>
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="sheet in workbookBoundary.expectedSheets"
                                :key="`expected-${sheet}`"
                                :value="sheet"
                                severity="success"
                                rounded
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium">Sheet còn thiếu</p>
                        <div v-if="workbookBoundary.missingSheets.length" class="flex flex-wrap gap-2">
                            <Tag
                                v-for="sheet in workbookBoundary.missingSheets"
                                :key="`missing-${sheet}`"
                                :value="sheet"
                                severity="danger"
                                rounded
                            />
                        </div>
                        <p v-else class="text-sm">
                            Không thiếu sheet import nào.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <p class="text-sm font-medium">Sheet ngoài contract import</p>
                        <div v-if="workbookBoundary.unexpectedSheets.length" class="flex flex-wrap gap-2">
                            <Tag
                                v-for="sheet in workbookBoundary.unexpectedSheets"
                                :key="`unexpected-${sheet}`"
                                :value="sheet"
                                severity="warn"
                                rounded
                            />
                        </div>
                        <p v-else class="text-sm">
                            Không có sheet ngoài contract import.
                        </p>
                    </div>
                </div>

                <div class="space-y-3">
                    <p class="text-sm font-medium">Header line 1 của từng sheet import hợp lệ</p>

                    <div class="grid gap-4">
                        <div
                            v-for="sheet in workbookBoundary.expectedSheets"
                            :key="`headers-${sheet}`"
                            class="rounded-[1rem] border p-4"
                            :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                        >
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ sheet }}
                                    </p>
                                    <p class="text-sm">
                                        {{
                                            workbookBoundary.headerRowBySheet[sheet]?.length
                                                ? `Đã đọc ${workbookBoundary.headerRowBySheet[sheet].length} cột ở line 1.`
                                                : 'Sheet này chưa có dữ liệu header để hiển thị ở boundary hiện tại.'
                                        }}
                                    </p>
                                    <p class="mt-1 text-sm">
                                        {{
                                            workbookBoundary.emptyStateBySheet[sheet]
                                                ? 'Sheet hiện không có dòng dữ liệu nào sau header.'
                                                : `Có ${workbookBoundary.dataRowCountBySheet[sheet] ?? 0} dòng dữ liệu sau header.`
                                        }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Tag
                                        :value="workbookBoundary.headerRowBySheet[sheet]?.length ? 'Có header' : 'Chưa có header'"
                                        :severity="workbookBoundary.headerRowBySheet[sheet]?.length ? 'success' : 'warn'"
                                        rounded
                                    />
                                    <Tag
                                        :value="workbookBoundary.emptyStateBySheet[sheet] ? 'Sheet rỗng' : `${workbookBoundary.dataRowCountBySheet[sheet] ?? 0} dòng dữ liệu`"
                                        :severity="workbookBoundary.emptyStateBySheet[sheet] ? 'warn' : 'info'"
                                        rounded
                                    />
                                </div>
                            </div>

                            <div
                                v-if="workbookBoundary.headerRowBySheet[sheet]?.length"
                                class="mt-3 flex flex-wrap gap-2"
                            >
                                <Tag
                                    v-for="(header, headerIndex) in workbookBoundary.headerRowBySheet[sheet]"
                                    :key="`${sheet}-${headerIndex}`"
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
