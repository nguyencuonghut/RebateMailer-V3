<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border px-4 py-2 text-xs font-semibold uppercase tracking-widest shadow-sm transition duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:ring-offset-2 disabled:opacity-25"
        :style="{
            borderColor: 'var(--dashboard-panel-border)',
            background: 'var(--dashboard-card-bg)',
            color: 'var(--dashboard-strong-text)',
        }"
    >
        <slot />
    </button>
</template>
