import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export type TemplateSectionDefinition = {
    type: string;
    label: string;
    description: string;
    kind: 'text' | 'table' | 'composite';
    sourceSheet: string | null;
};

export type TongHopRowType = 'blank' | 'parent' | 'child' | 'data' | 'total' | 'text';
export type KhoanNppRowType = 'program-loop' | 'blank' | 'total' | 'in-words';
export type CamCaRowType = 'value-row' | 'parent' | 'child-value' | 'child-program-loop' | 'blank' | 'total' | 'in-words';
export type KeyAccountRowType = 'value-row' | 'parent' | 'child-value' | 'child-program-loop' | 'blank' | 'total' | 'in-words';
export type TemplateTableRowType = TongHopRowType | KhoanNppRowType | CamCaRowType | KeyAccountRowType;

export type TongHopBindingOption = {
    key: string;
    label: string;
    valuePreview: string;
    quantityPreview?: string;
    supportRatePreview?: string;
    amountPreview?: string;
    defaultValueColumn?: 'quantity' | 'supportRate' | 'amount';
};

export type BuilderRow = {
    content: string;
    indentLevel?: number;
    rowType?: TemplateTableRowType;
    columnKey?: string | null;
    valueColumn?: 'quantity' | 'supportRate' | 'amount' | null;
    hideWhenValueZero?: boolean;
    isBold?: boolean;
};

export type BuilderSection = {
    type: string;
    label?: string;
    description?: string;
    kind?: 'text' | 'table' | 'composite';
    sourceSheet?: string | null;
    content?: string;
    rows?: BuilderRow[];
    blocks?: {
        normalCustomer?: {
            title?: string;
            signatureImageDataUrl?: string | null;
            representativeRole?: string;
            representativeName?: string;
        };
        keyAccountCustomer?: {
            title?: string;
            signatureImageDataUrl?: string | null;
            representativeRole?: string;
            representativeName?: string;
        };
    };
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
const KHOAN_NPP_SECTION_TYPE = 'khoan-npp-table';
const CAM_CA_SECTION_TYPE = 'cam-ca-table';
const KEY_ACCOUNT_SECTION_TYPE = 'key-account-table';

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
        const rowType = (row.rowType as TongHopRowType | undefined) ?? 'blank';
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

function buildKhoanNppRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    return rows.map((row, rowIndex) => {
        const rowType: KhoanNppRowType = (row.rowType as KhoanNppRowType | undefined) ?? 'blank';
        const defaultBold = rowType === 'total';
        const isBold = row.isBold ?? defaultBold;

        return {
            ...row,
            indentLevel: 0,
            rowType,
            columnKey: null,
            hideWhenValueZero: true,
            isBold,
            numbering: '',
            styleRole: 'neutral',
            fontWeight: isBold ? 'bold' : 'regular',
            renderKey: `${sectionType}-row-${rowIndex}`,
        };
    });
}

function buildCamCaRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    let parentCounter = 0;
    let childCounter = 0;

    return rows.map((row, rowIndex) => {
        const rowType: CamCaRowType = (row.rowType as CamCaRowType | undefined) ?? 'blank';
        const defaultBold = rowType === 'parent' || rowType === 'total';
        const isBold = row.isBold ?? defaultBold;
        const usesColumnKey = ['value-row', 'parent', 'child-value'].includes(rowType);

        let numbering = '';
        let styleRole: 'parent' | 'child' | 'neutral' = 'neutral';

        if (rowType === 'parent') {
            parentCounter += 1;
            childCounter = 0;
            numbering = toRoman(parentCounter);
            styleRole = 'parent';
        } else if (rowType === 'child-value' || rowType === 'child-program-loop') {
            childCounter += 1;
            numbering = String(childCounter);
            styleRole = 'child';
        }

        return {
            ...row,
            indentLevel: rowType === 'child-value' || rowType === 'child-program-loop' ? 1 : 0,
            rowType,
            columnKey: usesColumnKey ? (row.columnKey ?? null) : null,
            hideWhenValueZero: row.hideWhenValueZero ?? true,
            isBold,
            numbering,
            styleRole,
            fontWeight: isBold ? 'bold' : 'regular',
            renderKey: `${sectionType}-row-${rowIndex}`,
        };
    });
}

