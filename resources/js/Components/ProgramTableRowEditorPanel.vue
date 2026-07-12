<script setup>
import { computed } from 'vue';
import ExerciseVariantStrip from './ExerciseVariantStrip.vue';
import LoadModePicker from './LoadModePicker.vue';
import OptionButtonGroup from './OptionButtonGroup.vue';
import { PROGRAM_TABLE_SECTIONS } from '../config/programTableSections';
import { SET_OPTIONS, REP_OPTIONS, formatEditorLineRecapParts } from '../utils/programBuilder';
import { useTableRowEditor } from '../composables/useTableRowEditor';

const props = defineProps({
  athleteOneRm: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
});

const editor = useTableRowEditor();

const row = computed(() => editor?.state.row ?? null);
const sessionHeading = computed(() => editor?.state.sessionHeading ?? '');
const defaultLift = computed(() => editor?.state.defaultLift ?? 'squat');
const rowNumber = computed(() => (editor?.state.rowIndex ?? 0) + 1);
const recapParts = computed(() => {
  if (!row.value) {
    return null;
  }

  return formatEditorLineRecapParts(row.value, {
    oneRm: props.athleteOneRm,
    defaultLift: defaultLift.value,
    section: row.value.section,
  });
});

const recapSectionClass = computed(() => {
  const section = row.value?.section;
  if (section === 'topset') {
    return 'text-amber-300';
  }
  if (section === 'backoff') {
    return 'text-blue-300';
  }
  if (section === 'accessory') {
    return 'text-emerald-300';
  }
  return 'text-slate-300';
});

function patchRow(patch) {
  if (!row.value || !editor?.state.onUpdate) {
    return;
  }

  const nextRow = {
    ...row.value,
    ...patch,
  };

  editor.state.onUpdate(nextRow);
  editor.state.row = nextRow;
}

function onSectionChange(section) {
  patchRow({ section });
}

function onExerciseSelected(payload) {
  patchRow({
    exercise_variant_id: payload.exercise_variant_id ?? null,
    exercise_name: payload.exercise_name ?? '',
    lift: ['squat', 'bench', 'deadlift'].includes(payload.lift) ? payload.lift : row.value?.lift,
    movement_pattern: payload.movement_pattern ?? row.value?.movement_pattern ?? '',
  });
}

function normalizeFieldValue(field, value) {
  if (['sets', 'reps', 'rest_seconds'].includes(field)) {
    if (value === '' || value == null) {
      return '';
    }

    const parsed = Number(value);

    return Number.isNaN(parsed) ? value : parsed;
  }

  return value;
}

function updateField(field, value) {
  patchRow({ [field]: normalizeFieldValue(field, value) });
}

function parseRest(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return '';
  }

  const parsed = Number.parseInt(value, 10);

  return Number.isNaN(parsed) ? '' : parsed;
}

function goToNextRow() {
  editor?.state.onGoToNextRow?.();
}
</script>

<template>
  <aside
    class="flex w-[36rem] shrink-0 flex-col rounded-xl border border-slate-800 bg-slate-900/90 shadow-lg"
  >
    <div class="flex items-center justify-between gap-2 border-b border-slate-800 px-4 py-2.5">
      <div class="min-w-0">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-blue-300">Édition rapide</p>
        <p class="truncate text-sm font-semibold text-white">
          {{ row ? `Ligne ${rowNumber}` : 'Aucune ligne' }}
          <span v-if="row && sessionHeading" class="font-normal text-slate-400">· {{ sessionHeading }}</span>
        </p>
      </div>
      <button
        v-if="row"
        type="button"
        class="shrink-0 text-xs font-medium text-slate-500 hover:text-slate-300"
        @click="editor?.clearSelection()"
      >
        Fermer
      </button>
    </div>

    <div v-if="row" class="space-y-3 px-4 py-3">
      <div>
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Type</p>
        <div class="mt-1.5 flex gap-1.5">
          <button
            v-for="option in PROGRAM_TABLE_SECTIONS"
            :key="option.value"
            type="button"
            class="rounded-lg px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
            :class="
              row.section === option.value
                ? option.buttonActiveClass
                : option.buttonInactiveClass
            "
            @click="onSectionChange(option.value)"
          >
            {{ option.shortLabel }}
          </button>
        </div>
      </div>

      <div>
        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Exercice</p>
        <div class="mt-1.5">
          <ExerciseVariantStrip
            accessory-panel
            :default-lift="row.lift ?? defaultLift"
            :exercise-variant-id="row.exercise_variant_id"
            :exercise-name="row.exercise_name ?? ''"
            @select="onExerciseSelected"
          />
        </div>
      </div>

      <div class="grid grid-cols-2 gap-3">
        <OptionButtonGroup
          :model-value="row.sets"
          :options="SET_OPTIONS"
          :columns="5"
          label="Séries"
          dense
          @update:model-value="updateField('sets', $event)"
        />
        <OptionButtonGroup
          :model-value="row.reps"
          :options="REP_OPTIONS"
          :columns="6"
          label="Reps"
          dense
          @update:model-value="updateField('reps', $event)"
        />
      </div>

      <LoadModePicker v-if="editor?.state.row" v-model="editor.state.row" compact />

      <div class="flex items-end gap-2.5">
        <label class="block min-w-0 flex-1 text-[11px] font-medium uppercase tracking-wide text-slate-500">
          Repos (s)
          <input
            :value="row.rest_seconds"
            type="number"
            min="0"
            max="900"
            step="15"
            placeholder="120"
            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white placeholder:text-slate-600"
            @input="updateField('rest_seconds', parseRest($event.target.value))"
          />
        </label>
        <div class="min-w-0 flex-[2] rounded-lg border border-blue-500/20 bg-blue-950/25 px-2.5 py-2">
          <p class="text-[10px] font-medium uppercase tracking-wide text-blue-300/80">Récap</p>
          <p v-if="recapParts" class="mt-0.5 text-sm leading-snug text-white">
            <span class="font-semibold" :class="recapSectionClass">{{ recapParts.section }}</span>
            <span> · {{ recapParts.main }}</span>
            <span v-if="recapParts.e1rm" class="text-[11px] font-normal text-slate-500">
              · e1RM {{ recapParts.e1rm }} kg
            </span>
          </p>
          <p v-else class="mt-0.5 text-sm font-medium text-slate-500">—</p>
        </div>
      </div>
    </div>

    <div v-else class="px-4 py-5 text-center text-sm text-slate-500">
      Clique sur une ligne du tableau pour l'éditer ici.
    </div>

    <div v-if="row" class="border-t border-slate-800 px-4 py-2.5">
      <button
        type="button"
        class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white transition hover:bg-blue-500"
        @click="goToNextRow"
      >
        Ligne suivante →
      </button>
    </div>
  </aside>
</template>
