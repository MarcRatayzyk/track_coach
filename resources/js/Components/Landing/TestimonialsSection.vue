<script setup>
import FadeIn from './FadeIn.vue';

const testimonials = [
    {
        name: 'Julien Moreau',
        club: 'Force Lyon · Coach IPF',
        initials: 'JM',
        quote:
            'J’ai arrêté Excel en deux semaines. Mes athlètes envoient leurs vidéos ici, je programme ici, on parle ici. Plus de chaos WhatsApp.',
    },
    {
        name: 'Camille Roux',
        club: 'Iron Base Paris',
        initials: 'CR',
        quote:
            'Enfin un outil qui parle squat, bench et deadlift. Les PRs et le suivi de volume me font gagner des heures chaque semaine.',
    },
    {
        name: 'Alex Duarte',
        club: 'Atlas Strength · 18 athlètes',
        initials: 'AD',
        quote:
            'La plateforme a l’air d’un vrai produit pro. Mes athlètes sont plus disciplinés depuis qu’ils ont leur espace dédié.',
    },
];
</script>

<template>
    <section
        id="temoignages"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="testimonials-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Témoignages</p>
                <h2
                    id="testimonials-heading"
                    class="mt-3 max-w-xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Des coachs qui ont structuré leur pratique
                </h2>
            </FadeIn>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                <FadeIn
                    v-for="(t, i) in testimonials"
                    :key="t.name"
                    :delay="0.1 * i"
                    class-name="lp-glass lp-glass-hover flex flex-col rounded-[22px] p-7"
                >
                    <svg class="h-6 w-6 text-blue-400/50" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7.17 6A5.17 5.17 0 002 11.17V18h6.83v-6.83H6.1A3.1 3.1 0 019.2 8.07L7.17 6zm12 0A5.17 5.17 0 0014 11.17V18h6.83v-6.83h-2.73a3.1 3.1 0 013.1-3.1L19.17 6z" />
                    </svg>
                    <blockquote class="mt-5 flex-1 text-[15px] leading-relaxed text-slate-300">
                        « {{ t.quote }} »
                    </blockquote>
                    <div class="mt-7 flex items-center gap-3 border-t border-white/[0.06] pt-5">
                        <span
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-700 text-sm font-bold text-white shadow-lg shadow-blue-900/40"
                        >
                            {{ t.initials }}
                        </span>
                        <div>
                            <p class="font-semibold text-white">{{ t.name }}</p>
                            <p class="text-sm text-slate-500">{{ t.club }}</p>
                        </div>
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