function buildKeyAccountRows(sectionType: string, rows: BuilderRow[]): RenderableBuilderRow[] {
    let parentCounter = 0;
    let childCounter = 0;

    return rows.map((row, rowIndex) => {
        const rowType: KeyAccountRowType = (row.rowType as KeyAccountRowType | undefined) ?? 'blank';
        const defaultBold = rowType === 'parent' || rowType === 'total';
        const isBold = row.isBold ?? defaultBold;
        const usesColumnKey = ['value-row', 'parent', 'child-value'].includes(rowType);

        let numbering = '';
        let styleRole: 'parent' | 'child' | 'neutral' = 'neutral';

        if (rowType === 'parent') {
            parentCounter += 1;
            childCounter = 0;
            numbering = toRoman(parentCounter);
            styleRole = 'parent';
        } else if (rowType === 'child-value' || rowType === 'child-program-loop') {
            childCounter += 1;
            numbering = String(childCounter);
            styleRole = 'child';
        }

        return {
            ...row,
            indentLevel: rowType === 'child-value' || rowType === 'child-program-loop' ? 1 : 0,
            rowType,
            columnKey: usesColumnKey ? (row.columnKey ?? null) : null,
            valueColumn: usesColumnKey ? (row.valueColumn ?? null) : null,
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
    if (sectionType === TONG_HOP_SECTION_TYPE) {
        return buildTongHopRows(sectionType, rows);
    }

    if (sectionType === KHOAN_NPP_SECTION_TYPE) {
        return buildKhoanNppRows(sectionType, rows);
    }

    if (sectionType === CAM_CA_SECTION_TYPE) {
        return buildCamCaRows(sectionType, rows);
    }

    if (sectionType === KEY_ACCOUNT_SECTION_TYPE) {
        return buildKeyAccountRows(sectionType, rows);
    }

    return buildLegacyRows(sectionType, rows);
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
        indentLevel: sectionType === TONG_HOP_SECTION_TYPE || sectionType === KHOAN_NPP_SECTION_TYPE || sectionType === CAM_CA_SECTION_TYPE || sectionType === KEY_ACCOUNT_SECTION_TYPE ? undefined : (row.indentLevel ?? 0),
        rowType: sectionType === TONG_HOP_SECTION_TYPE || sectionType === KHOAN_NPP_SECTION_TYPE || sectionType === CAM_CA_SECTION_TYPE || sectionType === KEY_ACCOUNT_SECTION_TYPE ? (row.rowType ?? 'blank') : undefined,
        columnKey: sectionType === TONG_HOP_SECTION_TYPE || sectionType === CAM_CA_SECTION_TYPE || sectionType === KEY_ACCOUNT_SECTION_TYPE ? (row.columnKey ?? null) : undefined,
        valueColumn: sectionType === KEY_ACCOUNT_SECTION_TYPE ? (row.valueColumn ?? null) : undefined,
        hideWhenValueZero: sectionType === TONG_HOP_SECTION_TYPE || sectionType === KHOAN_NPP_SECTION_TYPE || sectionType === CAM_CA_SECTION_TYPE || sectionType === KEY_ACCOUNT_SECTION_TYPE ? (row.hideWhenValueZero ?? false) : undefined,
        isBold: sectionType === TONG_HOP_SECTION_TYPE || sectionType === KHOAN_NPP_SECTION_TYPE || sectionType === CAM_CA_SECTION_TYPE || sectionType === KEY_ACCOUNT_SECTION_TYPE ? row.isBold ?? false : undefined,
    }));
}

function buildTongHopDraftRow(rowType: TongHopRowType): BuilderRow {
    return {
        content: '',
        rowType,
        columnKey: null,
        hideWhenValueZero: true,
        isBold: rowType === 'parent' || rowType === 'total',
    };
}

function buildKhoanNppDraftRow(rowType: KhoanNppRowType): BuilderRow {
    return {
        content: rowType === 'total' ? 'Cộng' : rowType === 'in-words' ? 'Bằng chữ:' : '',
        rowType,
        hideWhenValueZero: true,
        isBold: rowType === 'total',
    };
}

function buildCamCaDraftRow(rowType: CamCaRowType): BuilderRow {
    return {
        content: rowType === 'total' ? 'Cộng' : rowType === 'in-words' ? 'Bằng chữ:' : '',
        rowType,
        columnKey: ['value-row', 'parent', 'child-value'].includes(rowType) ? null : undefined,
        hideWhenValueZero: true,
        isBold: rowType === 'parent' || rowType === 'total',
    };
}

function buildKeyAccountDraftRow(rowType: KeyAccountRowType): BuilderRow {
    return {
        content: rowType === 'total' ? 'Cộng' : rowType === 'in-words' ? 'Bằng chữ:' : '',
        rowType,
        columnKey: ['value-row', 'parent', 'child-value'].includes(rowType) ? null : undefined,
        valueColumn: ['value-row', 'parent', 'child-value'].includes(rowType) ? null : undefined,
        hideWhenValueZero: ['value-row', 'parent', 'child-value'].includes(rowType),
        isBold: rowType === 'parent' || rowType === 'total',
    };
}

