<script setup>
import { ref } from 'vue';
import FadeIn from './FadeIn.vue';
import ScreenFrame from './ScreenFrame.vue';

const screens = [
    {
        id: 'dashboard',
        label: 'Dashboard',
        title: 'Pilotage quotidien',
        description: 'Actions prioritaires, retours à traiter et file d’attente — tout ce qui compte aujourd’hui.',
        src: '/images/landing/actions.png',
        alt: 'Dashboard coach Power Roster — actions prioritaires',
    },
    {
        id: 'feedback',
        label: 'Retours',
        title: 'Feedback de séance',
        description: 'Analyse prévue vs réalisé, commentaires athlète et réponse coach sur un même écran.',
        src: '/images/landing/session-feedback.png',
        alt: 'Écran retours de séance Power Roster',
    },
    {
        id: 'messaging',
        label: 'Messagerie',
        title: 'Conversations contextualisées',
        description: 'Inbox, chat et contexte athlète (poids, PRs, planning) côte à côte.',
        src: '/images/landing/messaging.png',
        alt: 'Messagerie coach Power Roster',
    },
    {
        id: 'alerts',
        label: 'Alertes',
        title: 'Signaux à traiter',
        description: 'Retours en retard, plans de match manquants, baisse d’adhérence — rien ne passe.',
        src: '/images/landing/alerts.png',
        alt: 'Panneau alertes coach Power Roster',
    },
    {
        id: 'roster',
        label: 'Roster',
        title: 'Vue d’ensemble athlètes',
        description: 'Totaux, GL Points, check-ins, adhérence et prochaines compétitions en un coup d’œil.',
        src: '/images/landing/roster.png',
        alt: 'Tableau roster athlètes',
    },
    {
        id: 'program',
        label: 'Programmes',
        title: 'Builder SBD',
        description: 'Séries, reps, charges kg / RPE / % — édition rapide ligne par ligne.',
        src: '/images/landing/program-editor.png',
        alt: 'Éditeur de programme powerlifting',
    },
    {
        id: 'calendar',
        label: 'Calendrier',
        title: 'Blocs & meets',
        description: 'Blocs roster, compétitions et rappels sur une timeline claire.',
        src: '/images/landing/calendar.png',
        alt: 'Calendrier blocs et compétitions',
    },
    {
        id: 'stats',
        label: 'Stats',
        title: 'Analyse de performance',
        description: 'Volume, topset e1RM, répartition SBD et charge moyenne par semaine.',
        src: '/images/landing/stats.png',
        alt: 'Tableau de bord statistiques',
    },
];

const active = ref(0);

function select(i) {
    active.value = i;
}
</script>

<template>
    <section
        id="demo"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="demo-heading"
    >
        <div class="mx-auto w-full max-w-[960px]">
            <FadeIn class-name="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Démonstration</p>
                <h2
                    id="demo-heading"
                    class="mx-auto mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Le produit, pas une maquette
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    Screens réels de Power Roster — dashboard, retours, messagerie, alertes et plus.
                </p>
            </FadeIn>

            <FadeIn :delay="0.1" class-name="mt-10">
                <div
                    class="flex flex-wrap items-center justify-center gap-2"
                    role="tablist"
                    aria-label="Écrans produit"
                >
                    <button
                        v-for="(screen, i) in screens"
                        :key="screen.id"
                        type="button"
                        role="tab"
                        :aria-selected="active === i"
                        class="rounded-xl border px-3.5 py-2 text-sm font-semibold transition"
                        :class="
                            active === i
                                ? 'border-blue-400/40 bg-blue-500/15 text-blue-300 shadow-[0_0_24px_-8px_rgba(59,130,246,0.5)]'
                                : 'border-white/10 bg-white/[0.02] text-slate-400 hover:border-white/20 hover:text-white'
                        "
                        @click="select(i)"
                    >
                        {{ screen.label }}
                    </button>
                </div>
            </FadeIn>

            <FadeIn :delay="0.15" class-name="mt-8">
                <div class="mx-auto grid max-w-3xl items-start gap-6 lg:max-w-none lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-8">
                    <div class="lp-glass rounded-[20px] p-5 sm:p-6">
                        <p class="text-xs font-semibold uppercase tracking-wider text-blue-400">
                            {{ screens[active].label }}
                        </p>
                        <h3 class="mt-2 text-xl font-black tracking-tight text-white sm:text-2xl">
                            {{ screens[active].title }}
                        </h3>
                        <p class="mt-3 text-sm leading-relaxed text-slate-400 sm:text-[15px]">
                            {{ screens[active].description }}
                        </p>
                        <a
                            href="/demo"
                            class="lp-btn-primary mt-6 inline-flex px-5 py-2.5 text-sm"
                        >
                            Ouvrir la sandbox démo
                        </a>
                    </div>

                    <div class="relative mx-auto w-full max-w-[560px] lg:mx-0 lg:max-w-[520px]">
                        <div
                            class="pointer-events-none absolute -inset-3 rounded-[24px] bg-blue-500/10 blur-2xl"
                            aria-hidden="true"
                        />
                        <div
                            v-for="(screen, i) in screens"
                            :key="screen.id"
                            v-show="active === i"
                            role="tabpanel"
                        >
                            <ScreenFrame
                                :src="screen.src"
                                :alt="screen.alt"
                                :label="`Power Roster · ${screen.label}`"
                                loading="lazy"
                            />
                        </div>
                    </div>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
