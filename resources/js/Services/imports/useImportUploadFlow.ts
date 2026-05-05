import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { LocalImportFile } from './useImportUploadCard';

export type ImportUploadReceipt = {
    originalFileName: string;
    size: number;
    storedPath: string;
    uploadedAt: string;
    nextStep: string;
};

type UploadResponse = {
    status: 'ok';
    message: string;
    toast: ImportPageToast;
    data: ImportUploadReceipt;
};

export const useImportUploadFlow = () => {
    const toast = useToast();
    const isUploading = ref(false);
    const uploadReceipt = ref<ImportUploadReceipt | null>(null);

    const uploadSelectedFile = async (file: LocalImportFile | null, uploadUrl: string): Promise<string | null> => {
        if (!file) {
            return 'Vui lòng chọn file Excel trước khi tiếp tục.';
        }

        isUploading.value = true;

        try {
            const formData = new FormData();
            formData.append('file', file.file);

            const response = await axios.post<UploadResponse>(uploadUrl, formData, {
                headers: {
                    Accept: 'application/json',
                },
            });

            uploadReceipt.value = response.data.data;

            toast.add({
                severity: response.data.toast.severity,
                summary: response.data.toast.summary,
                detail: response.data.toast.detail,
                life: response.data.toast.life ?? 4000,
            });

            return null;
        } catch (error) {
            if (axios.isAxiosError(error)) {
                const backendMessage = error.response?.data?.errors?.file?.[0];

                if (backendMessage) {
                    toast.add({
                        severity: 'error',
                        summary: 'Không thể tải file',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }
            }

            const fallbackMessage = 'Tải file lên thất bại. Vui lòng thử lại.';

            toast.add({
                severity: 'error',
                summary: 'Không thể tải file',
                detail: fallbackMessage,
                life: 4000,
            });

            return fallbackMessage;
        } finally {
            isUploading.value = false;
        }
    };

    return {
        isUploading,
        uploadReceipt,
        uploadSelectedFile,
    };
};
