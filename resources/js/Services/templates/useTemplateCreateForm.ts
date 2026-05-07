import { useForm } from '@inertiajs/vue3';

export const useTemplateCreateForm = () => {
    const form = useForm({
        name: '',
        subject_template: '',
        greeting_template: '',
    });

    const insertVariable = (field: 'subject_template' | 'greeting_template', token: string): void => {
        form[field] = `${form[field]}${form[field] ? ' ' : ''}${token}`;
    };

    const submitCreate = (): void => {
        form.post(route('templates.store'), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return {
        form,
        insertVariable,
        submitCreate,
    };
};
