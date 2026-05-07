<script setup lang="ts">
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import { formatImportNumber } from '@/Services/imports/useImportNumberFormatter';

defineProps<{
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
            batchId: number;
            batchCode: string;
            customerCode: string;
            customerFullName: string;
            month: string;
        };
    } | null;
}>();

const formatPreviewValue = (content: string, value: string): string => {
    if (value.trim() === '') {
        return '-';
    }

    return content === 'Bằng chữ'
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
                    <div class="flex flex-wrap gap-2.5">
                        <Tag :value="`Batch: ${preview.sample.batchCode}`" severity="info" rounded />
                        <Tag :value="`Khách: ${preview.sample.customerCode}`" severity="contrast" rounded />
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
                                        v-for="row in preview.rows"
                                        :key="`${row.numbering}-${row.content}-${row.indentLevel}`"
                                        class="border-t"
                                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                                    >
                                        <td class="px-4 py-3 align-top" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ row.numbering || '—' }}
                                        </td>
                                        <td class="px-4 py-3 align-top" :style="{ paddingLeft: `${1 + row.indentLevel * 1.25}rem`, color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }">
                                            <div>{{ row.content }}</div>
                                            <div v-if="row.columnKey" class="mt-1 text-xs" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                [{{ row.columnKey }}]
                                            </div>
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
