<script setup lang="ts">
import type { KhoanNppPreview } from '@/Services/imports/useKhoanNppPreviewFlow';
import { useImportDetailTableVisibility } from '@/Services/imports/useImportDetailTableVisibility';
import { formatImportNumber } from '@/Services/imports/useImportNumberFormatter';
import { useKhoanNppPreviewPresentation } from '@/Services/imports/useKhoanNppPreviewPresentation';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import { computed } from 'vue';

const props = defineProps<{
    preview: KhoanNppPreview | null;
    isLoading: boolean;
    errorMessage: string;
    canPreview: boolean;
}>();

const emit = defineEmits<{
    load: [];
}>();

const presentation = computed(() => useKhoanNppPreviewPresentation(props.preview));
const { showDetailsTable, detailToggleLabel, detailToggleIcon, detailToggleHelper, toggleDetailsTable } = useImportDetailTableVisibility();
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Xử lý riêng cho sheet Khoán NPP
                </p>
                <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Xem trước dữ liệu khoán theo từng khách hàng
                </p>
            </div>

            <Button
                :label="errorMessage ? 'Thử xem lại' : 'Xem sheet Khoán NPP'"
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
                    <p class="text-sm font-medium">Sheet đang xem</p>
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
                    <p class="text-sm font-medium">Block chương trình</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ formatImportNumber(preview.programBlockCount) }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Header cố định của sheet Khoán NPP</p>
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

            <div
                v-if="presentation"
                class="rounded-[1rem] border p-4 text-sm leading-6"
                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
            >
                <p :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ presentation.programBlockSummary }}
                </p>
                <p class="mt-2">
                    {{ presentation.usageCoverageSummary }}
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
                <Column field="feedCategory" header="Thức ăn chăn nuôi" />
                <Column header="Tổng cộng">
                    <template #body="{ data }">
                        {{ formatImportNumber(data.grandTotal) }}
                    </template>
                </Column>
                <Column header="Phạm vi CT đang dùng">
                    <template #body="{ data }">
                        <div
                            v-if="presentation"
                            class="space-y-2 text-sm"
                        >
                            <p>{{ presentation.recordsByCustomerCode[data.customerCode]?.usageSummary }}</p>
                            <div class="flex flex-wrap gap-2">
                                <Tag
                                    :value="`Số CT có dữ liệu: ${formatImportNumber(presentation.recordsByCustomerCode[data.customerCode]?.usedProgramCount ?? 0)}`"
                                    severity="contrast"
                                    rounded
                                />
                                <Tag
                                    :value="`CT cao nhất: ${presentation.recordsByCustomerCode[data.customerCode]?.maxProgramIndex ? `CT${formatImportNumber(presentation.recordsByCustomerCode[data.customerCode]?.maxProgramIndex)}` : '-'}`"
                                    severity="info"
                                    rounded
                                />
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Chương trình khoán">
                    <template #body="{ data }">
                        <div
                            v-if="presentation"
                            class="space-y-2"
                        >
                            <div
                                v-for="item in presentation.recordsByCustomerCode[data.customerCode]?.programItems ?? []"
                                :key="`${data.customerCode}-${item.programIndex}`"
                                class="rounded-[0.85rem] border p-3"
                                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                            >
                                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ item.title }}
                                </p>
                                <p class="mt-1 text-sm leading-6">
                                    {{ item.content }}
                                </p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <Tag :value="item.quantityLabel" severity="info" rounded />
                                    <Tag :value="item.supportRateLabel" severity="warn" rounded />
                                    <Tag :value="item.amountLabel" severity="success" rounded />
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
                Bảng chi tiết đang được thu gọn để ưu tiên phần phạm vi CT và mức độ sử dụng chương trình.
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
            Chưa có dữ liệu xem trước cho sheet Khoán NPP. Sau khi cấu trúc tệp Excel hợp lệ, khu vực này sẽ hiển thị dữ liệu khoán đã được xử lý riêng.
        </div>
    </div>
</template>
