<script setup lang="ts">
import type { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import AppLayout from '../layout/AppLayout.vue';

defineProps<{
    title: string;
    description: string;
    capability: string;
    status: string;
}>();

const page = usePage<PageProps>();
</script>

<template>
    <Head :title="title" />

    <AppLayout :app-name="page.props.appName">
        <Card class="sakai-panel rounded-[2rem] border-0">
            <template #content>
                <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(18rem,1fr)]">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.28em] text-teal-600">
                            Khu vực nghiệp vụ
                        </p>
                        <h2 class="mt-4 text-3xl font-semibold tracking-tight" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ title }}
                        </h2>
                        <p class="mt-4 text-base leading-7" :style="{ color: 'var(--dashboard-muted-text)' }">
                            {{ description }}
                        </p>
                    </div>

                    <div class="rounded-[1.5rem] border p-5" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                        <p class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Khả năng đang mở
                        </p>
                        <p class="mt-3 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            {{ capability }}
                        </p>
                        <Tag :value="status" rounded class="mt-4" />
                    </div>
                </div>
            </template>
        </Card>
    </AppLayout>
</template>
