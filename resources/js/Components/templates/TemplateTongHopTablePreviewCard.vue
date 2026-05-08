<script setup lang="ts">
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import { router } from '@inertiajs/vue3';
import { formatImportNumber } from '@/Services/imports/useImportNumberFormatter';

const props = defineProps<{
    preview: {
        title: string;
        sourceSheet: string;
        rows: Array<{
            content: string;
            indentLevel: number;
            rowType?: string;
            columnKey?: string | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: string;
            fontWeight: string;
            value: string;
        }>;
        errors: string[];
        sample: {
            recordId: number;
            batchId: number;
            batchCode: string;
            customerCode: string;
            customerFullName: string;
            month: string;
        };
    } | null;
    previewCustomers: Array<{
        recordId: number;
        customerCode: string;
        customerFullName: string;
        label: string;
        batchCode: string;
        month: string;
    }>;
    selectedRecordId: number | null;
    draftSection?: {
        type: string;
        rows?: Array<{
            content: string;
            indentLevel?: number;
            rowType?: string;
            columnKey?: string | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: string;
            fontWeight: string;
        }>;
    } | null;
    bindingOptions?: Array<{
        key: string;
        label: string;
        valuePreview: string;
    }>;
}>();

const isSwitchingCustomer = ref(false);

const handlePreviewCustomerChange = (recordId: number | null): void => {
    isSwitchingCustomer.value = true;

    router.get(
        route('templates.index'),
        recordId ? { tong_hop_preview_record: recordId } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['tongHopTablePreview', 'tongHopPreviewCustomers', 'selectedTongHopPreviewRecordId', 'tongHopBindingOptions'],
            onFinish: () => {
                isSwitchingCustomer.value = false;
            },
        },
    );
};

const previewCustomerLabel = computed(() => {
    const sample = props.preview?.sample;

    if (!sample) {
        return '';
    }

    if (sample.customerFullName.trim() === '') {
        return sample.customerCode;
    }

    if (sample.customerCode && sample.customerFullName.startsWith(sample.customerCode)) {
        return sample.customerFullName;
    }

    return `${sample.customerCode} - ${sample.customerFullName}`;
});

const formatPreviewValue = (content: string, value: string): string => {
    if (value.trim() === '') {
        return '-';
    }

    return content === 'Bằng chữ'
        ? value
        : formatImportNumber(value, value);
};

const shouldHideWhenValueZero = (value: string): boolean => {
    const normalized = value.replaceAll(',', '').trim();

    if (normalized === '') {
        return true;
    }

    if (!/^-?\d+(?:\.\d+)?$/.test(normalized)) {
        return false;
    }

    return Number(normalized) === 0;
};

const effectivePreviewRows = computed(() => {
    if (!props.preview) {
        return [];
    }

    if (!props.draftSection?.rows?.length) {
        return props.preview.rows;
    }

    const valueMap = new Map((props.bindingOptions ?? []).map((option) => [option.key, option.valuePreview]));

    return props.draftSection.rows.flatMap((row) => {
        const columnKey = row.columnKey?.trim() || row.content.trim();
        const value = valueMap.get(columnKey) ?? '';

        if (row.hideWhenValueZero && shouldHideWhenValueZero(value)) {
            return [];
        }

        return [{
            ...row,
            value,
        }];
    });
});
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Preview table Tổng hợp
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Table Chế độ tháng render từ dữ liệu aggregate thật
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview này chỉ dùng dữ liệu đã aggregate từ sheet Tổng hợp. Cột Nội dung lấy từ các row của builder và chỉ map theo label thật đang có trong dữ liệu đã persist.
                    </p>
                </div>

                <div
                    v-if="!preview"
                    class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                    :style="{
                        borderColor: 'var(--dashboard-panel-border)',
                        background: 'var(--dashboard-card-bg)',
                        color: 'var(--dashboard-muted-text)',
                    }"
                >
                    Chưa có đủ dữ liệu để preview table Chế độ tháng. Cần có section `Tổng hợp` trong template và ít nhất một batch aggregate có dữ liệu từ sheet Tổng hợp.
                </div>

                <template v-else>
                    <div class="grid gap-3 xl:grid-cols-[minmax(0,22rem)_1fr] xl:items-end">
                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                Chọn khách từ dữ liệu đã parse
                            </label>
                            <Select
                                :model-value="selectedRecordId"
                                :options="previewCustomers"
                                option-label="label"
                                option-value="recordId"
                                filter
                                show-clear
                                fluid
                                :loading="isSwitchingCustomer"
                                placeholder="Tìm theo mã hoặc tên khách hàng"
                                @update:model-value="handlePreviewCustomerChange"
                            />
                        </div>

                        <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Preview đang bind vào record aggregate thật của khách đã chọn. Khi đổi khách, bảng và cả value preview của các binding key sẽ reload theo đúng dữ liệu `Tổng hợp` của khách đó.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        <Tag :value="`Batch: ${preview.sample.batchCode}`" severity="info" rounded />
                        <Tag :value="`Khách: ${previewCustomerLabel}`" severity="contrast" rounded />
                        <Tag :value="`Sheet: ${preview.sourceSheet}`" severity="secondary" rounded />
                        <Tag :value="`Tháng: ${preview.sample.month || 'Chưa có'}`" severity="secondary" rounded />
                    </div>

                    <div class="overflow-hidden rounded-[1.4rem] border" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                        <div class="px-4 py-3" :style="{ background: 'var(--dashboard-card-bg)' }">
                            <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ preview.title }}
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-sm">
                                <thead :style="{ background: 'var(--dashboard-card-bg)' }">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">STT</th>
                                        <th class="px-4 py-3 text-left font-semibold">Nội dung</th>
                                        <th class="px-4 py-3 text-right font-semibold">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in effectivePreviewRows"
                                        :key="`${row.numbering}-${row.content}-${row.indentLevel}`"
                                        class="border-t"
                                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                                    >
                                        <td class="px-4 py-3 align-top" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ row.numbering }}
                                        </td>
                                        <td class="px-4 py-3 align-top" :style="{ paddingLeft: `${1 + (row.indentLevel ?? 0) * 1.25}rem`, color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }">
                                            <div>{{ row.content }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right align-top" :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 500 }">
                                            {{ formatPreviewValue(row.content, row.value) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div
                        v-if="preview.errors.length > 0"
                        class="rounded-[1.2rem] border p-4"
                        :style="{ borderColor: 'rgba(239, 68, 68, 0.28)', background: 'rgba(239, 68, 68, 0.08)' }"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-500">
                            Lỗi preview
                        </p>
                        <ul class="mt-2 space-y-2">
                            <li
                                v-for="error in preview.errors"
                                :key="error"
                                class="text-sm leading-6"
                                :style="{ color: 'var(--dashboard-strong-text)' }"
                            >
                                {{ error }}
                            </li>
                        </ul>
                    </div>
                </template>
            </div>
        </template>
    </Card>
</template>
