<script setup lang="ts">
import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
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
const page = usePage<PageProps>();
const user = page.props.auth.user;
const userInitials = user?.name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('') ?? 'RM';
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
                                <p class="truncate text-[11px] font-semibold uppercase tracking-[0.32em]" style="color: var(--dashboard-muted-text)">
                                    Bảng điều khiển
                                </p>
                                <h1 class="truncate text-base font-semibold tracking-tight sm:text-lg" :style="{ color: 'var(--dashboard-strong-text)' }">
                                    {{ appName }}
                                </h1>
                            </div>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                        <div class="hidden text-right md:block">
                            <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                                {{ user?.name ?? 'Người dùng' }}
                            </p>
                            <p class="text-xs" style="color: var(--dashboard-muted-text)">
                                {{ user?.roles?.[0] ?? 'Nội bộ' }}
                            </p>
                        </div>

                        <Button
                            :icon="isDarkMode ? 'pi pi-sun' : 'pi pi-moon'"
                            rounded
                            text
                            severity="secondary"
                            :aria-label="isDarkMode ? 'Chuyển sang giao diện sáng' : 'Chuyển sang giao diện tối'"
                            @click="toggleTheme"
                        />

                        <Avatar :label="userInitials" shape="circle" class="bg-slate-900 text-white" />
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>
