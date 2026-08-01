<script setup>
import FadeIn from './FadeIn.vue';

const benefits = [
    {
        big: 'Temps',
        title: 'Tu gagnes du temps',
        description: 'Moins de copier-coller, moins de chasse aux vidéos. Plus de coaching réel.',
    },
    {
        big: 'Précision',
        title: 'Tes athlètes progressent',
        description: 'Charges, RPE, PRs et retours alignés : tu ajustes sur des faits, pas sur l’intuition.',
    },
    {
        big: '1',
        label: 'source de vérité',
        title: 'Tout est centralisé',
        description: 'Programmes, messages, meets et historique : un seul endroit pour toute la roster.',
    },
];
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="benefits-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Bénéfices</p>
                <h2
                    id="benefits-heading"
                    class="mt-3 max-w-xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Ce que change Power Roster au quotidien
                </h2>
            </FadeIn>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                <FadeIn
                    v-for="(b, i) in benefits"
                    :key="b.title"
                    :delay="0.1 * i"
                    class-name="lp-glass lp-glass-hover rounded-[22px] p-8"
                >
                    <p class="text-4xl font-black tracking-tight text-blue-400 sm:text-5xl">{{ b.big }}</p>
                    <p v-if="b.label" class="mt-1 text-sm font-medium text-slate-500">{{ b.label }}</p>
                    <h3 class="mt-6 text-xl font-bold text-white">{{ b.title }}</h3>
                    <p class="mt-2 text-[15px] leading-relaxed text-slate-400">{{ b.description }}</p>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
