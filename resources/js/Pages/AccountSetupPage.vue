<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    submitUrl: {
        type: String,
        required: true,
    },
});

const form = useForm({
    password: '',
    password_confirmation: '',
    weight_class: '',
    bio: '',
});

function submit() {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 px-4 py-12 text-slate-100">
        <Head title="Activer mon compte" />
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
            <h1 class="text-2xl font-bold text-white">Activer ton compte</h1>
            <p class="mt-2 text-slate-400">
                Bonjour <span class="font-medium text-slate-200">{{ user.name }}</span>, choisis un mot de passe et
                complète les informations utiles pour ton coach.
            </p>
            <p class="mt-3 text-sm text-slate-500">E-mail de connexion : {{ user.email }}</p>

            <form class="mt-8 space-y-5" @submit.prevent="submit">
                <label class="block text-sm font-medium text-slate-400">
                    Mot de passe
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
                <label class="block text-sm font-medium text-slate-400">
                    Catégorie de poids (optionnel)
                    <input
                        v-model="form.weight_class"
                        type="text"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                        placeholder="Ex. 83 kg"
                    />
                </label>
                <label class="block text-sm font-medium text-slate-400">
                    Bio / objectifs (optionnel)
                    <textarea
                        v-model="form.bio"
                        rows="3"
                        class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                        placeholder="Quelques lignes pour ton coach…"
                    />
                </label>
                <p v-if="Object.keys(form.errors).length" class="text-sm text-red-400">
                    {{ Object.values(form.errors).flat().join(' ') }}
                </p>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                    Activer et enregistrer
                </button>
            </form>
            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="/login" class="text-blue-400 hover:text-blue-300">Retour à la connexion</a>
            </p>
        </div>
    </div>
</template>
