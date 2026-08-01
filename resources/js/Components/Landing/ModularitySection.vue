<script setup>
import FadeIn from './FadeIn.vue';
import ScreenFrame from './ScreenFrame.vue';

const pillars = [
    {
        title: 'Tableaux de programmation',
        description: 'Colonnes, modes d’exercice, charge kg · % · RPE : chaque coach construit son tableur.',
    },
    {
        title: 'Graphiques sur mesure',
        description: 'Métriques, regroupements et filtres SBD : tu visualises ce qui compte pour ta méthode.',
    },
    {
        title: 'Check-ins & formulaires',
        description: 'Readiness et retours adaptés à ton suivi, pas un questionnaire figé pour tout le monde.',
    },
];

const showcases = [
    {
        title: 'Ton tableau, tes colonnes',
        description:
            'Choisis ce qui apparaît dans le tableur jour : Type, Séries, Reps, Charge, RPE, Repos, Muscles… et prévisualise en live.',
        src: '/images/landing/day-table-layout.png',
        alt: 'Configuration du tableau jour : colonnes et aperçu live',
        label: 'Power Roster · Tableau jour',
    },
    {
        title: 'Tes stats, ton angle',
        description:
            'Crée des graphiques volume, e1RM, RPE ou charge moyenne : par semaine, par lift, avec les filtres de ton choix.',
        src: '/images/landing/chart-builder.png',
        alt: 'Constructeur de graphiques personnalisés avec aperçu',
        label: 'Power Roster · Graphiques',
    },
];
</script>

<template>
    <section
        id="modularite"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="modularity-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Modularité</p>
                <h2
                    id="modularity-heading"
                    class="mt-3 max-w-3xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Une plateforme qui s’adapte à ta façon de coacher
                </h2>
                <p class="mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">
                    Powerlifting, force, préparation spécifique : tu configures tableaux, graphiques et check-ins
                    selon ta méthode, pas l’inverse.
                </p>
            </FadeIn>

            <div class="mt-12 grid gap-4 sm:grid-cols-3">
                <FadeIn
                    v-for="(pillar, i) in pillars"
                    :key="pillar.title"
                    :delay="0.06 * i"
                    class-name="rounded-[18px] border border-white/[0.06] bg-white/[0.02] px-5 py-5"
                >
                    <h3 class="text-base font-bold text-white">{{ pillar.title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-400">{{ pillar.description }}</p>
                </FadeIn>
            </div>

            <div class="mt-14 grid gap-10 lg:grid-cols-2 lg:gap-8">
                <FadeIn
                    v-for="(item, i) in showcases"
                    :key="item.title"
                    :delay="0.08 + i * 0.1"
                    class-name="flex flex-col"
                >
                    <div class="mb-5 max-w-md">
                        <h3 class="text-xl font-bold text-white sm:text-2xl">{{ item.title }}</h3>
                        <p class="mt-2 text-[15px] leading-relaxed text-slate-400">{{ item.description }}</p>
                    </div>
                    <div class="relative flex-1">
                        <div
                            class="pointer-events-none absolute -inset-3 rounded-[28px] bg-blue-500/10 blur-2xl"
                            aria-hidden="true"
                        />
                        <ScreenFrame
                            :src="item.src"
                            :alt="item.alt"
                            :label="item.label"
                            loading="lazy"
                        />
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
