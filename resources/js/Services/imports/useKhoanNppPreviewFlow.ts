import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { ImportUploadReceipt } from './useImportUploadFlow';

export type KhoanNppProgramItem = {
    programIndex: number;
    content: string;
    quantity: string;
    supportRate: string;
    amount: string;
};

export type KhoanNppPreviewRecord = {
    stt: string;
    month: string;
    customerCode: string;
    customerFullName: string;
    email: string;
    address: string;
    feedCategory: string;
    grandTotal: string;
    totalInWords: string;
    programItems: KhoanNppProgramItem[];
};

export type KhoanNppPreview = {
    sheetName: string;
    fixedHeaders: string[];
    programBlockCount: number;
    recordCount: number;
    records: KhoanNppPreviewRecord[];
    nextStep: string;
};

type KhoanNppPreviewResponse = {
    status: 'ok' | 'error';
    message: string;
    toast: ImportPageToast;
    data: KhoanNppPreview;
    errors?: Record<string, string[]>;
};

export const useKhoanNppPreviewFlow = () => {
    const toast = useToast();
    const isLoadingKhoanNppPreview = ref(false);
    const khoanNppPreview = ref<KhoanNppPreview | null>(null);
    const khoanNppErrorMessage = ref('');

    const loadKhoanNppPreview = async (
        receipt: ImportUploadReceipt | null,
        previewUrl: string,
    ): Promise<string | null> => {
        if (!receipt) {
            return 'Chưa có receipt upload để preview sheet Khoán NPP.';
        }

        isLoadingKhoanNppPreview.value = true;
        khoanNppErrorMessage.value = '';

        try {
            const response = await axios.post<KhoanNppPreviewResponse>(
                previewUrl,
                {
                    storedPath: receipt.storedPath,
                },
                {
                    headers: {
                        Accept: 'application/json',
                    },
                },
            );

            khoanNppPreview.value = response.data.data;

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
                    ?? error.response?.data?.errors?.khoanNpp?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    khoanNppErrorMessage.value = backendMessage;

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể preview sheet Khoán NPP',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Preview sheet Khoán NPP thất bại. Vui lòng thử lại.';
            khoanNppErrorMessage.value = fallbackMessage;

            toast.add({
                severity: 'error',
                summary: 'Không thể preview sheet Khoán NPP',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isLoadingKhoanNppPreview.value = false;
        }
    };

    const resetKhoanNppPreview = (): void => {
        khoanNppPreview.value = null;
        khoanNppErrorMessage.value = '';
    };

    return {
        isLoadingKhoanNppPreview,
        khoanNppPreview,
        khoanNppErrorMessage,
        loadKhoanNppPreview,
        resetKhoanNppPreview,
    };
};
