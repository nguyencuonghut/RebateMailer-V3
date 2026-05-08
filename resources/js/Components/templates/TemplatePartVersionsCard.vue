<script setup lang="ts">
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';

const props = defineProps<{
    title: string;
    description: string;
    canManageTemplates?: boolean;
    isBindingVersionId?: number | null;
    group: {
        partType: string;
        code: string;
        label: string;
        kind: 'text' | 'table';
        sourceSheet: string | null;
        maxActiveVersions: number;
        selectedVersionId: number | null;
        versionCount: number;
        activeVersionCount: number;
        versions: Array<{
            id: number;
            versionNo: number;
            versionLabel: string;
            isActive: boolean;
            hasTextTemplate: boolean;
            rowCount: number;
            legacyMailTemplateId: number | null;
            updatedAt: string | null;
        }>;
    } | null;
}>();

const emit = defineEmits<{
    (event: 'select-version', payload: { partType: string; versionId: number }): void;
}>();
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Part Versions
                        </p>
                        <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ title }}
                        </h2>
                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ description }}
                        </p>
                    </div>

                    <Tag
                        :value="group ? `${group.activeVersionCount}/${group.maxActiveVersions} active` : 'Chưa có part'"
                        :severity="group ? 'info' : 'secondary'"
                        rounded
                    />
                </div>

                <div
                    v-if="!group"
                    class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6"
                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                >
                    Part này chưa được cấu hình trong hệ thống.
                </div>

                <div v-else class="space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <Tag :value="group.code" severity="contrast" rounded />
                        <Tag v-if="group.sourceSheet" :value="`Sheet: ${group.sourceSheet}`" severity="info" rounded />
                        <Tag :value="`${group.versionCount} version`" severity="success" rounded />
                    </div>

                    <div
                        v-if="group.selectedVersionId === null"
                        class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6"
                        :style="{ borderColor: 'rgba(20, 184, 166, 0.28)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                    >
                        Canvas hiện tại chưa ghép version nào cho part này.
                        <span v-if="canManageTemplates">
                            Hãy dùng lại một version có sẵn bằng nút <strong>`Dùng cho canvas này`</strong> hoặc thêm part mới vào canvas ở builder phía dưới.
                        </span>
                    </div>

                    <div
                        v-if="group.versions.length === 0"
                        class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                    >
                        Part này chưa có version nào được lưu.
                    </div>

                    <div v-else class="space-y-3">
                        <article
                            v-for="version in group.versions"
                            :key="version.id"
                            class="rounded-[1.2rem] border p-4"
                            :style="{
                                borderColor: version.id === group.selectedVersionId ? 'rgba(20, 184, 166, 0.36)' : 'var(--dashboard-panel-border)',
                                background: 'var(--dashboard-card-bg)',
                            }"
                        >
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ version.versionLabel }}
                                        </p>
                                        <p class="mt-1 text-xs uppercase tracking-[0.16em]" :style="{ color: 'var(--dashboard-muted-text)' }">
                                            Version {{ version.versionNo }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Tag
                                            :value="version.id === group.selectedVersionId ? 'Đang ghép vào canvas' : 'Chưa ghép vào canvas'"
                                            :severity="version.id === group.selectedVersionId ? 'success' : 'secondary'"
                                            rounded
                                        />
                                        <Tag :value="version.isActive ? 'Active' : 'Inactive'" :severity="version.isActive ? 'info' : 'contrast'" rounded />
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Tag
                                        :value="group.kind === 'text' ? (version.hasTextTemplate ? 'Có text template' : 'Text rỗng') : `${version.rowCount} row`"
                                        severity="warn"
                                        rounded
                                    />
                                    <Tag
                                        v-if="version.legacyMailTemplateId"
                                        :value="`Legacy template #${version.legacyMailTemplateId}`"
                                        severity="secondary"
                                        rounded
                                    />
                                </div>

                                <div v-if="props.canManageTemplates" class="flex flex-wrap justify-end gap-2">
                                    <Tag
                                        v-if="version.id === group.selectedVersionId"
                                        value="Canvas đang dùng version này"
                                        severity="success"
                                        rounded
                                    />
                                    <Button
                                        v-else
                                        :label="props.isBindingVersionId === version.id ? 'Đang ghép...' : 'Dùng cho canvas này'"
                                        size="small"
                                        severity="info"
                                        variant="outlined"
                                        :loading="props.isBindingVersionId === version.id"
                                        @click="emit('select-version', { partType: group.partType, versionId: version.id })"
                                    />
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </div>
        </template>
    </Card>
</template>
