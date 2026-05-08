<script setup lang="ts">
import { computed, ref } from 'vue';
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import TemplateBuilderCanvas from '@/Components/templates/TemplateBuilderCanvas.vue';
import TemplateCreateFormCard from '@/Components/templates/TemplateCreateFormCard.vue';
import TemplateVariableContractCard from '@/Components/templates/TemplateVariableContractCard.vue';
import TemplatePartVersionsCard from '@/Components/templates/TemplatePartVersionsCard.vue';
import TemplateSubjectPreviewCard from '@/Components/templates/TemplateSubjectPreviewCard.vue';
import TemplateGreetingPreviewCard from '@/Components/templates/TemplateGreetingPreviewCard.vue';
import TemplateTongHopTablePreviewCard from '@/Components/templates/TemplateTongHopTablePreviewCard.vue';
import TemplateKhoanNppTablePreviewCard from '@/Components/templates/TemplateKhoanNppTablePreviewCard.vue';
import type { TemplateTableRowType } from '@/Services/templates/useTemplateBuilderCanvas';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import AppLayout from '../../layout/AppLayout.vue';

const props = defineProps<{
    title: string;
    description: string;
    currentSlice: {
        code: string;
        label: string;
    };
    canManageTemplates: boolean;
    writeCapabilities: string[];
    readOnlyNotice: string;
    templateList: Array<{
        id: number;
        name: string;
        subjectTemplate: string;
        isActive: boolean;
        statusLabel: string;
        sectionCount: number;
        createdBy: string;
        updatedAt: string | null;
    }>;
    activeTemplateId: number | null;
    builderTemplate: {
        id: number;
        name: string;
        subjectTemplate: string;
        structure: {
            version?: string;
            sections?: Array<{
                type: string;
                label?: string;
                description?: string;
                kind?: 'text' | 'table';
                sourceSheet?: string | null;
                content?: string;
                rows?: Array<{
                    content: string;
                    indentLevel?: number;
                    rowType?: 'blank' | 'parent' | 'child' | 'data' | 'total' | 'text';
                    columnKey?: string | null;
                    hideWhenValueZero?: boolean;
                    isBold?: boolean;
                }>;
            }>;
        };
    } | null;
    canvasComposition: {
        canvasId: number;
        canvasName: string;
        storageModel: string;
        partSelections: Array<{
            partType: string;
            code: string;
            label: string;
            kind: 'text' | 'table';
            sourceSheet: string | null;
            maxActiveVersions: number;
            activePolicy: string;
            isConfigured: boolean;
            selectedVersion: {
                versionKey: string;
                versionLabel: string;
                selectionMode: string;
                mailTemplateId?: number;
                versionNo?: number;
            } | null;
            contentSummary: {
                hasContent: boolean;
                rowCount: number;
                textLength: number;
            };
        }>;
    } | null;
    partVersionGroups: Array<{
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
    }>;
    subjectPreview: {
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
    greetingPreview: {
        templateText: string;
        renderedText: string;
        errors: string[];
        sample: {
            batchId: number;
            batchCode: string;
            customerCode: string;
            customerFullName: string;
            month: string;
            address: string;
            feedCategory: string;
        };
    } | null;
    tongHopTablePreview: {
        title: string;
        sourceSheet: string;
        rows: Array<{
            content: string;
            indentLevel: number;
            rowType?: string;
            columnKey?: string | null;
            hideWhenValueZero?: boolean;
            isBold?: boolean;
            numbering: string;
            styleRole: string;
            fontWeight: string;
            value: string;
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
    } | null;
    tongHopPreviewCustomers: Array<{
        recordId: number;
        customerCode: string;
        customerFullName: string;
        label: string;
        batchCode: string;
        month: string;
    }>;
    selectedTongHopPreviewRecordId: number | null;
    khoanNppTablePreview: {
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
    khoanNppPreviewCustomers: Array<{
        recordId: number;
        customerCode: string;
        customerFullName: string;
        label: string;
        batchCode: string;
        month: string;
    }>;
    selectedKhoanNppPreviewRecordId: number | null;
    tongHopBindingOptions: Array<{
        key: string;
        label: string;
        valuePreview: string;
    }>;
    templateParts: Array<{
        code: string;
        type: string;
        label: string;
        description: string;
        kind: 'text' | 'table';
        sourceSheet: string | null;
        maxActiveVersions: number;
    }>;
    templateVariables: Array<{
        token: string;
        label: string;
        description: string;
    }>;
    constraints: string[];
    nextSlice: {
        code: string;
        label: string;
    };
}>();

const page = usePage<PageProps>();
const tongHopDraftSections = ref<Array<{
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
}>>([]);
const khoanNppDraftSections = ref<Array<{
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
}>>([]);

const partVersionGroupByType = computed(() =>
    Object.fromEntries(
        (props.partVersionGroups ?? []).map((group) => [group.partType, group]),
    ) as Record<string, (typeof props.partVersionGroups)[number]>,
);

const tongHopDraftSection = computed(() =>
    tongHopDraftSections.value.find((section) => section.type === 'tong-hop-table') ?? null,
);
const khoanNppDraftSection = computed(() =>
    khoanNppDraftSections.value.find((section) => section.type === 'khoan-npp-table') ?? null,
);
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <div class="space-y-6">
            <section class="sakai-panel rounded-[2rem] border-0 px-5 py-5 sm:px-6 sm:py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 max-w-3xl flex-1">
                        <p class="break-words whitespace-normal text-sm font-semibold uppercase tracking-[0.28em] text-teal-500">
                            Template email
                        </p>
                        <h1 class="mt-4 break-words text-3xl font-semibold tracking-tight md:text-4xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ title }}
                        </h1>
                        <p class="mt-4 break-words text-base leading-7 md:text-lg" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ description }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3 lg:max-w-sm lg:justify-end">
                        <Tag :value="`Slice ${currentSlice.code}`" severity="info" rounded />
                        <Tag :value="canManageTemplates ? 'Có quyền chỉnh sửa' : 'Chỉ xem'" :severity="canManageTemplates ? 'success' : 'warn'" rounded />
                    </div>
                </div>

                <div
                    class="mt-6 rounded-[1.4rem] border px-5 py-4"
                    :style="{
                        borderColor: canManageTemplates ? 'rgba(20, 184, 166, 0.32)' : 'rgba(245, 158, 11, 0.32)',
                        background: canManageTemplates ? 'rgba(20, 184, 166, 0.08)' : 'rgba(245, 158, 11, 0.08)',
                    }"
                >
                    <template v-if="canManageTemplates">
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Bề mặt chỉnh sửa đang mở
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2.5">
                            <Tag
                                v-for="capability in writeCapabilities"
                                :key="capability"
                                :value="capability"
                                severity="success"
                                rounded
                            />
                        </div>
                    </template>

                    <template v-else>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-500">
                            Chế độ chỉ xem
                        </p>
                        <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ readOnlyNotice }}
                        </p>
                    </template>
                </div>
            </section>

            <section class="sakai-panel rounded-[2rem] border-0 p-4 sm:p-5">
                <Tabs value="0" lazy scrollable>
                    <TabList
                        class="sticky top-0 z-10 rounded-[1.2rem] border px-2 py-2"
                        :style="{
                            borderColor: 'var(--dashboard-panel-border)',
                            background: 'color-mix(in srgb, var(--dashboard-card-bg) 92%, transparent)',
                        }"
                    >
                        <Tab value="0">Canvas chính</Tab>
                        <Tab value="1">Subject</Tab>
                        <Tab value="2">Lời chào</Tab>
                        <Tab value="3">Bảng chế độ tháng</Tab>
                        <Tab value="4">Bảng chương trình khoán đặc biệt</Tab>
                        <Tab value="5">Bảng chiết khấu cám cá</Tab>
                        <Tab value="6">Bảng chiết khấu Key Account</Tab>
                    </TabList>

                    <TabPanels class="mt-4">
                        <TabPanel value="0">
                            <div class="space-y-6">
                                <TemplateCreateFormCard v-if="canManageTemplates" />

                                <Card v-if="canvasComposition" class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-4">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                        Canvas Composition
                                                    </p>
                                                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        {{ canvasComposition.canvasName }}
                                                    </h2>
                                                    <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                        Slice `2.0-R3` đã chuyển write path sang từng part và canvas composition. `mail_templates` hiện chỉ còn là bridge đọc cho UI legacy.
                                                    </p>
                                                </div>

                                                <Tag :value="canvasComposition.storageModel" severity="info" rounded />
                                            </div>

                                            <div class="grid gap-3 xl:grid-cols-2">
                                                <article
                                                    v-for="part in canvasComposition.partSelections"
                                                    :key="part.partType"
                                                    class="rounded-[1.2rem] border p-4"
                                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                                                >
                                                    <div class="flex flex-col gap-3">
                                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                                            <div>
                                                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                                                    {{ part.code }}
                                                                </p>
                                                                <h3 class="mt-2 text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                    {{ part.label }}
                                                                </h3>
                                                            </div>

                                                            <Tag :value="part.isConfigured ? 'Đã ghép vào canvas' : 'Chưa cấu hình'" :severity="part.isConfigured ? 'success' : 'secondary'" rounded />
                                                        </div>

                                                        <div class="flex flex-wrap gap-2">
                                                            <Tag :value="part.maxActiveVersions === 1 ? '1 version active' : `${part.maxActiveVersions} version active`" severity="warn" rounded />
                                                            <Tag v-if="part.sourceSheet" :value="`Sheet: ${part.sourceSheet}`" severity="info" rounded />
                                                            <Tag v-if="part.selectedVersion" :value="part.selectedVersion.versionLabel" severity="contrast" rounded />
                                                        </div>

                                                        <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                            <template v-if="part.kind === 'text'">
                                                                Text length: {{ part.contentSummary.textLength }} ký tự
                                                            </template>
                                                            <template v-else>
                                                                Row count: {{ part.contentSummary.rowCount }}
                                                            </template>
                                                        </p>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-4">
                                            <div>
                                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                    Canvas workspace
                                                </p>
                                                <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                    Bề mặt ghép version vào email template
                                                </h2>
                                                <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                    Tab `Canvas chính` chỉ còn chịu trách nhiệm composition: xem trạng thái ghép, kiểm tra template list và theo dõi part nào đang được chọn trong canvas. Việc sửa nội dung thật được chuyển xuống các tab part riêng.
                                                </p>
                                            </div>

                                            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                                <article
                                                    v-for="part in templateParts"
                                                    :key="part.code"
                                                    class="rounded-[1.2rem] border p-4"
                                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                                                >
                                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                                        {{ part.code }}
                                                    </p>
                                                    <h3 class="mt-2 text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        {{ part.label }}
                                                    </h3>
                                                    <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                        {{ part.description }}
                                                    </p>
                                                </article>
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-4">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                        Danh sách template
                                                    </p>
                                                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                        Trạng thái template hiện có
                                                    </h2>
                                                </div>

                                                <Tag
                                                    :value="activeTemplateId ? `Template active #${activeTemplateId}` : 'Chưa có template active'"
                                                    :severity="activeTemplateId ? 'success' : 'warn'"
                                                    rounded
                                                />
                                            </div>

                                            <div v-if="templateList.length === 0" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                                                Hệ thống chưa có template email nào được lưu. Bước tiếp theo sẽ mở form tạo template đầu tiên.
                                            </div>

                                            <div v-else class="space-y-3">
                                                <article
                                                    v-for="templateItem in templateList"
                                                    :key="templateItem.id"
                                                    class="rounded-[1.4rem] border p-4"
                                                    :style="{
                                                        borderColor: templateItem.isActive ? 'rgba(20, 184, 166, 0.36)' : 'var(--dashboard-panel-border)',
                                                        background: 'var(--dashboard-card-bg)',
                                                    }"
                                                >
                                                    <div class="flex flex-col gap-3">
                                                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                            <div>
                                                                <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                                    {{ templateItem.name }}
                                                                </p>
                                                                <p class="mt-1 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                                    {{ templateItem.subjectTemplate }}
                                                                </p>
                                                            </div>

                                                            <Tag :value="templateItem.statusLabel" :severity="templateItem.isActive ? 'success' : 'secondary'" rounded />
                                                        </div>

                                                        <div class="flex flex-wrap gap-2.5">
                                                            <Tag :value="`${templateItem.sectionCount} phần`" severity="info" rounded />
                                                            <Tag :value="`Tạo bởi: ${templateItem.createdBy}`" severity="secondary" rounded />
                                                        </div>
                                                    </div>
                                                </article>
                                            </div>
                                        </div>
                                    </template>
                                </Card>

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-4">
                                            <div>
                                                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                    Ràng buộc đã chốt
                                                </p>
                                                <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                    Phạm vi của {{ currentSlice.code }}
                                                </h2>
                                            </div>

                                            <ul class="space-y-3">
                                                <li
                                                    v-for="constraint in constraints"
                                                    :key="constraint"
                                                    class="rounded-2xl border px-4 py-3 text-sm leading-6"
                                                    :style="{
                                                        borderColor: 'var(--dashboard-panel-border)',
                                                        background: 'var(--dashboard-card-bg)',
                                                        color: 'var(--dashboard-muted-text)',
                                                    }"
                                                >
                                                    {{ constraint }}
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </Card>

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-3">
                                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                Bước tiếp theo
                                            </p>
                                            <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ nextSlice.code }} - {{ nextSlice.label }}
                                            </h2>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Sau lát này, module template sẽ có form tạo template đầu tiên để bắt đầu lưu subject và cấu trúc body cơ bản.
                                            </p>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </TabPanel>

                        <TabPanel value="1">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Subject"
                                    description="Chọn và theo dõi version hiện có của part `Subject`. Builder phía dưới chỉnh trực tiếp version đang được ghép vào canvas."
                                    :group="partVersionGroupByType['subject'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['subject']"
                                />

                                <TemplateVariableContractCard
                                    :variables="templateVariables"
                                    :can-manage-templates="canManageTemplates"
                                />

                                <TemplateSubjectPreviewCard :preview="subjectPreview" />
                            </div>
                        </TabPanel>

                        <TabPanel value="2">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Lời chào"
                                    description="Part `Lời chào` có lifecycle riêng với policy active khác `Subject`. Tab này chỉ tập trung vào version của lời chào."
                                    :group="partVersionGroupByType['greeting'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['greeting']"
                                />

                                <TemplateVariableContractCard
                                    :variables="templateVariables"
                                    :can-manage-templates="canManageTemplates"
                                />

                                <TemplateGreetingPreviewCard :preview="greetingPreview" />
                            </div>
                        </TabPanel>

                        <TabPanel value="3">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Bảng chế độ tháng"
                                    description="Quản lý version độc lập cho part `Tổng hợp`. Canvas chỉ ghép một version đang chọn của part này."
                                    :group="partVersionGroupByType['tong-hop-table'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['tong-hop-table']"
                                    @draft-change="tongHopDraftSections = $event"
                                />

                                <TemplateTongHopTablePreviewCard
                                    :preview="tongHopTablePreview"
                                    :draft-section="tongHopDraftSection"
                                    :binding-options="tongHopBindingOptions"
                                    :preview-customers="tongHopPreviewCustomers"
                                    :selected-record-id="selectedTongHopPreviewRecordId"
                                />
                            </div>
                        </TabPanel>

                        <TabPanel value="4">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Bảng chương trình khoán đặc biệt"
                                    description="Part `Khoán NPP` có version riêng, active riêng và được ghép linh động vào canvas."
                                    :group="partVersionGroupByType['khoan-npp-table'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['khoan-npp-table']"
                                    @draft-change="khoanNppDraftSections = $event"
                                />

                                <TemplateKhoanNppTablePreviewCard
                                    :preview="khoanNppTablePreview"
                                    :draft-section="khoanNppDraftSection"
                                    :preview-customers="khoanNppPreviewCustomers"
                                    :selected-record-id="selectedKhoanNppPreviewRecordId"
                                />
                            </div>
                        </TabPanel>

                        <TabPanel value="5">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Bảng chiết khấu cám cá"
                                    description="Part `Cám cá` được quản lý như một tập version độc lập với canvas."
                                    :group="partVersionGroupByType['cam-ca-table'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['cam-ca-table']"
                                />

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-3">
                                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                Bảng chiết khấu cám cá
                                            </p>
                                            <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                Tab này sẽ nhận preview riêng cho dữ liệu `Cám cá`
                                            </h2>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Tab này giờ đã có builder canvas riêng cho section Cám cá. Phần preview dữ liệu thật sẽ được nối tiếp ở lát kế tiếp.
                                            </p>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </TabPanel>

                        <TabPanel value="6">
                            <div class="space-y-6">
                                <TemplatePartVersionsCard
                                    title="Version của Bảng chiết khấu Key Account"
                                    description="Part `Key Account` có lifecycle version riêng và chỉ được ghép vào canvas qua composition binding."
                                    :group="partVersionGroupByType['key-account-table'] ?? null"
                                />

                                <TemplateBuilderCanvas
                                    :template="builderTemplate"
                                    :can-manage-templates="canManageTemplates"
                                    :section-catalog="templateParts"
                                    :tong-hop-binding-options="tongHopBindingOptions"
                                    :visible-section-types="['key-account-table']"
                                />

                                <Card class="sakai-panel rounded-[2rem] border-0">
                                    <template #content>
                                        <div class="space-y-3">
                                            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                                                Bảng chiết khấu Key Account
                                            </p>
                                            <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                Tab này sẽ nhận preview riêng cho dữ liệu `Key Account`
                                            </h2>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Tab này giờ đã có builder canvas riêng cho section Key Account. Phần preview dữ liệu thật sẽ được nối tiếp ở lát kế tiếp.
                                            </p>
                                        </div>
                                    </template>
                                </Card>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </section>
        </div>
    </AppLayout>
</template>
