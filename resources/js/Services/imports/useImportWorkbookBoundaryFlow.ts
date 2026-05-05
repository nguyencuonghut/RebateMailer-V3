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
    sheetCount: number;
    expectedSheets: string[];
    detectedSheets: string[];
    missingSheets: string[];
    unexpectedSheets: string[];
    nextStep: string;
};

type AnalyzeWorkbookResponse = {
    status: 'ok';
    message: string;
    toast: ImportPageToast;
    data: ImportWorkbookBoundary;
};

export const useImportWorkbookBoundaryFlow = () => {
    const toast = useToast();
    const isAnalyzingWorkbook = ref(false);
    const workbookBoundary = ref<ImportWorkbookBoundary | null>(null);

    const analyzeWorkbook = async (
        receipt: ImportUploadReceipt | null,
        analyzeUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có receipt upload để đọc workbook.';
        }

        isAnalyzingWorkbook.value = true;

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

            toast.add({
                severity: response.data.toast.severity,
                summary: response.data.toast.summary,
                detail: response.data.toast.detail,
                life: response.data.toast.life ?? 4000,
            });

            return null;
        } catch (error) {
            if (axios.isAxiosError(error)) {
                const backendMessage = error.response?.data?.errors?.storedPath?.[0];

                if (backendMessage) {
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
        workbookBoundary.value ? 'Đã đọc workbook' : 'Chưa đọc workbook',
    );

    const resetWorkbookBoundary = (): void => {
        workbookBoundary.value = null;
    };

    return {
        isAnalyzingWorkbook,
        workbookBoundary,
        analysisStatusText,
        analyzeWorkbook,
        resetWorkbookBoundary,
    };
};
