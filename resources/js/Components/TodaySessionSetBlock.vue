<script setup>
import { computed, ref, watch } from 'vue';
import OptionButtonGroup from './OptionButtonGroup.vue';
import {
  RPE_OPTIONS,
  formatLineRecap,
  formatLineRecapWithKg,
  inferLoadMode,
} from '../utils/programBuilder';

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  title: {
    type: String,
    required: true,
  },
  oneRm: {
    type: Object,
    default: () => ({}),
  },
  mainLift: {
    type: String,
    default: 'squat',
  },
  expanded: {
    type: Boolean,
    default: false,
  },
  validatedSetsCount: {
    type: Number,
    default: 0,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle', 'validate', 'save-note']);

const line = computed(() => props.item.line ?? {});

const totalSets = computed(() => Math.max(1, Number(line.value.sets ?? 1)));

const fullyValidated = computed(() => props.validatedSetsCount >= totalSets.value);

const collapsedRecap = computed(() => {
  const withKg = formatLineRecapWithKg(line.value, props.oneRm, props.mainLift);
  return withKg ?? formatLineRecap(line.value) ?? 'Série à renseigner';
});

const sectionClass = computed(() => {
  if (props.item.section === 'topset') {
    return 'text-blue-300';
  }
  if (props.item.section === 'backoff') {
    return 'text-amber-300';
  }
  return 'text-slate-400';
});

const currentSetNumber = computed(() =>
  Math.min(props.validatedSetsCount + 1, totalSets.value),
);

const validateButtonLabel = computed(() => {
  if (props.saving) {
    return 'Enregistrement…';
  }
  if (totalSets.value > 1 && !fullyValidated.value) {
    return 'Valider une série';
  }
  return 'Valider la série';
});

const hasAthleteNote = computed(() => Boolean(String(line.value.athlete_note ?? '').trim()));

const chargeModes = [
  { id: 'kg', label: 'kg' },
  { id: 'percent', label: '% 1RM' },
];

const activeChargeMode = ref(inferLoadMode(line.value) === 'percent' ? 'percent' : 'kg');

watch(
  () => [line.value.load, line.value.load_percent, line.value.load_mode],
  () => {
    const mode = inferLoadMode(line.value);
    if (mode === 'percent') {
      activeChargeMode.value = 'percent';
    } else if (mode === 'kg') {
      activeChargeMode.value = 'kg';
    }
  },
);

function updateIntegerField(field, rawValue) {
  const parsed = rawValue === '' ? null : Number.parseInt(rawValue, 10);
  line.value[field] = Number.isFinite(parsed) && parsed > 0 ? parsed : null;
}

function updateDecimalField(field, rawValue) {
  const parsed = rawValue === '' ? null : Number.parseFloat(String(rawValue).replace(',', '.'));
  line.value[field] = Number.isFinite(parsed) && parsed >= 0 ? parsed : null;
}

function setChargeMode(mode) {
  activeChargeMode.value = mode;
  line.value.load_mode = mode;
  if (mode === 'kg') {
    line.value.load_percent = null;
  } else {
    line.value.load = null;
  }
}

function updateRpe(value) {
  line.value.rpe = value;
}

function updateAthleteNote(rawValue) {
  line.value.athlete_note = rawValue;
}

function persistNote() {
  emit('save-note');
}

function validateLine() {
  if (!canValidate.value) {
    return;
  }

  line.value.load_mode = activeChargeMode.value;
  emit('validate');
}

const hasCharge = computed(() => {
  if (activeChargeMode.value === 'kg') {
    return line.value.load != null && line.value.load !== '';
  }

  return line.value.load_percent != null && line.value.load_percent !== '';
});

const hasRpe = computed(() => line.value.rpe != null && line.value.rpe !== '');

const canValidate = computed(() => hasCharge.value && hasRpe.value);

const inputClass =
  'mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white placeholder:text-slate-600';
</script>

<template>
  <article
    class="overflow-hidden rounded-xl border transition"
    :class="
      expanded
        ? 'border-blue-500/40 bg-slate-950/70'
        : fullyValidated
          ? 'border-emerald-500/30 bg-emerald-950/10'
          : validatedSetsCount > 0
            ? 'border-amber-500/30 bg-amber-950/10'
            : 'border-slate-800 bg-slate-950/40'
    "
  >
    <button
      type="button"
      class="flex w-full items-center gap-2 px-3 py-2.5 text-left transition hover:bg-slate-900/50"
      :aria-expanded="expanded"
      @click="emit('toggle')"
    >
      <span
        class="inline-block w-4 shrink-0 text-center text-sm text-slate-500 transition-transform duration-200"
        :class="expanded ? 'rotate-90' : ''"
        aria-hidden="true"
      >
        &gt;
      </span>
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold uppercase tracking-wide" :class="sectionClass">
          {{ title }}
        </p>
        <p class="truncate text-sm font-medium text-slate-100">
          {{ collapsedRecap }}
        </p>
        <p
          v-if="!expanded && hasAthleteNote"
          class="mt-0.5 truncate text-xs text-slate-400"
        >
          Note : {{ line.athlete_note }}
        </p>
      </div>
      <span
        v-if="fullyValidated"
        class="shrink-0 rounded-full border border-emerald-500/40 bg-emerald-950/40 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
      >
        Validée
      </span>
      <span
        v-else-if="validatedSetsCount > 0"
        class="shrink-0 rounded-full border border-amber-500/40 bg-amber-950/40 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-300"
      >
        {{ validatedSetsCount }}/{{ totalSets }}
      </span>
    </button>

    <div v-if="expanded" class="border-t border-slate-800 px-3 py-3">
      <template v-if="!fullyValidated">
        <p v-if="totalSets > 1" class="mb-3 text-xs font-medium text-blue-300/90">
          Série {{ currentSetNumber }} sur {{ totalSets }}
        </p>

        <div class="grid grid-cols-2 gap-3">
          <label class="block text-xs font-medium text-slate-400">
            Reps
            <input
              :value="line.reps ?? ''"
              type="number"
              min="1"
              max="30"
              step="1"
              inputmode="numeric"
              :class="inputClass"
              @input="updateIntegerField('reps', $event.target.value)"
            />
          </label>

          <div>
            <p class="text-xs font-medium text-slate-400">Charge</p>
            <div class="mt-1 flex flex-wrap gap-1.5">
              <button
                v-for="mode in chargeModes"
                :key="mode.id"
                type="button"
                class="rounded-lg px-2.5 py-1 text-[11px] font-medium transition"
                :class="
                  activeChargeMode === mode.id
                    ? 'bg-blue-600 text-white'
                    : 'border border-slate-700 text-slate-400 hover:border-slate-600 hover:text-white'
                "
                @click="setChargeMode(mode.id)"
              >
                {{ mode.label }}
              </button>
            </div>

            <label v-if="activeChargeMode === 'kg'" class="mt-2 block text-xs text-slate-500">
              <input
                :value="line.load ?? ''"
                type="number"
                min="0"
                max="999"
                step="0.5"
                inputmode="decimal"
                placeholder="kg"
                :class="inputClass"
                @input="updateDecimalField('load', $event.target.value)"
              />
            </label>

            <label v-else class="mt-2 block text-xs text-slate-500">
              <input
                :value="line.load_percent ?? ''"
                type="number"
                min="0"
                max="100"
                step="0.5"
                inputmode="decimal"
                placeholder="% 1RM"
                :class="inputClass"
                @input="updateDecimalField('load_percent', $event.target.value)"
              />
            </label>
          </div>
        </div>

        <div class="mt-4">
          <OptionButtonGroup
            :model-value="line.rpe"
            :options="RPE_OPTIONS"
            label="RPE de la série"
            @update:model-value="updateRpe"
          />
        </div>

        <p v-if="!canValidate" class="mt-3 text-xs text-slate-500">
          Renseigne la charge et le RPE pour valider la série.
        </p>

        <button
          type="button"
          class="mt-4 w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="saving || !canValidate"
          @click="validateLine"
        >
          {{ validateButtonLabel }}
        </button>
      </template>

      <div v-else class="space-y-3">
        <p class="text-xs text-emerald-300/90">Exercice validé — note optionnelle (non envoyée dans le retour).</p>
        <label class="block text-xs font-medium text-slate-400">
          Note sur cet exercice
          <textarea
            :value="line.athlete_note ?? ''"
            rows="3"
            maxlength="1000"
            placeholder="Sensation, douleur, commentaire…"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white placeholder:text-slate-600"
            @input="updateAthleteNote($event.target.value)"
            @blur="persistNote"
          />
        </label>
        <button
          type="button"
          class="w-full rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-200 hover:border-slate-500 disabled:opacity-50"
          :disabled="saving"
          @click="persistNote"
        >
          {{ saving ? 'Enregistrement…' : 'Enregistrer la note' }}
        </button>
      </div>
    </div>
  </article>
</template>
