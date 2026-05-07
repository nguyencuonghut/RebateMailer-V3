import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export type TemplateSectionDefinition = {
    type: string;
    label: string;
    description: string;
    kind: 'text' | 'table';
    sourceSheet: string | null;
};

export type TongHopRowType = 'blank' | 'parent' | 'child' | 'data' | 'total' | 'text';

export type TongHopBindingOption = {
    key: string;
    label: string;
    valuePreview: string;
};

export type BuilderRow = {
    content: string;
    indentLevel?: number;
    rowType?: TongHopRowType;
    columnKey?: string | null;
    hideWhenValueZero?: boolean;
    isBold?: boolean;
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
    styleRole: 'parent' | 'child' | 'neutral';
    fontWeight: 'bold' | 'regular';
};

type RenderableBuilderSection = Omit<BuilderSection, 'rows'> & {
    renderKey: string;
    rows: RenderableBuilderRow[];
};

const TONG_HOP_SECTION_TYPE = 'tong-hop-table';

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

function buildLegacyRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    const counters = new Map<number, number>();

    return rows.map((row, rowIndex) => {
        const indentLevel = Math.max(0, row.indentLevel ?? 0);

        Array.from(counters.keys())
            .filter((level) => level > indentLevel)
            .forEach((level) => counters.delete(level));

        counters.set(indentLevel, (counters.get(indentLevel) ?? 0) + 1);

        const isBold = row.isBold ?? (indentLevel === 0);

        return {
            ...row,
            indentLevel,
            rowType: row.rowType,
            columnKey: row.columnKey ?? null,
            hideWhenValueZero: row.hideWhenValueZero ?? false,
            isBold,
            numbering: indentLevel === 0
                ? toRoman(counters.get(0) ?? 1)
                : String(counters.get(indentLevel) ?? 1),
            styleRole: indentLevel === 0 ? 'parent' : 'child',
            fontWeight: isBold ? 'bold' : 'regular',
            renderKey: `${sectionType}-row-${rowIndex}`,
        };
    });
}

function buildTongHopRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    let parentCounter = 0;
    let childCounter = 0;

    return rows.map((row, rowIndex) => {
        const rowType: TongHopRowType = row.rowType ?? 'blank';
        const indentLevel = rowType === 'child' ? 1 : 0;
        const defaultBold = rowType === 'parent' || rowType === 'total';
        const isBold = row.isBold ?? defaultBold;

        let numbering = '';
        let styleRole: 'parent' | 'child' | 'neutral' = 'neutral';

        if (rowType === 'parent') {
            parentCounter += 1;
            childCounter = 0;
            numbering = toRoman(parentCounter);
            styleRole = 'parent';
        } else if (rowType === 'child') {
            childCounter += 1;
            numbering = String(childCounter);
            styleRole = 'child';
        }

        return {
            ...row,
            indentLevel,
            rowType,
            columnKey: row.columnKey ?? null,
            hideWhenValueZero: row.hideWhenValueZero ?? false,
            isBold,
            numbering,
            styleRole,
            fontWeight: isBold ? 'bold' : 'regular',
            renderKey: `${sectionType}-row-${rowIndex}`,
        };
    });
}

function buildNumberedRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    return sectionType === TONG_HOP_SECTION_TYPE
        ? buildTongHopRows(sectionType, rows)
        : buildLegacyRows(sectionType, rows);
}

function buildRenderableSection(section: BuilderSection, index: number): RenderableBuilderSection {
    return {
        ...section,
        renderKey: `${section.type}-${index}`,
        rows: buildNumberedRows(section.type, section.rows ?? []),
    };
}

function serializeRows(sectionType: string, rows: RenderableBuilderRow[]): BuilderRow[] {
    return rows.map((row) => ({
        content: row.content,
        indentLevel: sectionType === TONG_HOP_SECTION_TYPE ? undefined : (row.indentLevel ?? 0),
        rowType: sectionType === TONG_HOP_SECTION_TYPE ? (row.rowType ?? 'blank') : undefined,
        columnKey: sectionType === TONG_HOP_SECTION_TYPE ? (row.columnKey ?? null) : undefined,
        hideWhenValueZero: sectionType === TONG_HOP_SECTION_TYPE ? (row.hideWhenValueZero ?? false) : undefined,
        isBold: sectionType === TONG_HOP_SECTION_TYPE ? row.isBold ?? false : undefined,
    }));
}

