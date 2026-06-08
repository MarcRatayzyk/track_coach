<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import ReadinessTrendChart from './charts/ReadinessTrendChart.vue';

const props = defineProps({
  athleteId: {
    type: Number,
    required: true,
  },
  todayReadiness: {
    type: Object,
    default: null,
  },
  readinessRecent: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
});

const showHistory = ref(false);

const form = useForm({
  sleep_score: props.todayReadiness?.sleep_score ?? 7,
  stress_score: props.todayReadiness?.stress_score ?? 7,
  motivation_score: props.todayReadiness?.motivation_score ?? 7,
  notes: props.todayReadiness?.notes ?? '',
});

const metrics = [
  { key: 'sleep_score', label: 'Sommeil', hint: 'Qualité du sommeil' },
  { key: 'stress_score', label: 'Détente', hint: '1 stressé → 10 détendu' },
  { key: 'motivation_score', label: 'Motivation', hint: 'Envie de t’entraîner' },
];

const scoreOptions = Array.from({ length: 10 }, (_, i) => i + 1);

const computedReadiness = computed(() =>
  Math.round((form.sleep_score + form.stress_score + form.motivation_score) / 3),
);

const recentAverage = computed(() => {
  if (!props.readinessRecent.length) {
    return null;
  }
  const sum = props.readinessRecent.reduce((acc, entry) => acc + Number(entry.score ?? 0), 0);
  return Math.round(sum / props.readinessRecent.length);
});

function submitReadiness() {
  form.post(`/athletes/${props.athleteId}/readiness`, {
    preserveScroll: true,
  });
}

function subscoreDetail(entry) {
  return `S${entry.sleep_score} · D${entry.stress_score} · M${entry.motivation_score}`;
}
</script>

<template>
  <section class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold text-white">Readiness</h2>
      <div class="flex gap-2">
        <div
          v-if="canEdit"
          class="rounded-lg border border-blue-500/30 bg-blue-950/20 px-2.5 py-1.5 text-center"
        >
          <p class="text-[9px] uppercase tracking-wide text-blue-400/80">Auj.</p>
          <p class="text-sm font-bold text-blue-200">{{ computedReadiness }}/10</p>
        </div>
        <div
          v-if="recentAverage !== null"
          class="rounded-lg border border-emerald-500/30 bg-emerald-950/20 px-2.5 py-1.5 text-center"
        >
          <p class="text-[9px] uppercase tracking-wide text-emerald-400/80">Moy. 7j</p>
          <p class="text-sm font-bold text-emerald-200">{{ recentAverage }}/10</p>
        </div>
      </div>
    </div>

    <form v-if="canEdit" class="mt-3 space-y-3" @submit.prevent="submitReadiness">
      <div class="grid gap-3 sm:grid-cols-2">
        <div
          v-for="metric in metrics"
          :key="metric.key"
          class="rounded-lg border border-slate-800 bg-slate-950/40 p-3"
        >
          <p class="text-xs font-medium text-white">{{ metric.label }}</p>
          <p class="text-[10px] text-slate-500">{{ metric.hint }}</p>
          <div class="mt-2 flex flex-wrap gap-1">
            <button
              v-for="value in scoreOptions"
              :key="`${metric.key}-${value}`"
              type="button"
              class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold transition"
              :class="
                form[metric.key] === value
                  ? 'border-blue-500 bg-blue-600 text-white'
                  : 'border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-500'
              "
              @click="form[metric.key] = value"
            >
              {{ value }}
            </button>
          </div>
          <p v-if="form.errors[metric.key]" class="mt-1 text-xs text-red-400">
            {{ form.errors[metric.key] }}
          </p>
        </div>

        <label class="flex min-h-full flex-col rounded-lg border border-slate-800 bg-slate-950/40 p-3 text-xs font-medium text-slate-400">
          Notes
          <textarea
            v-model="form.notes"
            rows="4"
            maxlength="500"
            placeholder="Sommeil, courbatures…"
            class="mt-2 min-h-0 flex-1 resize-none rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
      >
        {{ todayReadiness ? 'Mettre à jour' : 'Enregistrer' }}
      </button>
    </form>

    <ReadinessTrendChart
      v-if="readinessRecent.length"
      :entries="readinessRecent"
      class="mt-3"
    />

    <div v-if="readinessRecent.length" class="mt-3 border-t border-slate-800 pt-3">
      <button
        type="button"
        class="text-xs font-medium text-slate-400 hover:text-slate-200"
        @click="showHistory = !showHistory"
      >
        {{ showHistory ? 'Masquer' : 'Voir' }} les 7 derniers jours
      </button>
      <ul v-if="showHistory" class="mt-2 space-y-1">
        <li
          v-for="entry in readinessRecent"
          :key="entry.entry_date"
          class="flex items-center justify-between gap-2 text-xs"
        >
          <div>
            <span class="text-slate-400">{{ formatCalendarFr(entry.entry_date, 'medium') }}</span>
            <span class="ml-1 text-slate-600">{{ subscoreDetail(entry) }}</span>
          </div>
          <span class="font-semibold text-slate-200">{{ entry.score }}/10</span>
        </li>
      </ul>
    </div>
    <p v-else-if="!canEdit" class="mt-3 text-xs text-slate-500">Aucune saisie récente.</p>
  </section>
</template>
