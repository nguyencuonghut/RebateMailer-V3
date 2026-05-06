import axios from 'axios';
import { computed, ref } from 'vue';
import { useToast } from 'primevue/usetoast';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';

export type TongHopPreviewRecord = {
    stt: string;
    month: string;
    customerCode: string;
    customerFullName: string;
    customerName: string;
    email: string;
    address: string;
    feedCategory: string;
    totalQuantity: string;
    revenue: string;
    invoiceDiscount: string;
    commitmentBonus: string;
    fishFeedDiscount: string;
    otherDiscount: string;
    grandTotal: string;
    totalInWords: string;
    dynamicItems: Array<{
        label: string;
        value: string;
    }>;
};

export type TongHopPreview = {
    sheetName: string;
    fixedHeaders: string[];
    dynamicHeaders: string[];
    recordCount: number;
    records: TongHopPreviewRecord[];
    nextStep: string;
};

type TongHopPreviewResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: TongHopPreview;
    errors?: Record<string, string[]>;
};

export const useTongHopPreviewFlow = (initialPreview: TongHopPreview | null = null) => {
    const toast = useToast();
    const isLoadingTongHopPreview = ref(false);
    const tongHopPreview = ref<TongHopPreview | null>(initialPreview);
    const tongHopErrorMessage = ref('');

    const loadTongHopPreview = async (
        receipt: ImportUploadReceipt | null,
        previewUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có thông tin tải file lên để xem trước sheet Tổng hợp.';
        }

        isLoadingTongHopPreview.value = true;
        tongHopErrorMessage.value = '';

        try {
            const response = await axios.post<TongHopPreviewResponse>(
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

            tongHopPreview.value = response.data.data;

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
                    ?? error.response?.data?.errors?.tongHop?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    tongHopErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể xem trước sheet Tổng hợp',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Xem trước sheet Tổng hợp thất bại. Vui lòng thử lại.';
            tongHopErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể xem trước sheet Tổng hợp',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isLoadingTongHopPreview.value = false;
        }
    };

    const canPreviewTongHop = computed(
        () => true,
    );

    const resetTongHopPreview = (): void => {
        tongHopPreview.value = null;
        tongHopErrorMessage.value = '';
    };

    return {
        isLoadingTongHopPreview,
        tongHopPreview,
        tongHopErrorMessage,
        canPreviewTongHop,
        loadTongHopPreview,
        resetTongHopPreview,
    };
};
