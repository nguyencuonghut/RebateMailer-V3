import { useForm } from '@inertiajs/vue3';

export const useTemplateCreateForm = () => {
    const form = useForm({
        name: '',
    });

    const submitCreate = (): void => {
        form.post(route('templates.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return {
        form,
        submitCreate,
    };
};
