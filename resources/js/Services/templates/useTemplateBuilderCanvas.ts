import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export type TemplateSectionDefinition = {
    type: string;
    label: string;
    description: string;
    kind: 'text' | 'table';
    sourceSheet: string | null;
};

export type BuilderRow = {
    content: string;
    indentLevel?: number;
};

export type BuilderSection = {
    type: string;
    label?: string;
    description?: string;
    kind?: 'text' | 'table';
    sourceSheet?: string | null;
    content?: string;
    rows?: BuilderRow[];
};

export type BuilderTemplate = {
    id: number;
    name: string;
    subjectTemplate: string;
    structure: {
        version?: string;
        sections?: BuilderSection[];
    };
};

type RenderableBuilderRow = BuilderRow & {
    renderKey: string;
    numbering: string;
    styleRole: 'parent' | 'child';
    fontWeight: 'bold' | 'regular';
};

type RenderableBuilderSection = Omit<BuilderSection, 'rows'> & {
    renderKey: string;
    rows: RenderableBuilderRow[];
};

function buildRenderableSection(section: BuilderSection, index: number): RenderableBuilderSection {
    return {
        ...section,
        renderKey: `${section.type}-${index}`,
        rows: buildNumberedRows(section.type, section.rows ?? []),
    };
}

function toRoman(number: number): string {
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

    let remaining = number;
    let result = '';

    for (const [value, glyph] of map) {
        while (remaining >= value) {
            result += glyph;
            remaining -= value;
        }
    }

    return result;
}

function buildNumberedRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    const counters = new Map<number, number>();

    return rows.map((row, rowIndex) => {
        const indentLevel = Math.max(0, row.indentLevel ?? 0);

        Array.from(counters.keys())
            .filter((level) => level > indentLevel)
            .forEach((level) => counters.delete(level));

        counters.set(indentLevel, (counters.get(indentLevel) ?? 0) + 1);

        return {
            ...row,
            indentLevel,
            numbering: indentLevel === 0
                ? toRoman(counters.get(0) ?? 1)
                : String(counters.get(indentLevel) ?? 1),
            styleRole: indentLevel === 0 ? 'parent' : 'child',
            fontWeight: indentLevel === 0 ? 'bold' : 'regular',
            renderKey: `${sectionType}-row-${rowIndex}`,
        };
    });
}

export function useTemplateBuilderCanvas(
    template: () => BuilderTemplate | null,
    canManageTemplates: () => boolean,
    sectionCatalog: () => TemplateSectionDefinition[],
) {
    const sectionDraft = ref<RenderableBuilderSection[]>([]);

    watch(
        template,
        (activeTemplate) => {
            sectionDraft.value = activeTemplate?.structure.sections?.map(buildRenderableSection) ?? [];
        },
        { immediate: true },
    );

    const sectionCount = computed(() => sectionDraft.value.length);

    const catalogItems = computed(() => {
        const activeTypes = new Set(sectionDraft.value.map((section) => section.type));

        return sectionCatalog().map((section) => ({
            ...section,
            isAdded: activeTypes.has(section.type),
            canAdd: canManageTemplates() && template() !== null && !activeTypes.has(section.type),
        }));
    });

    const tableSectionCount = computed(() =>
        sectionDraft.value.filter((section) => section.kind === 'table').length,
    );

    const hasUnsavedChanges = ref(false);

    watch(
        sectionDraft,
        () => {
            hasUnsavedChanges.value = true;
        },
        { deep: true },
    );

    watch(
        template,
        () => {
            hasUnsavedChanges.value = false;
        },
    );

    const addSection = (type: string): void => {
        const activeTemplate = template();

        if (!activeTemplate || !canManageTemplates()) {
            return;
        }

        router.post(
            route('templates.sections.store', activeTemplate.id),
            { type },
            {
                preserveScroll: true,
            },
        );
    };

    const addRow = (sectionType: string): void => {
        const section = sectionDraft.value.find((item) => item.type === sectionType && item.kind === 'table');

        if (!section) {
            return;
        }

        const nextIndex = section.rows?.length ?? 0;

        section.rows = buildNumberedRows(section.type, [
            ...section.rows,
            {
                content: '',
                indentLevel: 0,
            },
        ]);
    };

    const removeRow = (sectionType: string, rowKey: string): void => {
        const section = sectionDraft.value.find((item) => item.type === sectionType && item.kind === 'table');

        if (!section) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            section.rows
                .filter((row) => row.renderKey !== rowKey)
                .map((row) => ({
                    content: row.content,
                    indentLevel: row.indentLevel ?? 0,
                })),
        );
    };

    const increaseIndent = (sectionType: string, rowKey: string): void => {
        const section = sectionDraft.value.find((item) => item.type === sectionType && item.kind === 'table');

        if (!section) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            section.rows.map((row) => ({
                content: row.content,
                indentLevel: row.renderKey === rowKey
                    ? Math.min(4, (row.indentLevel ?? 0) + 1)
                    : (row.indentLevel ?? 0),
            })),
        );
    };

    const decreaseIndent = (sectionType: string, rowKey: string): void => {
        const section = sectionDraft.value.find((item) => item.type === sectionType && item.kind === 'table');

        if (!section) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            section.rows.map((row) => ({
                content: row.content,
                indentLevel: row.renderKey === rowKey
                    ? Math.max(0, (row.indentLevel ?? 0) - 1)
                    : (row.indentLevel ?? 0),
            })),
        );
    };

    const saveStructure = (): void => {
        const activeTemplate = template();

        if (!activeTemplate || !canManageTemplates()) {
            return;
        }

        router.put(
            route('templates.structure.update', activeTemplate.id),
            {
                version: '2.2-E',
                sections: sectionDraft.value.map((section) => ({
                    type: section.type,
                    label: section.label,
                    description: section.description,
                    kind: section.kind,
                    sourceSheet: section.sourceSheet,
                    content: section.content,
                    rows: section.kind === 'table'
                        ? (section.rows ?? []).map((row) => ({
                            content: row.content,
                            indentLevel: row.indentLevel ?? 0,
                        }))
                        : undefined,
                })),
            },
            {
                preserveScroll: true,
            },
        );
    };

    return {
        sectionDraft,
        sectionCount,
        catalogItems,
        tableSectionCount,
        hasUnsavedChanges,
        addSection,
        addRow,
        removeRow,
        increaseIndent,
        decreaseIndent,
        saveStructure,
    };
}
