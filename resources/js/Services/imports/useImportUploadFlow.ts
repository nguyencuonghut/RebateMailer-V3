import axios from 'axios';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';
import type { LocalImportFile } from './useImportUploadCard';

export type ImportUploadReceipt = {
    originalFileName: string;
    size: number | null;
    storedPath: string;
    uploadedAt: string | null;
    importBatch: {
        id: number;
        batchCode: string;
        name: string;
        status: string;
    };
    nextStep: string;
};

type UploadResponse = {
    status: 'ok';
    message: string;
    toast: ImportPageToast;
    data: ImportUploadReceipt;
};

export const useImportUploadFlow = (initialReceipt: ImportUploadReceipt | null = null) => {
    const toast = useToast();
    const isUploading = ref(false);
    const uploadReceipt = ref<ImportUploadReceipt | null>(initialReceipt);

    const uploadSelectedFile = async (
        file: LocalImportFile | null,
        batchName: string,
        uploadUrl: string,
    ): Promise<string | null> => {
        if (!file) {
            return 'Vui lòng chọn file Excel trước khi tiếp tục.';
        }

        if (batchName.trim() === '') {
            return 'Vui lòng nhập tên batch trước khi tiếp tục.';
        }

        isUploading.value = true;

        try {
            const formData = new FormData();
            formData.append('file', file.file);
            formData.append('batch_name', batchName.trim());

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
                const backendMessage =
                    error.response?.data?.errors?.batch_name?.[0]
                    ?? error.response?.data?.errors?.file?.[0]
                    ?? error.response?.data?.message;

                if (backendMessage) {
                    toast.add({
                        severity: 'error',
                        summary: 'Không thể tải file',
                        detail: backendMessage,
                        life: 4000,
                    });

                    return backendMessage;
                }

                if ((error.response?.status ?? 0) >= 500) {
                    const serverFailureMessage = 'Máy chủ trả về lỗi nội bộ khi tải file lên. Vui lòng kiểm tra log hệ thống.';

                    toast.add({
                        severity: 'error',
                        summary: 'Không thể tải file',
                        detail: serverFailureMessage,
                        life: 4000,
                    });

                    return serverFailureMessage;
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
