<script setup lang="ts">
import { computed, watch } from 'vue';
import Draggable from 'vuedraggable';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Textarea from 'primevue/textarea';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import {
    useTemplateBuilderCanvas,
    type BuilderTemplate,
    type TemplateSectionDefinition,
    type TongHopBindingOption,
    type TemplateTableRowType,
} from '@/Services/templates/useTemplateBuilderCanvas';

const props = defineProps<{
    template: BuilderTemplate | null;
    canManageTemplates: boolean;
    sectionCatalog: TemplateSectionDefinition[];
    tongHopBindingOptions: TongHopBindingOption[];
    visibleSectionTypes?: string[];
}>();

const emit = defineEmits<{
    'draft-change': [sections: Array<{
        type: string;
        label?: string;
        description?: string;
        kind?: 'text' | 'table';
        sourceSheet?: string | null;
        content?: string;
        rows?: Array<{
            content: string;
            indentLevel?: number;
            rowType?: TemplateTableRowType;
            columnKey?: string | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: 'parent' | 'child' | 'neutral';
            fontWeight: 'bold' | 'regular';
        }>;
    }>]
}>();

const {
    sectionDraft,
    catalogItems,
    hasUnsavedChanges,
    addSection,
    addRow,
    removeRow,
    increaseIndent,
    decreaseIndent,
    addTongHopRow,
    addKhoanNppRow,
    addTongHopChildRow,
    toggleTongHopRowBold,
    toggleKhoanNppRowBold,
    toggleTongHopHideWhenZero,
    updateTongHopColumnKey,
    saveCanvasComposition,
    savePart,
    saveValidationErrors,
    saveValidationScope,
    saveValidationSummary,
    saveValidationTarget,
    tongHopSectionType,
    khoanNppSectionType,
} = useTemplateBuilderCanvas(
    () => props.template,
    () => props.canManageTemplates,
    () => props.sectionCatalog,
);

const activeVisibleSectionTypes = computed(() => props.visibleSectionTypes ?? []);

const isFilteredView = computed(() => activeVisibleSectionTypes.value.length > 0);

const visibleCatalogItems = computed(() => {
    if (!isFilteredView.value) {
        return catalogItems.value;
    }

    const allowedTypes = new Set(activeVisibleSectionTypes.value);

    return catalogItems.value.filter((item) => allowedTypes.has(item.type));
});

const visibleSectionDraft = computed(() => {
    if (!isFilteredView.value) {
        return sectionDraft.value;
    }

    const allowedTypes = new Set(activeVisibleSectionTypes.value);

    return sectionDraft.value.filter((section) => allowedTypes.has(section.type));
});

const visibleSectionCount = computed(() => visibleSectionDraft.value.length);

const visibleTableSectionCount = computed(() =>
    visibleSectionDraft.value.filter((section) => section.kind === 'table').length,
);

const isSinglePartView = computed(() => isFilteredView.value && visibleSectionDraft.value.length === 1);
const activePartType = computed(() => visibleSectionDraft.value[0]?.type ?? null);

const visibleSaveValidationErrors = computed(() => {
    if (saveValidationScope.value === 'part') {
        return saveValidationTarget.value === activePartType.value
            ? saveValidationErrors.value
            : {};
    }

    return saveValidationErrors.value;
});

const visibleSaveValidationSummary = computed(() => {
    if (saveValidationScope.value === 'part' && saveValidationTarget.value !== activePartType.value) {
        return null;
    }

    return saveValidationSummary.value;
});

const builderValidationMessages = computed(() => {
    const rowErrorPattern = /^section\.rows\.\d+\./;

    return Array.from(
        new Set(
            Object.entries(visibleSaveValidationErrors.value)
                .filter(([key]) => !rowErrorPattern.test(key))
                .map(([, message]) => message),
        ),
    );
});

const textPartContentError = computed(() => visibleSaveValidationErrors.value.content ?? null);

const getRowValidationMessages = (rowIndex: number): string[] =>
    Array.from(
        new Set(
            Object.entries(visibleSaveValidationErrors.value)
                .filter(([key]) => key.startsWith(`section.rows.${rowIndex}.`))
                .map(([, message]) => message),
        ),
    );

