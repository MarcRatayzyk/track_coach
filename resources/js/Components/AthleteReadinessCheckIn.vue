<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
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
  todayBodyWeight: {
    type: Object,
    default: null,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const showHistory = ref(false);

const readinessForm = useForm({
  sleep_score: props.todayReadiness?.sleep_score ?? 7,
  stress_score: props.todayReadiness?.stress_score ?? 7,
  motivation_score: props.todayReadiness?.motivation_score ?? 7,
  notes: props.todayReadiness?.notes ?? '',
});

const bodyWeightForm = useForm({
  weight_kg: props.todayBodyWeight?.weight_kg ?? '',
});

const metrics = [
  { key: 'sleep_score', label: 'Sommeil', short: 'S' },
  { key: 'stress_score', label: 'Détente', short: 'D' },
  { key: 'motivation_score', label: 'Motivation', short: 'M' },
];

const scoreOptions = Array.from({ length: 10 }, (_, i) => i + 1);

const computedReadiness = computed(() =>
  Math.round(
    (readinessForm.sleep_score + readinessForm.stress_score + readinessForm.motivation_score) / 3,
  ),
);

const readinessLast7d = computed(() =>
  filterEntriesByRange(props.readinessRecent, 'entry_date', '7d'),
);

const recentAverage = computed(() => {
  if (!readinessLast7d.value.length) {
    return null;
  }
  const sum = readinessLast7d.value.reduce((acc, entry) => acc + Number(entry.score ?? 0), 0);
  return Math.round(sum / readinessLast7d.value.length);
});

function submitReadiness() {
  readinessForm.post(`/athletes/${props.athleteId}/readiness`, {
    preserveScroll: true,
  });
}

function submitBodyWeight() {
  bodyWeightForm.post(`/athletes/${props.athleteId}/body-weight`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <section
    class="rounded-2xl border border-slate-800 bg-slate-900/50 shadow-lg"
    :class="compact ? 'p-3' : 'flex h-full flex-col p-4'"
  >
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
      <div :class="compact ? 'space-y-2' : 'grid gap-3 sm:grid-cols-2'">
        <div
          v-for="metric in metrics"
          :key="metric.key"
          class="rounded-lg border border-slate-800 bg-slate-950/40 p-2.5"
        >
          <p class="text-[11px] font-medium text-white">
            {{ compact ? metric.short : metric.label }}
          </p>
          <div class="mt-1.5 flex flex-wrap gap-0.5">
            <button
              v-for="value in scoreOptions"
              :key="`${metric.key}-${value}`"
              type="button"
              class="flex items-center justify-center rounded-md border text-[11px] font-semibold transition"
              :class="[
                compact ? 'h-6 w-6' : 'h-8 w-8',
                readinessForm[metric.key] === value
                  ? 'border-blue-500 bg-blue-600 text-white'
                  : 'border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-500',
              ]"
              @click="readinessForm[metric.key] = value"
            >
              {{ value }}
            </button>
          </div>
          <p v-if="readinessForm.errors[metric.key]" class="mt-1 text-xs text-red-400">
            {{ readinessForm.errors[metric.key] }}
          </p>
        </div>

        <label
          v-if="!compact"
          class="flex min-h-full flex-col rounded-lg border border-slate-800 bg-slate-950/40 p-3 text-xs font-medium text-slate-400"
        >
          Notes
          <textarea
            v-model="readinessForm.notes"
            rows="4"
            maxlength="500"
            placeholder="Sommeil, courbatures…"
            class="mt-2 min-h-0 flex-1 resize-none rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
      </div>

      <button
        type="submit"
        :disabled="readinessForm.processing"
        class="w-full rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
      >
        {{ todayReadiness ? 'Mettre à jour' : 'Enregistrer' }}
      </button>
    </form>

    <ReadinessTrendChart
      v-if="!compact && readinessRecent.length"
      :entries="readinessRecent"
      class="mt-3"
    />

    <div v-if="!compact && readinessLast7d.length" class="mt-3 border-t border-slate-800 pt-3">
      <button
        type="button"
        class="text-xs font-medium text-slate-400 hover:text-slate-200"
        @click="showHistory = !showHistory"
      >
        {{ showHistory ? 'Masquer' : 'Voir' }} les 7 derniers jours
      </button>
      <ul v-if="showHistory" class="mt-2 space-y-1">
        <li
          v-for="entry in readinessLast7d"
          :key="entry.entry_date"
          class="flex items-center justify-between gap-2 text-xs"
        >
          <div>
            <span class="text-slate-400">{{ formatCalendarFr(entry.entry_date, 'medium') }}</span>
            <span class="ml-1 text-slate-600">
              S{{ entry.sleep_score }} · D{{ entry.stress_score }} · M{{ entry.motivation_score }}
            </span>
          </div>
          <span class="font-semibold text-slate-200">{{ entry.score }}/10</span>
        </li>
      </ul>
    </div>
    <p v-else-if="!canEdit && !compact" class="mt-3 text-xs text-slate-500">Aucune saisie récente.</p>

    <div
      v-if="canEdit"
      class="border-t border-slate-800"
      :class="compact ? 'mt-3 pt-3' : 'mt-4 pt-4'"
    >
      <h3 class="text-xs font-semibold text-white">Poids du corps</h3>
      <form class="mt-2 flex items-end gap-2" @submit.prevent="submitBodyWeight">
        <label class="min-w-0 flex-1 text-[11px] text-slate-400">
          Aujourd'hui (kg)
          <input
            v-model="bodyWeightForm.weight_kg"
            type="number"
            step="0.1"
            min="30"
            max="250"
            inputmode="decimal"
            placeholder="82.5"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
          <p v-if="bodyWeightForm.errors.weight_kg" class="mt-1 text-xs text-red-400">
            {{ bodyWeightForm.errors.weight_kg }}
          </p>
        </label>
        <button
          type="submit"
          :disabled="bodyWeightForm.processing"
          class="shrink-0 rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-800 disabled:opacity-50"
        >
          {{ todayBodyWeight ? 'MAJ' : 'OK' }}
        </button>
      </form>
    </div>
  </section>
</template>
