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
            rowType?: 'program-loop' | 'blank' | 'total' | 'in-words' | string;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: string;
            fontWeight: string;
        }>;
    } | null;
}>();

const isSwitchingCustomer = ref(false);

const handlePreviewCustomerChange = (recordId: number | null): void => {
    isSwitchingCustomer.value = true;

    router.get(
        route('templates.index'),
        recordId ? { khoan_npp_preview_record: recordId } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['khoanNppTablePreview', 'khoanNppPreviewCustomers', 'selectedKhoanNppPreviewRecordId'],
            onFinish: () => {
                isSwitchingCustomer.value = false;
            },
        },
    );
};

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

const shouldHideProgramItem = (programItem: { content?: string; quantity?: string; supportRate?: string; amount?: string }): boolean =>
    isZeroOrBlank(programItem.content ?? '')
    && isZeroOrBlank(programItem.quantity ?? '')
    && isZeroOrBlank(programItem.supportRate ?? '')
    && isZeroOrBlank(programItem.amount ?? '');

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

const effectivePreviewRows = computed(() => {
    const preview = props.preview;

    if (!preview) {
        return [];
    }

    if (!props.draftSection?.rows?.length) {
        return preview.rows;
    }

    let numbering = 0;
    const programItems = preview.sampleData.programItems ?? [];

    return props.draftSection.rows.flatMap((row) => {
        if (row.rowType === 'program-loop') {
            return programItems.flatMap((programItem) => {
                if (shouldHideProgramItem(programItem)) {
                    return [];
                }

                numbering += 1;

                return [{
                    rowType: 'program-loop',
                    numbering: String(numbering),
                    content: (programItem.content ?? '').trim(),
                    quantity: (programItem.quantity ?? '').trim(),
                    supportRate: (programItem.supportRate ?? '').trim(),
                    amount: (programItem.amount ?? '').trim(),
                    fontWeight: row.fontWeight,
                    styleRole: 'neutral',
                }];
            });
        }

        if (row.rowType === 'blank') {
            return [{
                rowType: 'blank',
                numbering: '',
                content: '',
                quantity: '',
                supportRate: '',
                amount: '',
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            }];
        }

        if (row.rowType === 'total') {
            return [{
                rowType: 'total',
                numbering: '',
                content: row.content.trim() || 'Cộng',
                quantity: '',
                supportRate: '',
                amount: preview.sampleData.grandTotal ?? '',
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            }];
        }

        if (row.rowType === 'in-words') {
            return [{
                rowType: 'in-words',
                numbering: '',
                content: row.content.trim() || 'Bằng chữ:',
                quantity: '',
                supportRate: '',
                amount: preview.sampleData.totalInWords ?? '',
                fontWeight: row.fontWeight,
                styleRole: 'neutral',
            }];
        }

        return [];
    });
});

const formatAmount = (value: string, rowType: string): string => {
    if (value.trim() === '') {
        return '';
    }

    return rowType === 'in-words'
        ? value
        : formatImportNumber(value, value);
};

const formatCellNumber = (value: string): string => {
    if (value.trim() === '') {
        return '';
    }

    return formatImportNumber(value, value);
};
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Preview table Khoán NPP
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Table Chương trình khoán đặc biệt render từ dữ liệu aggregate thật
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview này lấy dữ liệu từ <code>programItems[]</code> của sheet `Khoán NPP`. STT auto tăng theo số CT render thực tế, và CT sẽ bị ẩn nếu cả `Nội dung | SL | đ/kg | Thành tiền` đều bằng `0` hoặc rỗng.
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
                    Chưa có đủ dữ liệu để preview table Chương trình khoán đặc biệt. Cần có section `Khoán NPP` trong template và ít nhất một batch aggregate có dữ liệu từ sheet Khoán NPP.
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
                            Preview đang bind vào record aggregate thật của khách đã chọn. Khi đổi khách, bảng sẽ reload lại đúng <code>programItems[]</code>, <code>grandTotal</code> và <code>totalInWords</code> của khách đó.
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
                                        <th class="px-4 py-3 text-left font-semibold">Nội dung chương trình</th>
                                        <th class="px-4 py-3 text-right font-semibold">Sản lượng</th>
                                        <th class="px-4 py-3 text-right font-semibold">Mức hỗ trợ</th>
                                        <th class="px-4 py-3 text-right font-semibold">Tổng tiền (VNĐ)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in effectivePreviewRows"
                                        :key="`${row.rowType}-${row.numbering}-${row.content}-${row.amount}`"
                                        class="border-t"
                                        :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                                    >
                                        <td class="px-4 py-3 align-top" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ row.numbering }}
                                        </td>
                                        <td
                                            class="px-4 py-3 align-top"
                                            :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 400 }"
                                        >
                                            {{ row.content }}
                                        </td>
                                        <td class="px-4 py-3 text-right align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ formatCellNumber(row.quantity) }}
                                        </td>
                                        <td class="px-4 py-3 text-right align-top" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ formatCellNumber(row.supportRate) }}
                                        </td>
                                        <td
                                            class="px-4 py-3 text-right align-top"
                                            :style="{ color: 'var(--dashboard-strong-text)', fontWeight: row.fontWeight === 'bold' ? 700 : 500 }"
                                        >
                                            {{ formatAmount(row.amount, row.rowType) }}
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