const saveButtonLabel = computed(() => {
    if (isSinglePartView.value) {
        return 'Lưu phần này';
    }

    return 'Lưu canvas';
});

const handleSave = (): void => {
    if (isSinglePartView.value && activePartType.value) {
        savePart(activePartType.value);

        return;
    }

    saveCanvasComposition();
};

watch(
    sectionDraft,
    (sections) => {
        emit('draft-change', sections);
    },
    { deep: true, immediate: true },
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
                            Canvas hiện đọc trực tiếp từ <code>structure_json</code> và mount bằng <code>vuedraggable</code>. Các table section đang được tách dần sang semantic row model riêng theo từng sheet nguồn.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <Tag :value="`${visibleSectionCount} section trong canvas`" severity="info" rounded />
                        <Tag :value="`${visibleTableSectionCount} bảng đang mở row editor`" severity="contrast" rounded />
                        <Button
                            v-if="canManageTemplates && template"
                            :label="saveButtonLabel"
                            size="small"
                            :disabled="!hasUnsavedChanges"
                            @click="handleSave"
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
                                    {{ isFilteredView ? 'Catalog section đang mở' : 'Catalog 6 phần chính' }}
                                </p>
                                <h3 class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ isFilteredView ? 'Section đúng với tab hiện tại' : 'Chọn đúng section nghiệp vụ để thêm vào canvas' }}
                                </h3>
                            </div>

                            <Tag :value="`${visibleCatalogItems.filter((item) => item.isAdded).length}/${visibleCatalogItems.length} section đã có`" severity="success" rounded />
                        </div>

                        <div class="mt-4 grid gap-3 xl:grid-cols-2">
                            <article
                                v-for="section in visibleCatalogItems"
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

                    <div
                        v-if="visibleSaveValidationSummary"
                        class="rounded-[1.4rem] border px-5 py-4"
                        :style="{
                            borderColor: 'rgba(239, 68, 68, 0.36)',
                            background: 'rgba(239, 68, 68, 0.08)',
                        }"
                    >
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-red-400">
                            Không thể lưu cấu hình
                        </p>
                        <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ visibleSaveValidationSummary }}
                        </p>
                        <ul
                            v-if="builderValidationMessages.length > 0"
                            class="mt-3 space-y-2 text-sm leading-6"
                            :style="{ color: 'var(--dashboard-strong-text)' }"
                        >
                            <li
                                v-for="message in builderValidationMessages"
                                :key="message"
                            >
                                {{ message }}
                            </li>
                        </ul>
                    </div>

                    <div class="rounded-[1.6rem] border p-4 sm:p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <Draggable
                            :model-value="visibleSectionDraft"
                            item-key="renderKey"
                            :disabled="true"
                            ghost-class="opacity-60"
                            :class="isFilteredView ? 'space-y-0' : 'space-y-3'"
                        >
                            <template #item="{ element, index }">
                                <article
                                    :class="isFilteredView ? 'p-0' : 'rounded-[1.25rem] border p-4'"
                                    :style="isFilteredView
                                        ? {}
                                        : {
                                            borderColor: 'rgba(20, 184, 166, 0.24)',
                                            background: 'rgba(15, 23, 42, 0.04)',
                                        }"
                                >
                                    <div v-if="!isFilteredView" class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
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

                                    <p v-if="!isFilteredView && element.sourceSheet" class="mt-3 text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        Sheet nguồn: {{ element.sourceSheet }}
                                    </p>

                                    <div v-if="element.kind === 'text'" class="mt-3">
                                        <Textarea
                                            v-if="isFilteredView && canManageTemplates"
                                            v-model="element.content"
                                            auto-resize
                                            rows="4"
                                            fluid
                                            :pt="{ root: { class: 'font-medium' } }"
                                            placeholder="Nhập nội dung của phần template"
                                        />
                                        <small
                                            v-if="isFilteredView && canManageTemplates && textPartContentError"
                                            class="mt-2 block text-sm text-red-400"
                                        >
                                            {{ textPartContentError }}
                                        </small>
                                        <p
                                            v-else
                                            class="text-sm leading-6 whitespace-pre-line"
                                            :style="{ color: 'var(--dashboard-muted-text)' }"
                                        >
                                            {{ element.content ?? element.description ?? 'Section này chưa có nội dung text trực tiếp.' }}
                                        </p>
                                    </div>

                                    <p
                                        v-else-if="!isFilteredView"
                                        class="mt-3 text-sm leading-6 whitespace-pre-line"
                                        :style="{ color: 'var(--dashboard-muted-text)' }"
                                    >
                                        {{ element.description ?? 'Section này chưa có nội dung text trực tiếp.' }}
                                    </p>

                                    <div
                                        v-if="element.kind === 'table'"
                                        :class="isFilteredView ? 'space-y-4' : 'mt-4 rounded-[1rem] border p-4'"
                                        :style="isFilteredView
                                            ? {}
                                            : {
                                                borderColor: 'rgba(20, 184, 166, 0.18)',
                                                background: 'var(--dashboard-card-bg)',
                                            }"
                                    >
                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                                            <div class="xl:max-w-[26rem] xl:flex-none">
                                                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-teal-500">
                                                    Table rows
                                                </p>
                                                <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                    <template v-if="element.type === tongHopSectionType">
                                                        Thiết kế đúng semantics cho bảng Chế độ tháng: dòng trống, mục cha, mục con, tổng cộng, dòng chữ và mapping key từ dữ liệu đã parse.
                                                    </template>
                                                    <template v-else-if="element.type === khoanNppSectionType">
                                                        Bảng Khoán NPP không map theo tên cột tĩnh. Dòng dữ liệu thật phải lặp từ các `programItems[]` đã parse ra từ từng cụm `CT i | SL | đ/kg | Thành tiền`.
                                                    </template>
                                                    <template v-else>
                                                        Thêm, xóa và kéo thả thứ tự dòng trong chính table section này.
                                                    </template>
                                                </p>
                                            </div>

                                            <div v-if="canManageTemplates && isFilteredView" class="flex flex-wrap gap-2 xl:flex-nowrap xl:justify-end">
                                                <template v-if="element.type === tongHopSectionType">
                                                    <Button label="Mục cha" size="small" variant="outlined" @click="addTongHopRow(element.type, 'parent')" />
                                                    <Button label="Dữ liệu" size="small" variant="outlined" @click="addTongHopRow(element.type, 'data')" />
                                                    <Button label="Trống" size="small" variant="outlined" @click="addTongHopRow(element.type, 'blank')" />
                                                    <Button label="Tổng cộng" size="small" severity="success" variant="outlined" @click="addTongHopRow(element.type, 'total')" />
                                                    <Button label="Dòng chữ" size="small" severity="warn" variant="outlined" @click="addTongHopRow(element.type, 'text')" />
                                                </template>
                                                <template v-else-if="element.type === khoanNppSectionType">
                                                    <Button label="Dòng CT" size="small" variant="outlined" @click="addKhoanNppRow(element.type, 'program-loop')" />
                                                    <Button label="Trống" size="small" variant="outlined" @click="addKhoanNppRow(element.type, 'blank')" />
                                                    <Button label="Tổng cộng" size="small" severity="success" variant="outlined" @click="addKhoanNppRow(element.type, 'total')" />
                                                    <Button label="Dòng chữ" size="small" severity="warn" variant="outlined" @click="addKhoanNppRow(element.type, 'in-words')" />
                                                </template>
                                                <Button
                                                    v-else
                                                    label="Thêm dòng"
                                                    size="small"
                                                    @click="addRow(element.type)"
                                                />
                                            </div>
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
                                                    <div v-if="element.type === tongHopSectionType" class="space-y-3">
                                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start">
                                                            <div class="flex items-center gap-2 xl:w-[18rem]">
                                                                <button
                                                                    type="button"
                                                                    class="template-row-handle inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm"
                                                                    :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
                                                                >
                                                                    ↕
                                                                </button>

                                                                <Tag
                                                                    :value="row.rowType === 'blank' ? 'Trống' : row.rowType === 'parent' ? 'Mục cha' : row.rowType === 'child' ? 'Mục con' : row.rowType === 'data' ? 'Dữ liệu' : row.rowType === 'total' ? 'Tổng cộng' : 'Dòng chữ'"
                                                                    :severity="row.rowType === 'parent' ? 'info' : row.rowType === 'child' ? 'secondary' : row.rowType === 'data' ? 'contrast' : row.rowType === 'total' ? 'success' : row.rowType === 'text' ? 'warn' : 'contrast'"
                                                                    rounded
                                                                />

                                                                <div class="min-w-0">
                                                                    <p class="text-sm" :class="row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal'" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                        {{ row.numbering || '—' }} · Dòng {{ rowIndex + 1 }}
                                                                    </p>
                                                                    <p class="text-xs" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                                        {{ row.rowType === 'child' ? 'Hiển thị số nguyên' : row.rowType === 'parent' ? 'Hiển thị số La Mã' : 'Không đánh số' }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="min-w-0 flex-1 xl:max-w-none">
                                                                <InputText
                                                                    v-model="row.content"
                                                                    fluid
                                                                    :disabled="!isFilteredView || !canManageTemplates"
                                                                    :pt="{ root: { class: row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal' } }"
                                                                    placeholder="Nhập tên dòng sẽ hiển thị trong bảng email"
                                                                />
                                                            </div>

                                                            <div class="xl:w-[18rem]">
                                                                <Select
                                                                    :model-value="row.columnKey ?? null"
                                                                    :options="tongHopBindingOptions"
                                                                    option-label="label"
                                                                    option-value="key"
                                                                    filter
                                                                    show-clear
                                                                    fluid
                                                                    :disabled="!isFilteredView || !canManageTemplates"
                                                                    placeholder="Chọn key dữ liệu đã parse"
                                                                    @update:model-value="updateTongHopColumnKey(element.type, row.renderKey, $event)"
                                                                />
                                                            </div>

                                                            <div v-if="canManageTemplates && isFilteredView" class="flex flex-wrap items-center gap-2 xl:ml-auto xl:flex-nowrap">
                                                                <Button
                                                                    :label="row.isBold ? 'B đậm' : 'B thường'"
                                                                    size="small"
                                                                    :severity="row.isBold ? 'info' : 'secondary'"
                                                                    variant="outlined"
                                                                    @click="toggleTongHopRowBold(element.type, row.renderKey)"
                                                                />
                                                                <Button
                                                                    :label="row.hideWhenValueZero ? 'Ẩn khi = 0 hoặc rỗng' : 'Hiện cả = 0 hoặc rỗng'"
                                                                    size="small"
                                                                    :severity="row.hideWhenValueZero ? 'warn' : 'secondary'"
                                                                    variant="outlined"
                                                                    @click="toggleTongHopHideWhenZero(element.type, row.renderKey)"
                                                                />
                                                                <Button
                                                                    v-if="row.rowType === 'parent'"
                                                                    label="+ Mục con"
                                                                    size="small"
                                                                    variant="outlined"
                                                                    @click="addTongHopChildRow(element.type, row.renderKey)"
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

                                                        <div v-if="getRowValidationMessages(rowIndex).length > 0" class="rounded-[0.9rem] border px-4 py-3" :style="{ borderColor: 'rgba(239, 68, 68, 0.36)', background: 'rgba(239, 68, 68, 0.08)' }">
                                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-400">
                                                                Lỗi dòng {{ rowIndex + 1 }}
                                                            </p>
                                                            <ul class="mt-2 space-y-1 text-sm leading-6" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                <li
                                                                    v-for="message in getRowValidationMessages(rowIndex)"
                                                                    :key="`${row.renderKey}-${message}`"
                                                                >
                                                                    {{ message }}
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    <div v-else-if="element.type === khoanNppSectionType" class="space-y-3">
                                                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start">
                                                            <div class="flex items-center gap-2 xl:w-[18rem]">
                                                                <button
                                                                    type="button"
                                                                    class="template-row-handle inline-flex h-9 w-9 items-center justify-center rounded-full border text-sm"
                                                                    :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
                                                                >
                                                                    ↕
                                                                </button>

                                                                <Tag
                                                                    :value="row.rowType === 'program-loop' ? 'Dòng CT' : row.rowType === 'total' ? 'Tổng cộng' : row.rowType === 'in-words' ? 'Dòng chữ' : 'Trống'"
                                                                    :severity="row.rowType === 'program-loop' ? 'contrast' : row.rowType === 'total' ? 'success' : row.rowType === 'in-words' ? 'warn' : 'secondary'"
                                                                    rounded
                                                                />

                                                                <div class="min-w-0">
                                                                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                        Dòng {{ rowIndex + 1 }}
                                                                    </p>
                                                                    <p class="text-xs" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                                        {{ row.rowType === 'program-loop' ? 'STT auto tăng theo số CT render thực tế' : 'Không đánh STT' }}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="min-w-0 flex-1 xl:max-w-none">
                                                                <template v-if="row.rowType === 'program-loop'">
                                                                    <div
                                                                        class="rounded-[0.85rem] border px-4 py-2.5 text-sm leading-6"
                                                                        :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
                                                                    >
                                                                        Lặp qua các CT có dữ liệu thật từ <code>programItems[]</code>. Preview và email sẽ ẩn CT nếu cả `Nội dung | SL | đ/kg | Thành tiền` đều bằng `0` hoặc rỗng.
                                                                    </div>
                                                                </template>
                                                                <template v-else-if="row.rowType === 'blank'">
                                                                    <div
                                                                        class="rounded-[0.85rem] border px-4 py-2.5 text-sm leading-6"
                                                                        :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
                                                                    >
                                                                        Dòng trống để tạo khoảng cách giữa block dữ liệu và dòng tổng kết.
                                                                    </div>
                                                                </template>
                                                                <template v-else>
                                                                    <InputText
                                                                        v-model="row.content"
                                                                        fluid
                                                                        :disabled="!isFilteredView || !canManageTemplates"
                                                                        :pt="{ root: { class: row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal' } }"
                                                                        :placeholder="row.rowType === 'total' ? 'Nhập label tổng, ví dụ: Cộng' : 'Nhập label dòng chữ, ví dụ: Bằng chữ:'"
                                                                    />
                                                                </template>
                                                            </div>

                                                            <div v-if="canManageTemplates && isFilteredView" class="flex flex-wrap items-center gap-2 xl:ml-auto xl:flex-nowrap">
                                                                <Button
                                                                    v-if="row.rowType === 'total' || row.rowType === 'in-words'"
                                                                    :label="row.isBold ? 'B đậm' : 'B thường'"
                                                                    size="small"
                                                                    :severity="row.isBold ? 'info' : 'secondary'"
                                                                    variant="outlined"
                                                                    @click="toggleKhoanNppRowBold(element.type, row.renderKey)"
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

                                                        <div v-if="getRowValidationMessages(rowIndex).length > 0" class="rounded-[0.9rem] border px-4 py-3" :style="{ borderColor: 'rgba(239, 68, 68, 0.36)', background: 'rgba(239, 68, 68, 0.08)' }">
                                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-400">
                                                                Lỗi dòng {{ rowIndex + 1 }}
                                                            </p>
                                                            <ul class="mt-2 space-y-1 text-sm leading-6" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                <li
                                                                    v-for="message in getRowValidationMessages(rowIndex)"
                                                                    :key="`${row.renderKey}-${message}`"
                                                                >
                                                                    {{ message }}
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>

                                                    <div v-else class="flex flex-col gap-3">
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

                                                            <div v-if="canManageTemplates && isFilteredView" class="flex flex-wrap items-center gap-2">
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
                                                                :disabled="!isFilteredView || !canManageTemplates"
                                                                :pt="{
                                                                    root: {
                                                                        class: row.fontWeight === 'bold' ? 'font-semibold' : 'font-normal',
                                                                    },
                                                                }"
                                                                placeholder="Nhập nội dung dòng của bảng"
                                                            />
                                                        </div>

                                                        <div v-if="getRowValidationMessages(rowIndex).length > 0" class="rounded-[0.9rem] border px-4 py-3" :style="{ borderColor: 'rgba(239, 68, 68, 0.36)', background: 'rgba(239, 68, 68, 0.08)' }">
                                                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-red-400">
                                                                Lỗi dòng {{ rowIndex + 1 }}
                                                            </p>
                                                            <ul class="mt-2 space-y-1 text-sm leading-6" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                <li
                                                                    v-for="message in getRowValidationMessages(rowIndex)"
                                                                    :key="`${row.renderKey}-${message}`"
                                                                >
                                                                    {{ message }}
                                                                </li>
                                                            </ul>
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
