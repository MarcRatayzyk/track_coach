<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { filterEntriesByRange } from '../utils/athleteOverviewStats';
import ReadinessDynamicFields from './ReadinessDynamicFields.vue';
import ReadinessWeekTable from './ReadinessWeekTable.vue';
import { emptyValuesForFields } from '../config/readinessFormFields';

const props = defineProps({
  athleteId: {
    type: Number,
    required: true,
  },
  readinessForm: {
    type: Object,
    default: null,
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

const fields = computed(() => props.readinessForm?.fields ?? []);

const readinessFormState = useForm({
  values: {
    ...emptyValuesForFields(fields.value),
    ...(props.todayReadiness?.values ?? {}),
  },
  notes: props.todayReadiness?.notes ?? '',
});

const bodyWeightForm = useForm({
  weight_kg: props.todayBodyWeight?.weight_kg ?? '',
});

watch(
  () => [props.todayReadiness, props.readinessForm],
  () => {
    readinessFormState.values = {
      ...emptyValuesForFields(fields.value),
      ...(props.todayReadiness?.values ?? {}),
    };
    readinessFormState.notes = props.todayReadiness?.notes ?? '';
  },
  { deep: true },
);

const readinessLast7d = computed(() =>
  filterEntriesByRange(props.readinessRecent, 'entry_date', '7d'),
);

const checkins7d = computed(() => readinessLast7d.value.length);

function submitReadiness() {
  readinessFormState.post(`/athletes/${props.athleteId}/readiness`, {
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
      <div
        class="rounded-lg border border-emerald-500/30 bg-emerald-950/20 px-2.5 py-1.5 text-center"
      >
        <p class="text-[9px] uppercase tracking-wide text-emerald-400/80">Check-in 7j</p>
        <p class="text-sm font-bold text-emerald-200">{{ checkins7d }}/7</p>
      </div>
    </div>

    <form v-if="canEdit" class="mt-3 space-y-3" @submit.prevent="submitReadiness">
      <ReadinessDynamicFields
        v-model="readinessFormState.values"
        :fields="fields"
        :errors="readinessFormState.errors"
      />

      <label
        v-if="!compact"
        class="block rounded-lg border border-slate-800 bg-slate-950/40 p-3 text-xs font-medium text-slate-400"
      >
        Notes
        <textarea
          v-model="readinessFormState.notes"
          rows="3"
          maxlength="500"
          placeholder="Sommeil, courbatures…"
          class="mt-2 w-full resize-none rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>

      <button
        type="submit"
        :disabled="readinessFormState.processing"
        class="w-full rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
      >
        {{ todayReadiness ? 'Mettre à jour' : 'Enregistrer' }}
      </button>
    </form>

    <ReadinessWeekTable
      v-if="!compact"
      class="mt-3"
      :fields="fields"
      :entries="readinessRecent"
    />

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
          >
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
