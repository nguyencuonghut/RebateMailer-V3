import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import type { ImportHistoryItem } from './useImportsIndexPage';
import { formatImportNumber } from './useImportNumberFormatter';
import { getImportBatchStatusPresentation, type ImportBatchStatusSeverity } from './useImportBatchStatusPresentation';

export type ImportHistoryRow = ImportHistoryItem & {
    statusLabel: string;
    statusSeverity: ImportBatchStatusSeverity;
    uploadedAtLabel: string;
    parsedRecordCountLabel: string;
    aggregatedRecordCountLabel: string;
};

const formatUploadedAt = (value: string | null): string => {
    if (!value) {
        return 'Chưa có thời điểm';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('vi-VN', {
        dateStyle: 'short',
        timeStyle: 'short',
    }).format(date);
};

export const useImportBatchHistory = (
    history: ImportHistoryItem[],
    activeBatchId: number | null,
) => {
    const historyRows = computed<ImportHistoryRow[]>(() =>
        history.map((item) => ({
            ...item,
            statusLabel: getImportBatchStatusPresentation(item.status).label,
            statusSeverity: getImportBatchStatusPresentation(item.status).severity,
            uploadedAtLabel: formatUploadedAt(item.uploadedAt),
            parsedRecordCountLabel: formatImportNumber(item.parsedRecordCount),
            aggregatedRecordCountLabel: formatImportNumber(item.aggregatedRecordCount),
        })),
    );

    const openBatch = (batchId: number): void => {
        router.get(
            route('imports.index'),
            { batch: batchId },
            {
                preserveScroll: true,
                preserveState: false,
            },
        );
    };

    const isActiveBatch = (batchId: number): boolean => activeBatchId === batchId;

    const openTemplates = (batchId: number): void => {
        router.get(
            route('templates.index'),
            { preview_batch: batchId },
            {
                preserveScroll: true,
                preserveState: false,
            },
        );
    };

    const deletingBatchId = ref<number | null>(null);

    const deleteBatch = (batchId: number, batchName: string): void => {
        if (!window.confirm(`Xóa đợt nhập "${batchName}"?\n\nToàn bộ dữ liệu của đợt nhập này sẽ bị xóa vĩnh viễn.`)) {
            return;
        }

        deletingBatchId.value = batchId;

        router.delete(route('imports.batches.destroy', { importBatch: batchId }), {
            preserveScroll: true,
            onFinish: () => {
                deletingBatchId.value = null;
            },
        });
    };

    return {
        historyRows,
        openBatch,
        openTemplates,
        isActiveBatch,
        deleteBatch,
        deletingBatchId,
    };
};
