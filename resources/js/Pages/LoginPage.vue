<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import UiIcon from '../Components/UiIcon.vue';
import { useNativeApp } from '../composables/useNativeApp';

const { isMobileApp } = useNativeApp();

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
});

const form = useForm({
    email: props.email,
    password: '',
    remember: isMobileApp,
});

function submit() {
    if (isMobileApp) {
        form.remember = true;
    }

    form.post('/login', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Connexion" />

    <div class="min-h-screen bg-slate-950 text-slate-200 lg:grid lg:grid-cols-2 tc-native-safe-top">
        <!-- Panneau branding -->
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
                    Ton espace coach & athlète, prêt en quelques secondes.
                </h1>
                <p class="mt-5 max-w-md text-lg leading-relaxed text-slate-400">
                    Roster, programmes, messagerie et suivi SBD — tout est centralisé pour un coaching
                    powerlifting structuré.
                </p>
            </div>
            <ul class="relative mt-12 space-y-4 text-slate-300">
                <li class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600/15 text-blue-400"
                    >
                        <UiIcon name="dashboard" class="h-5 w-5" />
                    </span>
                    Dashboard et tâches en un coup d’œil
                </li>
                <li class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600/15 text-blue-400"
                    >
                        <UiIcon name="clipboard" class="h-5 w-5" />
                    </span>
                    Programmes et assignations simplifiés
                </li>
                <li class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-600/15 text-blue-400"
                    >
                        <UiIcon name="trophy" class="h-5 w-5" />
                    </span>
                    Records et objectifs de compétition
                </li>
            </ul>
        </div>

        <!-- Formulaire -->
        <div class="flex min-h-screen flex-col justify-center px-6 py-10 sm:px-10 lg:px-14 xl:px-20">
            <div class="mx-auto w-full max-w-lg">
                <div class="mb-8 flex items-center justify-between lg:hidden">
                    <div class="inline-flex items-center gap-2">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/25 text-blue-400"
                        >
                            <UiIcon name="bolt" class="h-5 w-5" />
                        </span>
                        <span class="text-lg font-bold text-white">Track Coach</span>
                    </div>
                </div>

                <Link
                    v-if="!isMobileApp"
                    href="/"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 transition hover:text-slate-300"
                >
                    <span aria-hidden="true">←</span>
                    Retour à l’accueil
                </Link>

                <h2 :class="isMobileApp ? 'text-3xl font-bold tracking-tight text-white' : 'mt-6 text-3xl font-bold tracking-tight text-white'">
                    Connexion
                </h2>
                <p v-if="isMobileApp" class="mt-2 text-base text-blue-300/90">
                    Application mobile
                </p>
                <p class="mt-2 text-base text-slate-400">
                    Accède à ton espace coach ou athlète avec ton e-mail et ton mot de passe.
                </p>

                <div
                    v-if="$page.props.flash?.error"
                    class="mt-6 rounded-xl border border-red-500/30 bg-red-950/40 px-4 py-3 text-sm text-red-200"
                >
                    {{ $page.props.flash.error }}
                </div>

                <div
                    v-if="$page.props.flash?.success"
                    class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200"
                >
                    {{ $page.props.flash.success }}
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300">
                            Adresse e-mail
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            autocomplete="username"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            placeholder="coach@exemple.fr"
                        />
                        <p v-if="form.errors.email" class="mt-2 text-sm text-red-400">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-300">
                            Mot de passe
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="mt-2 text-sm text-red-400">
                            {{ form.errors.password }}
                        </p>
                        <div class="mt-2 flex justify-end">
                            <Link href="/forgot-password" class="text-sm text-blue-400 hover:text-blue-300">
                                Mot de passe oublié ?
                            </Link>
                        </div>
                    </div>

                    <label v-if="!isMobileApp" class="flex cursor-pointer items-center gap-3">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-blue-600 focus:ring-blue-500/40"
                        />
                        <span class="text-sm text-slate-400">Rester connecté sur cet appareil</span>
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-4 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500 disabled:opacity-60"
                    >
                        <span v-if="form.processing">Connexion…</span>
                        <span v-else>Se connecter</span>
                        <span v-if="!form.processing" aria-hidden="true">→</span>
                    </button>
                </form>

                <p v-if="!isMobileApp" class="mt-8 text-center text-sm text-slate-500">
                    Coach ?
                    <Link href="/register" class="font-medium text-blue-400 hover:text-blue-300">
                        Créer un compte
                    </Link>
                    — Athlète ? Utilise le lien d’activation transmis par ton coach.
                </p>
                <p v-else class="mt-8 text-center text-sm text-slate-500">
                    Athlète ? Utilise le lien d’activation transmis par ton coach si tu n’as pas encore activé ton compte.
                </p>
            </div>
        </div>
    </div>
</template>
