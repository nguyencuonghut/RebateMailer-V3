<script setup lang="ts">
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps<{
    preview: {
        templateText: string;
        renderedText: string;
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
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Preview subject
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Subject render từ dữ liệu aggregate thật
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Preview này dùng template hiện tại và một bản ghi aggregate đã persist trong hệ thống, không dùng dữ liệu minh họa tự dựng.
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
                    Chưa có đủ dữ liệu thật để preview subject. Cần có template hiện tại và ít nhất một batch import đã aggregate thành công.
                </div>

                <template v-else>
                    <div class="flex flex-wrap gap-2.5">
                        <Tag :value="`Batch: ${preview.sample.batchCode}`" severity="info" rounded />
                        <Tag :value="`Khách: ${preview.sample.customerCode}`" severity="contrast" rounded />
                        <Tag :value="`Tháng: ${preview.sample.month || 'Chưa có'}`" severity="secondary" rounded />
                    </div>

                    <div class="rounded-[1.2rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                            Template gốc
                        </p>
                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ preview.templateText }}
                        </p>
                    </div>

                    <div class="rounded-[1.2rem] border p-4" :style="{ borderColor: 'rgba(20, 184, 166, 0.28)', background: 'rgba(20, 184, 166, 0.08)' }">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                            Kết quả render
                        </p>
                        <p class="mt-2 text-base font-semibold leading-7" :style="{ color: 'var(--dashboard-strong-text)' }">
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
