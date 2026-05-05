import { computed, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';

export type ImportPageToast = {
    severity: 'success' | 'info' | 'warn' | 'error' | 'secondary' | 'contrast';
    summary: string;
    detail: string;
    life?: number;
};

export type ImportPageProps = {
    title: string;
    description: string;
    uploadPolicy: {
        acceptedExtension: string;
        acceptedMimeLabel: string;
    };
    acceptedSheets: string[];
    nextSlice: {
        code: string;
        label: string;
    };
    toast: ImportPageToast;
};

export const useImportsIndexPage = (props: ImportPageProps) => {
    const toast = useToast();

    const acceptedSheetTags = computed(() =>
        props.acceptedSheets.map((label) => ({
            label,
            severity: 'contrast' as const,
        })),
    );

    const uploadReadinessItems = computed(() => [
        `Chỉ nhận file ${props.uploadPolicy.acceptedExtension}`,
        'Dữ liệu import nghiệp vụ gồm đúng 4 sheet',
        `Bước kế tiếp: ${props.nextSlice.code} - ${props.nextSlice.label}`,
    ]);

    const disabledActionMessage = computed(
        () => `Tính năng chọn file sẽ được mở trong ${props.nextSlice.code}.`,
    );

    onMounted(() => {
        if (!props.toast?.detail) {
            return;
        }

        toast.add({
            severity: props.toast.severity,
            summary: props.toast.summary,
            detail: props.toast.detail,
            life: props.toast.life ?? 4000,
        });
    });

    return {
        acceptedSheetTags,
        uploadReadinessItems,
        disabledActionMessage,
    };
};
