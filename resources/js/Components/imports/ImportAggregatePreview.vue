<script setup lang="ts">
import type { AggregatePreview } from '@/Services/imports/useAggregatePreviewFlow';
import { useImportDetailTableVisibility } from '@/Services/imports/useImportDetailTableVisibility';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    preview: AggregatePreview | null;
    isLoading: boolean;
    errorMessage: string;
    canPreview: boolean;
}>();

const emit = defineEmits<{
    load: [];
}>();

const { showDetailsTable, detailToggleLabel, detailToggleIcon, detailToggleHelper, toggleDetailsTable } = useImportDetailTableVisibility();
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Dữ liệu hợp nhất theo Mã số
                </p>
                <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Preview dữ liệu hợp nhất giữa Khách thường và Key Account
                </p>
            </div>

            <Button
                :label="errorMessage ? 'Thử xem lại' : 'Xem dữ liệu hợp nhất'"
                :icon="errorMessage ? 'pi pi-refresh' : 'pi pi-sitemap'"
                :loading="isLoading"
                :disabled="isLoading || !canPreview"
                @click="emit('load')"
            />
        </div>

        <Message v-if="errorMessage" severity="error" :closable="false">
            {{ errorMessage }}
        </Message>

        <div
            v-if="preview"
            class="space-y-4 rounded-[1.2rem] border border-dashed p-4"
            :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
        >
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Tổng số khách</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.summary.totalCustomerCount }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Khách thường</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.summary.normalCustomerCount }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Key Account</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.summary.keyAccountCustomerCount }}
                    </p>
                </div>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm">
                    {{ detailToggleHelper }}
                </p>

                <Button
                    :label="detailToggleLabel"
                    :icon="detailToggleIcon"
                    severity="secondary"
                    outlined
                    @click="toggleDetailsTable"
                />
            </div>

            <DataTable
                v-if="showDetailsTable"
                :value="preview.records"
                paginator
                :rows="10"
                responsive-layout="scroll"
                class="p-datatable-sm"
            >
                <Column field="customerCode" header="Mã số" />
                <Column field="customerType" header="Loại khách">
                    <template #body="{ data }">
                        <Tag
                            :value="data.customerType"
                            :severity="data.customerType === 'Key Account' ? 'warn' : 'success'"
                            rounded
                        />
                    </template>
                </Column>
                <Column header="Nguồn dữ liệu">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="sheet in data.sourceSheets"
                                :key="`${data.customerCode}-${sheet}`"
                                :value="sheet"
                                severity="contrast"
                                rounded
                            />
                        </div>
                    </template>
                </Column>
                <Column header="Section có dữ liệu">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag v-if="data.tongHop" value="Tổng hợp" severity="info" rounded />
                            <Tag v-if="data.khoanNpp" value="Khoán NPP" severity="info" rounded />
                            <Tag v-if="data.camCa" value="Cám cá" severity="info" rounded />
                            <Tag v-if="data.keyAccount" value="Key Account" severity="warn" rounded />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div
                v-else
                class="rounded-[1rem] border border-dashed p-4 text-sm"
                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
            >
                Bảng chi tiết đang được thu gọn để giảm độ dài trang trên màn hình nhỏ.
            </div>

            <p class="text-sm leading-6">
                {{ preview.nextStep }}
            </p>
        </div>

        <div
            v-else
            class="rounded-[1.2rem] border border-dashed p-4 text-sm"
            :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
        >
            Chưa có dữ liệu hợp nhất để xem trước. Sau khi cấu trúc tệp Excel hợp lệ, khu vực này sẽ hiển thị dữ liệu đã được gom theo Mã số.
        </div>
    </div>
</template>
