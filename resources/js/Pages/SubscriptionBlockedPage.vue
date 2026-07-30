<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="mx-auto flex min-h-[70vh] w-full max-w-lg flex-col items-center justify-center px-4 py-12 text-center">
        <Head title="Accès suspendu" />

        <div class="rounded-2xl border border-slate-700/80 bg-slate-800/40 p-8">
            <p class="text-sm font-semibold uppercase tracking-wider text-amber-400">Abonnement</p>
            <h1 class="mt-3 text-2xl font-bold text-white">Accès temporairement bloqué</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-400">
                L’abonnement ou l’essai de ton coach a expiré. L’application est indisponible jusqu’à
                ce que ton coach réactive son accès.
            </p>
            <Link
                href="/logout"
                method="post"
                as="button"
                class="mt-6 inline-flex rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800"
            >
                Se déconnecter
            </Link>
        </div>
    </div>
</template>
