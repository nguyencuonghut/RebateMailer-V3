<script setup lang="ts">
const props = defineProps<{
    appName: string;
}>();

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="mt-6 border-t px-1 pt-6 text-sm" :style="{ borderColor: 'var(--dashboard-panel-border)', color: 'var(--dashboard-muted-text)' }">
        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div class="space-y-1">
                <p class="text-sm font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">
                    {{ props.appName }}
                </p>
                <p>Hệ thống điều phối import rebate, mẫu email và chiến dịch gửi mail nội bộ.</p>
            </div>

            <div class="space-y-1 text-left md:text-right">
                <p>Môi trường vận hành nội bộ • Laravel 13 • InertiaJS • PrimeVue</p>
                <p>
                    © {{ currentYear }} Tony Nguyen. Bảo lưu mọi quyền.
                </p>
            </div>
        </div>
    </footer>
</template>
