<script setup lang="ts">
import { computed } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
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

const isInWordsRow = (row: { content: string; rowType?: string; columnKey?: string | null }): boolean => {
    const rowType = row.rowType?.trim() ?? '';
    const content = (row.content ?? '').trim();
    const columnKey = row.columnKey?.trim() ?? '';

    return rowType === 'in-words'
        || rowType === 'text'
        || columnKey === 'Bằng chữ'
        || content.startsWith('Bằng chữ');
};

const formatPreviewValue = (row: { content: string; rowType?: string; columnKey?: string | null }, value: string): string => {
    if (value.trim() === '') {
        return '-';
    }

    return isInWordsRow(row)
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

const toRoman = (value: number): string => {
    const map: Array<[number, string]> = [
        [1000, 'M'],
        [900, 'CM'],
        [500, 'D'],
        [400, 'CD'],
        [100, 'C'],
        [90, 'XC'],
        [50, 'L'],
        [40, 'XL'],
        [10, 'X'],
        [9, 'IX'],
        [5, 'V'],
        [4, 'IV'],
        [1, 'I'],
    ];

    let remaining = value;
    let roman = '';

    for (const [number, glyph] of map) {
        while (remaining >= number) {
            roman += glyph;
            remaining -= number;
        }
    }

    return roman;
};

const recalculateVisibleNumbering = <
    T extends {
        rowType?: string;
        numbering: string;
    },
>(rows: T[]): T[] => {
    let parentCounter = 0;
    let childCounter = 0;

    return rows.map((row) => {
        const rowType = row.rowType?.trim() ?? '';

        if (rowType === 'parent') {
            parentCounter += 1;
            childCounter = 0;

            return {
                ...row,
                numbering: toRoman(parentCounter),
            };
        }

        if (rowType === 'child') {
            childCounter += 1;

            return {
                ...row,
                numbering: String(childCounter),
            };
        }

        return {
            ...row,
            numbering: '',
        };
    });
};

const effectivePreviewRows = computed(() => {
    if (!props.preview) {
        return [];
    }

    if (!props.draftSection?.rows?.length) {
        return recalculateVisibleNumbering(props.preview.rows);
    }

    const valueMap = new Map((props.bindingOptions ?? []).map((option) => [option.key, option.valuePreview]));
    const visibleRows = props.draftSection.rows.flatMap((row) => {
        const rowType = row.rowType?.trim() ?? 'blank';
        const content = (row.content ?? '').trim();
        const columnKey = row.columnKey?.trim() || content;
        const value = valueMap.get(columnKey) ?? '';
        const hideWhenZero = row.hideWhenValueZero ?? false;

        if ((rowType === 'parent' || rowType === 'child' || rowType === 'data') && hideWhenZero && shouldHideWhenValueZero(value)) {
            return [];
        }

        if (rowType === 'blank') {
            return [{
                ...row,
                rowType,
                numbering: '',
                value: '',
            }];
        }

        if (rowType === 'total' || rowType === 'text') {
            return [{
                ...row,
                rowType,
                numbering: '',
                value,
            }];
        }

        if (rowType === 'parent' || rowType === 'child' || rowType === 'data') {
            return [{
                ...row,
                rowType,
                columnKey,
                value,
            }];
        }

        return [];
    });

    return recalculateVisibleNumbering(visibleRows);
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
                    <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview đang bind vào preview context chung của page: một batch import và một khách hàng aggregate được chọn ở đầu màn hình. Binding options của bảng này cũng đang bám đúng record đó.
                    </p>

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
                                        <td
                                            class="px-4 py-3 align-top"
                                            :style="{ color: 'var(--dashboard-muted-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }"
                                        >
                                            {{ row.numbering }}
                                        </td>
                                        <td class="px-4 py-3 align-top" :style="{ paddingLeft: `${1 + (row.indentLevel ?? 0) * 1.25}rem`, color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }">
                                            <div>{{ row.content }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-right align-top" :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 500 }">
                                            {{ formatPreviewValue(row, row.value) }}
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
