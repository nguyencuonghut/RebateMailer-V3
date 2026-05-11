<script setup lang="ts">
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';

withDefaults(defineProps<{
    placeholder?: string;
    clearLabel?: string;
}>(), {
    placeholder: 'Tìm kiếm trong bảng',
    clearLabel: 'Xóa lọc',
});

const model = defineModel<string>({
    default: '',
});

const emit = defineEmits<{
    clear: [];
}>();

const clearFilter = (): void => {
    model.value = '';
    emit('clear');
};
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <InputText
            v-model="model"
            :placeholder="placeholder"
            class="w-full sm:max-w-sm"
        />

        <Button
            v-if="model"
            :label="clearLabel"
            icon="pi pi-times"
            severity="secondary"
            outlined
            class="whitespace-nowrap"
            @click="clearFilter"
        />
    </div>
</template>
