<script setup>
import FadeIn from './FadeIn.vue';

const features = [
    {
        title: 'Gestion des athlètes',
        description: 'Totaux, catégories, check-ins, adhérence et prochaines compétitions — toute la roster en un tableau.',
        image: '/images/landing/roster.png',
        imagePos: 'object-top',
        path: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    },
    {
        title: 'Programmation',
        description: 'Builder SBD avec types TOP / BACK / ACC, charges kg · RPE · %, édition rapide ligne par ligne.',
        image: '/images/landing/program-editor.png',
        imagePos: 'object-left-top',
        path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Suivi SBD',
        description: 'Volume, topset e1RM et charge moyenne par semaine pour squat, bench et deadlift.',
        image: '/images/landing/stats.png',
        imagePos: 'object-top',
        path: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    },
    {
        title: 'Retours & feedback',
        description: 'Analyse prévue vs réalisé, vidéo et réponse coach — tout sur un même écran.',
        image: '/images/landing/session-feedback.png',
        imagePos: 'object-left-top',
        path: 'M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z',
    },
    {
        title: 'Messagerie',
        description: 'Conversations contextualisées par athlète, sans quitter le dashboard.',
        image: '/images/landing/messaging.png',
        imagePos: 'object-left-top',
        path: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
    },
    {
        title: 'Compétitions',
        description: 'Meets, countdowns J-X et plans de match — alertes urgentes incluses.',
        image: '/images/landing/calendar.png',
        imagePos: 'object-center',
        path: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    },
    {
        title: 'Calendrier',
        description: 'Blocs, rappels et compétitions sur une timeline multi-mois.',
        image: '/images/landing/calendar.png',
        imagePos: 'object-top',
        path: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    },
    {
        title: 'Alertes coach',
        description: 'Adhérence en baisse, plans de match manquants — rien ne passe entre les mailles.',
        image: '/images/landing/alerts.png',
        imagePos: 'object-top',
        path: 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
    },
    {
        title: 'Statistiques',
        description: 'Graphiques volume, e1RM et répartition SBD — dashboard stats personnalisable.',
        image: '/images/landing/stats.png',
        imagePos: 'object-center',
        path: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
    },
    {
        title: 'Édition rapide',
        description: 'Séries, reps et charge en grille — passe à la ligne suivante sans quitter le flow.',
        image: '/images/landing/program-editor.png',
        imagePos: 'object-right-top',
        path: 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z',
    },
];
</script>

<template>
    <section
        id="fonctionnalites"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="features-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Fonctionnalités</p>
                <h2
                    id="features-heading"
                    class="mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Tout ce dont un coach power a besoin
                </h2>
                <p class="mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    Chaque écran est pensé pour le force athlétique — pas pour du fitness générique.
                </p>
            </FadeIn>

            <div class="mt-14 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                <FadeIn
                    v-for="(f, i) in features"
                    :key="f.title"
                    :delay="Math.min(i * 0.05, 0.35)"
                    class-name="group lp-glass lp-glass-hover flex flex-col overflow-hidden rounded-[20px]"
                >
                    <div class="relative h-[140px] overflow-hidden border-b border-white/[0.06] bg-[#070d1f]">
                        <img
                            :src="f.image"
                            :alt="f.title"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full scale-105 object-cover transition duration-500 group-hover:scale-110"
                            :class="f.imagePos"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#050B1E]/80 via-transparent to-transparent"
                            aria-hidden="true"
                        />
                    </div>

                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/15 text-blue-400 transition group-hover:bg-blue-500/25">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="f.path" />
                            </svg>
                        </div>
                        <h3 class="mt-4 text-lg font-bold text-white">{{ f.title }}</h3>
                        <p class="mt-1.5 text-[15px] leading-relaxed text-slate-400">{{ f.description }}</p>
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
