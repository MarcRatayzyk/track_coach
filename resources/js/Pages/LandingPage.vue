<script setup>
import { Head } from '@inertiajs/vue3';
import AppLogo from '../Components/AppLogo.vue';
import UiIcon from '../Components/UiIcon.vue';
import { track } from '../utils/analytics';

function trackCta(ctaId) {
    track('cta_clicked', { cta_id: ctaId });
}

const features = [
    {
        icon: 'video',
        title: 'Retours vidéo annotés',
        description:
            'Tes athlètes envoient leurs vidéos de séance, tu réponds avec des annotations dessinées image par image. Le feedback technique qui fait la différence.',
    },
    {
        icon: 'dashboard',
        title: 'Dashboard intelligent',
        description:
            'Visualise tes tâches, compétitions à venir et records récents en un coup d’œil pour ne rien laisser passer.',
    },
    {
        icon: 'users',
        title: 'Gestion des athlètes',
        description:
            'Centralise les profils, catégories de poids et suivi individuel de toute ta roster en un seul endroit.',
    },
    {
        icon: 'clipboard',
        title: 'Programmes sur mesure',
        description:
            'Crée des modèles de blocs, assigne-les en quelques clics et adapte la charge selon chaque athlète.',
    },
    {
        icon: 'chat',
        title: 'Messagerie intégrée',
        description:
            'Échange avec tes athlètes sans quitter l’app : consignes de séance, RPE et retours en temps réel.',
    },
    {
        icon: 'trophy',
        title: 'Records & progression',
        description:
            'Suis squat, bench et deadlift dans le temps pour objectiver la progression vers le peak.',
    },
    {
        icon: 'calendar',
        title: 'Calendrier compétitions',
        description:
            'Planifie les meets, fixe les objectifs de total et aligne la programmation sur la date J.',
    },
];

const steps = [
    {
        step: '01',
        title: 'Crée ton compte coach',
        description: 'Inscription gratuite en quelques clics — tu accèdes immédiatement à ton dashboard.',
    },
    {
        step: '02',
        title: 'Invite tes athlètes',
        description: 'Ajoute tes athlètes par e-mail : ils reçoivent un lien pour activer leur compte.',
    },
    {
        step: '03',
        title: 'Coache au quotidien',
        description: 'Programmes, messagerie et retours vidéo : tout converge vers un suivi structuré.',
    },
];
</script>

