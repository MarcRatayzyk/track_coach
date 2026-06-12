<script setup>
import { computed } from 'vue';
import { COMPETITION_COLORS, glowCardStyle } from '../utils/chartTheme';
import { theme } from '../composables/useTheme';

const props = defineProps({
  weightClass: {
    type: String,
    default: '—',
  },
  practiceDurationLabel: {
    type: String,
    default: '—',
  },
  latestCompetitionDateLabel: {
    type: String,
    default: '—',
  },
  latestCompetitionBars: {
    type: Object,
    default: () => ({ squat: '—', bench: '—', deadlift: '—' }),
  },
  trainingPrs: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
  nextCompetition: {
    type: Object,
    default: null,
  },
  isCoach: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['open-competition', 'add-competition']);

const compactCards = computed(() => [
  { label: 'Catégorie', value: props.weightClass || '—' },
  { label: 'Temps de pratique', value: props.practiceDurationLabel || '—' },
]);

const competitionCountdown = computed(() => {
  if (!props.nextCompetition) {
    return null;
  }
  const days = props.nextCompetition.days_until;
  if (days === 0) {
    return 'J-0';
  }
  return `J-${days}`;
});

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}

function openNextCompetition() {
  if (props.nextCompetition) {
    emit('open-competition', props.nextCompetition);
  }
}

const nextCompGlowStyle = computed(() => {
  void theme.value;

  return glowCardStyle(COMPETITION_COLORS);
});
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold text-white">Profil</h2>
        <p class="mt-1 text-xs text-slate-500">
          Informations clés et repères de performance.
        </p>
      </div>
    </div>

    <div class="mt-4 grid gap-3 lg:grid-cols-3">
      <article
        v-for="card in compactCards"
        :key="card.label"
        class="min-h-[6.5rem] rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ card.label }}</p>
        <p class="mt-1 text-sm font-semibold text-slate-100">{{ card.value }}</p>
      </article>

      <button
        v-if="nextCompetition"
        type="button"
        class="glow-card glow-card--pulse min-h-[6.5rem] rounded-xl px-3 py-3 text-left transition-shadow duration-300"
        :style="nextCompGlowStyle"
        @click="openNextCompetition"
      >
        <p class="text-[11px] uppercase tracking-wide text-slate-500">Prochaine comp</p>
        <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ competitionCountdown }}</p>
        <p class="mt-0.5 truncate text-[11px] text-slate-400">{{ nextCompetition.name }}</p>
        <p v-if="nextCompetition.goal" class="mt-0.5 truncate text-[10px] text-slate-500">
          Objectif : {{ nextCompetition.goal }}
        </p>
      </button>

      <article
        v-else
        class="flex min-h-[6.5rem] flex-col justify-between rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3"
      >
        <div>
          <p class="text-[11px] uppercase tracking-wide text-slate-500">Prochaine comp</p>
          <p class="mt-1 text-sm font-semibold text-slate-500">—</p>
        </div>
        <button
          v-if="isCoach"
          type="button"
          class="self-start rounded-lg border border-blue-500/40 px-2.5 py-1 text-[11px] font-semibold text-blue-300 hover:bg-blue-500/10"
          @click="emit('add-competition')"
        >
          Ajouter
        </button>
      </article>
    </div>

    <article class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3">
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold text-white">Barres dernière comp</h3>
            <span class="text-[11px] text-slate-500">{{ latestCompetitionDateLabel }}</span>
          </div>
          <ul class="mt-3 space-y-2 text-sm">
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Squat</span>
              <span class="font-medium text-slate-100">{{ latestCompetitionBars.squat }}</span>
            </li>
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Bench</span>
              <span class="font-medium text-slate-100">{{ latestCompetitionBars.bench }}</span>
            </li>
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Deadlift</span>
              <span class="font-medium text-slate-100">{{ latestCompetitionBars.deadlift }}</span>
            </li>
          </ul>
        </div>

        <div class="sm:border-l sm:border-slate-800 sm:pl-4">
          <h3 class="text-sm font-semibold text-white">PR à l'entraînement</h3>
          <ul class="mt-3 space-y-2 text-sm">
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Squat</span>
              <span class="font-medium text-slate-100">{{ formatKg(trainingPrs.squat) }}</span>
            </li>
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Bench</span>
              <span class="font-medium text-slate-100">{{ formatKg(trainingPrs.bench) }}</span>
            </li>
            <li class="flex items-center justify-between gap-3">
              <span class="text-slate-400">Deadlift</span>
              <span class="font-medium text-slate-100">{{ formatKg(trainingPrs.deadlift) }}</span>
            </li>
          </ul>
        </div>
      </div>
    </article>
  </section>
</template>

<style scoped>
.glow-card:hover:not(:disabled) {
  filter: brightness(1.04);
}

.glow-card--pulse {
  animation: glow-pulse 3.5s ease-in-out infinite;
}

@keyframes glow-pulse {
  0%,
  100% {
    filter: brightness(1);
  }

  50% {
    filter: brightness(1.08);
  }
}
</style>
