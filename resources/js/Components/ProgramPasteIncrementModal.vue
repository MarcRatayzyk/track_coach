<script setup>
import { ref, watch } from 'vue';
import { MAIN_LIFTS, uppercaseSessionLabel } from '../utils/programBuilder';
import { PROGRAM_TABLE_SECTIONS } from '../config/programTableSections';
import {
  DEFAULT_INCREMENT_LIFTS,
  DEFAULT_INCREMENT_SECTIONS,
} from '../utils/programBuilderClipboard';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  exerciseNames: {
    type: Array,
    default: () => [],
  },
  pasteKind: {
    type: String,
    default: 'session',
    validator: (value) => ['session', 'week'].includes(value),
  },
  defaultSessionLabel: {
    type: String,
    default: '',
  },
  defaultSessionNotes: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['confirm', 'cancel']);

const incrementKg = ref('0');
const incrementPercent = ref('0');
const incrementRpe = ref('0');
const sessionLabel = ref('');
const sessionNotes = ref('');
const selectedSections = ref([...DEFAULT_INCREMENT_SECTIONS]);
const selectedLifts = ref([...DEFAULT_INCREMENT_LIFTS]);
const selectedExercises = ref([]);
const errorMessage = ref('');

function resetForm() {
  incrementKg.value = '0';
  incrementPercent.value = '0';
  incrementRpe.value = '0';
  sessionLabel.value = uppercaseSessionLabel(props.defaultSessionLabel);
  sessionNotes.value = props.defaultSessionNotes;
  selectedSections.value = [...DEFAULT_INCREMENT_SECTIONS];
  selectedLifts.value = [...DEFAULT_INCREMENT_LIFTS];
  selectedExercises.value = [...props.exerciseNames];
  errorMessage.value = '';
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      resetForm();
    }
  },
);

watch(
  () => props.exerciseNames,
  (names) => {
    if (props.open) {
      selectedExercises.value = [...names];
    }
  },
);

function parseIncrementValue(raw) {
  const normalized = raw.trim() === '' ? '0' : raw.trim().replace(',', '.');
  const parsed = Number(normalized);

  if (!Number.isFinite(parsed)) {
    return null;
  }

  return parsed;
}

function toggleValue(list, value) {
  if (list.includes(value)) {
    return list.filter((item) => item !== value);
  }

  return [...list, value];
}

function onSessionLabelInput(event) {
  sessionLabel.value = event.target.value.toUpperCase();
}

function onConfirm() {
  const kg = parseIncrementValue(incrementKg.value);
  const percent = parseIncrementValue(incrementPercent.value);
  const rpe = parseIncrementValue(incrementRpe.value);

  if (kg === null || percent === null || rpe === null) {
    errorMessage.value = 'Entre des nombres valides, par exemple 0, 2.5 ou -2.5.';
    return;
  }

  if (selectedSections.value.length === 0) {
    errorMessage.value = 'Sélectionne au moins un type de série.';
    return;
  }

  if (selectedLifts.value.length === 0) {
    errorMessage.value = 'Sélectionne au moins un mouvement.';
    return;
  }

  if (props.exerciseNames.length > 0 && selectedExercises.value.length === 0) {
    errorMessage.value = 'Sélectionne au moins un exercice.';
    return;
  }

  errorMessage.value = '';
  emit('confirm', {
    incrementKg: kg,
    incrementPercent: percent,
    incrementRpe: rpe,
    sections: [...selectedSections.value],
    lifts: [...selectedLifts.value],
    exerciseNames: [...selectedExercises.value],
    sessionLabel: props.pasteKind === 'session' ? uppercaseSessionLabel(sessionLabel.value) : '',
    sessionNotes: sessionNotes.value,
  });
}

