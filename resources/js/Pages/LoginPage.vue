<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLogo from '../Components/AppLogo.vue';
import UiIcon from '../Components/UiIcon.vue';
import { useNativeApp } from '../composables/useNativeApp';
import { track } from '../utils/analytics';

const { isMobileApp } = useNativeApp();

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
});

const showPassword = ref(false);

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
        onSuccess: () => {
            track('user_logged_in');
        },
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
                <Link href="/" class="inline-flex items-center">
                    <AppLogo
                        mark-class="h-11 w-11"
                        wordmark-class="text-2xl font-bold tracking-tight text-white"
                    />
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
                    <div class="inline-flex items-center">
                        <AppLogo
                            mark-class="h-10 w-10"
                            wordmark-class="text-lg font-bold text-white"
                        />
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
                        <div class="relative mt-2">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border border-slate-700 bg-slate-900/80 px-4 py-3.5 pr-12 text-base text-white placeholder-slate-500 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition hover:text-slate-200"
                                :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
                                :aria-pressed="showPassword"
                                @click="showPassword = !showPassword"
                            >
                                <svg
                                    v-if="!showPassword"
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"
                                    />
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="h-5 w-5"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"
                                    />
                                </svg>
                            </button>
                        </div>
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
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-4 text-base font-semibold leading-none text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500 disabled:opacity-60"
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
