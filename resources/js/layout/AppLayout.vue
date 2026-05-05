<script setup lang="ts">
import { computed, onMounted } from 'vue';
import AppFooter from './AppFooter.vue';
import AppSidebar from './AppSidebar.vue';
import AppTopbar from './AppTopbar.vue';
import { useLayout } from './composables/useLayout';

defineProps<{
    appName: string;
}>();

const { isSidebarOpen, toggleSidebar, closeSidebar, initializeSidebar, initializeTheme } = useLayout();

const contentClass = computed(() => (isSidebarOpen.value ? 'lg:pl-[18rem]' : 'lg:pl-0'));

onMounted(() => {
    initializeSidebar();
    initializeTheme();
});
</script>

<template>
    <div class="sakai-shell">
        <AppSidebar :open="isSidebarOpen" @close="closeSidebar" />

        <div class="flex min-h-screen flex-col transition-[padding] duration-200" :class="contentClass">
            <AppTopbar :app-name="appName" @toggle-menu="toggleSidebar" />

            <main class="flex-1 px-2 pb-8 pt-5 sm:px-3 sm:pt-6 lg:px-4">
                <div class="mx-auto w-full max-w-[96rem]">
                    <slot />
                </div>
            </main>

            <div class="px-2 pb-6 sm:px-3 lg:px-4">
                <div class="mx-auto w-full max-w-[96rem]">
                    <AppFooter :app-name="appName" />
                </div>
            </div>
        </div>
    </div>
</template>
