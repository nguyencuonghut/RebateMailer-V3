<script setup lang="ts">
import DataTableGlobalFilterToolbar from '@/Components/common/DataTableGlobalFilterToolbar.vue';
import type { KeyAccountPreview } from '@/Services/imports/useKeyAccountPreviewFlow';
import { useImportDetailTableVisibility } from '@/Services/imports/useImportDetailTableVisibility';
import { formatImportNumber } from '@/Services/imports/useImportNumberFormatter';
import { useDataTableGlobalFilter } from '@/Services/useDataTableGlobalFilter';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    preview: KeyAccountPreview | null;
    isLoading: boolean;
    errorMessage: string;
    canPreview: boolean;
}>();

const emit = defineEmits<{
    load: [];
}>();

const { showDetailsTable, detailToggleLabel, detailToggleIcon, detailToggleHelper, toggleDetailsTable } = useImportDetailTableVisibility();
const {
    filters,
    globalFilterFields,
    globalFilterValue,
    clearGlobalFilter,
} = useDataTableGlobalFilter<KeyAccountPreview['records'][number]>([
    'customerCode',
    'customerFullName',
    'month',
    'email',
    'address',
    'feedCategory',
    'totalQuantity',
    'revenue',
    'invoiceDiscount',
    'grandTotal',
    (record) => record.programItems.map((item) => `${item.programIndex} ${item.content} ${item.quantity} ${item.supportRate} ${item.amount}`).join(' '),
    (record) => record.discreteItems.map((item) => `${item.label} ${item.value}`).join(' '),
]);
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Parser riêng cho sheet Key Account
                </p>
                <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Preview dữ liệu Key Account theo cột rời rạc và block chương trình
                </p>
            </div>

            <Button
                :label="errorMessage ? 'Thử preview lại' : 'Preview sheet Key Account'"
                :icon="errorMessage ? 'pi pi-refresh' : 'pi pi-list'"
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
                    <p class="text-sm font-medium">Sheet đang preview</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.sheetName }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Số bản ghi</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ formatImportNumber(preview.recordCount) }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Số block chương trình</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ formatImportNumber(preview.programBlockCount) }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Header cố định của sheet Key Account</p>
                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="header in preview.fixedHeaders"
                        :key="`fixed-${header}`"
                        :value="header"
                        severity="success"
                        rounded
                    />
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Cột rời rạc có thể thay đổi theo tháng</p>
                <div class="flex flex-wrap gap-2">
                    <Tag
                        v-for="header in preview.discreteHeaders"
                        :key="`discrete-${header}`"
                        :value="header"
                        severity="contrast"
                        rounded
                    />
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
                v-model:filters="filters"
                :value="preview.records"
                :global-filter-fields="globalFilterFields"
                paginator
                :rows="10"
                responsive-layout="scroll"
                class="p-datatable-sm"
            >
                <template #header>
                    <DataTableGlobalFilterToolbar
                        v-model="globalFilterValue"
                        placeholder="Tìm theo mã khách, tên, tháng, CT, cột rời rạc"
                        @clear="clearGlobalFilter"
                    />
                </template>
                <Column field="customerFullName" header="Mã & tên khách hàng" />
                <Column field="month" header="Tháng" />
                <Column header="Tổng sản lượng">
                    <template #body="{ data }">
                        {{ formatImportNumber(data.totalQuantity) }}
                    </template>
                </Column>
                <Column header="Tổng cộng">
                    <template #body="{ data }">
                        {{ formatImportNumber(data.grandTotal) }}
                    </template>
                </Column>
                <Column header="Cột rời rạc có giá trị">
                    <template #body="{ data }">
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <Tag
                                    v-for="item in data.discreteItems"
                                    :key="`${data.customerCode}-${item.label}`"
                                    :value="`${item.label}: ${formatImportNumber(item.value)}`"
                                    severity="info"
                                    rounded
                                />
                            </div>
                            <p v-if="data.discreteItems.length === 0" class="text-sm">
                                Bản ghi này không có cột rời rạc nào có giá trị khác 0.
                            </p>
                        </div>
                    </template>
                </Column>
                <Column header="Block chương trình">
                    <template #body="{ data }">
                        <div class="space-y-2">
                            <div
                                v-for="item in data.programItems"
                                :key="`${data.customerCode}-${item.programIndex}`"
                                class="rounded-[0.85rem] border p-3"
                                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                            >
                                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    CT {{ item.programIndex }}
                                </p>
                                <p class="mt-1 text-sm leading-6">
                                    {{ item.content }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Tag :value="`SL: ${formatImportNumber(item.quantity)}`" severity="info" rounded />
                                    <Tag :value="`đ/kg: ${formatImportNumber(item.supportRate)}`" severity="warn" rounded />
                                    <Tag :value="`Thành tiền: ${formatImportNumber(item.amount)}`" severity="success" rounded />
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div
                v-else
                class="rounded-[1rem] border border-dashed p-4 text-sm"
                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
            >
                Bảng chi tiết đang được thu gọn để ưu tiên phần tóm tắt Key Account và giảm scroll trên màn hình nhỏ.
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
            Chưa có preview sheet Key Account. Sau khi workbook boundary hợp lệ, khu vực này sẽ hiển thị dữ liệu khách Key Account đã được parse riêng.
        </div>
    </div>
</template>
