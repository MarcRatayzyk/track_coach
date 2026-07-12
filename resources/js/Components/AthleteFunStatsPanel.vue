<script setup>
const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const cards = [
  { key: 'session_streak', label: 'Streak séances', suffix: ' j' },
  { key: 'pr_count_year', label: 'PR cette année', suffix: '' },
  { key: 'high_rpe_sessions_month', label: 'Séances RPE ≥ 9 (mois)', suffix: '' },
  { key: 'total_gain_kg_since_coaching', label: 'Gain SBD depuis suivi', suffix: ' kg' },
  { key: 'logged_sessions_total', label: 'Séances loguées', suffix: '' },
  { key: 'pr_count_since_coaching', label: 'PR depuis suivi', suffix: '' },
];

function displayValue(key) {
  const value = props.stats?.[key];
  if (value == null || value === '') {
    return '—';
  }
  return value;
}
</script>

<template>
  <section
    class="rounded-xl border border-amber-500/20 bg-amber-950/10 shadow-lg"
    :class="compact ? 'p-3' : 'p-4'"
  >
    <h2 class="text-sm font-semibold text-amber-100">
      {{ compact ? 'Highlights' : 'Statistiques incroyables' }}
    </h2>
    <div
      class="mt-3 grid gap-2"
      :class="compact ? 'grid-cols-2' : 'grid-cols-2 sm:grid-cols-3'"
    >
      <article
        v-for="card in cards"
        :key="card.key"
        class="rounded-xl border border-amber-500/10 bg-slate-950/40 px-3 py-2.5"
      >
        <p class="text-[10px] uppercase tracking-wide text-amber-200/60">{{ card.label }}</p>
        <p class="mt-0.5 text-lg font-bold tabular-nums text-white">
          {{ displayValue(card.key) }}<span v-if="displayValue(card.key) !== '—'" class="text-sm font-medium text-amber-100/70">{{ card.suffix }}</span>
        </p>
      </article>
    </div>
  </section>
</template>
