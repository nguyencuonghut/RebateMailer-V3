<script setup lang="ts">
import type { CamCaPreview } from '@/Services/imports/useCamCaPreviewFlow';
import Button from 'primevue/button';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Message from 'primevue/message';
import Tag from 'primevue/tag';

defineProps<{
    preview: CamCaPreview | null;
    isLoading: boolean;
    errorMessage: string;
    canPreview: boolean;
}>();

const emit = defineEmits<{
    load: [];
}>();
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                    Parser riêng cho sheet Cám cá
                </p>
                <p class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    Preview dữ liệu cám cá theo cột rời rạc và các cặp CT | Thành tiền
                </p>
            </div>

            <Button
                :label="errorMessage ? 'Thử preview lại' : 'Preview sheet Cám cá'"
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
                        {{ preview.recordCount }}
                    </p>
                </div>

                <div class="rounded-[1rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <p class="text-sm font-medium">Số cặp CT | Thành tiền</p>
                    <p class="mt-2 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        {{ preview.programPairCount }}
                    </p>
                </div>
            </div>

            <div class="space-y-2">
                <p class="text-sm font-medium">Header cố định của sheet Cám cá</p>
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

            <DataTable
                :value="preview.records"
                paginator
                :rows="10"
                responsive-layout="scroll"
                class="p-datatable-sm"
            >
                <Column field="customerFullName" header="Mã & tên khách hàng" />
                <Column field="month" header="Tháng" />
                <Column field="totalQuantity" header="Tổng sản lượng" />
                <Column field="grandTotal" header="Tổng cộng" />
                <Column header="Các cặp CT | Thành tiền">
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
                                    <Tag :value="`Thành tiền: ${item.amount || '-'}`" severity="success" rounded />
                                </div>
                            </div>
                            <p v-if="data.programItems.length === 0" class="text-sm">
                                Bản ghi này không có dữ liệu CT.
                            </p>
                        </div>
                    </template>
                </Column>
                <Column header="Các cột rời rạc có giá trị">
                    <template #body="{ data }">
                        <div class="space-y-2">
                            <div class="flex flex-wrap gap-2">
                                <Tag
                                    v-for="item in data.discreteItems"
                                    :key="`${data.customerCode}-${item.label}`"
                                    :value="`${item.label}: ${item.value}`"
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
            </DataTable>

            <p class="text-sm leading-6">
                {{ preview.nextStep }}
            </p>
        </div>

        <div
            v-else
            class="rounded-[1.2rem] border border-dashed p-4 text-sm"
            :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
        >
            Chưa có preview sheet Cám cá. Sau khi workbook boundary hợp lệ, khu vực này sẽ hiển thị dữ liệu cám cá đã được parse riêng.
        </div>
    </div>
</template>
