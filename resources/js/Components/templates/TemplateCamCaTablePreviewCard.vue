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
            value: string;
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
    }>;
    draftSection?: {
        type: string;
        rows?: Array<{
            content: string;
            rowType?: string;
            columnKey?: string | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: 'parent' | 'child' | 'neutral';
            fontWeight: 'bold' | 'regular';
        }>;
    } | null;
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

const valueMap = computed(() => new Map((props.bindingOptions ?? []).map((option) => [option.key, option.valuePreview])));

const isZeroOrBlank = (value: string): boolean => {
    const normalized = value.replaceAll(',', '').trim();

    if (normalized === '') {
        return true;
    }

    if (!/^-?\\d+(?:\\.\\d+)?$/.test(normalized)) {
        return false;
    }

    return Number(normalized) === 0;
};

const shouldHideProgramItem = (programItem: { content?: string; amount?: string }): boolean =>
    isZeroOrBlank(programItem.content ?? '') && isZeroOrBlank(programItem.amount ?? '');

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
        value: string;
        fontWeight: string;
        styleRole: string;
    }> = [];
    let parentCounter = 0;
    let childCounter = 0;

    for (const row of props.draftSection.rows) {
        const rowType = row.rowType ?? 'blank';
        const content = row.content?.trim() ?? '';
        const columnKey = row.columnKey?.trim() || '';

        if (rowType === 'value-row') {
            const value = valueMap.value.get(columnKey) ?? '';

            if (row.hideWhenValueZero && isZeroOrBlank(value)) {
                continue;
            }

            rows.push({
                rowType,
                numbering: '',
                content,
                value,
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            });

            continue;
        }

        if (rowType === 'parent') {
            const value = valueMap.value.get(columnKey) ?? '';

            if (row.hideWhenValueZero && isZeroOrBlank(value)) {
                continue;
            }

            parentCounter += 1;
            childCounter = 0;

            rows.push({
                rowType,
                numbering: row.numbering || ['I', 'II', 'III', 'IV', 'V'][parentCounter - 1] || String(parentCounter),
                content,
                value,
                fontWeight: row.fontWeight,
                styleRole: 'parent',
            });

            continue;
        }

        if (rowType === 'child-value') {
            const value = valueMap.value.get(columnKey) ?? '';

            if (row.hideWhenValueZero && isZeroOrBlank(value)) {
                continue;
            }

            childCounter += 1;

            rows.push({
                rowType,
                numbering: String(childCounter),
                content,
                value,
                fontWeight: row.fontWeight,
                styleRole: 'child',
            });

            continue;
        }

        if (rowType === 'child-program-loop') {
            for (const programItem of preview.sampleData.programItems ?? []) {
                if (shouldHideProgramItem(programItem)) {
                    continue;
                }

                childCounter += 1;

                rows.push({
                    rowType,
                    numbering: String(childCounter),
                    content: (programItem.content ?? '').trim(),
                    value: (programItem.amount ?? '').trim(),
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
                value: '',
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
                value: preview.sampleData.grandTotal ?? '',
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
                value: preview.sampleData.totalInWords ?? '',
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            });
        }
    }

    return rows;
});

const formatPreviewValue = (rowType: string, value: string): string => {
    if (value.trim() === '') {
        return '';
    }

    return rowType === 'in-words'
        ? value
        : formatImportNumber(value, value);
};
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Preview table Cám cá
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Table Chiết khấu cám cá render từ dữ liệu aggregate thật
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview này trộn 2 nguồn dữ liệu của sheet `Cám cá`: các giá trị đơn từ cột đã parse và các CT sinh ra từ <code>programItems[]</code>.
                    </p>
                </div>

                <div
                    v-if="!preview"
                    class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                >
                    Chưa có đủ dữ liệu để preview table Chiết khấu cám cá. Cần có section `Cám cá` trong template và ít nhất một batch aggregate có dữ liệu từ sheet Cám cá.
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
                                        :key="`${row.rowType}-${row.numbering}-${row.content}-${row.value}`"
                                        class="border-t"
                                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                                    >
                                        <td
                                            class="px-4 py-3 align-top"
                                            :style="{ color: 'var(--dashboard-muted-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }"
                                        >
                                            {{ row.numbering }}
                                        </td>
                                        <td class="px-4 py-3 align-top" :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }">
                                            {{ row.content }}
                                        </td>
                                        <td class="px-4 py-3 text-right align-top" :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 500 }">
                                            {{ formatPreviewValue(row.rowType, row.value) }}
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
