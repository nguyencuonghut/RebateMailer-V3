import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { computed, onBeforeUnmount, ref } from 'vue';
import type { ImportPageToast } from './useImportsIndexPage';

type ImportBatchStatusResponse = {
    status: 'ok';
    message: string;
    toast: ImportPageToast | null;
    data: {
        importBatch: {
            id: number;
            batchCode: string;
            status: string;
            startedAt: string | null;
            completedAt: string | null;
        };
        lifecycle: {
            isQueued: boolean;
            isProcessing: boolean;
            isCompleted: boolean;
            isFailed: boolean;
            isTerminal: boolean;
            stateLabel: string;
        };
        workbookBoundaryReady: boolean;
        parsedSheetCount: number;
        aggregateReady: boolean;
        parsedRecordCount: number;
        aggregatedRecordCount: number;
        processingError: string | null;
    };
};

type TerminalCallback = (payload: ImportBatchStatusResponse['data']) => void;

export const useImportBatchStatusMonitor = (initialError: string | null = null) => {
    const toast = useToast();
    const isPolling = ref(false);
    const processingError = ref(initialError ?? '');
    const currentBatchStatus = ref<string | null>(null);
    const lastHandledTerminalStatus = ref<string | null>(null);
    let timer: ReturnType<typeof setInterval> | null = null;

    const isBatchProcessing = computed(
        () => currentBatchStatus.value === 'queued' || currentBatchStatus.value === 'processing' || isPolling.value,
    );

    const stopPolling = (): void => {
        if (timer !== null) {
            clearInterval(timer);
            timer = null;
        }

        isPolling.value = false;
    };

    const handleTerminalState = (
        response: ImportBatchStatusResponse,
        onTerminal: TerminalCallback,
    ): void => {
        stopPolling();
        currentBatchStatus.value = response.data.importBatch.status;
        processingError.value = response.data.processingError ?? '';

        if (
            response.toast !== null
            && lastHandledTerminalStatus.value !== response.data.importBatch.status
        ) {
            lastHandledTerminalStatus.value = response.data.importBatch.status;

            toast.add({
                severity: response.toast.severity,
                summary: response.toast.summary,
                detail: response.toast.detail,
                life: response.toast.life ?? 5000,
            });
        }

        onTerminal(response.data);
    };

    const pollOnce = async (
        statusUrl: string,
        onTerminal: TerminalCallback,
    ): Promise<void> => {
        const response = await axios.get<ImportBatchStatusResponse>(statusUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        currentBatchStatus.value = response.data.data.importBatch.status;
        processingError.value = response.data.data.processingError ?? '';

        if (response.data.data.lifecycle.isTerminal) {
            handleTerminalState(response.data, onTerminal);
        }
    };

    const startMonitoring = (
        batchId: number,
        statusUrl: string,
        onTerminal: TerminalCallback = () => {
            router.get(
                route('imports.index'),
                { batch: batchId },
                {
                    preserveScroll: true,
                    preserveState: false,
                },
            );
        },
    ): void => {
        stopPolling();
        isPolling.value = true;

        const execute = async (): Promise<void> => {
            try {
                await pollOnce(statusUrl, onTerminal);
            } catch (error) {
                stopPolling();
                processingError.value = axios.isAxiosError(error)
                    ? error.response?.data?.message ?? 'Không thể đọc trạng thái batch import.'
                    : 'Không thể đọc trạng thái batch import.';
            }
        };

        void execute();
        timer = setInterval(() => {
            void execute();
        }, 2500);
    };

    onBeforeUnmount(() => {
        stopPolling();
    });

    return {
        isPolling,
        isBatchProcessing,
        processingError,
        currentBatchStatus,
        startMonitoring,
        stopPolling,
    };
};
