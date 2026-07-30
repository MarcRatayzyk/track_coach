<script setup>
import FadeIn from './FadeIn.vue';

const pains = [
    {
        title: 'Données éparpillées',
        description:
            'PRs dans un Excel, programmes dans un Drive, notes partout. Impossible d’avoir une vision claire de ta roster.',
        icon: 'data',
    },
    {
        title: 'Vidéos dans WhatsApp',
        description:
            'Les séances se noient dans les fils. Tu perds le contexte, le timing et la qualité du feedback technique.',
        icon: 'video',
    },
    {
        title: 'Suivi impossible',
        description:
            'Sans historique centralisé, tu coches des cases au feeling. La progression réelle reste floue.',
        icon: 'track',
    },
];
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="pain-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Le problème</p>
                <h2
                    id="pain-heading"
                    class="mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Pourquoi les coachs abandonnent les feuilles Excel&nbsp;?
                </h2>
            </FadeIn>

            <div class="mt-12 grid gap-5 md:grid-cols-3">
                <FadeIn
                    v-for="(pain, i) in pains"
                    :key="pain.title"
                    :delay="0.08 * i"
                    class-name="lp-glass lp-glass-hover rounded-[20px] p-7"
                >
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-red-400/20 bg-red-500/10 text-red-400">
                        <span class="text-lg font-bold" aria-hidden="true">✕</span>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-white">{{ pain.title }}</h3>
                    <p class="mt-2 text-[15px] leading-relaxed text-slate-400">{{ pain.description }}</p>
                </FadeIn>
            </div>

            <FadeIn :delay="0.2" class-name="mt-10 lp-glass rounded-[22px] p-8 sm:p-10">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="max-w-2xl">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">La solution</p>
                        <h3 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">
                            Power Roster. Une plateforme. Zéro friction.
                        </h3>
                        <p class="mt-3 text-base leading-relaxed text-slate-400">
                            Remplace Excel, WhatsApp et Drive par un outil pensé pour le powerlifting :
                            roster, programmes SBD, vidéos annotées, messages et meets — synchronisés.
                        </p>
                    </div>
                    <a
                        href="/register"
                        class="lp-btn-primary shrink-0 px-7 py-3.5 text-[15px]"
                    >
                        Passer à Power Roster
                    </a>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
