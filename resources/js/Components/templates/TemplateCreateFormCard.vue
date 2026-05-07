<script setup lang="ts">
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import { useTemplateCreateForm } from '@/Services/templates/useTemplateCreateForm';

const { form, submitCreate } = useTemplateCreateForm();
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Tạo canvas email
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Khởi tạo canvas chính
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Tab này chỉ tạo metadata cho canvas. Nội dung `Subject` và `Lời chào` sẽ được nhập riêng trong tab part tương ứng cùng preview của chúng.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitCreate">
                    <div class="space-y-2">
                        <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên canvas/template</label>
                        <InputText v-model="form.name" fluid />
                        <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                    </div>

                    <div class="flex justify-end">
                        <Button type="submit" label="Tạo canvas" :loading="form.processing" />
                    </div>
                </form>
            </div>
        </template>
    </Card>
</template>
