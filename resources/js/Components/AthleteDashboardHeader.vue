<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
import { formatCalendarFr } from '../utils/formatDates';
import { BLOCK_TYPES } from '../utils/programBuilder';
import {
  BLOCK_TYPE_COLORS,
  COMPETITION_COLORS,
  glowCardStyle,
  prGlowCardStyle,
  READINESS_COLORS,
} from '../utils/chartTheme';
import { theme } from '../composables/useTheme';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  athleteName: {
    type: String,
    required: true,
  },
  athleteId: {
    type: Number,
    required: true,
  },
  nextCompetition: {
    type: Object,
    default: null,
  },
  blockProgress: {
    type: Object,
    default: null,
  },
  latestPr: {
    type: Object,
    default: null,
  },
  readinessRecent: {
    type: Array,
    default: () => [],
  },
  feedbackDueToday: {
    type: Boolean,
    default: false,
  },
  feedbackFrequency: {
    type: String,
    default: 'weekly',
  },
});

const todayLabel = formatCalendarFr(new Date().toISOString().slice(0, 10), 'long');

const blockTypeLabel = computed(() => {
  const value = props.blockProgress?.block_type;
  return BLOCK_TYPES.find((item) => item.value === value)?.label ?? value ?? '—';
});

const blockTypeColors = computed(() => {
  const value = props.blockProgress?.block_type;
  return BLOCK_TYPE_COLORS[value] ?? BLOCK_TYPE_COLORS.volume;
});

const phaseWeekLabel = computed(() => {
  const week = props.blockProgress?.week_current;
  if (!week) {
    return blockTypeLabel.value;
  }
  return `${blockTypeLabel.value} S${week}`;
});

const prValues = computed(() => {
  const pr = props.latestPr;
  if (!pr) {
    return null;
  }
  return {
    squat: Number(pr.squat ?? 0),
    bench: Number(pr.bench ?? 0),
    deadlift: Number(pr.deadlift ?? 0),
    gainKg: Number(pr.gain_kg ?? 0),
  };
});

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

const readinessAverage7d = computed(() => {
  const scores = filterEntriesByRange(props.readinessRecent, 'entry_date', '7d')
    .map((entry) => Number(entry.score))
    .filter((score) => Number.isFinite(score));

  if (scores.length === 0) {
    return null;
  }

  const sum = scores.reduce((total, score) => total + score, 0);
  return Math.round((sum / scores.length) * 10) / 10;
});

const blockGlowStyle = computed(() => {
  void theme.value;

  return glowCardStyle(blockTypeColors.value);
});

const prGlowStyle = computed(() => {
  void theme.value;

  return prGlowCardStyle();
});

const competitionGlowStyle = computed(() => {
  void theme.value;

  return glowCardStyle(COMPETITION_COLORS);
});

const readinessGlowStyle = computed(() => {
  void theme.value;

  return glowCardStyle(READINESS_COLORS.score);
});
</script>

<template>
  <div class="space-y-4">
    <div>
      <p class="text-xs text-slate-500">{{ todayLabel }}</p>
      <h1 class="mt-1 text-xl font-bold text-white sm:text-2xl">
        Bonjour, {{ athleteName }}
      </h1>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
      <div
        class="glow-card rounded-xl px-3 py-2.5 transition-shadow duration-300"
        :style="blockGlowStyle"
      >
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Phase</p>
        <p class="mt-0.5 text-lg font-bold tabular-nums text-white">
          {{ phaseWeekLabel }}
        </p>
      </div>

      <div
        class="glow-card rounded-xl px-3 py-2.5 transition-shadow duration-300"
        :style="prGlowStyle"
      >
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Total PR</p>
        <template v-if="prValues">
          <p class="mt-0.5 text-lg font-bold tabular-nums text-white">
            <span>S {{ prValues.squat }}</span>
            <span class="mx-1 text-slate-600">·</span>
            <span>B {{ prValues.bench }}</span>
            <span class="mx-1 text-slate-600">·</span>
            <span>D {{ prValues.deadlift }}</span>
          </p>
          <p class="mt-1 flex items-center gap-1 text-sm font-medium text-slate-400">
            <UiIcon
              name="arrow-trend-up"
              class="h-4 w-4 text-slate-500"
              :class="prValues.gainKg < 0 ? 'rotate-180' : ''"
            />
            <span>{{ prValues.gainKg > 0 ? '+' : '' }}{{ prValues.gainKg }} kg</span>
          </p>
        </template>
        <p v-else class="mt-0.5 text-lg font-bold text-slate-500">—</p>
      </div>

      <div
        class="glow-card glow-card--pulse rounded-xl px-3 py-2.5 transition-shadow duration-300"
        :style="competitionGlowStyle"
      >
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Compétition</p>
        <template v-if="competitionCountdown">
          <Link
            :href="`/athletes/${athleteId}?competition=${nextCompetition.id}`"
            class="mt-0.5 block text-lg font-bold tabular-nums text-white hover:text-amber-100"
          >
            {{ competitionCountdown }}
          </Link>
          <p class="mt-0.5 truncate text-[11px] text-slate-400">
            {{ nextCompetition.name }}
          </p>
          <p v-if="nextCompetition.goal" class="mt-0.5 truncate text-[10px] text-slate-500">
            Objectif : {{ nextCompetition.goal }}
          </p>
        </template>
        <p v-else class="mt-0.5 text-lg font-bold text-slate-500">—</p>
      </div>

      <div
        class="glow-card rounded-xl px-3 py-2.5 transition-shadow duration-300"
        :style="readinessGlowStyle"
      >
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Forme 7j</p>
        <p class="mt-0.5 text-lg font-bold tabular-nums text-white">
          {{ readinessAverage7d != null ? `${readinessAverage7d}/10` : '—' }}
        </p>
      </div>
    </div>

    <Link
      v-if="feedbackDueToday"
      href="/feedbacks"
      class="flex items-center justify-between gap-3 rounded-xl border border-blue-500/40 bg-blue-600/15 px-4 py-2.5 text-sm transition hover:bg-blue-600/25"
    >
      <span class="font-medium text-blue-200">
        {{ feedbackFrequency === 'weekly'
          ? 'Retour hebdomadaire attendu'
          : 'Retour vidéo attendu pour ta séance du jour' }}
      </span>
      <span class="shrink-0 text-xs font-semibold text-blue-300">Envoyer →</span>
    </Link>
  </div>
</template>

<style scoped>
.glow-card:hover {
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
