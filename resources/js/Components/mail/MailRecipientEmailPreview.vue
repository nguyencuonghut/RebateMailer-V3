<script setup lang="ts">
const props = defineProps<{
    preview: {
        html: string;
    };
}>();
</script>

<template>
    <div class="overflow-hidden rounded-[1.4rem] border" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
        <iframe
            :srcdoc="props.preview.html"
            class="h-[78vh] w-full border-0 bg-white"
            sandbox="allow-same-origin"
            title="Xem trước email HTML"
        />
    </div>
</template>
