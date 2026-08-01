<script setup>
import FadeIn from './FadeIn.vue';

const sharedFeatures = [
    'Programmation SBD & builder',
    'Suivi PRs, readiness & adhérence',
    'Retours de séance',
    'Messagerie coach ↔ athlète',
    'Compétitions & match plans',
    'Dashboard coach',
];

const plans = [
    {
        key: 'starter',
        name: 'Starter',
        price: '34,99',
        description: 'Jusqu’à 15 athlètes',
        cta: 'S’abonner : 34,99 €/mois',
        href: '/subscribe/starter',
        highlight: false,
        features: [...sharedFeatures, 'Jusqu’à 15 athlètes actifs'],
    },
    {
        key: 'growth',
        name: 'Growth',
        price: '49,99',
        description: 'De 16 à 40 athlètes',
        cta: 'S’abonner : 49,99 €/mois',
        href: '/subscribe/growth',
        highlight: true,
        features: [...sharedFeatures, 'Jusqu’à 40 athlètes actifs'],
    },
    {
        key: 'scale',
        name: 'Scale',
        price: '74,99',
        description: '41 athlètes et plus',
        cta: 'S’abonner : 74,99 €/mois',
        href: '/subscribe/scale',
        highlight: false,
        features: [...sharedFeatures, 'Athlètes illimités'],
    },
];
</script>

<template>
    <section
        id="pricing"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="pricing-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn class-name="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Tarifs</p>
                <h2
                    id="pricing-heading"
                    class="mx-auto mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Simple : selon ton roster
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    Toutes les fonctionnalités incluses. Tarifs selon le nombre d’athlètes actifs.
                    Essai 14 jours sans carte à l’inscription.
                </p>
            </FadeIn>

            <div class="mt-12 grid gap-4 md:grid-cols-3">
                <FadeIn
                    v-for="(plan, i) in plans"
                    :key="plan.key"
                    :delay="0.08 * i"
                    class-name="h-full"
                >
                    <article
                        class="flex h-full flex-col rounded-[22px] border p-6 sm:p-7"
                        :class="
                            plan.highlight
                                ? 'border-blue-400/40 bg-blue-500/10 shadow-[0_0_40px_-16px_rgba(59,130,246,0.55)]'
                                : 'lp-glass'
                        "
                    >
                        <p class="text-sm font-semibold uppercase tracking-wider text-blue-400">
                            {{ plan.name }}
                        </p>
                        <p class="mt-4 text-4xl font-black text-white">
                            {{ plan.price }} €
                            <span class="text-base font-medium text-slate-400">/ mois</span>
                        </p>
                        <p class="mt-3 text-[15px] text-slate-400">{{ plan.description }}</p>

                        <ul class="mt-6 flex-1 space-y-2.5">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-start gap-2.5 text-sm text-slate-300"
                            >
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <a
                            :href="plan.href"
                            class="lp-btn-primary mt-8 flex w-full items-center justify-center px-5 py-3.5 text-sm leading-none"
                        >
                            {{ plan.cta }}
                        </a>
                    </article>
                </FadeIn>
            </div>

            <FadeIn :delay="0.25" class-name="mt-10 text-center">
                <p class="text-sm text-slate-500">
                    Déjà un compte ?
                    <a href="/login" class="font-semibold text-blue-400 hover:underline">Se connecter</a>
                    puis choisis un plan ci-dessus, ou
                    <a href="/subscribe/growth" class="font-semibold text-blue-400 hover:underline">reprendre Growth</a>
                    ·
                    <a href="/demo" class="font-semibold text-blue-400 hover:underline">Démo</a>
                </p>
            </FadeIn>
        </div>
    </section>
</template>
