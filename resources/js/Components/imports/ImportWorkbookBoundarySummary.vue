<script setup lang="ts">
import type { ImportWorkbookBoundary } from '@/Services/imports/useImportWorkbookBoundaryFlow';
import { useImportWorkbookBoundaryPreview } from '@/Services/imports/useImportWorkbookBoundaryPreview';
import Message from 'primevue/message';
import Tab from 'primevue/tab';
import TabList from 'primevue/tablist';
import TabPanel from 'primevue/tabpanel';
import TabPanels from 'primevue/tabpanels';
import Tabs from 'primevue/tabs';
import Tag from 'primevue/tag';
import { toRef } from 'vue';

const props = defineProps<{
    workbookBoundary: ImportWorkbookBoundary | null;
}>();

const { summaryItems, contractMessages, sheetDetails } = useImportWorkbookBoundaryPreview(
    toRef(props, 'workbookBoundary'),
);
</script>

<template>
    <div
        v-if="workbookBoundary"
        class="space-y-4 rounded-[1.2rem] border border-dashed p-4"
        :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
    >
        <div class="space-y-2">
            <p class="text-sm font-medium">Cấu trúc tệp Excel</p>
            <p class="text-sm">
                Đây là trạng thái nhận diện workbook tại thời điểm batch được xử lý và lưu vào hệ thống.
            </p>
        </div>

        <Tabs value="0" lazy>
            <TabList>
                <Tab value="0">Tổng quan</Tab>
                <Tab value="1">Quy ước nhập liệu</Tab>
                <Tab value="2">Chi tiết từng sheet</Tab>
            </TabList>

            <TabPanels>
                <TabPanel value="0">
                    <div class="space-y-4">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div
                                v-for="item in summaryItems"
                                :key="item.label"
                                class="rounded-[1rem] border p-4"
                                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                            >
                                <p class="text-sm font-medium">{{ item.label }}</p>
                                <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ item.value }}
                                </p>
                                <p class="mt-2 text-sm">
                                    {{ item.helper }}
                                </p>
                                <div class="mt-3">
                                    <Tag :value="item.label" :severity="item.severity" rounded />
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <p class="text-sm font-medium">Danh sách các sheet đã nhận diện</p>
                            <div class="flex flex-wrap gap-2">
                                <Tag
                                    v-for="sheet in workbookBoundary.detectedSheets"
                                    :key="sheet"
                                    :value="sheet"
                                    severity="info"
                                    rounded
                                />
                            </div>
                        </div>
                    </div>
                </TabPanel>

                <TabPanel value="1">
                    <div class="space-y-3">
                        <p class="text-sm font-medium">Kiểm tra quy ước 4 sheet nhập liệu</p>
                        <Message
                            v-for="message in contractMessages"
                            :key="message.text"
                            :severity="message.severity"
                            :closable="false"
                        >
                            {{ message.text }}
                        </Message>
                    </div>
                </TabPanel>

                <TabPanel value="2">
                    <div class="space-y-3">
                        <p class="text-sm font-medium">Chi tiết cấu trúc theo từng sheet nhập liệu hợp lệ</p>

                        <div class="grid gap-4">
                            <div
                                v-for="sheetDetail in sheetDetails"
                                :key="`headers-${sheetDetail.name}`"
                                class="rounded-[1rem] border p-4"
                                :style="{ borderColor: 'var(--dashboard-panel-border)' }"
                            >
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                            {{ sheetDetail.name }}
                                        </p>
                                        <p class="text-sm">
                                            {{
                                                sheetDetail.hasHeader
                                                    ? `Đã đọc ${sheetDetail.headerCount} cột ở line 1.`
                                                    : 'Sheet này chưa có dữ liệu header để hiển thị ở boundary hiện tại.'
                                            }}
                                        </p>
                                        <p class="mt-1 text-sm">
                                            {{
                                                sheetDetail.isEmpty
                                                    ? 'Sheet hiện không có dòng dữ liệu nào sau header.'
                                                    : `Có ${sheetDetail.dataRowCount} dòng dữ liệu sau header.`
                                            }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Tag
                                            :value="sheetDetail.headerStatusLabel"
                                            :severity="sheetDetail.hasHeader ? 'success' : 'warn'"
                                            rounded
                                        />
                                        <Tag
                                            :value="sheetDetail.rowStatusLabel"
                                            :severity="sheetDetail.rowStatusSeverity"
                                            rounded
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="sheetDetail.hasHeader"
                                    class="mt-3 flex flex-wrap gap-2"
                                >
                                    <Tag
                                        v-for="(header, headerIndex) in sheetDetail.headers"
                                        :key="`${sheetDetail.name}-${headerIndex}`"
                                        :value="header"
                                        severity="secondary"
                                        rounded
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </TabPanel>
            </TabPanels>
        </Tabs>

        <p class="text-sm leading-6">
            {{ workbookBoundary.nextStep }}
        </p>
    </div>

    <div
        v-else
        class="rounded-[1.2rem] border border-dashed p-4 text-sm"
        :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }"
    >
        Đợt nhập này chưa có thông tin cấu trúc tệp Excel đã được lưu.
    </div>
</template>
