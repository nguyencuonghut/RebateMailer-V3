<script setup lang="ts">
import type { LocalImportFile } from '@/Services/imports/useImportUploadCard';
import Button from 'primevue/button';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    canManageImports: boolean;
    acceptedExtension: string;
    acceptedMimeLabel: string;
    inputId: string;
    selectedFile: LocalImportFile | null;
    formattedFileSize: string;
    inlineError: string;
    disabledActionMessage: string;
    isUploading: boolean;
}>();

const emit = defineEmits<{
    open: [];
    clear: [];
    select: [event: Event];
    upload: [];
}>();
</script>

<template>
    <div class="space-y-4">
        <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
            Chọn file Excel trên máy để chuẩn bị cho lát cắt upload thật. Ở bước này hệ thống chỉ giữ file trong state cục bộ của trình duyệt.
        </p>

        <template v-if="canManageImports">
            <input
                :id="inputId"
                class="hidden"
                type="file"
                :accept="acceptedExtension"
                @change="emit('select', $event)"
            >

            <div class="grid gap-3 sm:grid-cols-2">
                <Button label="Chọn file Excel" icon="pi pi-file-excel" @click="emit('open')" />
                <Button label="Xóa lựa chọn" icon="pi pi-times" outlined :disabled="!selectedFile" @click="emit('clear')" />
            </div>

            <Button
                label="Tải file lên"
                icon="pi pi-upload"
                :loading="isUploading"
                :disabled="!selectedFile || isUploading"
                @click="emit('upload')"
            />

            <div class="rounded-[1.2rem] border border-dashed p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Định dạng chấp nhận
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <Tag :value="acceptedExtension" severity="success" rounded />
                    <span class="text-sm" :style="{ color: 'var(--dashboard-strong-text)' }">{{ acceptedMimeLabel }}</span>
                </div>

                <div v-if="selectedFile" class="mt-4 space-y-2">
                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">File đã chọn</p>
                    <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ selectedFile.name }}
                    </p>
                    <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Dung lượng: {{ formattedFileSize }}
                    </p>
                </div>
            </div>

            <Message v-if="inlineError" severity="error" :closable="false">
                {{ inlineError }}
            </Message>
        </template>

        <Message v-else severity="warn" :closable="false">
            {{ disabledActionMessage }}
        </Message>
    </div>
</template>
