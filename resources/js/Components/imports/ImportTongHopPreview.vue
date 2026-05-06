<script setup lang="ts">
import type { TongHopPreview } from '@/Services/imports/useTongHopPreviewFlow';
import { useImportDetailTableVisibility } from '@/Services/imports/useImportDetailTableVisibility';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    preview: TongHopPreview | null;
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
                    Xử lý riêng cho sheet Tổng hợp
                </p>
                <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Preview dữ liệu đã chuẩn hóa cho Khách thường
                </p>
            </div>

            <Button
                :label="errorMessage ? 'Thử xem lại' : 'Xem sheet Tổng hợp'"
                :icon="errorMessage ? 'pi pi-refresh' : 'pi pi-table'"
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
                    <p class="text-sm font-medium">Sheet đang xem</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.sheetName }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Số bản ghi</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.recordCount }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Cột động nhận diện</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.dynamicHeaders.length }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Header cố định của sheet Tổng hợp</p>
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
                <p class="text-sm font-medium">Header động đang có dữ liệu preview</p>
                <div v-if="preview.dynamicHeaders.length" class="flex flex-wrap gap-2">
                    <Tag
                        v-for="header in preview.dynamicHeaders"
                        :key="`dynamic-${header}`"
                        :value="header"
                        severity="warn"
                        rounded
                    />
                </div>
                <p v-else class="text-sm">
                    Chưa có header động nào được nhận diện ở sheet này.
                </p>
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
                <Column field="customerFullName" header="Mã & tên khách hàng" />
                <Column field="month" header="Tháng" />
                <Column field="email" header="Email" />
                <Column field="invoiceDiscount" header="Tiền chiết khấu theo Hóa đơn" />
                <Column field="grandTotal" header="Tổng cộng" />
                <Column header="Nội dung động">
                    <template #body="{ data }">
                        <div class="flex flex-wrap gap-2">
                            <Tag
                                v-for="item in data.dynamicItems"
                                :key="`${data.customerCode}-${item.label}`"
                                :value="`${item.label}: ${item.value}`"
                                severity="secondary"
                                rounded
                            />
                        </div>
                    </template>
                </Column>
            </DataTable>

            <div
                v-else
                class="rounded-[1rem] border border-dashed p-4 text-sm"
                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
            >
                Bảng chi tiết đang được thu gọn để ưu tiên phần tóm tắt và các header đã nhận diện.
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
            Chưa có dữ liệu xem trước cho sheet Tổng hợp. Sau khi cấu trúc tệp Excel hợp lệ, khu vực này sẽ hiển thị dữ liệu đã xử lý riêng cho sheet Tổng hợp.
        </div>
    </div>
</template>
