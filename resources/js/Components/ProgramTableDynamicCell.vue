<script setup>
import ProgramTableExerciseSelect from './ProgramTableExerciseSelect.vue';
import ProgramTableLiftSelect from './ProgramTableLiftSelect.vue';
import ProgramTableSectionSelect from './ProgramTableSectionSelect.vue';
import ProgramTableLoadCell from './ProgramTableLoadCell.vue';
import { PRESCRIPTION_VALUE_INPUT_CLASS } from '../config/dayTableColumns';

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
    <input
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
    <input
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
    <ProgramTableLoadCell
      :row="row"
      :default-load-mode="defaultLoadMode"
      :preview="preview"
      @update="emit('update', $event)"
    />
  </template>

  <template v-else-if="columnId === 'rest'">
    <input
      :value="row.rest_seconds"
      type="number"
      min="0"
      max="900"
      step="15"
      placeholder="s"
      :readonly="preview"
      :class="PRESCRIPTION_VALUE_INPUT_CLASS"
      @input="updateField('rest_seconds', parseInteger($event.target.value))"
    />
  </template>

  <template v-else-if="columnId === 'muscles'">
    <span class="block px-1 py-1 text-xs text-slate-400">
      {{ row.movement_pattern || '—' }}
    </span>
  </template>
</template>