function onCancel() {
  emit('cancel');
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="onCancel"
    >
      <div
        class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
            <p v-if="hint" class="mt-1 text-xs text-slate-500">
              {{ hint }}
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="onCancel"
          >
            ✕
          </button>
        </div>

        <label v-if="pasteKind === 'session'" class="mt-4 block text-xs text-slate-500">
          Titre de la séance
          <input
            :value="sessionLabel"
            type="text"
            maxlength="255"
            placeholder="Nom de la séance"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm uppercase text-white outline-none"
            @input="onSessionLabelInput"
          />
        </label>

        <label class="mt-4 block text-xs text-slate-500">
          Notes de séance
          <textarea
            v-model="sessionNotes"
            rows="2"
            maxlength="2000"
            placeholder="Consignes, remarques, contexte…"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none placeholder:text-slate-600"
          />
        </label>

        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <label class="block text-xs text-slate-500">
            Incrément kg
            <input
              v-model="incrementKg"
              type="text"
              inputmode="decimal"
              placeholder="0"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none"
              @keydown.enter.prevent="onConfirm"
            />
          </label>
          <label class="block text-xs text-slate-500">
            Incrément %
            <input
              v-model="incrementPercent"
              type="text"
              inputmode="decimal"
              placeholder="0"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none"
              @keydown.enter.prevent="onConfirm"
            />
          </label>
          <label class="block text-xs text-slate-500">
            Incrément RPE
            <input
              v-model="incrementRpe"
              type="text"
              inputmode="decimal"
              placeholder="0"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none"
              @keydown.enter.prevent="onConfirm"
            />
          </label>
        </div>

        <fieldset class="mt-4">
          <legend class="text-xs font-medium text-slate-400">Type de série</legend>
          <div class="mt-2 flex flex-wrap gap-2">
            <label
              v-for="section in PROGRAM_TABLE_SECTIONS"
              :key="section.value"
              class="flex cursor-pointer items-center gap-1.5 rounded-md border border-slate-700 px-2.5 py-1.5 text-xs text-slate-300"
            >
              <input
                type="checkbox"
                class="rounded border-slate-600"
                :checked="selectedSections.includes(section.value)"
                @change="selectedSections = toggleValue(selectedSections, section.value)"
              />
              {{ section.label }}
            </label>
          </div>
        </fieldset>

        <fieldset class="mt-4">
          <legend class="text-xs font-medium text-slate-400">Mouvement</legend>
          <div class="mt-2 flex flex-wrap gap-2">
            <label
              v-for="lift in MAIN_LIFTS"
              :key="lift.value"
              class="flex cursor-pointer items-center gap-1.5 rounded-md border border-slate-700 px-2.5 py-1.5 text-xs text-slate-300"
            >
              <input
                type="checkbox"
                class="rounded border-slate-600"
                :checked="selectedLifts.includes(lift.value)"
                @change="selectedLifts = toggleValue(selectedLifts, lift.value)"
              />
              {{ lift.label }}
            </label>
          </div>
        </fieldset>

        <fieldset v-if="exerciseNames.length > 0" class="mt-4">
          <legend class="text-xs font-medium text-slate-400">Exercice</legend>
          <div class="mt-2 max-h-32 space-y-1 overflow-y-auto rounded-lg border border-slate-800 p-2">
            <label
              v-for="name in exerciseNames"
              :key="name"
              class="flex cursor-pointer items-center gap-2 rounded px-1 py-1 text-xs text-slate-300 hover:bg-slate-800/50"
            >
              <input
                type="checkbox"
                class="rounded border-slate-600"
                :checked="selectedExercises.includes(name)"
                @change="selectedExercises = toggleValue(selectedExercises, name)"
              />
              {{ name }}
            </label>
          </div>
        </fieldset>

        <p v-if="errorMessage" class="mt-3 text-xs text-red-400">
          {{ errorMessage }}
        </p>

        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800"
            @click="onCancel"
          >
            Annuler
          </button>
          <button
            type="button"
            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
            @click="onConfirm"
          >
            Confirmer
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
