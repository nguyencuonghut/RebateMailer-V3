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
            rowType: string;
            numbering: string;
            content: string;
            quantity: string;
            supportRate: string;
            amount: string;
            fontWeight: string;
            styleRole: string;
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
        sampleData: {
            programItems: Array<{
                programIndex?: number;
                content?: string;
                quantity?: string;
                supportRate?: string;
                amount?: string;
            }>;
            grandTotal: string;
            totalInWords: string;
        };
    } | null;
    bindingOptions: Array<{
        key: string;
        label: string;
        valuePreview: string;
        quantityPreview?: string;
        supportRatePreview?: string;
        amountPreview?: string;
        defaultValueColumn?: 'quantity' | 'supportRate' | 'amount';
    }>;
    draftSection?: {
        type: string;
        rows?: Array<{
            content: string;
            rowType?: string;
            columnKey?: string | null;
            valueColumn?: 'quantity' | 'supportRate' | 'amount' | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: 'parent' | 'child' | 'neutral';
            fontWeight: 'bold' | 'regular';
        }>;
    } | null;
}>();

const valueMap = computed(() =>
    new Map((props.bindingOptions ?? []).map((option) => [option.key, {
        quantity: option.quantityPreview ?? '',
        supportRate: option.supportRatePreview ?? '',
        amount: option.amountPreview ?? '',
        defaultValueColumn: option.defaultValueColumn ?? 'amount',
    }]))
);

const isBlank = (value: string): boolean => value.trim() === '';

const isZeroOrBlank = (value: string): boolean => {
    const normalized = value.replaceAll(',', '').trim();

    if (normalized === '') {
        return true;
    }

    if (!/^-?\d+(?:\.\d+)?$/.test(normalized)) {
        return false;
    }

    return Number(normalized) === 0;
};

const shouldSkipProgramItem = (programItem: { content?: string; quantity?: string; supportRate?: string; amount?: string }): boolean =>
    isBlank(programItem.content ?? '')
        && isBlank(programItem.quantity ?? '')
        && isBlank(programItem.supportRate ?? '')
        && isBlank(programItem.amount ?? '');

const resolveTargetColumn = (
    rowValueColumn: 'quantity' | 'supportRate' | 'amount' | null | undefined,
    defaultValueColumn: 'quantity' | 'supportRate' | 'amount',
): 'quantity' | 'supportRate' | 'amount' =>
    rowValueColumn && ['quantity', 'supportRate', 'amount'].includes(rowValueColumn)
        ? rowValueColumn
        : defaultValueColumn;

