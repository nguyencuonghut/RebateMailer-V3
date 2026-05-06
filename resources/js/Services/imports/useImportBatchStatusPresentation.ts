export type ImportBatchStatusSeverity = 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast';

const statusMap: Record<string, { label: string; severity: ImportBatchStatusSeverity }> = {
    uploaded: { label: 'Đã tải lên', severity: 'info' },
    queued: { label: 'Đang chờ xử lý', severity: 'warn' },
    processing: { label: 'Đang xử lý nền', severity: 'warn' },
    workbook_analyzed: { label: 'Đã phân tích tệp Excel', severity: 'info' },
    parsed_partial: { label: 'Đã xử lý một phần', severity: 'warn' },
    parsed_complete: { label: 'Đã xử lý đủ 4 sheet', severity: 'success' },
    aggregated: { label: 'Đã hợp nhất dữ liệu', severity: 'success' },
    validated_with_warnings: { label: 'Có cảnh báo', severity: 'warn' },
    validated_ready: { label: 'Sẵn sàng sử dụng', severity: 'success' },
    failed: { label: 'Thất bại', severity: 'danger' },
};

export const getImportBatchStatusPresentation = (status: string): { label: string; severity: ImportBatchStatusSeverity } =>
    statusMap[status] ?? {
        label: status,
        severity: 'secondary',
    };
