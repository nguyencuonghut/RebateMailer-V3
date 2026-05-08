<script setup lang="ts">
import { computed, ref } from 'vue';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Select from 'primevue/select';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    preview: {
        templateText: string;
        renderedText: string;
        errors: string[];
        sample: {
            recordId: number;
            batchId: number;
            batchCode: string;
            customerCode: string;
            customerFullName: string;
            month: string;
            address: string;
            feedCategory: string;
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
}>();

const isSwitchingCustomer = ref(false);

const handlePreviewCustomerChange = (recordId: number | null): void => {
    isSwitchingCustomer.value = true;

    router.get(
        route('templates.index'),
        recordId ? { greeting_preview_record: recordId } : {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['greetingPreview', 'greetingPreviewCustomers', 'selectedGreetingPreviewRecordId'],
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
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Preview lời chào
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Lời chào render từ dữ liệu aggregate thật
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview này chỉ render phần lời chào ở đầu body email, dùng đúng dữ liệu aggregate đã persist trong hệ thống.
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
                    Chưa có đủ dữ liệu thật để preview lời chào. Cần có template hiện tại và ít nhất một batch import đã aggregate thành công.
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
                            Preview lời chào sẽ render lại theo đúng record aggregate thật của khách đã chọn.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        <Tag :value="`Batch: ${preview.sample.batchCode}`" severity="info" rounded />
                        <Tag :value="`Khách: ${previewCustomerLabel}`" severity="contrast" rounded />
                        <Tag :value="`Địa chỉ: ${preview.sample.address || 'Chưa có'}`" severity="secondary" rounded />
                        <Tag :value="`Thức ăn: ${preview.sample.feedCategory || 'Chưa có'}`" severity="secondary" rounded />
                    </div>

                    <div class="rounded-[1.2rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                            Template gốc
                        </p>
                        <p class="mt-2 whitespace-pre-line text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ preview.templateText }}
                        </p>
                    </div>

                    <div class="rounded-[1.2rem] border p-4" :style="{ borderColor: 'rgba(20, 184, 166, 0.28)', background: 'rgba(20, 184, 166, 0.08)' }">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                            Kết quả render
                        </p>
                        <p class="mt-2 whitespace-pre-line text-base leading-7" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ preview.renderedText }}
                        </p>
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