const resolveDisplayValue = (
    entry: { quantity: string; supportRate: string; amount: string; defaultValueColumn: 'quantity' | 'supportRate' | 'amount' },
    targetColumn: 'quantity' | 'supportRate' | 'amount',
): string => {
    const targetValue = (entry[targetColumn] ?? '').trim();

    if (targetValue !== '') {
        return targetValue;
    }

    const defaultValue = (entry[entry.defaultValueColumn] ?? '').trim();

    if (defaultValue !== '') {
        return defaultValue;
    }

    for (const candidateColumn of ['quantity', 'supportRate', 'amount'] as const) {
        const candidateValue = (entry[candidateColumn] ?? '').trim();

        if (candidateValue !== '') {
            return candidateValue;
        }
    }

    return '';
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

const effectivePreviewRows = computed(() => {
    const preview = props.preview;

    if (!preview) {
        return [];
    }

    if (!props.draftSection?.rows?.length) {
        return preview.rows;
    }

    const rows: Array<{
        rowType: string;
        numbering: string;
        content: string;
        quantity: string;
        supportRate: string;
        amount: string;
        fontWeight: string;
        styleRole: string;
    }> = [];
    let parentCounter = 0;
    let childCounter = 0;

    for (const row of props.draftSection.rows) {
        const rowType = row.rowType ?? 'blank';
        const content = row.content?.trim() ?? '';
        const columnKey = row.columnKey?.trim() || '';

        if (rowType === 'value-row' || rowType === 'parent' || rowType === 'child-value') {
            const entry = valueMap.value.get(columnKey);

            if (!entry) {
                continue;
            }

            const valueColumn = resolveTargetColumn(row.valueColumn, entry.defaultValueColumn);
            const resolvedValue = resolveDisplayValue(entry, valueColumn);
            const quantity = valueColumn === 'quantity' ? resolvedValue : '';
            const supportRate = valueColumn === 'supportRate' ? resolvedValue : '';
            const amount = valueColumn === 'amount' ? resolvedValue : '';

            if (row.hideWhenValueZero && isZeroOrBlank(quantity) && isZeroOrBlank(supportRate) && isZeroOrBlank(amount)) {
                continue;
            }

            if (rowType === 'parent') {
                parentCounter += 1;
                childCounter = 0;
            } else if (rowType === 'child-value') {
                childCounter += 1;
            }

            rows.push({
                rowType,
                numbering: rowType === 'parent'
                    ? toRoman(parentCounter)
                    : (rowType === 'child-value' ? String(childCounter) : ''),
                content,
                quantity,
                supportRate,
                amount,
                fontWeight: row.fontWeight,
                styleRole: rowType === 'parent' ? 'parent' : (rowType === 'child-value' ? 'child' : 'neutral'),
            });

            continue;
        }

        if (rowType === 'child-program-loop') {
            for (const programItem of preview.sampleData.programItems ?? []) {
                if (shouldSkipProgramItem(programItem)) {
                    continue;
                }

                childCounter += 1;

                rows.push({
                    rowType,
                    numbering: String(childCounter),
                    content: programItem.content?.trim() ?? '',
                    quantity: programItem.quantity?.trim() ?? '',
                    supportRate: programItem.supportRate?.trim() ?? '',
                    amount: programItem.amount?.trim() ?? '',
                    fontWeight: row.fontWeight,
                    styleRole: 'child',
                });
            }

            continue;
        }

        if (rowType === 'blank') {
            rows.push({
                rowType,
                numbering: '',
                content: '',
                quantity: '',
                supportRate: '',
                amount: '',
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            });

            continue;
        }

        if (rowType === 'total') {
            rows.push({
                rowType,
                numbering: '',
                content: content || 'Cộng',
                quantity: '',
                supportRate: '',
                amount: preview.sampleData.grandTotal,
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            });

            continue;
        }

        if (rowType === 'in-words') {
            rows.push({
                rowType,
                numbering: '',
                content: content || 'Bằng chữ:',
                quantity: '',
                supportRate: '',
                amount: preview.sampleData.totalInWords,
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            });
        }
    }

    return rows;
});

const formatCell = (value: string, rowType: string): string => {
    if (value.trim() === '') {
        return rowType === 'in-words' ? '' : '-';
    }

    return rowType === 'in-words'
        ? value
        : formatImportNumber(value);
};
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Preview table Key Account
                        </p>
                        <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ preview?.title ?? 'Chiết khấu Key Account' }}
                        </h2>
                    </div>

                    <Tag
                        :value="preview ? `Sheet: ${preview.sourceSheet}` : 'Chưa có preview'"
                        :severity="preview ? 'info' : 'secondary'"
                        rounded
                    />
                </div>

                <div class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                    Preview này dùng dữ liệu thật từ aggregated records của sheet `Key Account`. Các dòng CT được lặp từ `Nội dung CT i | SL | đ/kg | Thành tiền`, và toàn bộ preview đang bám vào preview context chung của page.
                </div>

                <div v-if="preview?.sample" class="flex flex-wrap gap-2">
                    <Tag :value="preview.sample.customerFullName || preview.sample.customerCode" severity="contrast" rounded />
                    <Tag :value="`Batch ${preview.sample.batchCode}`" severity="info" rounded />
                    <Tag :value="`Tháng ${preview.sample.month}`" severity="success" rounded />
                </div>

                <div v-if="preview?.errors?.length" class="rounded-[1.2rem] border px-4 py-3" :style="{ borderColor: 'rgba(245, 158, 11, 0.36)', background: 'rgba(245, 158, 11, 0.08)' }">
                    <p class="text-sm font-semibold text-amber-400">Lỗi preview</p>
                    <ul class="mt-2 space-y-1 text-sm leading-6" :style="{ color: 'var(--dashboard-strong-text)' }">
                        <li v-for="error in preview.errors" :key="error">
                            {{ error }}
                        </li>
                    </ul>
                </div>

                <div v-if="preview" class="overflow-x-auto rounded-[1.4rem] border" :style="{ borderColor: 'var(--dashboard-panel-border)' }">
                    <table class="min-w-full">
                        <thead :style="{ background: 'var(--dashboard-card-bg)' }">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">STT</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Nội dung</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Sản lượng</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Mức hỗ trợ</th>
                                <th class="px-4 py-3 text-right text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">Tổng</th>
                            </tr>
                        </thead>
                        <tbody>
                                    <tr
                                        v-for="row in effectivePreviewRows"
                                        :key="`${row.rowType}-${row.numbering}-${row.content}-${row.quantity}-${row.supportRate}-${row.amount}`"
                                        class="border-t"
                                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                                    >
                                        <template v-if="row.rowType === 'total' || row.rowType === 'in-words'">
                                            <td
                                                class="px-4 py-3 text-sm align-top"
                                                :style="{ color: 'var(--dashboard-muted-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }"
                                            >
                                                {{ row.numbering }}
                                            </td>
                                            <td
                                                colspan="3"
                                                class="px-4 py-3 text-sm align-top"
                                                :class="row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal'"
                                                :style="{ color: 'var(--dashboard-strong-text)' }"
                                            >
                                                {{ row.content }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ formatCell(row.amount, row.rowType) }}
                                            </td>
                                        </template>
                                        <template v-else>
                                            <td
                                                class="px-4 py-3 text-sm align-top"
                                                :style="{ color: 'var(--dashboard-muted-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }"
                                            >
                                                {{ row.numbering }}
                                            </td>
                                            <td class="px-4 py-3 text-sm align-top" :class="row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal'" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ row.content }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ formatCell(row.quantity, row.rowType) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ formatCell(row.supportRate, row.rowType) }}
                                            </td>
                                            <td class="px-4 py-3 text-right text-sm align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ formatCell(row.amount, row.rowType) }}
                                            </td>
                                        </template>
                                    </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                    Chưa có preview dữ liệu thật cho part `Key Account`.
                </div>
            </div>
        </template>
    </Card>
</template>
