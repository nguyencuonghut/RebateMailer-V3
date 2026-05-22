<script setup lang="ts">
import { computed, reactive, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import type { PageProps } from '@/types';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';

type SignatureBlock = {
    title?: string;
    signatureImageDataUrl?: string | null;
    representativeRole?: string;
    representativeName?: string;
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
} | null;

const props = defineProps<{
    templateId: number | null;
    canManageTemplates: boolean;
    section: SignatureSection;
}>();

const page = usePage<PageProps>();
const form = reactive({
    normalCustomer: {
        title: 'Đại diện công ty',
        signatureImageDataUrl: null as string | null,
        representativeRole: '',
        representativeName: '',
    },
    keyAccountCustomer: {
        title: 'Đại diện công ty',
        signatureImageDataUrl: null as string | null,
        representativeRole: '',
        representativeName: '',
    },
});

const processingState = reactive({
    saving: false,
    uploadingBlock: null as 'normalCustomer' | 'keyAccountCustomer' | null,
});

const cloneSectionIntoForm = (): void => {
    const normal = props.section?.blocks?.normalCustomer ?? {};
    const keyAccount = props.section?.blocks?.keyAccountCustomer ?? {};

    form.normalCustomer.title = normal.title ?? 'Đại diện công ty';
    form.normalCustomer.signatureImageDataUrl = normal.signatureImageDataUrl ?? null;
    form.normalCustomer.representativeRole = normal.representativeRole ?? '';
    form.normalCustomer.representativeName = normal.representativeName ?? '';

    form.keyAccountCustomer.title = keyAccount.title ?? 'Đại diện công ty';
    form.keyAccountCustomer.signatureImageDataUrl = keyAccount.signatureImageDataUrl ?? null;
    form.keyAccountCustomer.representativeRole = keyAccount.representativeRole ?? '';
    form.keyAccountCustomer.representativeName = keyAccount.representativeName ?? '';
};

watch(
    () => props.section,
    () => {
        cloneSectionIntoForm();
    },
    { immediate: true, deep: true },
);

const errorBag = computed<Record<string, string>>(() => {
    const errors = page.props.errors ?? {};

    return Object.fromEntries(
        Object.entries(errors).filter(([key]) => key.startsWith('section.blocks.')),
    );
});

const resolveBlockErrors = (block: 'normalCustomer' | 'keyAccountCustomer'): string[] =>
    Array.from(
        new Set(
            Object.entries(errorBag.value)
                .filter(([key]) => key.startsWith(`section.blocks.${block}.`))
                .map(([, message]) => message),
        ),
    );

const buildPayload = () => ({
    type: 'representative-signature',
    label: 'Chữ ký đại diện',
    description: 'Chữ ký đại diện theo loại khách hàng.',
    kind: 'composite' as const,
    blocks: {
        normalCustomer: { ...form.normalCustomer },
        keyAccountCustomer: { ...form.keyAccountCustomer },
    },
});

const save = (): void => {
    if (!props.templateId || !props.canManageTemplates) {
        return;
    }

    processingState.saving = true;

    router.put(
        route('templates.parts.update', props.templateId),
        {
            partType: 'representative-signature',
            section: buildPayload(),
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                processingState.saving = false;
            },
        },
    );
};

const clearImage = (block: 'normalCustomer' | 'keyAccountCustomer'): void => {
    form[block].signatureImageDataUrl = null;
};

const handleSignatureFileChange = (event: Event, block: 'normalCustomer' | 'keyAccountCustomer'): void => {
    const target = event.target as HTMLInputElement | null;
    const file = target?.files?.[0];

    if (!file) {
        return;
    }

    processingState.uploadingBlock = block;

    const reader = new FileReader();

    reader.onload = () => {
        form[block].signatureImageDataUrl = typeof reader.result === 'string'
            ? reader.result
            : null;
        processingState.uploadingBlock = null;

        if (target) {
            target.value = '';
        }
    };

    reader.onerror = () => {
        processingState.uploadingBlock = null;

        if (target) {
            target.value = '';
        }
    };

    reader.readAsDataURL(file);
};
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                            Representative Signature
                        </p>
                        <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                            Chữ ký đại diện theo loại khách hàng
                        </h2>
                        <p class="mt-2 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                            Part này là composite block trong canvas. Mỗi template có sẵn 2 cấu hình riêng cho Khách thường và Key Account.
                        </p>
                    </div>

                    <Button
                        v-if="canManageTemplates"
                        label="Lưu chữ ký"
                        :loading="processingState.saving"
                        @click="save"
                    />
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="blockKey in ['normalCustomer', 'keyAccountCustomer'] as const"
                        :key="blockKey"
                        class="rounded-[1.2rem] border p-4"
                        :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }"
                    >
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-teal-500">
                                    {{ blockKey === 'normalCustomer' ? 'Khách thường' : 'Key Account' }}
                                </p>
                                <h3 class="mt-2 text-base font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ blockKey === 'normalCustomer' ? 'Block chữ ký Khách thường' : 'Block chữ ký Key Account' }}
                                </h3>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Dòng tiêu đề</label>
                                <InputText v-model="form[blockKey].title" fluid :disabled="!canManageTemplates" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Ảnh chữ ký</label>
                                <input
                                    class="block w-full text-sm"
                                    type="file"
                                    accept="image/png,image/jpeg,image/webp"
                                    :disabled="!canManageTemplates"
                                    @change="handleSignatureFileChange($event, blockKey)"
                                />
                                <div
                                    v-if="form[blockKey].signatureImageDataUrl"
                                    class="rounded-[1rem] border p-3"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-app-bg)' }"
                                >
                                    <img
                                        :src="form[blockKey].signatureImageDataUrl ?? undefined"
                                        :alt="`Chữ ký ${blockKey}`"
                                        class="max-h-32 max-w-full object-contain"
                                    />
                                    <div v-if="canManageTemplates" class="mt-3 flex justify-end">
                                        <Button
                                            label="Xóa ảnh"
                                            severity="danger"
                                            variant="outlined"
                                            size="small"
                                            @click="clearImage(blockKey)"
                                        />
                                    </div>
                                </div>
                                <p
                                    v-else
                                    class="rounded-[0.9rem] border px-4 py-3 text-sm leading-6"
                                    :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-app-bg)', color: 'var(--dashboard-muted-text)' }"
                                >
                                    {{ processingState.uploadingBlock === blockKey ? 'Đang đọc ảnh chữ ký...' : 'Chưa có ảnh chữ ký cho block này.' }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Chức vụ người đại diện</label>
                                <InputText v-model="form[blockKey].representativeRole" fluid :disabled="!canManageTemplates" />
                            </div>

                            <div class="space-y-2">
                                <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên người đại diện</label>
                                <InputText v-model="form[blockKey].representativeName" fluid :disabled="!canManageTemplates" />
                            </div>

                            <ul
                                v-if="resolveBlockErrors(blockKey).length > 0"
                                class="rounded-[0.9rem] border px-4 py-3 text-sm leading-6"
                                :style="{ borderColor: 'rgba(239, 68, 68, 0.36)', background: 'rgba(239, 68, 68, 0.08)', color: 'var(--dashboard-strong-text)' }"
                            >
                                <li
                                    v-for="message in resolveBlockErrors(blockKey)"
                                    :key="`${blockKey}-${message}`"
                                >
                                    {{ message }}
                                </li>
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </template>
    </Card>
</template>
