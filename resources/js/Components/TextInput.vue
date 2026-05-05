<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        class="rounded-md border shadow-sm focus:ring-2 focus:ring-teal-500/30"
        :style="{
            borderColor: 'var(--dashboard-panel-border)',
            background: 'var(--dashboard-card-bg)',
            color: 'var(--dashboard-strong-text)',
        }"
        v-model="model"
        ref="input"
    />
</template>