<template>
    <Head title="Power Roster — Coaching powerlifting structuré" />

    <div class="min-h-screen bg-slate-950 text-slate-200">
        <!-- Nav -->
        <header
            class="fixed inset-x-0 top-0 z-50 border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-xl"
        >
            <div class="mx-auto flex w-full max-w-[1600px] items-center justify-between gap-2 px-4 py-3 sm:gap-3 sm:px-10 sm:py-4 lg:px-14 xl:px-20">
                <a href="/" class="flex min-w-0 items-center gap-2 sm:gap-3">
                    <AppLogo
                        mark-class="h-9 w-9 sm:h-11 sm:w-11"
                        wordmark-class="truncate text-base font-bold tracking-tight text-white sm:text-xl"
                    />
                </a>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <a
                        href="/login"
                        class="rounded-xl border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200 sm:px-5 sm:py-2.5 sm:text-sm"
                        @click="trackCta('nav_login')"
                    >
                        Se connecter
                    </a>
                    <a
                        href="/register"
                        class="rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-lg shadow-blue-900/40 sm:px-5 sm:py-2.5 sm:text-sm"
                        @click="trackCta('nav_register')"
                    >
                        Créer un compte
                    </a>
                </div>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden pt-28 pb-20 lg:pt-36 lg:pb-28">
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_80%_60%_at_50%_-20%,rgba(59,130,246,0.25),transparent)]"
            />
            <div
                class="pointer-events-none absolute -right-32 top-20 h-96 w-96 rounded-full bg-blue-600/10 blur-3xl"
            />
            <div
                class="relative mx-auto grid w-full max-w-[1600px] gap-12 px-6 sm:px-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:gap-16 lg:px-14 xl:px-20"
            >
                <div>
                <p
                    class="inline-flex items-center gap-2 rounded-full border border-blue-500/30 bg-blue-600/10 px-4 py-1.5 text-sm font-medium text-blue-300"
                >
                    <UiIcon name="bolt" class="h-4 w-4" />
                    Conçu pour les coachs powerlifting
                </p>
                <h1
                    class="mt-8 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl xl:text-6xl 2xl:text-7xl"
                >
                    Structure ton coaching.
                    <span class="text-blue-400">Pousse tes athlètes</span>
                    vers leurs meilleurs totaux.
                </h1>
                <p class="mt-6 max-w-2xl text-lg leading-relaxed text-slate-400 lg:text-xl">
                    Power Roster réunit roster, programmation, retours vidéo annotés et messagerie dans
                    une seule plateforme — pour que tu passes moins de temps à jongler entre outils et
                    plus de temps à faire progresser tes athlètes.
                </p>
                <div class="mt-10 flex flex-wrap items-center gap-4">
                    <a
                        href="/register"
                        class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-8 py-4 text-base font-semibold text-white shadow-xl shadow-blue-900/50 transition hover:bg-blue-500"
                        @click="trackCta('hero_register')"
                    >
                        Créer mon compte coach
                        <span aria-hidden="true">→</span>
                    </a>
                    <a
                        href="/login"
                        class="inline-flex items-center rounded-2xl border border-slate-600 bg-slate-900/50 px-8 py-4 text-base font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-800/50"
                        @click="trackCta('hero_login')"
                    >
                        Se connecter
                    </a>
                    <a
                        href="#fonctionnalites"
                        class="inline-flex items-center rounded-2xl border border-transparent px-4 py-4 text-base font-semibold text-slate-400 transition hover:text-slate-200"
                        @click="trackCta('hero_features')"
                    >
                        Découvrir les fonctionnalités
                    </a>
                </div>

                </div>

                <div
                    class="rounded-3xl border border-slate-700/80 bg-slate-900/60 p-8 shadow-2xl shadow-black/40 backdrop-blur-sm lg:p-10"
                >
                    <p class="text-sm font-semibold uppercase tracking-widest text-blue-400">Accès rapide</p>
                    <h2 class="mt-2 text-2xl font-bold text-white">Coach ou athlète ?</h2>
                    <p class="mt-2 text-slate-400">
                        Les coachs créent un compte. Les athlètes activent l’accès via l’invitation de leur coach.
                    </p>
                    <div class="mt-8 space-y-4">
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-700/60 bg-slate-950/80 p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-600/20 text-blue-400">
                                <UiIcon name="dashboard" class="h-6 w-6" />
                            </span>
                            <div>
                                <p class="font-semibold text-white">Dashboard coach</p>
                                <p class="text-sm text-slate-500">Tâches, compétitions, messages</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-4 rounded-2xl border border-slate-700/60 bg-slate-950/80 p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600/20 text-emerald-400">
                                <UiIcon name="user-circle" class="h-6 w-6" />
                            </span>
                            <div>
                                <p class="font-semibold text-white">Espace athlète</p>
                                <p class="text-sm text-slate-500">Profil, PRs et objectifs meet</p>
                            </div>
                        </div>
                    </div>
                    <a
                        href="/register"
                        class="mt-8 flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 py-4 text-base font-semibold text-white transition hover:bg-blue-500"
                    >
                        Créer un compte coach <span aria-hidden="true">→</span>
                    </a>
                    <a
                        href="/login"
                        class="mt-3 flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-600 py-4 text-base font-semibold text-slate-200 transition hover:bg-slate-800/50"
                    >
                        Déjà inscrit ? Se connecter
                    </a>
                </div>

                <dl
                    class="col-span-full grid grid-cols-2 gap-8 border-t border-slate-800 pt-12 sm:grid-cols-4"
                >
                    <div>
                        <dt class="text-3xl font-bold text-white lg:text-4xl">1</dt>
                        <dd class="mt-1 text-sm text-slate-400">espace unifié</dd>
                    </div>
                    <div>
                        <dt class="text-3xl font-bold text-white lg:text-4xl">SBD</dt>
                        <dd class="mt-1 text-sm text-slate-400">suivi des mouvements</dd>
                    </div>
                    <div>
                        <dt class="text-3xl font-bold text-white lg:text-4xl">24/7</dt>
                        <dd class="mt-1 text-sm text-slate-400">accès athlètes & coach</dd>
                    </div>
                    <div>
                        <dt class="text-3xl font-bold text-white lg:text-4xl">FR</dt>
                        <dd class="mt-1 text-sm text-slate-400">interface en français</dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- Features -->
        <section id="fonctionnalites" class="border-t border-slate-800/80 bg-slate-900/30 py-20 lg:py-28">
            <div class="mx-auto w-full max-w-[1600px] px-6 sm:px-10 lg:px-14 xl:px-20">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-400">
                    Fonctionnalités
                </p>
                <h2 class="mt-3 max-w-2xl text-3xl font-bold text-white lg:text-4xl">
                    Tout ce dont un coach power a besoin, sans la complexité
                </h2>
                <p class="mt-4 max-w-2xl text-lg text-slate-400">
                    De l’invitation du premier athlète au peak en compétition, chaque brique est pensée
                    pour le force athlétique.
                </p>
                <div class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-slate-800 bg-slate-950/60 p-7 transition hover:border-blue-500/40 hover:bg-slate-900/80"
                    >
                        <span
                            class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600/15 text-blue-400 transition group-hover:bg-blue-600/25"
                        >
                            <UiIcon :name="feature.icon" class="h-6 w-6" />
                        </span>
                        <h3 class="mt-5 text-xl font-semibold text-white">{{ feature.title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-400">{{ feature.description }}</p>
                    </article>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section class="py-20 lg:py-28">
            <div class="mx-auto w-full max-w-[1600px] px-6 sm:px-10 lg:px-14 xl:px-20">
                <p class="text-sm font-semibold uppercase tracking-widest text-blue-400">
                    Comment ça marche
                </p>
                <h2 class="mt-3 text-3xl font-bold text-white lg:text-4xl">
                    Opérationnel en trois étapes
                </h2>
                <ol class="mt-14 grid gap-8 lg:grid-cols-3">
                    <li
                        v-for="item in steps"
                        :key="item.step"
                        class="relative rounded-2xl border border-slate-800 bg-gradient-to-b from-slate-900/80 to-slate-950 p-8"
                    >
                        <span class="text-5xl font-bold text-slate-800">{{ item.step }}</span>
                        <h3 class="mt-4 text-xl font-semibold text-white">{{ item.title }}</h3>
                        <p class="mt-2 leading-relaxed text-slate-400">{{ item.description }}</p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- Audience -->
        <section class="border-t border-slate-800/80 bg-slate-900/30 py-20 lg:py-28">
            <div class="mx-auto w-full max-w-[1600px] px-6 sm:px-10 lg:px-14 xl:px-20">
                <div class="grid gap-8 lg:grid-cols-2">
                    <div
                        class="rounded-2xl border border-blue-500/30 bg-blue-600/5 p-8 lg:p-10"
                    >
                        <p class="text-sm font-semibold uppercase tracking-widest text-blue-400">
                            Pour les coachs
                        </p>
                        <h3 class="mt-3 text-2xl font-bold text-white">
                            Pilote ta roster comme un pro
                        </h3>
                        <ul class="mt-6 space-y-3 text-slate-300">
                            <li class="flex gap-3">
                                <span class="text-blue-400">✓</span>
                                Dashboard avec tâches et alertes
                            </li>
                            <li class="flex gap-3">
                                <span class="text-blue-400">✓</span>
                                Builder de programmes et assignations
                            </li>
                            <li class="flex gap-3">
                                <span class="text-blue-400">✓</span>
                                Messagerie dédiée par athlète
                            </li>
                        </ul>
                        <a
                            href="/register"
                            class="mt-8 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-blue-500"
                        >
                            Créer mon compte
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                    <div
                        class="rounded-2xl border border-slate-700 bg-slate-950/80 p-8 lg:p-10"
                    >
                        <p class="text-sm font-semibold uppercase tracking-widest text-slate-400">
                            Pour les athlètes
                        </p>
                        <h3 class="mt-3 text-2xl font-bold text-white">
                            Reste aligné avec ton coach
                        </h3>
                        <ul class="mt-6 space-y-3 text-slate-300">
                            <li class="flex gap-3">
                                <span class="text-emerald-400">✓</span>
                                Accès à ton profil et tes records
                            </li>
                            <li class="flex gap-3">
                                <span class="text-emerald-400">✓</span>
                                Objectifs de compétition visibles
                            </li>
                            <li class="flex gap-3">
                                <span class="text-emerald-400">✓</span>
                                Échanges directs avec ton coach
                            </li>
                        </ul>
                        <p class="mt-8 text-sm text-slate-400">
                            Compte activé sur invitation de ton coach.
                            <a href="/login" class="font-medium text-blue-400 hover:text-blue-300">Se connecter</a>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-20 lg:py-28">
            <div
                class="mx-auto w-full max-w-[1600px] px-6 sm:px-10 lg:px-14 xl:px-20"
            >
            <div
                class="rounded-3xl border border-blue-500/30 bg-gradient-to-br from-blue-600/20 via-slate-900 to-slate-950 px-8 py-14 text-center lg:px-16 lg:py-20"
            >
                <h2 class="text-3xl font-bold text-white lg:text-4xl">
                    Prêt à structurer ton coaching ?
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-lg text-slate-300">
                    Crée ton compte coach gratuitement, invite tes athlètes et structure ton coaching dès
                    aujourd’hui.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <a
                        href="/register"
                        class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-10 py-4 text-lg font-semibold text-white shadow-xl shadow-blue-900/50 transition hover:bg-blue-500"
                        @click="trackCta('footer_register')"
                    >
                        Créer mon compte coach
                        <span aria-hidden="true">→</span>
                    </a>
                    <a
                        href="/login"
                        class="inline-flex items-center rounded-2xl border border-slate-600 px-10 py-4 text-lg font-semibold text-slate-200 transition hover:border-slate-500 hover:bg-slate-800/50"
                        @click="trackCta('footer_login')"
                    >
                        Se connecter
                    </a>
                </div>
            </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-slate-800 py-10">
            <div
                class="mx-auto flex w-full max-w-[1600px] flex-col items-center justify-between gap-4 px-6 text-sm text-slate-500 sm:flex-row lg:px-10"
            >
                <div class="flex items-center gap-2">
                    <AppLogo
                        mark-class="h-8 w-8"
                        wordmark-class="font-semibold text-slate-400"
                    />
                </div>
                <p>Power Roster — Coaching powerlifting : roster, programmes & performance.</p>
                <div class="flex items-center gap-4">
                    <a href="/register" class="text-blue-400 transition hover:text-blue-300">
                        Inscription coach
                    </a>
                    <a href="/login" class="text-slate-400 transition hover:text-slate-300">
                        Connexion
                    </a>
                    <a href="/confidentialite" class="text-slate-400 transition hover:text-slate-300">
                        Confidentialité
                    </a>
                </div>
            </div>
        </footer>
    </div>
</template>
