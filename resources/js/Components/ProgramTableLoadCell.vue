<script setup>
import { computed, ref, watch } from 'vue';
import {
  LOAD_MODE_KG,
  LOAD_MODE_PERCENT,
  LOAD_MODE_RPE,
  PRESCRIPTION_LOAD_INPUT_CLASS,
} from '../config/dayTableColumns';

const props = defineProps({
  row: {
    type: Object,
    required: true,
  },
  defaultLoadMode: {
    type: String,
    default: LOAD_MODE_KG,
  },
  preview: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update']);

const modes = [
  { id: LOAD_MODE_KG, label: 'kg' },
  { id: LOAD_MODE_PERCENT, label: '%' },
  { id: LOAD_MODE_RPE, label: 'RPE' },
];

function inferMode(row) {
  if (row?.rpe !== '' && row?.rpe != null) {
    return LOAD_MODE_RPE;
  }
  if (row?.load_percent !== '' && row?.load_percent != null) {
    return LOAD_MODE_PERCENT;
  }
  if (row?.load !== '' && row?.load != null) {
    return LOAD_MODE_KG;
  }

  return row?.load_mode ?? props.defaultLoadMode ?? LOAD_MODE_KG;
}

const activeMode = ref(inferMode(props.row));

watch(
  () => props.row,
  (row) => {
    activeMode.value = inferMode(row);
  },
  { deep: true },
);

watch(
  () => [props.row.rpe, props.row.load_percent, props.row.load],
  () => {
    if (!props.row.load_mode) {
      activeMode.value = inferMode(props.row);
    }
  },
);

function setMode(mode) {
  activeMode.value = mode;
  emit('update', {
    ...props.row,
    load_mode: mode,
    load: '',
    load_percent: '',
    rpe: '',
  });
}

function updateField(field, value) {
  const next = { ...props.row, [field]: value };

  if (field === 'load') {
    next.load_percent = '';
    next.rpe = '';
  } else if (field === 'load_percent') {
    next.load = '';
    next.rpe = '';
  } else if (field === 'rpe') {
    next.load = '';
    next.load_percent = '';
  }

  next.load_mode = activeMode.value;
  emit('update', next);
}

function parseNumber(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return '';
  }

  const parsed = Number(String(value).replace(',', '.'));

  return Number.isNaN(parsed) ? '' : parsed;
}

const placeholder = computed(() => {
  if (activeMode.value === LOAD_MODE_RPE) {
    return '8.5';
  }
  if (activeMode.value === LOAD_MODE_PERCENT) {
    return '82.5';
  }

  return '140';
});
</script>

<template>
  <div class="space-y-0">
    <div class="flex justify-center gap-0.5">
      <button
        v-for="mode in modes"
        :key="mode.id"
        type="button"
        class="rounded px-1 py-0.5 text-[9px] font-medium uppercase tracking-wide transition"
        :class="
          activeMode === mode.id
            ? 'bg-blue-600 text-white'
            : 'text-slate-500 hover:bg-slate-800 hover:text-slate-300'
        "
        :disabled="preview"
        @click="setMode(mode.id)"
      >
        {{ mode.label }}
      </button>
    </div>

    <input
      v-if="activeMode === LOAD_MODE_KG"
      :value="row.load"
      type="text"
      inputmode="decimal"
      :placeholder="placeholder"
      :readonly="preview"
      :class="PRESCRIPTION_LOAD_INPUT_CLASS"
      @input="updateField('load', parseNumber($event.target.value))"
    />
    <input
      v-else-if="activeMode === LOAD_MODE_PERCENT"
      :value="row.load_percent"
      type="number"
      min="0"
      max="100"
      step="0.5"
      :placeholder="placeholder"
      :readonly="preview"
      :class="PRESCRIPTION_LOAD_INPUT_CLASS"
      @input="updateField('load_percent', parseNumber($event.target.value))"
    />
    <input
      v-else
      :value="row.rpe"
      type="number"
      min="1"
      max="10"
      step="0.5"
      :placeholder="placeholder"
      :readonly="preview"
      :class="PRESCRIPTION_LOAD_INPUT_CLASS"
      @input="updateField('rpe', parseNumber($event.target.value))"
    />
  </div>
</template>
