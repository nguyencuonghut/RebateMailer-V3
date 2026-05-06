import { FilterMatchMode } from '@primevue/core/api';
import { computed, ref } from 'vue';

type GlobalFilterField<T> = keyof T | ((data: T) => string);

type DataTableFilterState = {
    global: {
        value: string | null;
        matchMode: string;
    };
};

export const useDataTableGlobalFilter = <T>(
    fields: GlobalFilterField<T>[],
    initialValue = '',
) => {
    const filters = ref<DataTableFilterState>({
        global: {
            value: initialValue || null,
            matchMode: FilterMatchMode.CONTAINS,
        },
    });

    const globalFilterValue = computed<string>({
        get: () => filters.value.global.value ?? '',
        set: (value: string) => {
            filters.value.global.value = value.trim() === '' ? null : value;
        },
    });

    const hasActiveGlobalFilter = computed(() => globalFilterValue.value.trim().length > 0);

    const clearGlobalFilter = (): void => {
        filters.value.global.value = null;
    };

    return {
        filters,
        globalFilterFields: fields,
        globalFilterValue,
        hasActiveGlobalFilter,
        clearGlobalFilter,
    };
};
