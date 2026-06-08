<script setup>
import { computed } from 'vue';

const props = defineProps({
  weightClass: {
    type: String,
    default: '—',
  },
  feedbackLabel: {
    type: String,
    default: 'Hebdomadaire',
  },
  isCoach: {
    type: Boolean,
    default: false,
  },
  feedbackProcessing: {
    type: Boolean,
    default: false,
  },
  nextFeedbackButtonLabel: {
    type: String,
    default: '',
  },
  practiceDurationLabel: {
    type: String,
    default: '—',
  },
  followUpStartedAtLabel: {
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
});

const emit = defineEmits(['toggle-feedback']);

const compactCards = computed(() => [
  { label: 'Catégorie', value: props.weightClass || '—' },
  { label: 'Temps de pratique', value: props.practiceDurationLabel || '—' },
  { label: 'Début du suivi', value: props.followUpStartedAtLabel || '—' },
]);

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}
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

    <div class="mt-4 grid gap-3 2xl:grid-cols-[minmax(0,1.15fr)_minmax(22rem,0.95fr)]">
      <div class="grid grid-cols-2 gap-3">
        <article
          v-for="card in compactCards"
          :key="card.label"
          class="min-h-[6.5rem] rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3"
        >
          <p class="text-[11px] uppercase tracking-wide text-slate-500">{{ card.label }}</p>
          <p class="mt-1 text-sm font-semibold text-slate-100">{{ card.value }}</p>
        </article>

        <article class="min-h-[6.5rem] rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3">
          <div class="flex h-full flex-col justify-between gap-3">
            <div class="min-w-0">
              <p class="text-[11px] uppercase tracking-wide text-slate-500">Type de retour</p>
              <p class="mt-1 text-sm font-semibold text-slate-100">{{ feedbackLabel }}</p>
            </div>
            <button
              v-if="isCoach"
              type="button"
              :disabled="feedbackProcessing"
              class="self-start rounded-lg border border-blue-500/40 px-2.5 py-1 text-[11px] font-semibold text-blue-300 hover:bg-blue-500/10 disabled:opacity-50"
              @click="emit('toggle-feedback')"
            >
              {{ nextFeedbackButtonLabel }}
            </button>
          </div>
        </article>
      </div>

      <div class="grid gap-3 xl:grid-cols-2">
        <article class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 min-h-[10.5rem]">
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
        </article>

        <article class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 min-h-[10.5rem]">
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
        </article>
      </div>
    </div>
  </section>
</template>
