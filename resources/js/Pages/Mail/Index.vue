<script setup lang="ts">
import type { PageProps } from '@/types';
import MailRecipientEmailPreview from '@/Components/mail/MailRecipientEmailPreview.vue';
import DataTableGlobalFilterToolbar from '@/Components/common/DataTableGlobalFilterToolbar.vue';
import { useDataTableGlobalFilter } from '@/Services/useDataTableGlobalFilter';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Textarea from 'primevue/textarea';
import { computed } from 'vue';
import AppLayout from '../../layout/AppLayout.vue';

type BatchOption = {
    batchId: number;
    batchCode: string;
    batchName: string;
    label: string;
};

type TemplateOption = {
    canvasId: number;
    name: string;
    isActive: boolean;
    partCount: number;
    label: string;
};

type CampaignOption = {
    campaignId: number;
    name: string;
    status: string;
    label: string;
};

type RecipientRow = {
    id: number;
    customerCode: string;
    customerFullName: string;
    recipientEmail: string | null;
    customerType: string;
    deliveryStatus: string;
    deliveryStatusLabel: string;
    latestErrorMessage: string | null;
    attemptsCount: number;
};

const props = defineProps<{
    title: string;
    description: string;
    canManageCampaigns: boolean;
    readOnlyNotice: string;
    batchOptions: BatchOption[];
    templateOptions: TemplateOption[];
    campaignOptions: CampaignOption[];
    selectedCampaignId: number | null;
    selectedRecipientId: number | null;
    selectedCampaign: {
        id: number;
        name: string;
        notes: string | null;
        status: string;
        statusLabel: string;
        createdBy: string;
        createdAt: string | null;
        batch: {
            id: number | null;
            batchCode: string | null;
            batchName: string | null;
        };
        template: {
            id: number | null;
            name: string | null;
            isActive: boolean;
        };
        recipientSummary: {
            total: number;
            pending: number;
            sent: number;
            failed: number;
        };
    } | null;
    selectedRecipientPreview: {
        recipient: {
            id: number;
            customerCode: string;
            customerFullName: string;
            recipientEmail: string | null;
            customerType: string;
        };
        subject: {
            templateText: string;
            renderedText: string;
            errors: string[];
        } | null;
        greeting: {
            templateText: string;
            renderedText: string;
            errors: string[];
        } | null;
        tables: Array<{
            type: string;
            label: string;
            title: string;
            sourceSheet: string | null;
            rows: Array<Record<string, unknown>>;
            errors: string[];
        }>;
        errors: string[];
        html: string;
    } | null;
    recipientList: RecipientRow[];
}>();

const page = usePage<PageProps>();

const createForm = useForm({
    name: '',
    import_batch_id: props.batchOptions[0]?.batchId ?? null,
    mail_template_canvas_id: props.templateOptions[0]?.canvasId ?? null,
    notes: '',
});

const {
    filters: recipientFilters,
    globalFilterFields: recipientGlobalFilterFields,
    globalFilterValue: recipientGlobalFilterValue,
    clearGlobalFilter: clearRecipientGlobalFilter,
} = useDataTableGlobalFilter<RecipientRow>([
    'customerCode',
    'customerFullName',
    'recipientEmail',
    'customerType',
    'deliveryStatusLabel',
    'latestErrorMessage',
]);

const selectedCampaignOption = computed(() =>
    props.campaignOptions.find((campaign) => campaign.campaignId === props.selectedCampaignId)?.campaignId ?? null,
);
const isPreviewDialogVisible = computed(() => props.selectedRecipientPreview !== null);

const submitCreate = (): void => {
    createForm.post(route('mail.campaigns.store'), {
        preserveScroll: true,
    });
};

