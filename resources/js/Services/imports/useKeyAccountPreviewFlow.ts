import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';

export type KeyAccountProgramItem = {
    programIndex: number;
    content: string;
    quantity: string;
    supportRate: string;
    amount: string;
};

export type KeyAccountDiscreteItem = {
    label: string;
    value: string;
};

export type KeyAccountPreviewRecord = {
    stt: string;
    month: string;
    customerCode: string;
    customerFullName: string;
    email: string;
    address: string;
    feedCategory: string;
    totalQuantity: string;
    revenue: string;
    invoiceDiscount: string;
    grandTotal: string;
    totalInWords: string;
    programItems: KeyAccountProgramItem[];
    discreteItems: KeyAccountDiscreteItem[];
};

export type KeyAccountPreview = {
    sheetName: string;
    fixedHeaders: string[];
    discreteHeaders: string[];
    programBlockCount: number;
    recordCount: number;
    records: KeyAccountPreviewRecord[];
    nextStep: string;
};

type KeyAccountPreviewResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: KeyAccountPreview;
    errors?: Record<string, string[]>;
};

export const useKeyAccountPreviewFlow = (
    workbookBoundary: { value: ImportWorkbookBoundary | null },
    initialPreview: KeyAccountPreview | null = null,
) => {
    const toast = useToast();
    const isLoadingKeyAccountPreview = ref(false);
    const keyAccountPreview = ref<KeyAccountPreview | null>(initialPreview);
    const keyAccountErrorMessage = ref('');

    const canPreviewKeyAccount = computed(() =>
        workbookBoundary.value?.sheets.some((sheet) => sheet.name === 'Key Account' && sheet.present) ?? false,
    );

    const loadKeyAccountPreview = async (
        receipt: ImportUploadReceipt | null,
        previewUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có thông tin tải file lên để xem trước sheet Khách hàng trọng điểm.';
        }

        isLoadingKeyAccountPreview.value = true;
        keyAccountErrorMessage.value = '';

        try {
            const response = await axios.post<KeyAccountPreviewResponse>(
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

            keyAccountPreview.value = response.data.data;

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
                    ?? error.response?.data?.errors?.keyAccount?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    keyAccountErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể xem trước sheet Khách hàng trọng điểm',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Xem trước sheet Khách hàng trọng điểm thất bại. Vui lòng thử lại.';
            keyAccountErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể xem trước sheet Khách hàng trọng điểm',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isLoadingKeyAccountPreview.value = false;
        }
    };

    const resetKeyAccountPreview = (): void => {
        keyAccountPreview.value = null;
        keyAccountErrorMessage.value = '';
    };

    return {
        isLoadingKeyAccountPreview,
        keyAccountPreview,
        keyAccountErrorMessage,
        canPreviewKeyAccount,
        loadKeyAccountPreview,
        resetKeyAccountPreview,
    };
};
