<script setup lang="ts">
import type { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

type MenuItem = {
    label: string;
    icon: string;
    permission?: string;
} & (
    | { routeName: string; href?: never }
    | { href: string; routeName?: never; target?: string }
);

type MenuSection = {
    label: string;
    items: MenuItem[];
};

defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage<PageProps>();

const user = page.props.auth.user;

const hasPermission = (permission?: string): boolean => {
    if (!permission) {
        return true;
    }

    return user?.permissions.includes(permission) ?? false;
};

const handleNavigation = (): void => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        emit('close');
    }
};

const itemRouteUrl = (item: MenuItem): string => (item.routeName ? route(item.routeName) : '');
const itemIsActive = (item: MenuItem): boolean => (item.routeName ? (route().current(item.routeName) ?? false) : false);

const menuSections: MenuSection[] = [
    {
        label: 'Trang chính',
        items: [
            { label: 'Tổng quan', icon: 'pi pi-home', routeName: 'dashboard' },
        ],
    },
    {
        label: 'Nền tảng',
        items: [
            { label: 'Import dữ liệu', icon: 'pi pi-upload', routeName: 'imports.index', permission: 'imports.view' },
            { label: 'Thiết kế mẫu email', icon: 'pi pi-pencil', routeName: 'templates.index', permission: 'templates.view' },
            { label: 'Điều phối gửi mail', icon: 'pi pi-send', routeName: 'mail.index', permission: 'mail.view' },
        ],
    },
    {
        label: 'Quản trị',
        items: [
            { label: 'Người dùng', icon: 'pi pi-users', routeName: 'users.index', permission: 'users.view' },
            { label: 'Hồ sơ cá nhân', icon: 'pi pi-user', routeName: 'profile.edit' },
        ],
    },
    {
        label: 'Trợ giúp',
        items: [
            { label: 'Hướng dẫn sử dụng', icon: 'pi pi-book', routeName: 'user-guide.index' },
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
        class="fixed inset-y-0 left-0 z-40 flex w-[17rem] flex-col border-r px-4 py-5 transition-transform duration-200 lg:w-[18rem] lg:px-4 lg:py-6"
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
                    <template v-for="item in section.items.filter((entry) => hasPermission(entry.permission))" :key="item.label">
                        <a
                            v-if="item.href"
                            :href="item.href"
                            :target="item.target"
                            class="sakai-sidebar-link"
                            @click="handleNavigation"
                        >
                            <i :class="item.icon" class="text-sm" />
                            <span class="font-medium">{{ item.label }}</span>
                        </a>
                        <Link
                            v-else
                            :href="itemRouteUrl(item)"
                            class="sakai-sidebar-link"
                            :class="{ 'sakai-sidebar-link-active': itemIsActive(item) }"
                            @click="handleNavigation"
                        >
                            <i :class="item.icon" class="text-sm" />
                            <span class="font-medium">{{ item.label }}</span>
                        </Link>
                    </template>
                </div>
            </section>
        </nav>

        <div class="mt-auto px-2 pt-8">
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="sakai-sidebar-link w-full text-left"
            >
                <i class="pi pi-sign-out text-sm" />
                <span class="font-medium">Đăng xuất</span>
            </Link>
        </div>
    </aside>
</template>
