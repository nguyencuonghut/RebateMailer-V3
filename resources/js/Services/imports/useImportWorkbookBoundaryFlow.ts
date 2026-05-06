import axios from 'axios';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';

export type ImportWorkbookBoundaryActionConfig = {
    actionLabel: string;
    helperText: string;
    readyTitle: string;
    readyDescription: string;
    statusLabel: string;
    toast: ImportPageToast;
};

export type ImportWorkbookBoundary = {
    storedPath: string;
    contract: {
        version: string;
        stage: string;
        expectedSheetCount: number;
    };
    summary: {
        detectedSheetCount: number;
        missingSheetCount: number;
        unexpectedSheetCount: number;
    };
    expectedSheets: string[];
    detectedSheets: string[];
    missingSheets: string[];
    unexpectedSheets: string[];
    sheets: Array<{
        name: string;
        present: boolean;
        missing: boolean;
        headerRow: string[];
        dataRowCount: number;
        isEmpty: boolean;
    }>;
    nextStep: string;
};

type AnalyzeWorkbookResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: ImportWorkbookBoundary;
    errors?: Record<string, string[]>;
};

export const useImportWorkbookBoundaryFlow = () => {
    const toast = useToast();
    const isAnalyzingWorkbook = ref(false);
    const workbookBoundary = ref<ImportWorkbookBoundary | null>(null);
    const analysisErrorMessage = ref('');

    const analyzeWorkbook = async (
        receipt: ImportUploadReceipt | null,
        analyzeUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có receipt upload để đọc workbook.';
        }

        isAnalyzingWorkbook.value = true;
        analysisErrorMessage.value = '';

        try {
            const response = await axios.post<AnalyzeWorkbookResponse>(
                analyzeUrl,
                {
                    storedPath: receipt.storedPath,
                },
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            workbookBoundary.value = response.data.data;
            analysisErrorMessage.value = '';

            toast.add({
                severity: response.data.toast.severity,
                summary: response.data.toast.summary,
                detail: response.data.toast.detail,
                life: response.data.toast.life ?? 4000,
            });

            return null;
        } catch (error) {
            if (axios.isAxiosError(error)) {
                const backendMessage =
                    error.response?.data?.errors?.storedPath?.[0]
                    ?? error.response?.data?.errors?.workbook?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    analysisErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể đọc workbook',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Đọc cấu trúc workbook thất bại. Vui lòng thử lại.';
            analysisErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể đọc workbook',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isAnalyzingWorkbook.value = false;
        }
    };

    const analysisStatusText = computed(() =>
        workbookBoundary.value ? 'Đã đọc workbook' : analysisErrorMessage.value ? 'Phân tích thất bại' : 'Chưa đọc workbook',
    );

    const resetWorkbookBoundary = (): void => {
        workbookBoundary.value = null;
        analysisErrorMessage.value = '';
    };

    return {
        isAnalyzingWorkbook,
        workbookBoundary,
        analysisErrorMessage,
        analysisStatusText,
        analyzeWorkbook,
        resetWorkbookBoundary,
    };
};
