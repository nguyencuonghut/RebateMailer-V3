import { computed, ref } from 'vue';

export type LocalImportFile = {
    name: string;
    size: number;
    type: string;
    file: File;
};

export const useImportUploadCard = (acceptedExtension: string) => {
    const selectedFile = ref<LocalImportFile | null>(null);
    const inlineError = ref<string>('');
    const inputId = 'imports-local-file-input';

    const hasSelectedFile = computed(() => selectedFile.value !== null);

    const formattedFileSize = computed(() => {
        if (!selectedFile.value) {
            return '';
        }

        const bytes = selectedFile.value.size;

        if (bytes < 1024) {
            return `${bytes} B`;
        }

        if (bytes < 1024 * 1024) {
            return `${(bytes / 1024).toFixed(1)} KB`;
        }

        return `${(bytes / (1024 * 1024)).toFixed(2)} MB`;
    });

    const openFileDialog = (): void => {
        const input = document.getElementById(inputId) as HTMLInputElement | null;

        input?.click();
    };

    const clearSelection = (): void => {
        selectedFile.value = null;
        inlineError.value = '';

        const input = document.getElementById(inputId) as HTMLInputElement | null;

        if (input) {
            input.value = '';
        }
    };

    const onFileChange = (event: Event): void => {
        inlineError.value = '';

        const input = event.target as HTMLInputElement | null;
        const file = input?.files?.[0];

        if (!file) {
            selectedFile.value = null;
            return;
        }

        if (!file.name.toLowerCase().endsWith(acceptedExtension)) {
            selectedFile.value = null;
            inlineError.value = `Chỉ chấp nhận file ${acceptedExtension}.`;

            if (input) {
                input.value = '';
            }

            return;
        }

        selectedFile.value = {
            name: file.name,
            size: file.size,
            type: file.type,
            file,
        };
    };

    return {
        inputId,
        selectedFile,
        inlineError,
        hasSelectedFile,
        formattedFileSize,
        openFileDialog,
        clearSelection,
        onFileChange,
    };
};
