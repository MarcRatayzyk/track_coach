<script setup>
import { computed } from 'vue';
import ProgramTableExerciseSelect from './ProgramTableExerciseSelect.vue';
import ProgramTableLiftSelect from './ProgramTableLiftSelect.vue';
import ProgramTableSectionSelect from './ProgramTableSectionSelect.vue';
import ProgramTableLoadCell from './ProgramTableLoadCell.vue';
import { PRESCRIPTION_VALUE_INPUT_CLASS } from '../config/dayTableColumns';
import {
  formatSchemePrescription,
  plannedSetsForLine,
  restMinutesToSeconds,
  restSecondsToMinutes,
} from '../utils/programBuilder';

const props = defineProps({
  columnId: {
    type: String,
    required: true,
  },
  row: {
    type: Object,
    required: true,
  },
  defaultLift: {
    type: String,
    default: 'squat',
  },
  defaultLoadMode: {
    type: String,
    default: 'kg',
  },
  preview: {
    type: Boolean,
    default: false,
  },
  /** Tableur V2 : clic exo → édition rapide, pas le modal picker. */
  pickerEnabled: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update', 'activate-exercise']);

const scheme = computed(() => props.row?.set_scheme ?? 'standard');
const isRamp = computed(() => scheme.value === 'ramp');
const isCluster = computed(() => scheme.value === 'cluster');
const isSpecialScheme = computed(() => isRamp.value || isCluster.value);
const schemeText = computed(() => formatSchemePrescription(props.row));
const schemeBadge = computed(() => {
  if (isRamp.value) {
    return 'Ramp';
  }
  if (isCluster.value) {
    return 'Cluster';
  }
  return '';
});
const schemeSetsDisplay = computed(() => {
  if (isRamp.value) {
    return plannedSetsForLine(props.row);
  }
  if (isCluster.value) {
    return 1;
  }
  return props.row?.sets ?? '';
});
const schemeRepsDisplay = computed(() => {
  if (isCluster.value) {
    return props.row?.scheme_config?.reps ?? props.row?.reps ?? '—';
  }
  if (isRamp.value) {
    return '—';
  }
  return props.row?.reps ?? '';
});

function updateField(field, value) {
  emit('update', {
    ...props.row,
    [field]: value,
  });
}

function parseInteger(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return '';
  }

  const parsed = Number.parseInt(value, 10);

  return Number.isNaN(parsed) ? '' : parsed;
}

function parseDecimal(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return '';
  }

  const parsed = Number(String(value).replace(',', '.'));

  return Number.isNaN(parsed) ? '' : parsed;
}

function onExerciseSelect(payload) {
  emit('update', {
    ...props.row,
    exercise_variant_id: payload.exercise_variant_id,
    exercise_name: payload.exercise_name,
    lift: ['squat', 'bench', 'deadlift'].includes(payload.lift) ? payload.lift : props.row.lift,
    movement_pattern: payload.movement_pattern ?? props.row.movement_pattern ?? '',
  });
}

function onLiftChange(lift) {
  emit('update', {
    ...props.row,
    lift,
  });
}

function openQuickEdit() {
  emit('activate-exercise');
}
</script>

<template>
  <template v-if="columnId === 'exercise'">
    <ProgramTableExerciseSelect
      :section="row.section ?? 'accessory'"
      :exercise-variant-id="row.exercise_variant_id"
      :exercise-name="row.exercise_name"
      :default-lift="defaultLift"
      :picker-enabled="pickerEnabled"
      @select="onExerciseSelect"
      @activate="emit('activate-exercise')"
    />
  </template>

  <template v-else-if="columnId === 'main_lift'">
    <ProgramTableLiftSelect
      :model-value="row.lift ?? defaultLift"
      @update:model-value="onLiftChange"
    />
  </template>

  <template v-else-if="columnId === 'variant'">
    <ProgramTableExerciseSelect
      :section="row.section ?? 'accessory'"
      :exercise-variant-id="row.exercise_variant_id"
      :exercise-name="row.exercise_name"
      :default-lift="row.lift ?? defaultLift"
      :picker-enabled="pickerEnabled"
      @select="onExerciseSelect"
      @activate="emit('activate-exercise')"
    />
  </template>

  <template v-else-if="columnId === 'section'">
    <ProgramTableSectionSelect
      :model-value="row.section ?? 'accessory'"
      :preview="preview"
      @update:model-value="updateField('section', $event)"
    />
  </template>

  <template v-else-if="columnId === 'sets'">
    <button
      v-if="isSpecialScheme"
      type="button"
      class="flex w-full flex-col items-center justify-center gap-0.5 px-0.5 py-0.5 text-center"
      @click="openQuickEdit"
    >
      <span class="rounded bg-violet-500/20 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-violet-300">
        {{ schemeBadge }}
      </span>
      <span class="font-mono text-sm font-semibold tabular-nums text-white">{{ schemeSetsDisplay }}</span>
    </button>
    <input
      v-else
      :value="row.sets"
      type="number"
      min="1"
      max="10"
      :readonly="preview"
      :class="PRESCRIPTION_VALUE_INPUT_CLASS"
      @input="updateField('sets', parseInteger($event.target.value))"
    />
  </template>

  <template v-else-if="columnId === 'reps'">
    <button
      v-if="isSpecialScheme"
      type="button"
      class="flex w-full items-center justify-center px-0.5 py-1 font-mono text-sm font-semibold tabular-nums text-slate-300"
      @click="openQuickEdit"
    >
      {{ schemeRepsDisplay }}
    </button>
    <input
      v-else
      :value="row.reps"
      type="number"
      min="1"
      max="20"
      :readonly="preview"
      :class="PRESCRIPTION_VALUE_INPUT_CLASS"
      @input="updateField('reps', parseInteger($event.target.value))"
    />
  </template>

  <template v-else-if="columnId === 'load'">
    <button
      v-if="isSpecialScheme"
      type="button"
      class="flex w-full flex-col items-stretch gap-0.5 px-0.5 py-0.5 text-left"
      @click="openQuickEdit"
    >
      <span
        class="self-start rounded bg-violet-500/20 px-1 py-px text-[9px] font-bold uppercase tracking-wide text-violet-300"
      >
        {{ schemeBadge }}
      </span>
      <span class="text-[11px] font-semibold leading-snug text-white">
        {{ schemeText || '—' }}
      </span>
    </button>
    <ProgramTableLoadCell
      v-else
      :row="row"
      :default-load-mode="defaultLoadMode"
      :preview="preview"
      @update="emit('update', $event)"
    />
  </template>

  <template v-else-if="columnId === 'rpe'">
    <input
      :value="row.rpe"
      type="number"
      min="1"
      max="10"
      step="0.5"
      placeholder="8"
      :readonly="preview"
      :class="PRESCRIPTION_VALUE_INPUT_CLASS"
      @input="updateField('rpe', parseDecimal($event.target.value))"
    />
  </template>

  <template v-else-if="columnId === 'rest'">
    <input
      :value="restSecondsToMinutes(row.rest_seconds)"
      type="number"
      min="0"
      max="30"
      step="0.5"
      placeholder="min"
      :readonly="preview"
      :class="PRESCRIPTION_VALUE_INPUT_CLASS"
      @input="updateField('rest_seconds', restMinutesToSeconds($event.target.value))"
    />
  </template>

  <template v-else-if="columnId === 'muscles'">
    <span class="block px-1 py-1 text-xs text-slate-400">
      {{ row.movement_pattern || '—' }}
    </span>
  </template>
</template>
