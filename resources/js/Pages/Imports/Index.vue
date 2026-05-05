<script setup lang="ts">
import type { PageProps } from '@/types';
import type { ImportPageProps } from '@/Services/imports/useImportsIndexPage';
import { useImportsIndexPage } from '@/Services/imports/useImportsIndexPage';
import { Head, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layout/AppLayout.vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Message from 'primevue/message';
import Tag from 'primevue/tag';
import Toast from 'primevue/toast';

const props = defineProps<ImportPageProps>();

const page = usePage<PageProps>();
const { acceptedSheetTags, uploadReadinessItems, disabledActionMessage } = useImportsIndexPage(props);
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <Toast position="top-right" />

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(22rem,1fr)]">
            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #content>
                    <div class="space-y-6">
                        <div class="space-y-4">
                            <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                                Slice 1.1-A
                            </p>
                            <h1 class="text-3xl font-semibold tracking-tight sm:text-4xl" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ title }}
                            </h1>
                            <p class="max-w-3xl text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                                {{ description }}
                            </p>
                        </div>

                        <Message severity="info" :closable="false">
                            Màn hình import đã được mở đường để thay thế placeholder cũ. Chức năng upload thật sẽ được bật ở các lát cắt tiếp theo.
                        </Message>

                        <div class="rounded-[1.6rem] border p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        Chính sách tiếp nhận file
                                    </p>
                                    <p class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                        {{ uploadPolicy.acceptedMimeLabel }}
                                    </p>
                                </div>

                                <Tag :value="uploadPolicy.acceptedExtension" severity="success" rounded />
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <Tag
                                    v-for="sheet in acceptedSheetTags"
                                    :key="sheet.label"
                                    :value="sheet.label"
                                    :severity="sheet.severity"
                                    rounded
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <div class="grid gap-6">
                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Sẵn sàng triển khai
                    </template>
                    <template #content>
                        <ul class="space-y-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            <li v-for="item in uploadReadinessItems" :key="item" class="flex gap-3">
                                <i class="pi pi-check-circle mt-1 text-teal-600" />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </template>
                </Card>

                <Card class="sakai-panel rounded-[2rem] border-0">
                    <template #title>
                        Khung thao tác upload
                    </template>
                    <template #content>
                        <div class="space-y-4">
                            <p class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                Đây là shell UI của khu vực import. Logic chọn file và submit sẽ được mở ở lát cắt tiếp theo.
                            </p>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <Button label="Chọn file Excel" icon="pi pi-file-excel" disabled outlined />
                                <Button label="Tải file lên" icon="pi pi-upload" disabled />
                            </div>

                            <div class="rounded-[1.2rem] border border-dashed p-4 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
                                {{ disabledActionMessage }}
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