const openCampaign = (campaignId: number | null): void => {
    router.get(
        route('mail.index'),
        campaignId ? { campaign: campaignId } : {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const openRecipientPreview = (recipientId: number): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.get(
        route('mail.index'),
        {
            campaign: props.selectedCampaignId,
            recipient: recipientId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const closeRecipientPreview = (): void => {
    if (!props.selectedCampaignId) {
        return;
    }

    router.get(
        route('mail.index'),
        {
            campaign: props.selectedCampaignId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,1.5fr)]">
            <div class="space-y-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #content>
                        <div class="space-y-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-500">
                                        Gửi mail chiết khấu
                                    </p>
                                    <h1 class="mt-4 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ title }}
                                    </h1>
                                    <p class="mt-4 text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ description }}
                                    </p>
                                </div>

                                <Tag :value="canManageCampaigns ? 'Có quyền điều phối' : 'Chỉ xem'" :severity="canManageCampaigns ? 'success' : 'warn'" rounded />
                            </div>

                            <div
                                class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                                :style="{
                                    borderColor: canManageCampaigns ? 'rgba(20, 184, 166, 0.32)' : 'rgba(245, 158, 11, 0.32)',
                                    background: canManageCampaigns ? 'rgba(20, 184, 166, 0.08)' : 'rgba(245, 158, 11, 0.08)',
                                    color: 'var(--dashboard-muted-text)',
                                }"
                            >
                                <template v-if="canManageCampaigns">
                                    Tạo chiến dịch từ batch dữ liệu đã aggregate và template email đã sẵn sàng, sau đó hệ thống sẽ materialize danh sách người nhận để rà soát trước khi bước sang preview đầy đủ và dispatch hàng loạt.
                                </template>
                                <template v-else>
                                    {{ readOnlyNotice }}
                                </template>
                            </div>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Tạo chiến dịch gửi mail
                    </template>
                    <template #content>
                        <form class="space-y-4" @submit.prevent="submitCreate">
                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên chiến dịch</label>
                                <InputText v-model="createForm.name" fluid :disabled="!canManageCampaigns" />
                                <small v-if="createForm.errors.name" class="text-red-500">{{ createForm.errors.name }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Batch nhập liệu</label>
                                <Select
                                    v-model="createForm.import_batch_id"
                                    :options="batchOptions"
                                    option-label="label"
                                    option-value="batchId"
                                    filter
                                    fluid
                                    :disabled="!canManageCampaigns"
                                    placeholder="Chọn batch đã aggregate"
                                />
                                <small v-if="createForm.errors.import_batch_id" class="text-red-500">{{ createForm.errors.import_batch_id }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Template email</label>
                                <Select
                                    v-model="createForm.mail_template_canvas_id"
                                    :options="templateOptions"
                                    option-label="label"
                                    option-value="canvasId"
                                    filter
                                    fluid
                                    :disabled="!canManageCampaigns"
                                    placeholder="Chọn template canvas"
                                />
                                <small v-if="createForm.errors.mail_template_canvas_id" class="text-red-500">{{ createForm.errors.mail_template_canvas_id }}</small>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Ghi chú</label>
                                <Textarea v-model="createForm.notes" rows="4" fluid :disabled="!canManageCampaigns" />
                                <small v-if="createForm.errors.notes" class="text-red-500">{{ createForm.errors.notes }}</small>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    type="submit"
                                    label="Tạo chiến dịch"
                                    :loading="createForm.processing"
                                    :disabled="!canManageCampaigns || batchOptions.length === 0 || templateOptions.length === 0"
                                />
                            </div>
                        </form>
                    </template>
                </Card>
            </div>

            <div class="space-y-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Chiến dịch đang xem
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Chọn chiến dịch</label>
                                <Select
                                    :model-value="selectedCampaignOption"
                                    :options="campaignOptions"
                                    option-label="label"
                                    option-value="campaignId"
                                    filter
                                    show-clear
                                    fluid
                                    placeholder="Chọn chiến dịch đã tạo"
                                    @update:model-value="openCampaign"
                                />
                            </div>

                            <div
                                v-if="!selectedCampaign"
                                class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6"
                                :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                            >
                                Chưa có chiến dịch nào được tạo. Sau khi tạo chiến dịch đầu tiên, danh sách người nhận đã aggregate sẽ xuất hiện tại đây.
                            </div>

                            <template v-else>
                                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Tổng người nhận</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.total }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Chưa gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.pending }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Đã gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.sent }}</p>
                                    </div>
                                    <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                        <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Lỗi gửi</p>
                                        <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ selectedCampaign.recipientSummary.failed }}</p>
                                    </div>
                                </div>

                                <div class="rounded-[1.4rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <h2 class="text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                                {{ selectedCampaign.name }}
                                            </h2>
                                            <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Batch: {{ selectedCampaign.batch.batchCode }} - {{ selectedCampaign.batch.batchName || 'Chưa đặt tên' }}
                                            </p>
                                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                                Template: {{ selectedCampaign.template.name }}
                                            </p>
                                        </div>

                                        <Tag :value="selectedCampaign.statusLabel" severity="info" rounded />
                                    </div>

                                    <p v-if="selectedCampaign.notes" class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        {{ selectedCampaign.notes }}
                                    </p>
                                </div>
                            </template>
                        </div>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Danh sách người nhận đã aggregate
                    </template>
                    <template #content>
                        <div v-if="!selectedCampaign" class="rounded-[1.4rem] border px-5 py-4 text-sm leading-6" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }">
                            Chọn một chiến dịch để xem danh sách người nhận.
                        </div>

                        <div v-else class="space-y-3">
                            <DataTableGlobalFilterToolbar
                                v-model="recipientGlobalFilterValue"
                                placeholder="Tìm theo mã số, khách hàng, email, loại khách, trạng thái"
                                @clear="clearRecipientGlobalFilter"
                            />

                            <DataTable
                                v-model:filters="recipientFilters"
                                :value="recipientList"
                                :global-filter-fields="recipientGlobalFilterFields"
                                paginator
                                :rows="10"
                                responsive-layout="scroll"
                                class="p-datatable-sm"
                            >
                                <Column field="customerCode" header="Mã số" />
                                <Column field="customerFullName" header="Mã & tên khách hàng" />
                                <Column header="Email">
                                    <template #body="{ data }">
                                        <span :style="{ color: data.recipientEmail ? 'var(--dashboard-strong-text)' : 'var(--dashboard-muted-text)' }">
                                            {{ data.recipientEmail || 'Chưa có email' }}
                                        </span>
                                    </template>
                                </Column>
                                <Column field="customerType" header="Loại khách" />
                                <Column header="Trạng thái gửi">
                                    <template #body="{ data }">
                                        <Tag :value="data.deliveryStatusLabel" :severity="data.deliveryStatus === 'failed' ? 'danger' : data.deliveryStatus === 'sent' ? 'success' : 'warn'" rounded />
                                    </template>
                                </Column>
                                <Column header="Lỗi gần nhất">
                                    <template #body="{ data }">
                                        <span :style="{ color: 'var(--dashboard-muted-text)' }">
                                            {{ data.latestErrorMessage || 'Không có' }}
                                        </span>
                                    </template>
                                </Column>
                                <Column header="Thao tác">
                                    <template #body="{ data }">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                type="button"
                                                label="Xem trước email"
                                                size="small"
                                                outlined
                                                @click="openRecipientPreview(data.id)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DataTable>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <Dialog
            :visible="isPreviewDialogVisible"
            modal
            maximizable
            :style="{ width: 'min(1120px, 96vw)' }"
            header="Xem trước toàn bộ email"
            @update:visible="(visible) => { if (!visible) closeRecipientPreview() }"
        >
            <MailRecipientEmailPreview
                v-if="selectedRecipientPreview"
                :preview="selectedRecipientPreview"
            />
        </Dialog>
    </AppLayout>
</template>
