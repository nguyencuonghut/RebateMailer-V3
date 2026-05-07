<script setup lang="ts">
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

defineProps<{
    variables: Array<{
        token: string;
        label: string;
        description: string;
    }>;
    canManageTemplates: boolean;
}>();

const emit = defineEmits<{
    insert: [token: string];
}>();
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Contract biến
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Biến được phép dùng cho Subject và Lời chào
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Chỉ dùng các biến đã được xác nhận từ tài liệu gốc. Biến ngoài contract sẽ bị backend từ chối khi lưu template.
                    </p>
                </div>

                <div class="grid gap-3">
                    <article
                        v-for="variable in variables"
                        :key="variable.token"
                        class="rounded-[1.2rem] border p-4"
                        :style="{
                            borderColor: 'var(--dashboard-panel-border)',
                            background: 'var(--dashboard-card-bg)',
                        }"
                    >
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <Tag :value="variable.token" severity="info" rounded />
                                <p class="mt-3 text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ variable.label }}
                                </p>
                                <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                    {{ variable.description }}
                                </p>
                            </div>

                            <Button
                                v-if="canManageTemplates"
                                label="Chèn biến"
                                size="small"
                                @click="emit('insert', variable.token)"
                            />
                        </div>
                    </article>
                </div>
            </div>
        </template>
    </Card>
</template>
