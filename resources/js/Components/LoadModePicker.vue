<script setup>
import { ref, watch } from 'vue';
import OptionButtonGroup from './OptionButtonGroup.vue';
import { RPE_OPTIONS } from '../utils/programBuilder';

const props = defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
});

const line = defineModel({ type: Object, required: true });

const modes = [
  { id: 'rpe', label: 'RPE' },
  { id: 'percent', label: '% 1RM' },
  { id: 'kg', label: 'kg' },
];

const activeMode = ref(inferMode(line.value));

function inferMode(value) {
  if (value?.rpe != null && value.rpe !== '') {
    return 'rpe';
  }
  if (value?.load_percent != null && value.load_percent !== '') {
    return 'percent';
  }
  if (value?.load != null && value.load !== '') {
    return 'kg';
  }
  return line.value?.load_mode ?? null;
}

function setMode(mode) {
  activeMode.value = mode;
  line.value.load_mode = mode;
  line.value.rpe = null;
  line.value.load_percent = null;
  line.value.load = null;
}

function updateField(field, value) {
  line.value[field] = value === '' || value === null ? null : Number(value);
  line.value.load_mode = activeMode.value;
}

watch(
  () => line.value,
  (value) => {
    activeMode.value = inferMode(value);
  },
);

watch(
  () => [line.value?.rpe, line.value?.load_percent, line.value?.load],
  () => {
    if (!line.value?.load_mode) {
      activeMode.value = inferMode(line.value);
    }
  },
);
</script>

<template>
  <div :class="compact ? '' : 'mt-4'">
    <p class="font-medium text-slate-400" :class="compact ? 'text-[10px]' : 'text-xs'">Charge</p>
    <div class="flex flex-wrap gap-1.5" :class="compact ? 'mt-1' : 'mt-2 gap-2'">
      <button
        v-for="mode in modes"
        :key="mode.id"
        type="button"
        class="font-medium transition"
        :class="[
          compact
            ? 'rounded px-2 py-1 text-[10px]'
            : 'rounded-lg px-3 py-1.5 text-xs',
          activeMode === mode.id
            ? 'bg-blue-600 text-white'
            : 'border border-slate-700 text-slate-400 hover:border-slate-600 hover:text-white',
        ]"
        @click="setMode(mode.id)"
      >
        {{ mode.label }}
      </button>
    </div>

    <div v-if="activeMode === 'rpe'" :class="compact ? 'mt-1.5' : 'mt-3'">
      <OptionButtonGroup
        :model-value="line.rpe"
        :options="RPE_OPTIONS"
        :dense="compact"
        label="Valeur RPE"
        @update:model-value="updateField('rpe', $event)"
      />
    </div>

    <label
      v-else-if="activeMode === 'percent'"
      class="block max-w-xs text-slate-500"
      :class="compact ? 'mt-1.5 text-[10px]' : 'mt-3 text-xs'"
    >
      % du 1RM
      <input
        :value="line.load_percent ?? ''"
        type="number"
        min="0"
        max="100"
        step="0.5"
        placeholder="Ex. 82.5"
        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-white"
        :class="compact ? 'px-2 py-1.5 text-xs' : 'px-2.5 py-2 text-sm'"
        @input="updateField('load_percent', $event.target.value)"
      />
    </label>

    <label
      v-else-if="activeMode === 'kg'"
      class="block max-w-xs text-slate-500"
      :class="compact ? 'mt-1.5 text-[10px]' : 'mt-3 text-xs'"
    >
      Charge (kg)
      <input
        :value="line.load ?? ''"
        type="number"
        min="0"
        max="999"
        step="0.5"
        placeholder="Ex. 140"
        class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 text-white"
        :class="compact ? 'px-2 py-1.5 text-xs' : 'px-2.5 py-2 text-sm'"
        @input="updateField('load', $event.target.value)"
      />
    </label>

    <p v-else class="text-slate-500" :class="compact ? 'mt-1 text-[10px]' : 'mt-2 text-xs'">
      Choisis un type de charge ci-dessus.
    </p>
  </div>
</template>
