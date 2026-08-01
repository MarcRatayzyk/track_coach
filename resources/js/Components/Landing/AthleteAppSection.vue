<script setup>
import FadeIn from './FadeIn.vue';

const athleteBenefits = [
    {
        title: 'Programme clair, chaque jour',
        description: 'Séances SBD, charges et consignes accessibles sans chercher dans trois endroits différents.',
        path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        title: 'Feedback & vidéos intégrés',
        description: 'Envoie la vidéo de séance, reçois l’annotation coach : le contexte reste attaché à la séance.',
        path: 'M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z',
    },
    {
        title: 'Check-ins & messagerie',
        description: 'Readiness, questions et échanges avec le coach au même endroit que le programme.',
        path: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
    },
];

const coachGains = [
    {
        title: 'Vision globale par athlète',
        description: 'Programme, retours, check-ins et messages reliés : tu vois où en est chacun sans reconstruire le contexte.',
        path: 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z',
    },
    {
        title: 'Moins de relances, plus de coaching',
        description: 'L’athlète sait quoi faire et où remonter. Tu récupères des données structurées, pas des fils éparpillés.',
        path: 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
    },
    {
        title: 'Un seul fil conducteur',
        description: 'Ce que tu programmes est ce qu’il exécute. Le feedback revient au bon endroit : la boucle reste fermée.',
        path: 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
    },
];
</script>

<template>
    <section
        id="app-athlete"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="athlete-app-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Application athlète</p>
                <h2
                    id="athlete-app-heading"
                    class="mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Un espace dédié pour l’athlète et pour toi
                </h2>
                <p class="mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">
                    Plus besoin de jongler entre outils : l’athlète suit son programme là où tu coaches.
                    Le contexte reste partagé, la vision globale aussi.
                </p>
            </FadeIn>

            <div class="mt-14 grid gap-5 lg:grid-cols-2">
                <FadeIn class-name="lp-glass overflow-hidden rounded-[22px]">
                    <div class="border-b border-white/[0.06] px-7 py-6 sm:px-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-400">Pour l’athlète</p>
                        <h3 class="mt-2 text-xl font-bold text-white sm:text-2xl">Tout le coaching à portée de main</h3>
                    </div>
                    <ul class="space-y-0 divide-y divide-white/[0.06] p-2 sm:p-3">
                        <li
                            v-for="item in athleteBenefits"
                            :key="item.title"
                            class="flex gap-4 rounded-[16px] p-5 transition hover:bg-white/[0.03]"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/15 text-blue-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.path" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-white">{{ item.title }}</h4>
                                <p class="mt-1 text-[15px] leading-relaxed text-slate-400">{{ item.description }}</p>
                            </div>
                        </li>
                    </ul>
                </FadeIn>

                <FadeIn :delay="0.1" class-name="lp-glass overflow-hidden rounded-[22px]">
                    <div class="border-b border-white/[0.06] px-7 py-6 sm:px-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.16em] text-blue-400">Pour le coach</p>
                        <h3 class="mt-2 text-xl font-bold text-white sm:text-2xl">Ce que tu as à y gagner</h3>
                    </div>
                    <ul class="space-y-0 divide-y divide-white/[0.06] p-2 sm:p-3">
                        <li
                            v-for="item in coachGains"
                            :key="item.title"
                            class="flex gap-4 rounded-[16px] p-5 transition hover:bg-white/[0.03]"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/15 text-blue-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" :d="item.path" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-base font-bold text-white">{{ item.title }}</h4>
                                <p class="mt-1 text-[15px] leading-relaxed text-slate-400">{{ item.description }}</p>
                            </div>
                        </li>
                    </ul>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
