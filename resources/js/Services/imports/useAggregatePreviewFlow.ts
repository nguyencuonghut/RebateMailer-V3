import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';

type AggregateCustomerType = 'Khách thường' | 'Key Account';

export type AggregatedImportRecord = {
    customerCode: string;
    customerFullName: string;
    customerType: AggregateCustomerType;
    sourceSheets: string[];
    tongHop: Record<string, unknown> | null;
    khoanNpp: Record<string, unknown> | null;
    camCa: Record<string, unknown> | null;
    keyAccount: Record<string, unknown> | null;
    validationErrors: string[];
};

export type AggregatePreview = {
    summary: {
        totalCustomerCount: number;
        normalCustomerCount: number;
        keyAccountCustomerCount: number;
        errorCount: number;
    };
    records: AggregatedImportRecord[];
    nextStep: string;
};

type AggregatePreviewResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: AggregatePreview;
    errors?: Record<string, string[]>;
};

export const useAggregatePreviewFlow = (
    workbookBoundary: { value: ImportWorkbookBoundary | null },
    initialPreview: AggregatePreview | null = null,
) => {
    const toast = useToast();
    const isLoadingAggregatePreview = ref(false);
    const aggregatePreview = ref<AggregatePreview | null>(initialPreview);
    const aggregateErrorMessage = ref('');

    const canPreviewAggregate = computed(
        () => workbookBoundary.value !== null,
    );

    const loadAggregatePreview = async (
        receipt: ImportUploadReceipt | null,
        previewUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có thông tin tải file lên để xem trước dữ liệu hợp nhất.';
        }

        isLoadingAggregatePreview.value = true;
        aggregateErrorMessage.value = '';

        try {
            const response = await axios.post<AggregatePreviewResponse>(
                previewUrl,
                {
                    importBatchId: receipt.importBatch.id,
                },
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            aggregatePreview.value = response.data.data;

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
                    error.response?.data?.errors?.importBatchId?.[0]
                    ?? error.response?.data?.errors?.aggregator?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    aggregateErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể xem trước dữ liệu hợp nhất',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Xem trước dữ liệu hợp nhất thất bại. Vui lòng thử lại.';
            aggregateErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể xem trước dữ liệu hợp nhất',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isLoadingAggregatePreview.value = false;
        }
    };

    const resetAggregatePreview = (): void => {
        aggregatePreview.value = null;
        aggregateErrorMessage.value = '';
    };

    return {
        isLoadingAggregatePreview,
        aggregatePreview,
        aggregateErrorMessage,
        canPreviewAggregate,
        loadAggregatePreview,
        resetAggregatePreview,
    };
};
