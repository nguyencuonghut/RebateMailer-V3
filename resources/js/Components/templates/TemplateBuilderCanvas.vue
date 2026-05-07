<script setup lang="ts">
import Draggable from 'vuedraggable';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import { useTemplateBuilderCanvas, type BuilderTemplate, type TemplateSectionDefinition } from '@/Services/templates/useTemplateBuilderCanvas';

const props = defineProps<{
    template: BuilderTemplate | null;
    canManageTemplates: boolean;
    sectionCatalog: TemplateSectionDefinition[];
}>();

const { sectionDraft, sectionCount, catalogItems, tableSectionCount, hasUnsavedChanges, addSection, addRow, removeRow, increaseIndent, decreaseIndent, saveStructure } = useTemplateBuilderCanvas(
    () => props.template,
    () => props.canManageTemplates,
    () => props.sectionCatalog,
);
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Builder canvas
                        </p>
                        <h2 class="mt-3 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            Canvas template email
                        </h2>
                        <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Canvas hiện đọc trực tiếp từ <code>structure_json</code> và mount bằng <code>vuedraggable</code>. Lát cắt này tự áp format mặc định: dòng cha đậm, dòng con regular, đồng bộ với hierarchy và numbering hiện có.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Tag :value="`${sectionCount} section trong canvas`" severity="info" rounded />
                        <Tag :value="`${tableSectionCount} bảng đang mở row editor`" severity="contrast" rounded />
                        <Button
                            v-if="canManageTemplates && template"
                            label="Lưu cấu trúc"
                            size="small"
                            :disabled="!hasUnsavedChanges"
                            @click="saveStructure"
                        />
                    </div>
                </div>

                <div v-if="!template" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                    Chưa có template nào để dựng builder canvas. Hãy tạo template đầu tiên ở form bên phải.
                </div>

                <template v-else>
                    <div class="rounded-[1.4rem] border p-4 sm:p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                    Catalog 6 phần chính
                                </p>
                                <h3 class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    Chọn đúng section nghiệp vụ để thêm vào canvas
                                </h3>
                            </div>

                            <Tag :value="`${catalogItems.filter((item) => item.isAdded).length}/6 section đã có`" severity="success" rounded />
                        </div>

                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            <article
                                v-for="section in catalogItems"
                                :key="section.type"
                                class="rounded-[1.2rem] border p-4"
                                :style="{
                                    borderColor: section.isAdded ? 'rgba(20, 184, 166, 0.32)' : 'var(--dashboard-panel-border)',
                                    background: 'var(--dashboard-app-bg)',
                                }"
                            >
                                <div class="flex flex-col gap-3">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                                {{ section.kind === 'table' ? 'Table section' : 'Text section' }}
                                            </p>
                                            <h4 class="mt-2 text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ section.label }}
                                            </h4>
                                        </div>

                                        <Tag :value="section.isAdded ? 'Đã có trong canvas' : 'Chưa thêm'" :severity="section.isAdded ? 'success' : 'secondary'" rounded />
                                    </div>

                                    <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ section.description }}
                                    </p>

                                    <Tag
                                        v-if="section.sourceSheet"
                                        :value="`Sheet nguồn: ${section.sourceSheet}`"
                                        severity="info"
                                        rounded
                                    />

                                    <div v-if="canManageTemplates" class="pt-1">
                                        <Button
                                            :label="section.isAdded ? 'Section đã có' : 'Thêm vào canvas'"
                                            size="small"
                                            :disabled="!section.canAdd"
                                            @click="addSection(section.type)"
                                        />
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ template.name }}
                        </p>
                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ template.subjectTemplate }}
                        </p>
                    </div>

                    <div class="rounded-[1.6rem] border p-4 sm:p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <Draggable
                            v-model="sectionDraft"
                            item-key="renderKey"
                            :disabled="true"
                            ghost-class="opacity-60"
                            class="space-y-3"
                        >
                            <template #item="{ element, index }">
                                <article
                                    class="rounded-[1.25rem] border p-4"
                                    :style="{
                                        borderColor: 'rgba(20, 184, 166, 0.24)',
                                        background: 'rgba(15, 23, 42, 0.04)',
                                    }"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                                Section {{ index + 1 }}
                                            </p>
                                            <h3 class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ element.label ?? element.type }}
                                            </h3>
                                        </div>

                                        <Tag :value="element.type" severity="success" rounded />
                                    </div>

                                    <p v-if="element.sourceSheet" class="mt-3 text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        Sheet nguồn: {{ element.sourceSheet }}
                                    </p>

                                    <p class="mt-3 text-sm leading-6 whitespace-pre-line" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ element.content ?? element.description ?? 'Section này chưa có nội dung text trực tiếp.' }}
                                    </p>

                                    <div
                                        v-if="element.kind === 'table'"
                                        class="mt-4 rounded-[1rem] border p-4"
                                        :style="{
                                            borderColor: 'rgba(20, 184, 166, 0.18)',
                                            background: 'var(--dashboard-card-bg)',
                                        }"
                                    >
                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-500">
                                                    Table rows
                                                </p>
                                                <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                    Thêm, xóa và kéo thả thứ tự dòng trong chính table section này.
                                                </p>
                                            </div>

                                            <Button
                                                v-if="canManageTemplates"
                                                label="Thêm dòng"
                                                size="small"
                                                @click="addRow(element.type)"
                                            />
                                        </div>

                                        <div
                                            v-if="(element.rows?.length ?? 0) === 0"
                                            class="mt-4 rounded-2xl border px-4 py-3 text-sm leading-6"
                                            :style="{
                                                borderColor: 'var(--dashboard-panel-border)',
                                                color: 'var(--dashboard-muted-text)',
                                            }"
                                        >
                                            Bảng này chưa có dòng nào. Hãy thêm dòng đầu tiên để bắt đầu thiết kế nội dung.
                                        </div>

                                        <Draggable
                                            v-else
                                            v-model="element.rows"
                                            item-key="renderKey"
                                            handle=".template-row-handle"
                                            ghost-class="opacity-60"
                                            class="mt-4 space-y-3"
                                        >
                                            <template #item="{ element: row, index: rowIndex }">
                                                <article
                                                    class="rounded-[1rem] border p-3"
                                                    :style="{
                                                        borderColor: 'var(--dashboard-panel-border)',
                                                        background: 'var(--dashboard-app-bg)',
                                                    }"
                                                >
                                                    <div class="flex flex-col gap-3">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                            <div class="flex items-center gap-2">
                                                                <button
                                                                    type="button"
                                                                    class="template-row-handle inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm"
                                                                    :style="{
                                                                        borderColor: 'var(--dashboard-panel-border)',
                                                                        color: 'var(--dashboard-muted-text)',
                                                                    }"
                                                                >
                                                                    ↕
                                                                </button>
                                                                <div>
                                                                    <p
                                                                        class="text-sm"
                                                                        :class="row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal'"
                                                                        :style="{ color: 'var(--dashboard-strong-text)' }"
                                                                    >
                                                                        {{ row.numbering }}. Dòng {{ rowIndex + 1 }}
                                                                    </p>
                                                                    <p class="text-xs" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                                        Cấp hiện tại: {{ row.indentLevel ?? 0 }} · Vai trò: {{ row.styleRole === 'parent' ? 'Cha' : 'Con' }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div v-if="canManageTemplates" class="flex flex-wrap items-center gap-2">
                                                                <Button
                                                                    label="Giảm cấp"
                                                                    variant="outlined"
                                                                    size="small"
                                                                    :disabled="(row.indentLevel ?? 0) === 0"
                                                                    @click="decreaseIndent(element.type, row.renderKey)"
                                                                />
                                                                <Button
                                                                    label="Tăng cấp"
                                                                    variant="outlined"
                                                                    size="small"
                                                                    :disabled="(row.indentLevel ?? 0) >= 4"
                                                                    @click="increaseIndent(element.type, row.renderKey)"
                                                                />
                                                                <Button
                                                                    label="Xóa dòng"
                                                                    severity="danger"
                                                                    variant="outlined"
                                                                    size="small"
                                                                    @click="removeRow(element.type, row.renderKey)"
                                                                />
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="rounded-[0.9rem]"
                                                            :style="{
                                                                paddingLeft: `${((row.indentLevel ?? 0) * 1.25)}rem`,
                                                            }"
                                                        >
                                                            <Textarea
                                                                v-model="row.content"
                                                                auto-resize
                                                                rows="3"
                                                                fluid
                                                                :pt="{
                                                                    root: {
                                                                        class: row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal',
                                                                    },
                                                                }"
                                                                placeholder="Nhập nội dung dòng của bảng"
                                                            />
                                                        </div>
                                                    </div>
                                                </article>
                                            </template>
                                        </Draggable>
                                    </div>
                                </article>
                            </template>
                        </Draggable>
                    </div>
                </template>
            </div>
        </template>
    </Card>
</template>
