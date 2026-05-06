<script setup lang="ts">
import type { ImportWorkbookBoundary, ImportWorkbookBoundaryActionConfig } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import type { ImportUploadReceipt } from '@/Services/imports/useImportUploadFlow';
import { formatImportNumber } from '@/Services/imports/useImportNumberFormatter';
import Tag from 'primevue/tag';

defineProps<{
    receipt: ImportUploadReceipt | null;
    canManageImports: boolean;
    analysisPrep: ImportWorkbookBoundaryActionConfig;
    workbookBoundary: ImportWorkbookBoundary | null;
    isAnalyzingWorkbook: boolean;
    analysisErrorMessage: string;
    analysisStatusText: string;
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

            <Tag
                :value="receipt.importBatch.status === 'uploaded' ? 'Tải lên thành công' : 'Đợt nhập đã lưu'"
                :severity="receipt.importBatch.status === 'uploaded' ? 'success' : 'info'"
                rounded
            />
        </div>

        <dl class="grid gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Dung lượng</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.size === null ? 'Không còn dữ liệu dung lượng' : `${formatImportNumber(receipt.size)} bytes` }}
                </dd>
            </div>
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Thời điểm upload</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.uploadedAt ?? 'Chưa có dữ liệu thời điểm upload' }}
                </dd>
            </div>
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Mã đợt nhập</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.importBatch.batchCode }}
                </dd>
            </div>
            <div>
                <dt class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Trạng thái đợt nhập</dt>
                <dd class="mt-1 text-base font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ receipt.importBatch.status }}
                </dd>
            </div>
        </dl>

        <div class="rounded-[1.2rem] border border-dashed p-4 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
            {{ receipt.nextStep }}
        </div>
    </div>

    <div v-else class="rounded-[1.2rem] border border-dashed p-4 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
        Chưa có thông tin tiếp nhận file. Sau khi tải file thành công, khu vực này sẽ hiển thị thông tin file đã nhận.
    </div>
</template>
