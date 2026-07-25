<script setup>
import { computed } from 'vue';
import {
  SET_SCHEME_OPTIONS,
  createRampStep,
  emptySchemeConfig,
  inferLoadMode,
} from '../utils/programBuilder';

const line = defineModel({ type: Object, required: true });

defineProps({
  dense: { type: Boolean, default: false },
});

const scheme = computed(() => line.value?.set_scheme ?? 'standard');

const steps = computed(() => line.value?.scheme_config?.steps ?? []);

function setScheme(next) {
  if (!line.value) {
    return;
  }
  if (next === 'standard') {
    line.value.set_scheme = 'standard';
    line.value.scheme_config = null;
    return;
  }
  line.value.set_scheme = next;
  if (next === 'ramp') {
    const existingSteps = line.value.scheme_config?.steps;
    line.value.scheme_config =
      Array.isArray(existingSteps) && existingSteps.length
        ? { steps: existingSteps }
        : emptySchemeConfig('ramp', line.value);
    return;
  }
  if (next === 'cluster') {
    line.value.scheme_config = emptySchemeConfig('cluster', line.value);
  }
}

function ensureRampConfig() {
  if (!line.value.scheme_config || !Array.isArray(line.value.scheme_config.steps)) {
    line.value.scheme_config = emptySchemeConfig('ramp', line.value);
  }
}

function updateStep(index, patch) {
  ensureRampConfig();
  const next = line.value.scheme_config.steps.map((step, i) =>
    i === index ? { ...step, ...patch } : step,
  );
  line.value.scheme_config = { steps: next };
}

function addStep() {
  ensureRampConfig();
  if (line.value.scheme_config.steps.length >= 8) {
    return;
  }
  const last = line.value.scheme_config.steps[line.value.scheme_config.steps.length - 1];
  const bump = last?.load != null ? Number(last.load) + 10 : null;
  line.value.scheme_config = {
    steps: [
      ...line.value.scheme_config.steps,
      createRampStep({
        reps: last?.reps ?? 2,
        load: bump,
        load_percent: last?.load_percent ?? null,
        rpe: last?.rpe ?? null,
      }),
    ],
  };
}

function removeStep(index) {
  ensureRampConfig();
  if (line.value.scheme_config.steps.length <= 1) {
    return;
  }
  line.value.scheme_config = {
    steps: line.value.scheme_config.steps.filter((_, i) => i !== index),
  };
}

function updateCluster(field, value) {
  if (!line.value) {
    return;
  }
  const current = line.value.scheme_config ?? emptySchemeConfig('cluster');
  const parsed = value === '' || value == null ? null : Number(value);
  line.value.scheme_config = {
    ...current,
    [field]: Number.isNaN(parsed) ? null : parsed,
  };
  if (field === 'reps' && parsed != null) {
    line.value.reps = parsed;
  }
}

function stepLoadMode(step) {
  return inferLoadMode(step) ?? 'kg';
}

function setStepLoadMode(index, mode) {
  const step = steps.value[index] ?? createRampStep();
  const next = { ...step, load: null, load_percent: null, rpe: null };
  if (mode === 'kg') {
    next.load = step.load ?? 100;
  } else if (mode === 'percent') {
    next.load_percent = step.load_percent ?? 70;
  } else {
    next.rpe = step.rpe ?? 8;
  }
  updateStep(index, next);
}

function parseNum(value) {
  if (value === '' || value == null) {
    return null;
  }
  const n = Number(String(value).replace(',', '.'));
  return Number.isNaN(n) ? null : n;
}
</script>