export function useTemplateBuilderCanvas(
    template: () => BuilderTemplate | null,
    canManageTemplates: () => boolean,
    sectionCatalog: () => TemplateSectionDefinition[],
    keyAccountBindingOptions: () => TongHopBindingOption[],
) {
    const sectionDraft = ref<RenderableBuilderSection[]>([]);
    const saveValidationErrors = ref<Record<string, string>>({});
    const saveValidationTarget = ref<string | null>(null);
    const saveValidationScope = ref<'part' | 'canvas' | null>(null);
    const saveValidationSummary = ref<string | null>(null);

    const clearSaveValidationState = (): void => {
        saveValidationErrors.value = {};
        saveValidationTarget.value = null;
        saveValidationScope.value = null;
        saveValidationSummary.value = null;
    };

    const setSaveValidationState = (
        scope: 'part' | 'canvas',
        target: string | null,
        errors: Record<string, string>,
        summary: string,
    ): void => {
        saveValidationScope.value = scope;
        saveValidationTarget.value = target;
        saveValidationErrors.value = { ...errors };
        saveValidationSummary.value = summary;
    };

    watch(
        template,
        (activeTemplate) => {
            sectionDraft.value = activeTemplate?.structure.sections?.map(buildRenderableSection) ?? [];
            clearSaveValidationState();
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
            {
                type,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clearSaveValidationState();
                },
                onError: (errors) => {
                    setSaveValidationState(
                        'part',
                        type,
                        errors,
                        'Không thể thêm part này vào canvas. Cấu hình đang gửi chưa hợp lệ.',
                    );
                },
            },
        );
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

    const addKhoanNppRow = (sectionType: string, rowType: KhoanNppRowType): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KHOAN_NPP_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);

        if (rowType === 'program-loop' && rows.some((row) => row.rowType === 'program-loop')) {
            return;
        }

        section.rows = buildNumberedRows(section.type, [
            ...rows,
            buildKhoanNppDraftRow(rowType),
        ]);
    };

    const addCamCaRow = (sectionType: string, rowType: CamCaRowType): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== CAM_CA_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);

        if (rowType === 'child-program-loop' && rows.some((row) => row.rowType === 'child-program-loop')) {
            return;
        }

        section.rows = buildNumberedRows(section.type, [
            ...rows,
            buildCamCaDraftRow(rowType),
        ]);
    };

    const addKeyAccountRow = (sectionType: string, rowType: KeyAccountRowType): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);

        if (rowType === 'child-program-loop' && rows.some((row) => row.rowType === 'child-program-loop')) {
            return;
        }

        section.rows = buildNumberedRows(section.type, [
            ...rows,
            buildKeyAccountDraftRow(rowType),
        ]);
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

    const addCamCaChildRow = (sectionType: string, parentRowKey: string, rowType: 'child-value' | 'child-program-loop'): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== CAM_CA_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);
        const parentIndex = section.rows.findIndex((row) => row.renderKey === parentRowKey);

        if (parentIndex === -1) {
            return;
        }

        if (rowType === 'child-program-loop' && rows.some((row) => row.rowType === 'child-program-loop')) {
            return;
        }

        let insertIndex = parentIndex + 1;

        while (insertIndex < rows.length && ['child-value', 'child-program-loop'].includes((rows[insertIndex]?.rowType as string) ?? '')) {
            insertIndex += 1;
        }

        rows.splice(insertIndex, 0, buildCamCaDraftRow(rowType));
        section.rows = buildNumberedRows(section.type, rows);
    };

    const addKeyAccountChildRow = (sectionType: string, parentRowKey: string, rowType: 'child-value' | 'child-program-loop'): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
            return;
        }

        const rows = serializeRows(section.type, section.rows ?? []);
        const parentIndex = section.rows.findIndex((row) => row.renderKey === parentRowKey);

        if (parentIndex === -1) {
            return;
        }

        if (rowType === 'child-program-loop' && rows.some((row) => row.rowType === 'child-program-loop')) {
            return;
        }

        let insertIndex = parentIndex + 1;

        while (insertIndex < rows.length && ['child-value', 'child-program-loop'].includes((rows[insertIndex]?.rowType as string) ?? '')) {
            insertIndex += 1;
        }

        rows.splice(insertIndex, 0, buildKeyAccountDraftRow(rowType));
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

    const toggleKhoanNppRowBold = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KHOAN_NPP_SECTION_TYPE) {
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

    const toggleCamCaRowBold = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== CAM_CA_SECTION_TYPE) {
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

    const toggleKeyAccountRowBold = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
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

    const toggleCamCaHideWhenZero = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== CAM_CA_SECTION_TYPE) {
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

    const toggleKeyAccountHideWhenZero = (sectionType: string, rowKey: string): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
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

    const updateCamCaColumnKey = (sectionType: string, rowKey: string, columnKey: string | null): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== CAM_CA_SECTION_TYPE) {
            return;
        }

        const row = section.rows.find((item) => item.renderKey === rowKey);

        if (!row) {
            return;
        }

        row.columnKey = columnKey;
    };

    const updateKeyAccountColumnKey = (sectionType: string, rowKey: string, columnKey: string | null): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
            return;
        }

        const row = section.rows.find((item) => item.renderKey === rowKey);

        if (!row) {
            return;
        }

        row.columnKey = columnKey;

        const selectedOption = keyAccountBindingOptions().find((option) => option.key === columnKey);

        row.valueColumn = selectedOption?.defaultValueColumn ?? null;
    };

    const updateKeyAccountValueColumn = (
        sectionType: string,
        rowKey: string,
        valueColumn: 'quantity' | 'supportRate' | 'amount' | null,
    ): void => {
        const section = findSection(sectionType);

        if (!section || section.type !== KEY_ACCOUNT_SECTION_TYPE) {
            return;
        }

        const row = section.rows.find((item) => item.renderKey === rowKey);

        if (!row) {
            return;
        }

        row.valueColumn = valueColumn;
    };

    const saveCanvasComposition = (): void => {
        const activeTemplate = template();

        if (!activeTemplate || !canManageTemplates()) {
            return;
        }

        router.put(
            route('templates.canvas.update', activeTemplate.id),
            {
                partTypes: sectionDraft.value.map((section) => section.type),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clearSaveValidationState();
                },
                onError: (errors) => {
                    setSaveValidationState(
                        'canvas',
                        null,
                        errors,
                        'Không thể lưu canvas. Một hoặc nhiều cấu hình phần đang không hợp lệ.',
                    );
                },
            },
        );
    };

    const savePart = (partType: string): void => {
        const activeTemplate = template();

        if (!activeTemplate || !canManageTemplates()) {
            return;
        }

        const section = sectionDraft.value.find((item) => item.type === partType);

        if (!section) {
            return;
        }

        router.put(
            route('templates.parts.update', activeTemplate.id),
            {
                partType,
                content: section.kind === 'text' ? (section.content ?? '') : undefined,
                section: section.kind === 'table'
                    ? {
                        type: section.type,
                        label: section.label,
                        description: section.description,
                        kind: section.kind,
                        sourceSheet: section.sourceSheet,
                        rows: serializeRows(section.type, section.rows ?? []),
                    }
                    : undefined,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    clearSaveValidationState();
                },
                onError: (errors) => {
                    setSaveValidationState(
                        'part',
                        partType,
                        errors,
                        'Không thể lưu phần này. Một hoặc nhiều dòng cấu hình chưa hợp lệ.',
                    );
                },
            },
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
                onSuccess: () => {
                    clearSaveValidationState();
                },
                onError: (errors) => {
                    setSaveValidationState(
                        'canvas',
                        null,
                        errors,
                        'Không thể lưu cấu trúc template. Một hoặc nhiều phần đang không hợp lệ.',
                    );
                },
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
        addKhoanNppRow,
        addCamCaRow,
        addKeyAccountRow,
        addTongHopChildRow,
        addCamCaChildRow,
        addKeyAccountChildRow,
        toggleTongHopRowBold,
        toggleKhoanNppRowBold,
        toggleCamCaRowBold,
        toggleKeyAccountRowBold,
        toggleTongHopHideWhenZero,
        toggleCamCaHideWhenZero,
        toggleKeyAccountHideWhenZero,
        updateTongHopColumnKey,
        updateCamCaColumnKey,
        updateKeyAccountColumnKey,
        updateKeyAccountValueColumn,
        saveStructure,
        saveCanvasComposition,
        savePart,
        rehydrateSectionRows,
        clearSaveValidationState,
        saveValidationErrors,
        saveValidationTarget,
        saveValidationScope,
        saveValidationSummary,
        tongHopSectionType: TONG_HOP_SECTION_TYPE,
        khoanNppSectionType: KHOAN_NPP_SECTION_TYPE,
        camCaSectionType: CAM_CA_SECTION_TYPE,
        keyAccountSectionType: KEY_ACCOUNT_SECTION_TYPE,
    };
}
