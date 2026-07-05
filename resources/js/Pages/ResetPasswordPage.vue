<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/reset-password', {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 px-4 py-12 text-slate-100">
        <Head title="Nouveau mot de passe" />
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
            <h1 class="text-2xl font-bold text-white">Nouveau mot de passe</h1>
            <p class="mt-2 text-slate-400">Choisis un nouveau mot de passe pour ton compte.</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-400">
                    E-mail
                    <input
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                </label>
                <label class="block text-sm font-medium text-slate-400">
                    Nouveau mot de passe
                    <input
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                    />
                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-400">{{ form.errors.password }}</p>
                </label>
                <label class="block text-sm font-medium text-slate-400">
                    Confirmation
                    <input
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                    />
                </label>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                    Enregistrer
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                <Link href="/login" class="text-blue-400 hover:text-blue-300">Retour à la connexion</Link>
            </p>
        </div>
    </div>
</template>
