<script setup lang="ts">
defineProps<{
    appName: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900">
        <main class="mx-auto flex min-h-screen max-w-5xl items-center px-6 py-16">
            <section class="w-full rounded-3xl bg-white p-10 shadow-sm ring-1 ring-slate-200">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-slate-500">
                    Task 0.2
                </p>
                <h1 class="mt-4 text-4xl font-semibold tracking-tight text-slate-950">
                    {{ appName }}
                </h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">
                    Nen frontend core da chay qua Inertia, Vue 3 Composition API, va TypeScript.
                </p>
            </section>
        </main>
    </div>
</template>
