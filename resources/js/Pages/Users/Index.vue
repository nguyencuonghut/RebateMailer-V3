<script setup lang="ts">
import type { PageProps } from '@/types';
import DataTableGlobalFilterToolbar from '@/Components/common/DataTableGlobalFilterToolbar.vue';
import { useDataTableGlobalFilter } from '@/Services/useDataTableGlobalFilter';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import { computed, ref } from 'vue';
import AppLayout from '../../layout/AppLayout.vue';

type ManagedUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string | null;
    roles: string[];
    permissions: string[];
    is_current_user: boolean;
};

const props = defineProps<{
    users: ManagedUser[];
    roles: string[];
}>();

const page = usePage<PageProps>();
const editingUser = ref<ManagedUser | null>(null);
const editDialogVisible = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    role: props.roles[0] ?? '',
    password: '',
    password_confirmation: '',
});

const editForm = useForm({
    name: '',
    email: '',
    role: '',
    password: '',
    password_confirmation: '',
});

const summary = computed(() => ({
    total: props.users.length,
    admins: props.users.filter((user) => user.roles.includes('Admin')).length,
    users: props.users.filter((user) => user.roles.includes('Người dùng')).length,
    guests: props.users.filter((user) => user.roles.includes('Khách')).length,
}));

const {
    filters: userFilters,
    globalFilterFields: userGlobalFilterFields,
    globalFilterValue: userGlobalFilterValue,
    clearGlobalFilter: clearUserGlobalFilter,
} = useDataTableGlobalFilter<ManagedUser>([
    'name',
    'email',
    (user) => user.roles.join(' '),
    (user) => (user.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực'),
]);

const openEditDialog = (user: ManagedUser): void => {
    editingUser.value = user;
    editForm.reset();
    editForm.clearErrors();
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.role = user.roles[0] ?? '';
    editDialogVisible.value = true;
};

const submitCreate = (): void => {
    createForm.post(route('users.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const submitUpdate = (): void => {
    if (!editingUser.value) {
        return;
    }

    editForm.put(route('users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            editDialogVisible.value = false;
            editForm.reset();
        },
    });
};

const removeUser = (user: ManagedUser): void => {
    if (!window.confirm(`Xóa tài khoản ${user.email}?`)) {
        return;
    }

    if (page.props.auth.user?.id === user.id) {
        return;
    }

    router.delete(route('users.destroy', user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Quản lý người dùng" />

    <AppLayout :app-name="page.props.appName">
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.8fr)_minmax(23rem,1fr)]">
            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Quản lý người dùng
                </template>
                <template #content>
                    <div class="mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Tổng tài khoản</p>
                            <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ summary.total }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Admin</p>
                            <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ summary.admins }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Người dùng</p>
                            <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ summary.users }}</p>
                        </div>
                        <div class="rounded-[1.25rem] border p-4" :style="{ borderColor: 'var(--dashboard-panel-border)', background: 'var(--dashboard-card-bg)' }">
                            <p class="text-sm" :style="{ color: 'var(--dashboard-muted-text)' }">Khách</p>
                            <p class="mt-2 text-2xl font-semibold" :style="{ color: 'var(--dashboard-strong-text)' }">{{ summary.guests }}</p>
                        </div>
                    </div>

                    <DataTableGlobalFilterToolbar
                        v-model="userGlobalFilterValue"
                        placeholder="Tìm theo họ tên, email, vai trò, trạng thái"
                        @clear="clearUserGlobalFilter"
                    />

                    <DataTable
                        v-model:filters="userFilters"
                        :value="users"
                        :global-filter-fields="userGlobalFilterFields"
                        data-key="id"
                        striped-rows
                        responsive-layout="scroll"
                    >
                        <Column field="name" header="Họ tên" />
                        <Column field="email" header="Email" />
                        <Column header="Vai trò">
                            <template #body="{ data }">
                                <Tag :value="data.roles[0] ?? 'Chưa gán'" rounded severity="contrast" />
                            </template>
                        </Column>
                        <Column header="Trạng thái">
                            <template #body="{ data }">
                                <Tag
                                    :value="data.email_verified_at ? 'Đã xác thực' : 'Chưa xác thực'"
                                    :severity="data.email_verified_at ? 'success' : 'warn'"
                                    rounded
                                />
                            </template>
                        </Column>
                        <Column header="Thao tác">
                            <template #body="{ data }">
                                <div class="flex gap-2">
                                    <Button label="Sửa" size="small" outlined @click="openEditDialog(data)" />
                                    <Button
                                        label="Xóa"
                                        size="small"
                                        severity="danger"
                                        text
                                        :disabled="data.is_current_user"
                                        @click="removeUser(data)"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>

            <Card class="sakai-panel rounded-[2rem] border-0">
                <template #title>
                    Tạo người dùng mới
                </template>
                <template #content>
                    <form class="space-y-4" @submit.prevent="submitCreate">
                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Họ tên</label>
                            <InputText v-model="createForm.name" fluid />
                            <small v-if="createForm.errors.name" class="text-red-500">{{ createForm.errors.name }}</small>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Email</label>
                            <InputText v-model="createForm.email" fluid />
                            <small v-if="createForm.errors.email" class="text-red-500">{{ createForm.errors.email }}</small>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Vai trò</label>
                            <Select v-model="createForm.role" :options="roles" fluid />
                            <small v-if="createForm.errors.role" class="text-red-500">{{ createForm.errors.role }}</small>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Mật khẩu</label>
                            <Password v-model="createForm.password" toggle-mask fluid :feedback="false" />
                            <small v-if="createForm.errors.password" class="text-red-500">{{ createForm.errors.password }}</small>
                        </div>

                        <div class="space-y-2">
                            <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Xác nhận mật khẩu</label>
                            <Password v-model="createForm.password_confirmation" toggle-mask fluid :feedback="false" />
                        </div>

                        <Button type="submit" label="Tạo người dùng" :loading="createForm.processing" />
                    </form>
                </template>
            </Card>
        </div>

        <Dialog v-model:visible="editDialogVisible" modal header="Cập nhật người dùng" class="w-full max-w-xl">
            <form class="space-y-4" @submit.prevent="submitUpdate">
                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Họ tên</label>
                    <InputText v-model="editForm.name" fluid />
                    <small v-if="editForm.errors.name" class="text-red-500">{{ editForm.errors.name }}</small>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Email</label>
                    <InputText v-model="editForm.email" fluid />
                    <small v-if="editForm.errors.email" class="text-red-500">{{ editForm.errors.email }}</small>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Vai trò</label>
                    <Select v-model="editForm.role" :options="roles" fluid />
                    <small v-if="editForm.errors.role" class="text-red-500">{{ editForm.errors.role }}</small>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Mật khẩu mới</label>
                    <Password v-model="editForm.password" toggle-mask fluid :feedback="false" />
                    <small v-if="editForm.errors.password" class="text-red-500">{{ editForm.errors.password }}</small>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium" :style="{ color: 'var(--dashboard-muted-text)' }">Xác nhận mật khẩu mới</label>
                    <Password v-model="editForm.password_confirmation" toggle-mask fluid :feedback="false" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button type="button" label="Hủy" text @click="editDialogVisible = false" />
                    <Button type="submit" label="Lưu thay đổi" :loading="editForm.processing" />
                </div>
            </form>
        </Dialog>
    </AppLayout>
</template>
