<script setup lang="ts">
import type { ImportHistoryItem } from '@/Services/imports/useImportsIndexPage';
import { useImportBatchHistory } from '@/Services/imports/useImportBatchHistory';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

const props = defineProps<{
    history: ImportHistoryItem[];
    activeBatchId: number | null;
}>();

const { historyRows, openBatch, isActiveBatch } = useImportBatchHistory(props.history, props.activeBatchId);
</script>

<template>
    <div class="space-y-4">
        <div>
            <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                Danh sách batch đã lưu
            </p>
            <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                Mở lại preview từ dữ liệu đã persist trong DB
            </p>
        </div>

        <Message v-if="historyRows.length === 0" severity="info" :closable="false">
            Chưa có batch import nào trong hệ thống.
        </Message>

        <DataTable
            v-else
            :value="historyRows"
            paginator
            :rows="10"
            responsive-layout="scroll"
            class="p-datatable-sm"
        >
            <Column field="batchCode" header="Mã batch" />
            <Column field="originalFileName" header="File nguồn" />
            <Column header="Trạng thái">
                <template #body="{ data }">
                    <Tag :value="data.statusLabel" :severity="data.statusSeverity" rounded />
                </template>
            </Column>
            <Column field="uploadedBy" header="Người import" />
            <Column field="uploadedAtLabel" header="Thời điểm" />
            <Column field="parsedRecordCountLabel" header="Parsed" />
            <Column field="aggregatedRecordCountLabel" header="Aggregated" />
            <Column header="Mở lại">
                <template #body="{ data }">
                    <Button
                        :label="isActiveBatch(data.id) ? 'Đang xem' : 'Mở batch'"
                        :icon="isActiveBatch(data.id) ? 'pi pi-check' : 'pi pi-folder-open'"
                        :severity="isActiveBatch(data.id) ? 'contrast' : 'secondary'"
                        size="small"
                        @click="openBatch(data.id)"
                    />
                </template>
            </Column>
        </DataTable>
    </div>
</template>
