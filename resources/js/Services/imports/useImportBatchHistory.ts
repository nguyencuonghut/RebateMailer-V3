import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { ImportHistoryItem } from './useImportsIndexPage';

type HistoryStatusSeverity = 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast';

export type ImportHistoryRow = ImportHistoryItem & {
    statusLabel: string;
    statusSeverity: HistoryStatusSeverity;
    uploadedAtLabel: string;
};

const statusMap: Record<string, { label: string; severity: HistoryStatusSeverity }> = {
    uploaded: { label: 'Đã tải lên', severity: 'info' },
    workbook_analyzed: { label: 'Đã đọc workbook', severity: 'info' },
    parsed_partial: { label: 'Đã parse một phần', severity: 'warn' },
    parsed_complete: { label: 'Đã parse đủ sheet', severity: 'success' },
    aggregated: { label: 'Đã aggregate', severity: 'success' },
    validated_with_warnings: { label: 'Có cảnh báo', severity: 'warn' },
    validated_ready: { label: 'Sẵn sàng dùng', severity: 'success' },
    failed: { label: 'Thất bại', severity: 'danger' },
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
            statusLabel: statusMap[item.status]?.label ?? item.status,
            statusSeverity: statusMap[item.status]?.severity ?? 'secondary',
            uploadedAtLabel: formatUploadedAt(item.uploadedAt),
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

    return {
        historyRows,
        openBatch,
        isActiveBatch,
    };
};
