import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';
import type { AggregatePreview } from './useAggregatePreviewFlow';

type ProcessBatchResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: AggregatePreview;
    errors?: Record<string, string[]>;
};

export const useImportProcessBatchFlow = () => {
    const toast = useToast();
    const isProcessing = ref(false);
    const processingError = ref('');
    const processedPreview = ref<AggregatePreview | null>(null);

    const processBatch = async (
        receipt: ImportUploadReceipt | null,
        processUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có thông tin tải file lên để xử lý đợt nhập.';
        }

        isProcessing.value = true;
        processingError.value = '';

        try {
            const response = await axios.post<ProcessBatchResponse>(
                processUrl,
                { importBatchId: receipt.importBatch.id },
                { headers: { Accept: 'application/json' } },
            );

            processedPreview.value = response.data.data;

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
                    error.response?.data?.errors?.batch?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    processingError.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Xử lý dữ liệu thất bại',
                        detail: backendMessage,
                        life: 5000,
                    });

                    return backendMessage;
                }
            }

            const fallback = 'Xử lý dữ liệu thất bại. Vui lòng thử lại.';
            processingError.value = fallback;

            toast.add({
                severity: 'error',
                summary: 'Xử lý dữ liệu thất bại',
                detail: fallback,
                life: 5000,
            });

            return fallback;
        } finally {
            isProcessing.value = false;
        }
    };

    const resetProcessBatch = (): void => {
        processedPreview.value = null;
        processingError.value = '';
    };

    return {
        isProcessing,
        processingError,
        processedPreview,
        processBatch,
        resetProcessBatch,
    };
};
