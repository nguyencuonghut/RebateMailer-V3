<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

type MenuSection = {
    label: string;
    items: Array<{
        label: string;
        icon: string;
        href?: string;
        active?: boolean;
    }>;
};

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const menuSections: MenuSection[] = [
    {
        label: 'Trang chính',
        items: [
            { label: 'Tổng quan', icon: 'pi pi-home', href: '/', active: true },
        ],
    },
    {
        label: 'Nền tảng',
        items: [
            { label: 'Import dữ liệu', icon: 'pi pi-upload' },
            { label: 'Thiết kế mẫu email', icon: 'pi pi-pencil' },
            { label: 'Điều phối gửi mail', icon: 'pi pi-send' },
            { label: 'Theo dõi và gửi lại', icon: 'pi pi-sync' },
        ],
    },
];
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 bg-slate-950/45 backdrop-blur-[2px] lg:hidden"
        @click="emit('close')"
    />

    <aside
        class="fixed inset-y-0 left-0 z-40 w-[17rem] border-r px-4 py-5 transition-transform duration-200 lg:w-[18rem] lg:px-4 lg:py-6"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
        :style="{
            background: 'var(--dashboard-sidebar-bg)',
            borderColor: 'var(--dashboard-sidebar-border)',
        }"
    >
        <div class="flex items-center gap-3 px-2">
            <div
                class="flex h-11 w-11 items-center justify-center rounded-2xl"
                :style="{
                    background: 'var(--brand-logo-bg)',
                    color: 'var(--brand-logo-fg)',
                    boxShadow: 'inset 0 0 0 1px var(--brand-logo-ring)',
                }"
            >
                <AppLogo class="h-6 w-6" />
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.28em]" style="color: var(--dashboard-sidebar-muted)">
                    RebateMailer
                </p>
                <p class="text-base font-semibold" :style="{ color: 'var(--dashboard-sidebar-brand-text)' }">
                    Bảng điều khiển
                </p>
            </div>
        </div>

        <nav class="mt-8 space-y-6">
            <section v-for="section in menuSections" :key="section.label">
                <p class="px-2 text-xs font-semibold uppercase tracking-[0.28em]" style="color: var(--dashboard-sidebar-muted)">
                    {{ section.label }}
                </p>

                <div class="mt-3 space-y-1.5">
                    <template v-for="item in section.items" :key="item.label">
                        <Link
                            v-if="item.href"
                            :href="item.href"
                            class="sakai-sidebar-link"
                            :class="{ 'sakai-sidebar-link-active': item.active }"
                            @click="emit('close')"
                        >
                            <i :class="item.icon" class="text-sm" />
                            <span class="font-medium">{{ item.label }}</span>
                        </Link>

                        <div v-else class="sakai-sidebar-link opacity-70">
                            <i :class="item.icon" class="text-sm" />
                            <span class="font-medium">{{ item.label }}</span>
                        </div>
                    </template>
                </div>
            </section>
        </nav>
    </aside>
</template>