<template>
  <div class="space-y-3">
    <div>
      <p
        class="font-medium uppercase tracking-wide text-slate-500"
        :class="dense ? 'text-[11px]' : 'text-xs'"
      >
        Type de séries
      </p>
      <div class="mt-1.5 flex flex-wrap gap-1.5">
        <button
          v-for="option in SET_SCHEME_OPTIONS"
          :key="option.value"
          type="button"
          class="rounded-lg px-2.5 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition"
          :class="
            scheme === option.value
              ? 'bg-blue-600 text-white'
              : 'border border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-500'
          "
          @click="setScheme(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </div>

    <div v-if="scheme === 'ramp'" class="space-y-2">
      <p class="text-[11px] text-slate-500">Paliers (ex. 2@210 → 2@220 → 2@230)</p>
      <div
        v-for="(step, index) in steps"
        :key="index"
        class="grid grid-cols-[auto_4rem_1fr_auto] items-end gap-2 rounded-lg border border-slate-800 bg-slate-950/50 p-2"
      >
        <span class="pb-2 text-[10px] font-semibold text-slate-500">{{ index + 1 }}</span>
        <label class="block text-[10px] uppercase text-slate-500">
          Reps
          <input
            :value="step.reps ?? ''"
            type="number"
            min="1"
            max="20"
            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1.5 text-sm text-white"
            @input="updateStep(index, { reps: parseNum($event.target.value) })"
          />
        </label>
        <div>
          <div class="flex gap-1">
            <button
              v-for="mode in ['kg', 'percent', 'rpe']"
              :key="mode"
              type="button"
              class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase"
              :class="
                stepLoadMode(step) === mode
                  ? 'bg-slate-600 text-white'
                  : 'text-slate-500 hover:text-slate-300'
              "
              @click="setStepLoadMode(index, mode)"
            >
              {{ mode === 'percent' ? '%' : mode }}
            </button>
          </div>
          <input
            v-if="stepLoadMode(step) === 'kg'"
            :value="step.load ?? ''"
            type="number"
            min="0"
            step="0.5"
            placeholder="kg"
            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1.5 text-sm text-white"
            @input="updateStep(index, { load: parseNum($event.target.value), load_percent: null, rpe: null })"
          />
          <input
            v-else-if="stepLoadMode(step) === 'percent'"
            :value="step.load_percent ?? ''"
            type="number"
            min="0"
            max="100"
            step="0.5"
            placeholder="%"
            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1.5 text-sm text-white"
            @input="updateStep(index, { load_percent: parseNum($event.target.value), load: null, rpe: null })"
          />
          <input
            v-else
            :value="step.rpe ?? ''"
            type="number"
            min="1"
            max="10"
            step="0.5"
            placeholder="RPE"
            class="mt-1 w-full rounded-md border border-slate-700 bg-slate-900 px-2 py-1.5 text-sm text-white"
            @input="updateStep(index, { rpe: parseNum($event.target.value), load: null, load_percent: null })"
          />
        </div>
        <button
          type="button"
          class="pb-2 text-xs text-red-400 hover:text-red-300 disabled:opacity-40"
          :disabled="steps.length <= 1"
          @click="removeStep(index)"
        >
          ✕
        </button>
      </div>
      <button
        type="button"
        class="text-xs font-medium text-blue-300 hover:text-blue-200 disabled:opacity-40"
        :disabled="steps.length >= 8"
        @click="addStep"
      >
        + Ajouter un palier
      </button>
    </div>

    <div v-else-if="scheme === 'cluster'" class="grid grid-cols-2 gap-3">
      <label class="block text-[11px] font-medium uppercase tracking-wide text-slate-500">
        Reps totales
        <input
          :value="line.scheme_config?.reps ?? ''"
          type="number"
          min="1"
          max="200"
          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
          @input="updateCluster('reps', $event.target.value)"
        />
      </label>
      <label class="block text-[11px] font-medium uppercase tracking-wide text-slate-500">
        Durée (min)
        <input
          :value="line.scheme_config?.duration_minutes ?? ''"
          type="number"
          min="1"
          max="60"
          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
          @input="updateCluster('duration_minutes', $event.target.value)"
        />
      </label>
    </div>
  </div>
</template>
