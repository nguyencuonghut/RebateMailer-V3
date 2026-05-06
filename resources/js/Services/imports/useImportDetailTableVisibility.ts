import { computed, onMounted, ref } from 'vue';

export const useImportDetailTableVisibility = () => {
    const showDetailsTable = ref(false);

    onMounted(() => {
        if (typeof window === 'undefined') {
            return;
        }

        showDetailsTable.value = window.matchMedia('(min-width: 1024px)').matches;
    });

    const detailToggleLabel = computed(() =>
        showDetailsTable.value ? 'Ẩn bảng chi tiết' : 'Xem bảng chi tiết',
    );

    const detailToggleIcon = computed(() =>
        showDetailsTable.value ? 'pi pi-eye-slash' : 'pi pi-table',
    );

    const detailToggleHelper = computed(() =>
        showDetailsTable.value
            ? 'Bảng chi tiết đang hiển thị đầy đủ.'
            : 'Đang ưu tiên phần tóm tắt. Mở bảng khi cần rà chi tiết từng bản ghi.',
    );

    const toggleDetailsTable = (): void => {
        showDetailsTable.value = !showDetailsTable.value;
    };

    return {
        showDetailsTable,
        detailToggleLabel,
        detailToggleIcon,
        detailToggleHelper,
        toggleDetailsTable,
    };
};
