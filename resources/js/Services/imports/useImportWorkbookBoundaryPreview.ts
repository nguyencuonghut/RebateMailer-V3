import { computed, type Ref } from 'vue';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';
import { formatImportNumber } from './useImportNumberFormatter';

type Severity = 'success' | 'info' | 'warn' | 'danger' | 'secondary' | 'contrast';

export type WorkbookBoundarySummaryItem = {
    label: string;
    value: string;
    helper: string;
    severity: Severity;
};

export type WorkbookBoundaryContractMessage = {
    text: string;
    severity: 'success' | 'warn' | 'error';
};

export type WorkbookBoundarySheetDetail = {
    name: string;
    hasHeader: boolean;
    headerCount: number;
    headers: string[];
    dataRowCount: number;
    isEmpty: boolean;
    headerStatusLabel: string;
    rowStatusLabel: string;
    rowStatusSeverity: 'info' | 'warn';
};

export const useImportWorkbookBoundaryPreview = (workbookBoundary: Ref<ImportWorkbookBoundary | null>) => {
    const summaryItems = computed<WorkbookBoundarySummaryItem[]>(() => {
        const boundary = workbookBoundary.value;

        if (!boundary) {
            return [];
        }

        return [
            {
                label: 'Sheet đã nhận diện',
                value: formatImportNumber(boundary.summary.detectedSheetCount),
                helper: 'Tổng số sheet đọc được từ tệp Excel',
                severity: 'contrast',
            },
            {
                label: 'Sheet hợp lệ',
                value: formatImportNumber(boundary.contract.expectedSheetCount),
                helper: 'Quy ước nhập liệu nghiệp vụ cố định',
                severity: 'success',
            },
            {
                label: 'Sheet thiếu',
                value: formatImportNumber(boundary.summary.missingSheetCount),
                helper: boundary.missingSheets.length ? boundary.missingSheets.join(', ') : 'Không thiếu sheet nào',
                severity: boundary.missingSheets.length ? 'danger' : 'success',
            },
            {
                label: 'Sheet ngoài contract',
                value: formatImportNumber(boundary.summary.unexpectedSheetCount),
                helper: boundary.unexpectedSheets.length ? boundary.unexpectedSheets.join(', ') : 'Không có sheet ngoài quy ước',
                severity: boundary.unexpectedSheets.length ? 'warn' : 'success',
            },
        ];
    });

    const contractMessages = computed<WorkbookBoundaryContractMessage[]>(() => {
        const boundary = workbookBoundary.value;

        if (!boundary) {
            return [];
        }

        const messages: WorkbookBoundaryContractMessage[] = [];

        if (boundary.missingSheets.length === 0) {
            messages.push({
                text: 'Tệp Excel hiện không thiếu sheet nhập liệu nào trong quy ước 4 sheet.',
                severity: 'success',
            });
        } else {
            messages.push({
                text: `Tệp Excel đang thiếu ${boundary.missingSheets.length} sheet nhập liệu: ${boundary.missingSheets.join(', ')}.`,
                severity: 'error',
            });
        }

        if (boundary.unexpectedSheets.length === 0) {
            messages.push({
                text: 'Tệp Excel không có sheet ngoài quy ước nhập liệu.',
                severity: 'success',
            });
        } else {
            messages.push({
                text: `Tệp Excel có ${boundary.unexpectedSheets.length} sheet ngoài quy ước nhập liệu: ${boundary.unexpectedSheets.join(', ')}.`,
                severity: 'warn',
            });
        }

        return messages;
    });

    const sheetDetails = computed<WorkbookBoundarySheetDetail[]>(() => {
        const boundary = workbookBoundary.value;

        if (!boundary) {
            return [];
        }

        return boundary.sheets.map((sheet) => {
            const headers = sheet.headerRow ?? [];
            const dataRowCount = sheet.dataRowCount ?? 0;
            const isEmpty = sheet.isEmpty ?? true;

            return {
                name: sheet.name,
                hasHeader: headers.length > 0,
                headerCount: headers.length,
                headers,
                dataRowCount,
                isEmpty,
                headerStatusLabel: headers.length > 0 ? 'Có header' : 'Chưa có header',
                rowStatusLabel: isEmpty ? 'Sheet rỗng' : `${formatImportNumber(dataRowCount)} dòng dữ liệu`,
                rowStatusSeverity: isEmpty ? 'warn' : 'info',
            };
        });
    });

    return {
        summaryItems,
        contractMessages,
        sheetDetails,
    };
};
