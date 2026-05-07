<script setup lang="ts">
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Card from 'primevue/card';
import InputText from 'primevue/inputtext';
import { useTemplateCreateForm } from '@/Services/templates/useTemplateCreateForm';

const props = defineProps<{
    variables: Array<{
        token: string;
        label: string;
        description: string;
    }>;
}>();

const { form, insertVariable, submitCreate } = useTemplateCreateForm();
</script>

<template>
    <Card class="sakai-panel rounded-[2rem] border-0">
        <template #content>
            <div class="space-y-5">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-teal-500">
                        Tạo template đầu tiên
                    </p>
                    <h2 class="mt-3 text-xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                        Form khởi tạo cơ bản
                    </h2>
                    <p class="mt-3 text-sm leading-6" :style="{ color: 'var(--dashboard-muted-text)' }">
                        Bước này chỉ lưu tên template, subject và lời chào. Builder kéo thả sẽ được mở ở các lát cắt tiếp theo.
                    </p>
                </div>

                <form class="space-y-4" @submit.prevent="submitCreate">
                    <div class="space-y-2">
                        <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Tên template</label>
                        <InputText v-model="form.name" fluid />
                        <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Subject template</label>
                        <InputText v-model="form.subject_template" fluid />
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="variable in props.variables"
                                :key="`subject-${variable.token}`"
                                type="button"
                                size="small"
                                variant="outlined"
                                :label="variable.token"
                                @click="insertVariable('subject_template', variable.token)"
                            />
                        </div>
                        <small v-if="form.errors.subject_template" class="text-red-500">{{ form.errors.subject_template }}</small>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Lời chào</label>
                        <Textarea v-model="form.greeting_template" rows="5" auto-resize fluid />
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="variable in props.variables"
                                :key="`greeting-${variable.token}`"
                                type="button"
                                size="small"
                                variant="outlined"
                                :label="variable.token"
                                @click="insertVariable('greeting_template', variable.token)"
                            />
                        </div>
                        <small v-if="form.errors.greeting_template" class="text-red-500">{{ form.errors.greeting_template }}</small>
                    </div>

                    <Button type="submit" label="Tạo template" :loading="form.processing" />
                </form>
            </div>
        </template>
    </Card>
</template>
