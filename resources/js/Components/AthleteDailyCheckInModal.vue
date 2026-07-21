<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import ReadinessDynamicFields from './ReadinessDynamicFields.vue';
import { emptyValuesForFields } from '../config/readinessFormFields';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
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
  todayBodyWeight: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'skipped']);

const fields = computed(() => props.readinessForm?.fields ?? []);

const readinessForm = useForm({
  values: {},
  notes: '',
});

const bodyWeightForm = useForm({
  weight_kg: props.todayBodyWeight?.weight_kg ?? '',
});

const needsReadiness = computed(() => !props.todayReadiness);
const needsWeight = computed(() => !props.todayBodyWeight);

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }
    readinessForm.values = {
      ...emptyValuesForFields(fields.value),
      ...(props.todayReadiness?.values ?? {}),
    };
    readinessForm.notes = props.todayReadiness?.notes ?? '';
    bodyWeightForm.weight_kg = props.todayBodyWeight?.weight_kg ?? '';
  },
);

function close() {
  emit('close');
}

function skipForLater() {
  emit('skipped');
  close();
}

function submitAll() {
  if (needsReadiness.value) {
    readinessForm.post(`/athletes/${props.athleteId}/readiness`, {
      preserveScroll: true,
      onSuccess: () => {
        if (needsWeight.value && bodyWeightForm.weight_kg !== '' && bodyWeightForm.weight_kg != null) {
          bodyWeightForm.post(`/athletes/${props.athleteId}/body-weight`, {
            preserveScroll: true,
            onSuccess: () => close(),
          });
          return;
        }
        close();
      },
    });
    return;
  }

  if (needsWeight.value && bodyWeightForm.weight_kg !== '' && bodyWeightForm.weight_kg != null) {
    bodyWeightForm.post(`/athletes/${props.athleteId}/body-weight`, {
      preserveScroll: true,
      onSuccess: () => close(),
    });
    return;
  }

  close();
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="daily-checkin-title"
    >
      <button
        type="button"
        class="absolute inset-0 bg-slate-950/80"
        aria-label="Fermer"
        @click="skipForLater"
      />

      <div
        class="relative z-10 flex max-h-[92dvh] w-full max-w-md flex-col overflow-y-auto rounded-t-2xl border border-slate-800 bg-slate-900 p-4 shadow-2xl sm:max-h-[90vh] sm:rounded-2xl sm:p-5"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h2 id="daily-checkin-title" class="text-lg font-bold text-white">Check-in du jour</h2>
            <p class="mt-1 text-sm text-slate-400">
              Quelques secondes pour suivre ta forme avant la séance.
            </p>
          </div>
          <button
            type="button"
            class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="skipForLater"
          >
            ✕
          </button>
        </div>

        <div
          v-if="needsReadiness"
          class="mt-4 rounded-xl border border-slate-800 bg-slate-950/40 p-3"
        >
          <h3 class="text-sm font-semibold text-white">Indicateurs du jour</h3>
          <div class="mt-3">
            <ReadinessDynamicFields
              v-model="readinessForm.values"
              :fields="fields"
              :errors="readinessForm.errors"
            />
          </div>
        </div>

        <div
          v-if="needsWeight"
          class="mt-3 rounded-xl border border-slate-800 bg-slate-950/40 p-3"
        >
          <h3 class="text-sm font-semibold text-white">Poids du corps</h3>
          <label class="mt-2 block text-xs text-slate-400">
            Aujourd'hui (kg)
            <input
              v-model="bodyWeightForm.weight_kg"
              type="number"
              step="0.1"
              min="30"
              max="250"
              inputmode="decimal"
              placeholder="82.5"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white"
            >
            <p v-if="bodyWeightForm.errors.weight_kg" class="mt-1 text-xs text-red-400">
              {{ bodyWeightForm.errors.weight_kg }}
            </p>
          </label>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row-reverse">
          <button
            type="button"
            class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            :disabled="readinessForm.processing || bodyWeightForm.processing"
            @click="submitAll"
          >
            Enregistrer
          </button>
          <button
            type="button"
            class="w-full rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 hover:bg-slate-800"
            @click="skipForLater"
          >
            Plus tard
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
