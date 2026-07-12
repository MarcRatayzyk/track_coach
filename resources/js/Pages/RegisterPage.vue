<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiIcon from '../Components/UiIcon.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post('/register', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Créer un compte coach" />

    <div class="min-h-screen bg-slate-950 text-slate-200 lg:grid lg:grid-cols-2">
        <div
            class="relative hidden overflow-hidden border-r border-slate-800/80 bg-gradient-to-br from-slate-900 via-slate-950 to-slate-950 lg:flex lg:flex-col lg:justify-between lg:px-12 lg:py-12 xl:px-16 xl:py-14"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_70%_50%_at_20%_0%,rgba(59,130,246,0.22),transparent)]"
            />
            <div class="relative">
                <Link href="/" class="inline-flex items-center gap-3">
                    <span
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600/25 text-blue-400"
                    >
                        <UiIcon name="bolt" class="h-7 w-7" />
                    </span>
                    <span class="text-2xl font-bold tracking-tight text-white">Track Coach</span>
                </Link>
                <h1 class="mt-14 max-w-lg text-4xl font-bold leading-tight text-white xl:text-5xl">
                    Lance ton espace coach en quelques minutes.
                </h1>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-slate-400">
                    Crée ton compte, invite tes athlètes et partage-leur le lien d’activation, puis construis
                    leurs programmes et suis leur progression au quotidien.
                </p>
            </div>
            <ol class="relative mt-12 space-y-4 text-slate-300">
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-400">1</span>
                    <span>Crée ton compte coach</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-400">2</span>
                    <span>Invite tes athlètes — partage le lien d’activation</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-400">3</span>
                    <span>Programme, messagerie et retours vidéo centralisés</span>
                </li>
            </ol>
        </div>

        <div class="flex flex-col justify-center px-6 py-12 sm:px-10 lg:px-14 xl:px-20">
            <div class="mx-auto w-full max-w-md">
                <Link
                    href="/"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-300 lg:hidden"
                >
                    <span aria-hidden="true">←</span>
                    Retour à l’accueil
                </Link>

                <h2 class="mt-6 text-3xl font-bold text-white lg:mt-0">Créer un compte coach</h2>
                <p class="mt-2 text-slate-400">
                    Déjà inscrit ?
                    <Link href="/login" class="font-medium text-blue-400 hover:text-blue-300">Se connecter</Link>
                </p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-300">Nom affiché</label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            autocomplete="name"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            placeholder="Jean Dupont"
                        />
                        <p v-if="form.errors.name" class="mt-2 text-sm text-red-400">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300">Adresse e-mail</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            placeholder="coach@exemple.fr"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">{{ form.errors.email }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300">Mot de passe</label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            placeholder="8 caractères minimum"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">{{ form.errors.password }}</p>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-300">
                            Confirmation du mot de passe
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-4 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500 disabled:opacity-60"
                    >
                        <span v-if="form.processing">Création du compte…</span>
                        <span v-else>Créer mon compte coach</span>
                        <span v-if="!form.processing" aria-hidden="true">→</span>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-slate-500">
                    Tu es athlète ? Demande à ton coach de t’inviter — il te transmettra un lien pour activer ton
                    compte.
                </p>
            </div>
        </div>
    </div>
</template>
