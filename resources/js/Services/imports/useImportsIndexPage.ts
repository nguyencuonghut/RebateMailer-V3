import type { AggregatePreview } from './useAggregatePreviewFlow';
import type { CamCaPreview } from './useCamCaPreviewFlow';
import type { ImportUploadReceipt } from './useImportUploadFlow';
import type { ImportWorkbookBoundary } from './useImportWorkbookBoundaryFlow';
import type { KeyAccountPreview } from './useKeyAccountPreviewFlow';
import type { KhoanNppPreview } from './useKhoanNppPreviewFlow';
import type { TongHopPreview } from './useTongHopPreviewFlow';
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
    currentSlice: {
        code: string;
        label: string;
    };
    canManageImports: boolean;
    uploadPolicy: {
        acceptedExtension: string;
        acceptedMimeLabel: string;
    };
    acceptedSheets: string[];
    nextSlice: {
        code: string;
        label: string;
    };
    analysisPrep: {
        actionLabel: string;
        helperText: string;
        readyTitle: string;
        readyDescription: string;
        statusLabel: string;
        toast: ImportPageToast;
    };
    toast: ImportPageToast;
    activeBatchId: number | null;
    initialUploadReceipt: ImportUploadReceipt | null;
    initialBatchProcessingError: string | null;
    initialWorkbookBoundary: ImportWorkbookBoundary | null;
    initialTongHopPreview: TongHopPreview | null;
    initialKhoanNppPreview: KhoanNppPreview | null;
    initialCamCaPreview: CamCaPreview | null;
    initialKeyAccountPreview: KeyAccountPreview | null;
    initialAggregatePreview: AggregatePreview | null;
    importHistory: ImportHistoryItem[];
};

export type ImportHistoryItem = {
    id: number;
    batchCode: string;
    originalFileName: string;
    status: string;
    uploadedBy: string;
    uploadedAt: string | null;
    parsedRecordCount: number;
    aggregatedRecordCount: number;
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
        `Lát cắt hiện tại: ${props.currentSlice.code} - ${props.currentSlice.label}`,
        `Bước kế tiếp: ${props.nextSlice.code} - ${props.nextSlice.label}`,
    ]);

    const disabledActionMessage = computed(
        () =>
            props.canManageImports
                ? `Tính năng chọn file sẽ được mở trong ${props.nextSlice.code}.`
                : 'Tài khoản hiện tại chỉ có quyền xem khu vực import, chưa được thao tác tải file lên.',
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
