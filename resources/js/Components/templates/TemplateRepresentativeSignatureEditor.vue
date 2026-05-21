<script setup lang="ts">
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

type SignatureBlock = {
    title: string;
    signatureImageDataUrl: string | null;
    representativeRole: string | null;
    representativeName: string | null;
};

type SignatureSection = {
    type: string;
    label?: string;
    description?: string;
    kind?: 'text' | 'table' | 'composite';
    blocks?: {
        normalCustomer?: SignatureBlock;
        keyAccountCustomer?: SignatureBlock;
    };
};

const props = defineProps<{
    template: {
        id: number;
        structure: {
            sections?: SignatureSection[];
        };
    } | null;
    canManageTemplates: boolean;
}>();

const emptyBlock = (): SignatureBlock => ({
    title: 'Đại diện công ty',
    signatureImageDataUrl: null,
    representativeRole: null,
    representativeName: null,
});

const form = useForm({
    partType: 'representative-signature',
    section: {
        type: 'representative-signature',
        label: 'Khối chữ ký đại diện',
        description: 'Khối chữ ký cuối mail cho Khách thường và Key Account.',
        kind: 'composite' as const,
        blocks: {
            normalCustomer: emptyBlock(),
            keyAccountCustomer: emptyBlock(),
        },
    },
});

const signatureSection = computed(() => (
    props.template?.structure.sections?.find((section) => section.type === 'representative-signature') ?? null
));

const normalizeBlock = (block?: Partial<SignatureBlock> | null): SignatureBlock => ({
    title: block?.title?.trim() || 'Đại diện công ty',
    signatureImageDataUrl: block?.signatureImageDataUrl?.trim() || null,
    representativeRole: block?.representativeRole?.trim() || null,
    representativeName: block?.representativeName?.trim() || null,
});

const hydrateFormFromSection = (): void => {
    form.defaults({
        partType: 'representative-signature',
        section: {
            type: 'representative-signature',
            label: 'Khối chữ ký đại diện',
            description: 'Khối chữ ký cuối mail cho Khách thường và Key Account.',
            kind: 'composite',
            blocks: {
                normalCustomer: normalizeBlock(signatureSection.value?.blocks?.normalCustomer),
                keyAccountCustomer: normalizeBlock(signatureSection.value?.blocks?.keyAccountCustomer),
            },
        },
    });

    form.reset();
    form.clearErrors();
};

watch(signatureSection, hydrateFormFromSection, { immediate: true });

const extractMessages = (prefix: string): string[] => {
    return Array.from(
        new Set(
            Object.entries(form.errors)
                .filter(([key]) => key.startsWith(prefix))
                .map(([, value]) => value),
        ),
    );
};

const normalCustomerErrors = computed(() => extractMessages('section.blocks.normalCustomer.'));
const keyAccountCustomerErrors = computed(() => extractMessages('section.blocks.keyAccountCustomer.'));

const onSignatureFileChange = (event: Event, target: 'normalCustomer' | 'keyAccountCustomer'): void => {
    const input = event.target as HTMLInputElement | null;
    const file = input?.files?.[0];

    if (!file) {
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        form.section.blocks[target].signatureImageDataUrl = typeof reader.result === 'string' ? reader.result : null;
    };
    reader.readAsDataURL(file);
};

const clearSignatureImage = (target: 'normalCustomer' | 'keyAccountCustomer'): void => {
    form.section.blocks[target].signatureImageDataUrl = null;
};

const save = (): void => {
    if (!props.template || !props.canManageTemplates) {
        return;
    }

    form.put(route('templates.parts.update', props.template.id), {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Composite Part
                        </p>
                        <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            Chữ ký đại diện trong canvas
                        </h2>
                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Part này lưu đồng thời 2 block chữ ký cho <code>Khách thường</code> và <code>Key Account</code>. Campaign sẽ dùng block tương ứng theo loại khách khi render email và export PDF.
                        </p>
                    </div>

                    <Button
                        v-if="canManageTemplates && template"
                        label="Lưu phần này"
                        size="small"
                        :loading="form.processing"
                        @click="save"
                    />
                </div>

                <div
                    v-if="!template"
                    class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6"
                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                >
                    Chưa có template để cấu hình chữ ký đại diện.
                </div>

                <div
                    v-else-if="!canManageTemplates"
                    class="rounded-[1.2rem] border px-4 py-3 text-sm leading-6"
                    :style="{ borderColor: 'rgba(245, 158, 11, 0.28)', background: 'var(--dashboard-card-bg)', color: 'var(--dashboard-muted-text)' }"
                >
                    Tài khoản hiện tại chỉ được xem part chữ ký đại diện.
                </div>

                <div v-if="template" class="grid gap-5 xl:grid-cols-2">
                    <section
                        v-for="target in ['normalCustomer', 'keyAccountCustomer'] as const"
                        :key="target"
                        class="rounded-[1.4rem] border p-4 sm:p-5"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                    {{ target === 'normalCustomer' ? 'Khách thường' : 'Key Account' }}
                                </p>
                                <h3 class="mt-2 text-lg font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ form.section.blocks[target].title || 'Đại diện công ty' }}
                                </h3>
                            </div>

                            <div
                                v-if="(target === 'normalCustomer' ? normalCustomerErrors : keyAccountCustomerErrors).length > 0"
                                class="rounded-[1rem] border px-4 py-3 text-sm leading-6"
                                :style="{ borderColor: 'rgba(239, 68, 68, 0.28)', background: 'rgba(239, 68, 68, 0.06)', color: '#991b1b' }"
                            >
                                <div
                                    v-for="message in (target === 'normalCustomer' ? normalCustomerErrors : keyAccountCustomerErrors)"
                                    :key="message"
                                >
                                    {{ message }}
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Text tiêu đề</label>
                                <InputText
                                    v-model="form.section.blocks[target].title"
                                    :disabled="!canManageTemplates"
                                    fluid
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Ảnh chữ ký</label>
                                <input
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    :disabled="!canManageTemplates"
                                    class="block w-full text-sm"
                                    @change="onSignatureFileChange($event, target)"
                                >

                                <div
                                    class="rounded-[1rem] border p-4"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-app-bg)' }"
                                >
                                    <img
                                        v-if="form.section.blocks[target].signatureImageDataUrl"
                                        :src="form.section.blocks[target].signatureImageDataUrl || undefined"
                                        :alt="`Ảnh chữ ký ${target}`"
                                        class="max-h-28 max-w-full object-contain"
                                    >
                                    <p v-else class="text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                                        Chưa có ảnh chữ ký.
                                    </p>
                                </div>

                                <Button
                                    v-if="canManageTemplates && form.section.blocks[target].signatureImageDataUrl"
                                    type="button"
                                    label="Xóa ảnh"
                                    size="small"
                                    severity="secondary"
                                    variant="outlined"
                                    @click="clearSignatureImage(target)"
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Chức vụ</label>
                                <InputText
                                    v-model="form.section.blocks[target].representativeRole"
                                    :disabled="!canManageTemplates"
                                    fluid
                                />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên người đại diện</label>
                                <InputText
                                    v-model="form.section.blocks[target].representativeName"
                                    :disabled="!canManageTemplates"
                                    fluid
                                />
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </template>
    </Card>
</template>
