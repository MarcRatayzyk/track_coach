<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import PrEvolutionMiniCard from './charts/PrEvolutionMiniCard.vue';
import { formatCalendarFr } from '../utils/formatDates';
import { buildPrEvolutionSeries, currentValueFromSeries } from '../utils/prEvolution';
import { LIFT_LABELS } from '../utils/chartTheme';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  name: {
    type: String,
    default: '',
  },
  email: {
    type: String,
    default: '',
  },
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
  personalRecords: {
    type: Array,
    default: () => [],
  },
  nextCompetition: {
    type: Object,
    default: null,
  },
  isCoach: {
    type: Boolean,
    default: false,
  },
  canManageCompetitions: {
    type: Boolean,
    default: false,
  },
  feedbackFrequencyLabel: {
    type: String,
    default: 'Hebdomadaire',
  },
  nextFeedbackButtonLabel: {
    type: String,
    default: '',
  },
  programUrl: {
    type: String,
    default: null,
  },
  programExportUrl: {
    type: String,
    default: null,
  },
  athleteId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits([
  'open-competition',
  'add-competition',
  'toggle-feedback-frequency',
]);

const showProfileModal = ref(false);

const lifts = [
  { key: 'squat', label: 'Squat' },
  { key: 'bench', label: 'Bench' },
  { key: 'deadlift', label: 'Terre' },
];

const prEvolution = computed(() =>
  buildPrEvolutionSeries({
    personalRecords: props.personalRecords,
  }),
);

const prCards = computed(() => [
  { key: 'squat', label: LIFT_LABELS.squat, series: prEvolution.value.squat },
  { key: 'bench', label: LIFT_LABELS.bench, series: prEvolution.value.bench },
  { key: 'deadlift', label: LIFT_LABELS.deadlift, series: prEvolution.value.deadlift },
  { key: 'total', label: LIFT_LABELS.total, series: prEvolution.value.total, usePrGlow: true },
]);

const modalPrValues = computed(() =>
  prCards.value.map((card) => ({
    ...card,
    value: currentValueFromSeries(card.series),
  })),
);

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

const nextCompetitionDateLabel = computed(() =>
  props.nextCompetition?.competition_date
    ? formatCalendarFr(props.nextCompetition.competition_date, 'medium')
    : '—',
);

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}

function openNextCompetition() {
  if (props.nextCompetition) {
    emit('open-competition', props.nextCompetition);
    showProfileModal.value = false;
  }
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/50 p-3 shadow-lg">
    <div class="mb-2.5 flex items-center justify-between gap-3">
      <h2 class="truncate text-base font-bold text-white">{{ name }}</h2>
      <div class="flex shrink-0 items-center gap-2">
        <Link
          v-if="isCoach && programUrl"
          :href="programUrl"
          class="rounded-lg border border-emerald-500/40 bg-emerald-950/30 px-2.5 py-1.5 text-xs font-semibold text-emerald-200 transition hover:border-emerald-400/60 hover:bg-emerald-950/50"
        >
          Aller au programme
        </Link>
        <a
          v-if="isCoach && programExportUrl"
          :href="programExportUrl"
          class="rounded-lg border border-slate-600 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800/60"
        >
          PDF
        </a>
        <button
          type="button"
          class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-2 text-slate-400 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white"
          aria-label="Voir le profil"
          @click="showProfileModal = true"
        >
          <UiIcon name="user-circle" class="h-5 w-5" />
        </button>
      </div>
    </div>

    <div
      class="grid gap-1.5 sm:grid-cols-4"
      :class="isCoach ? 'grid-cols-2' : 'grid-cols-2 lg:grid-cols-4'"
    >
      <PrEvolutionMiniCard
        v-for="card in prCards"
        :key="card.key"
        :lift-key="card.key"
        :label="card.label"
        :series="card.series"
        :use-pr-glow="Boolean(card.usePrGlow)"
      />
    </div>

    <Teleport to="body">
      <div
        v-if="showProfileModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="athlete-profile-title"
        @click.self="showProfileModal = false"
      >
        <div
          class="tc-scrollbar max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 id="athlete-profile-title" class="text-lg font-bold text-white">{{ name }}</h2>
              <p v-if="email" class="mt-0.5 truncate text-sm text-slate-400">{{ email }}</p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="showProfileModal = false"
            >
              ✕
            </button>
          </div>

          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Temps de pratique</dt>
              <dd class="font-semibold text-white">{{ practiceDurationLabel || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Catégorie</dt>
              <dd class="font-semibold text-white">{{ weightClass || '—' }}</dd>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Retours vidéo</dt>
              <dd class="mt-1 flex items-center justify-between gap-3">
                <span class="font-semibold text-white">{{ feedbackFrequencyLabel }}</span>
                <button
                  v-if="isCoach && nextFeedbackButtonLabel"
                  type="button"
                  class="shrink-0 rounded-lg border border-blue-500/40 px-2.5 py-1 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
                  @click="emit('toggle-feedback-frequency')"
                >
                  {{ nextFeedbackButtonLabel }}
                </button>
              </dd>
            </div>
          </dl>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-semibold text-white">Prochaine compétition</p>
                <template v-if="nextCompetition">
                  <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ competitionCountdown }}</p>
                  <p class="truncate text-sm text-slate-300">{{ nextCompetition.name }}</p>
                  <p class="text-[11px] text-slate-500">{{ nextCompetitionDateLabel }}</p>
                  <p v-if="nextCompetition.goal" class="mt-0.5 text-xs text-slate-400">
                    Objectif : {{ nextCompetition.goal }}
                  </p>
                </template>
                <p v-else class="mt-1 text-sm text-slate-500">—</p>
              </div>
              <div class="flex shrink-0 flex-col gap-2">
              <Link
                v-if="nextCompetition && nextCompetition.days_until === 0 && athleteId"
                :href="`/athletes/${athleteId}/competitions/${nextCompetition.id}/live`"
                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-center text-xs font-semibold text-white hover:bg-emerald-500"
              >
                Meet live
              </Link>
              <button
                v-if="nextCompetition"
                type="button"
                class="shrink-0 rounded-lg border border-amber-500/40 px-2.5 py-1 text-xs font-semibold text-amber-300 hover:bg-amber-500/10"
                @click="openNextCompetition"
              >
                Voir
              </button>
              <button
                v-else-if="canManageCompetitions"
                type="button"
                class="shrink-0 rounded-lg border border-blue-500/40 px-2.5 py-1 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
                @click="emit('add-competition')"
              >
                Ajouter
              </button>
              </div>
            </div>
          </div>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <p class="text-xs font-semibold text-white">Records personnels</p>
            <ul class="mt-2 space-y-1.5 text-sm">
              <li
                v-for="card in modalPrValues"
                :key="`modal-pr-${card.key}`"
                class="flex items-center justify-between gap-2"
              >
                <span class="text-slate-400">{{ card.label }}</span>
                <span class="font-semibold tabular-nums text-white">{{ formatKg(card.value) }}</span>
              </li>
            </ul>
          </div>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <div class="flex flex-wrap items-baseline gap-x-2">
              <p class="text-xs font-semibold text-white">Dernière compétition</p>
              <span class="text-[10px] text-slate-500">{{ latestCompetitionDateLabel }}</span>
            </div>
            <div class="mt-1.5 space-y-1 text-sm text-slate-300">
              <p v-for="lift in lifts" :key="`modal-comp-${lift.key}`">
                <span class="text-slate-500">{{ lift.label }}</span>
                {{ latestCompetitionBars[lift.key] }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
