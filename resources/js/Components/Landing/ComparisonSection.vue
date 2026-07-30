<script setup>
import FadeIn from './FadeIn.vue';

const columns = ['Power Roster', 'Excel', 'WhatsApp', 'Google Drive', 'Notion'];

const rows = [
    { feature: 'Programmation SBD', values: [true, 'partiel', false, false, 'partiel'] },
    { feature: 'Suivi PRs automatique', values: [true, false, false, false, false] },
    { feature: 'Vidéos annotées', values: [true, false, false, false, false] },
    { feature: 'Messagerie contextualisée', values: [true, false, true, false, false] },
    { feature: 'Gestion des meets', values: [true, false, false, false, 'partiel'] },
    { feature: 'Dashboard coach', values: [true, false, false, false, 'partiel'] },
    { feature: 'Espace athlète dédié', values: [true, false, false, false, false] },
    { feature: 'Tout au même endroit', values: [true, false, false, false, false] },
];
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="compare-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">Comparaison</p>
                <h2
                    id="compare-heading"
                    class="mt-3 max-w-xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    Power Roster vs le bricolage
                </h2>
                <p class="mt-4 max-w-lg text-base text-slate-400">
                    Ce que tu perds en multipliant les outils — et ce que tu gagnes en centralisant.
                </p>
            </FadeIn>

            <FadeIn :delay="0.12" class-name="mt-10 overflow-x-auto">
                <div class="lp-glass min-w-[720px] overflow-hidden rounded-[22px]">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-white/[0.08]">
                                <th class="px-5 py-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Capacité</th>
                                <th
                                    v-for="(col, i) in columns"
                                    :key="col"
                                    class="px-4 py-4 text-center text-xs font-bold uppercase tracking-wider"
                                    :class="i === 0 ? 'text-blue-400' : 'text-slate-500'"
                                >
                                    {{ col }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in rows"
                                :key="row.feature"
                                class="border-b border-white/[0.04] last:border-0"
                            >
                                <td class="px-5 py-3.5 font-medium text-slate-300">{{ row.feature }}</td>
                                <td
                                    v-for="(val, i) in row.values"
                                    :key="columns[i]"
                                    class="px-4 py-3.5 text-center"
                                    :class="i === 0 ? 'bg-blue-500/[0.06]' : ''"
                                >
                                    <span v-if="val === true" class="lp-check inline-flex text-lg" aria-label="Oui">✓</span>
                                    <span v-else-if="val === false" class="lp-cross inline-flex text-lg" aria-label="Non">—</span>
                                    <span v-else class="text-[11px] font-medium text-slate-500">Partiel</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
