<script setup lang="ts">
defineProps<{
    value?: string;
}>();
</script>

<template>
    <label class="block text-sm font-medium" :style="{ color: 'var(--dashboard-strong-text)' }">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
