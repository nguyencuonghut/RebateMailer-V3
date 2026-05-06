import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { computed, ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';

export type CamCaProgramItem = {
    programIndex: number;
    content: string;
    amount: string;
};

export type CamCaDiscreteItem = {
    label: string;
    value: string;
};

export type CamCaPreviewRecord = {
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
    otherDiscount: string;
    grandTotal: string;
    totalInWords: string;
    programItems: CamCaProgramItem[];
    discreteItems: CamCaDiscreteItem[];
};

export type CamCaPreview = {
    sheetName: string;
    fixedHeaders: string[];
    discreteHeaders: string[];
    programPairCount: number;
    recordCount: number;
    records: CamCaPreviewRecord[];
    nextStep: string;
};

type CamCaPreviewResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: CamCaPreview;
    errors?: Record<string, string[]>;
};

export const useCamCaPreviewFlow = (
    workbookBoundary: { value: ImportWorkbookBoundary | null },
    initialPreview: CamCaPreview | null = null,
) => {
    const toast = useToast();
    const isLoadingCamCaPreview = ref(false);
    const camCaPreview = ref<CamCaPreview | null>(initialPreview);
    const camCaErrorMessage = ref('');

    const canPreviewCamCa = computed(() =>
        workbookBoundary.value?.sheets.some((sheet) => sheet.name === 'Cám cá' && sheet.present) ?? false,
    );

    const loadCamCaPreview = async (
        receipt: ImportUploadReceipt | null,
        previewUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có thông tin tải file lên để xem trước sheet Cám cá.';
        }

        isLoadingCamCaPreview.value = true;
        camCaErrorMessage.value = '';

        try {
            const response = await axios.post<CamCaPreviewResponse>(
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

            camCaPreview.value = response.data.data;

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
                    ?? error.response?.data?.errors?.camCa?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    camCaErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể xem trước sheet Cám cá',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Xem trước sheet Cám cá thất bại. Vui lòng thử lại.';
            camCaErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể xem trước sheet Cám cá',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isLoadingCamCaPreview.value = false;
        }
    };

    const resetCamCaPreview = (): void => {
        camCaPreview.value = null;
        camCaErrorMessage.value = '';
    };

    return {
        isLoadingCamCaPreview,
        camCaPreview,
        camCaErrorMessage,
        canPreviewCamCa,
        loadCamCaPreview,
        resetCamCaPreview,
    };
};
