<script setup lang="ts">
import type { AggregatePreview } from '@/Services/imports/useAggregatePreviewFlow';
import type { CamCaPreview } from '@/Services/imports/useCamCaPreviewFlow';
import type { KeyAccountPreview } from '@/Services/imports/useKeyAccountPreviewFlow';
import type { KhoanNppPreview } from '@/Services/imports/useKhoanNppPreviewFlow';
import type { TongHopPreview } from '@/Services/imports/useTongHopPreviewFlow';
import ImportAggregatePreview from '@/Components/imports/ImportAggregatePreview.vue';
import ImportCamCaPreview from '@/Components/imports/ImportCamCaPreview.vue';
import ImportKeyAccountPreview from '@/Components/imports/ImportKeyAccountPreview.vue';
import ImportKhoanNppPreview from '@/Components/imports/ImportKhoanNppPreview.vue';
import ImportTongHopPreview from '@/Components/imports/ImportTongHopPreview.vue';
import TabPanel from 'primevue/tabpanel';
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import Message from 'primevue/message';
import { ref, watch } from 'vue';

const props = defineProps<{
    aggregatePreview: AggregatePreview | null;
    tongHopPreview: TongHopPreview | null;
    khoanNppPreview: KhoanNppPreview | null;
    camCaPreview: CamCaPreview | null;
    keyAccountPreview: KeyAccountPreview | null;
    isLoadingAggregatePreview: boolean;
    isLoadingTongHopPreview: boolean;
    isLoadingKhoanNppPreview: boolean;
    isLoadingCamCaPreview: boolean;
    isLoadingKeyAccountPreview: boolean;
    aggregateErrorMessage: string;
    tongHopErrorMessage: string;
    khoanNppErrorMessage: string;
    camCaErrorMessage: string;
    keyAccountErrorMessage: string;
    canPreviewAggregate: boolean;
    canPreviewTongHop: boolean;
    canPreviewKhoanNpp: boolean;
    canPreviewCamCa: boolean;
    canPreviewKeyAccount: boolean;
    isProcessing: boolean;
    processingError: string;
}>();

const emit = defineEmits<{
    loadAggregate: [];
    loadTongHop: [];
    loadKhoanNpp: [];
    loadCamCa: [];
    loadKeyAccount: [];
}>();

const activeTab = ref('0');

watch(
    activeTab,
    (value) => {
        if (value === '0' && !props.aggregatePreview && props.canPreviewAggregate && !props.isLoadingAggregatePreview) {
            emit('loadAggregate');
        }

        if (value === '1' && !props.tongHopPreview && props.canPreviewTongHop && !props.isLoadingTongHopPreview) {
            emit('loadTongHop');
        }

        if (value === '2' && !props.khoanNppPreview && props.canPreviewKhoanNpp && !props.isLoadingKhoanNppPreview) {
            emit('loadKhoanNpp');
        }

        if (value === '3' && !props.camCaPreview && props.canPreviewCamCa && !props.isLoadingCamCaPreview) {
            emit('loadCamCa');
        }

        if (value === '4' && !props.keyAccountPreview && props.canPreviewKeyAccount && !props.isLoadingKeyAccountPreview) {
            emit('loadKeyAccount');
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-4">
        <Message v-if="processingError" severity="error" :closable="false">
            {{ processingError }}
        </Message>

        <div
            v-if="isProcessing"
            class="flex items-center gap-3 rounded-[1.2rem] border border-dashed p-5 text-sm"
            :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
        >
            <i class="pi pi-spin pi-spinner text-lg" />
            <span>Đang xử lý dữ liệu nhập — phân tích 4 sheet và hợp nhất theo Mã số. Vui lòng chờ...</span>
        </div>

        <div
            v-else-if="!aggregatePreview && !processingError"
            class="rounded-[1.2rem] border border-dashed p-5 text-sm"
            :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
        >
            Dữ liệu sẽ xuất hiện tại đây sau khi upload và xử lý thành công.
        </div>

        <Tabs v-else v-model:value="activeTab" lazy scrollable>
            <TabList class="sticky top-0 z-10 rounded-[1rem] border px-2 py-2 backdrop-blur-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'color-mix(in srgb, var(--dashboard-card-bg) 88%, transparent)' }">
                <Tab value="0">
                    <span class="sm:hidden">Hợp nhất</span>
                    <span class="hidden sm:inline">Dữ liệu hợp nhất</span>
                </Tab>
                <Tab value="1">
                    <span>Tổng hợp</span>
                </Tab>
                <Tab value="2">
                    <span class="sm:hidden">NPP</span>
                    <span class="hidden sm:inline">Khoán NPP</span>
                </Tab>
                <Tab value="3">
                    <span>Cám cá</span>
                </Tab>
                <Tab value="4">
                    <span class="sm:hidden">KA</span>
                    <span class="hidden sm:inline">Key Account</span>
                </Tab>
            </TabList>

            <TabPanels>
                <!-- Tab 1 — Dữ liệu hợp nhất (Aggregator) -->
                <TabPanel value="0">
                    <ImportAggregatePreview
                        :preview="aggregatePreview"
                        :is-loading="isLoadingAggregatePreview"
                        :error-message="aggregateErrorMessage"
                        :can-preview="canPreviewAggregate"
                        @load="emit('loadAggregate')"
                    />
                </TabPanel>

                <!-- Tab 2 — Tổng hợp -->
                <TabPanel value="1">
                    <ImportTongHopPreview
                        :preview="tongHopPreview"
                        :is-loading="isLoadingTongHopPreview"
                        :error-message="tongHopErrorMessage"
                        :can-preview="canPreviewTongHop"
                        @load="emit('loadTongHop')"
                    />
                </TabPanel>

                <!-- Tab 3 — Khoán NPP -->
                <TabPanel value="2">
                    <ImportKhoanNppPreview
                        :preview="khoanNppPreview"
                        :is-loading="isLoadingKhoanNppPreview"
                        :error-message="khoanNppErrorMessage"
                        :can-preview="canPreviewKhoanNpp"
                        @load="emit('loadKhoanNpp')"
                    />
                </TabPanel>

                <!-- Tab 4 — Cám cá -->
                <TabPanel value="3">
                    <ImportCamCaPreview
                        :preview="camCaPreview"
                        :is-loading="isLoadingCamCaPreview"
                        :error-message="camCaErrorMessage"
                        :can-preview="canPreviewCamCa"
                        @load="emit('loadCamCa')"
                    />
                </TabPanel>

                <!-- Tab 5 — Key Account -->
                <TabPanel value="4">
                    <ImportKeyAccountPreview
                        :preview="keyAccountPreview"
                        :is-loading="isLoadingKeyAccountPreview"
                        :error-message="keyAccountErrorMessage"
                        :can-preview="canPreviewKeyAccount"
                        @load="emit('loadKeyAccount')"
                    />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </div>
</template>