function buildTongHopDraftRow(rowType: TongHopRowType): BuilderRow {
    return {
        content: '',
        rowType,
        columnKey: null,
        hideWhenValueZero: false,
        isBold: rowType === 'parent' || rowType === 'total',
    };
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

        router.post(route('templates.sections.store', activeTemplate.id), { type }, { preserveScroll: true });
    };

    const findSection = (sectionType: string): RenderableBuilderSection | undefined =>
        sectionDraft.value.find((item) => item.type === sectionType && item.kind === 'table');

    const rehydrateSectionRows = (section: RenderableBuilderSection): void => {
        section.rows = buildNumberedRows(section.type, serializeRows(section.type, section.rows ?? []));
    };

    const addRow = (sectionType: string): void => {
        const section = findSection(sectionType);

        if (!section) {
            return;
        }

        section.rows = buildNumberedRows(section.type, [
            ...serializeRows(section.type, section.rows ?? []),
            {
                content: '',
                indentLevel: 0,
            },
        ]);
    };

    const removeRow = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            serializeRows(section.type, section.rows.filter((row) => row.renderKey !== rowKey)),
        );
    };

    const increaseIndent = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type === TONG_HOP_SECTION_TYPE) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            serializeRows(section.type, section.rows).map((row, index) => {
                const renderKey = section.rows[index]?.renderKey;

                return {
                    ...row,
                    indentLevel: renderKey === rowKey
                        ? Math.min(4, (row.indentLevel ?? 0) + 1)
                        : (row.indentLevel ?? 0),
                };
            }),
        );
    };

    const decreaseIndent = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type === TONG_HOP_SECTION_TYPE) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            serializeRows(section.type, section.rows).map((row, index) => {
                const renderKey = section.rows[index]?.renderKey;

                return {
                    ...row,
                    indentLevel: renderKey === rowKey
                        ? Math.max(0, (row.indentLevel ?? 0) - 1)
                        : (row.indentLevel ?? 0),
                };
            }),
        );
    };

    const addTongHopRow = (sectionType: string, rowType: TongHopRowType): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== TONG_HOP_SECTION_TYPE) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            [
                ...serializeRows(section.type, section.rows ?? []),
                buildTongHopDraftRow(rowType),
            ],
        );
    };

    const addTongHopChildRow = (sectionType: string, parentRowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== TONG_HOP_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);
        const parentIndex = section.rows.findIndex((row) => row.renderKey === parentRowKey);

        if (parentIndex === -1) {
            return;
        }

        let insertIndex = parentIndex + 1;

        while (insertIndex < rows.length && rows[insertIndex]?.rowType === 'child') {
            insertIndex += 1;
        }

        rows.splice(insertIndex, 0, buildTongHopDraftRow('child'));
        section.rows = buildNumberedRows(section.type, rows);
    };

    const toggleTongHopRowBold = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== TONG_HOP_SECTION_TYPE) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            serializeRows(section.type, section.rows).map((row, index) => ({
                ...row,
                isBold: section.rows[index]?.renderKey === rowKey
                    ? !(row.isBold ?? false)
                    : (row.isBold ?? false),
            })),
        );
    };

    const toggleTongHopHideWhenZero = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== TONG_HOP_SECTION_TYPE) {
            return;
        }

        section.rows = buildNumberedRows(
            section.type,
            serializeRows(section.type, section.rows).map((row, index) => ({
                ...row,
                hideWhenValueZero: section.rows[index]?.renderKey === rowKey
                    ? !(row.hideWhenValueZero ?? false)
                    : (row.hideWhenValueZero ?? false),
            })),
        );
    };

    const updateTongHopColumnKey = (sectionType: string, rowKey: string, columnKey: string | null): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== TONG_HOP_SECTION_TYPE) {
            return;
        }

        const row = section.rows.find((item) => item.renderKey === rowKey);

        if (!row) {
            return;
        }

        row.columnKey = columnKey;
    };

    const saveStructure = (): void => {
        const activeTemplate = template();

        if (!activeTemplate || !canManageTemplates()) {
            return;
        }

        router.put(
            route('templates.structure.update', activeTemplate.id),
            {
                version: '2.3-D',
                sections: sectionDraft.value.map((section) => ({
                    type: section.type,
                    label: section.label,
                    description: section.description,
                    kind: section.kind,
                    sourceSheet: section.sourceSheet,
                    content: section.content,
                    rows: section.kind === 'table'
                        ? serializeRows(section.type, section.rows ?? [])
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
        addTongHopRow,
        addTongHopChildRow,
        toggleTongHopRowBold,
        toggleTongHopHideWhenZero,
        updateTongHopColumnKey,
        saveStructure,
        rehydrateSectionRows,
        tongHopSectionType: TONG_HOP_SECTION_TYPE,
    };
}
