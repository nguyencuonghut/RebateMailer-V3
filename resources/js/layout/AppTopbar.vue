<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import AppLogo from './AppLogo.vue';
import { useLayout } from './composables/useLayout';

defineProps<{
    appName: string;
}>();

const emit = defineEmits<{
    toggleMenu: [];
}>();

const { isDarkMode, toggleTheme } = useLayout();
</script>

<template>
    <header class="sticky top-3 z-30 px-2 sm:top-4 sm:px-3 lg:px-4">
        <div class="mx-auto w-full max-w-[96rem]">
            <div class="sakai-panel rounded-[1.4rem] px-3 py-3 sm:px-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0 flex items-center gap-3">
                        <Button
                            icon="pi pi-bars"
                            severity="contrast"
                            rounded
                            text
                            aria-label="Mở menu"
                            @click="emit('toggleMenu')"
                        />

                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                                :style="{
                                    background: 'var(--brand-logo-bg)',
                                    color: 'var(--brand-logo-fg)',
                                    boxShadow: 'inset 0 0 0 1px var(--brand-logo-ring)',
                                }"
                            >
                                <AppLogo class="h-6 w-6" />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.32em] text-slate-400">
                                    Bảng điều khiển
                                </p>
                                <h1 class="truncate text-base font-semibold tracking-tight text-slate-950 sm:text-lg">
                                    {{ appName }}
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <Button
                            :icon="isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"
                            rounded
                            text
                            severity="secondary"
                            :aria-label="isDarkMode ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'"
                            @click="toggleTheme"
                        />

                        <Avatar label="RM" shape="circle" class="bg-slate-900 text-white" />
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
