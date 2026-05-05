<script setup lang="ts">
const props = defineProps<{
    appName: string;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-6 border-t border-slate-200/80 px-1 pt-6 text-sm text-slate-500">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="space-y-1">
                <p class="text-sm font-semibold text-slate-950">
                    {{ props.appName }}
                </p>
            </div>

            <div class="space-y-1 text-left md:text-right">
                <p>
                    © {{ currentYear }} Tony Nguyen. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</template>
