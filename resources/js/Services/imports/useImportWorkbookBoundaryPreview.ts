import { computed, type Ref } from 'vue';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';

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
                value: `${boundary.summary.detectedSheetCount}`,
                helper: 'Tổng số sheet đọc được từ workbook',
                severity: 'contrast',
            },
            {
                label: 'Sheet hợp lệ',
                value: `${boundary.contract.expectedSheetCount}`,
                helper: 'Contract import nghiệp vụ cố định',
                severity: 'success',
            },
            {
                label: 'Sheet thiếu',
                value: `${boundary.summary.missingSheetCount}`,
                helper: boundary.missingSheets.length ? boundary.missingSheets.join(', ') : 'Không thiếu sheet nào',
                severity: boundary.missingSheets.length ? 'danger' : 'success',
            },
            {
                label: 'Sheet ngoài contract',
                value: `${boundary.summary.unexpectedSheetCount}`,
                helper: boundary.unexpectedSheets.length ? boundary.unexpectedSheets.join(', ') : 'Không có sheet ngoài contract',
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
                text: 'Workbook hiện không thiếu sheet import nào trong contract 4 sheet.',
                severity: 'success',
            });
        } else {
            messages.push({
                text: `Workbook đang thiếu ${boundary.missingSheets.length} sheet import: ${boundary.missingSheets.join(', ')}.`,
                severity: 'error',
            });
        }

        if (boundary.unexpectedSheets.length === 0) {
            messages.push({
                text: 'Workbook không có sheet ngoài contract import.',
                severity: 'success',
            });
        } else {
            messages.push({
                text: `Workbook có ${boundary.unexpectedSheets.length} sheet ngoài contract import: ${boundary.unexpectedSheets.join(', ')}.`,
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
                rowStatusLabel: isEmpty ? 'Sheet rỗng' : `${dataRowCount} dòng dữ liệu`,
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
