<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import ScrollPicker from './ScrollPicker.vue';
import {
  RPE_OPTIONS,
  formatLineRecap,
  formatLineRecapWithKg,
  formatValidatedSetsRecapLines,
  inferLoadMode,
} from '../utils/programBuilder';
import { resolveLoadKg } from '../utils/trainingVolume';

const { t } = useI18n();

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
  validatedSets: {
    type: Array,
    default: () => [],
  },
  plannedSets: {
    type: Number,
    default: null,
  },
  saving: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['toggle', 'validate', 'save-note']);

const line = computed(() => props.item.line ?? {});

const scheme = computed(() => line.value.set_scheme ?? 'standard');
const isCluster = computed(() => scheme.value === 'cluster');
const isRamp = computed(() => scheme.value === 'ramp');

const clusterTarget = computed(() => {
  if (!isCluster.value) {
    return null;
  }
  const reps = line.value.scheme_config?.reps ?? line.value.reps;
  const minutes = line.value.scheme_config?.duration_minutes;
  if (reps == null || minutes == null) {
    return null;
  }
  return `${reps} reps en ${minutes} min`;
});

const totalSets = computed(() =>
  Math.max(1, Number(props.plannedSets ?? line.value.sets ?? 1)),
);

const fullyValidated = computed(() => props.validatedSetsCount >= totalSets.value);

const collapsedRecapLines = computed(() => {
  if (props.validatedSetsCount > 0 && props.validatedSets.length > 0) {
    return formatValidatedSetsRecapLines(
      line.value,
      props.validatedSets,
      props.oneRm,
      props.mainLift,
    );
  }

  const withKg = formatLineRecapWithKg(line.value, props.oneRm, props.mainLift);
  const fallback = withKg ?? formatLineRecap(line.value) ?? t('athleteUi.todaySessionSet.setToFill');
  return [fallback];
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
    return t('athleteUi.todaySessionSet.saving');
  }
  if (isCluster.value) {
    return t('athleteUi.todaySessionSet.validateCluster');
  }
  if (totalSets.value > 1 && !fullyValidated.value) {
    return isRamp.value ? t('athleteUi.todaySessionSet.validateRamp') : t('athleteUi.todaySessionSet.validateOneSet');
  }
  return t('athleteUi.todaySessionSet.validateSet');
});

const hasAthleteNote = computed(() => Boolean(String(line.value.athlete_note ?? '').trim()));

const chargeModes = [
  { id: 'kg', label: 'kg' },
  { id: 'percent', label: '%' },
];

function resolveInitialChargeMode() {
  const mode = inferLoadMode(line.value);
  if (mode === 'percent') {
    return 'percent';
  }
  if (mode === 'kg') {
    return 'kg';
  }
  if (line.value.load_percent != null && line.value.load_percent !== '') {
    return 'percent';
  }
  return 'kg';
}

const activeChargeMode = ref(resolveInitialChargeMode());

/** Snapshot de la charge prévue (programme) pour réinitialiser / convertir. */
const prescribed = ref(null);

const REP_OPTIONS = Array.from({ length: 40 }, (_, i) => i + 1);
const KG_OPTIONS = (() => {
  const values = [0];
  for (let kg = 2.5; kg <= 400; kg += 2.5) {
    values.push(Number(kg.toFixed(1)));
  }
  return values;
})();
const PERCENT_OPTIONS = (() => {
  const values = [];
  for (let pct = 20; pct <= 110; pct += 0.5) {
    values.push(Number(pct.toFixed(1)));
  }
  return values;
})();

const chargeOptions = computed(() =>
  activeChargeMode.value === 'kg' ? KG_OPTIONS : PERCENT_OPTIONS,
);

function snapToKg(value) {
  const num = Number(value);
  if (!Number.isFinite(num) || num < 0) {
    return null;
  }
  return Number((Math.round(num / 2.5) * 2.5).toFixed(1));
}

function snapToPercent(value) {
  const num = Number(value);
  if (!Number.isFinite(num)) {
    return null;
  }
  return Number((Math.round(num * 2) / 2).toFixed(1));
}

function oneRmForLine() {
  const lift = line.value.lift || props.mainLift || 'squat';
  const raw = props.oneRm?.[lift];
  const num = Number(raw);
  return Number.isFinite(num) && num > 0 ? num : null;
}

function plannedKg() {
  if (prescribed.value?.load != null && prescribed.value.load !== '') {
    return snapToKg(prescribed.value.load);
  }
  return snapToKg(
    resolveLoadKg(
      {
        load: prescribed.value?.load ?? line.value.load,
        load_percent: prescribed.value?.load_percent ?? line.value.load_percent,
        load_mode: prescribed.value?.mode ?? inferLoadMode(line.value),
        lift: line.value.lift,
        rpe: null,
      },
      props.oneRm,
      props.mainLift,
    ),
  );
}

function plannedPercent() {
  if (prescribed.value?.load_percent != null && prescribed.value.load_percent !== '') {
    return snapToPercent(prescribed.value.load_percent);
  }
  const kg = prescribed.value?.load ?? line.value.load;
  const orm = oneRmForLine();
  if (kg != null && kg !== '' && orm) {
    return snapToPercent((Number(kg) / orm) * 100);
  }
  return null;
}

function capturePrescribed({ force = false } = {}) {
  if (prescribed.value && !force) {
    return;
  }
  prescribed.value = {
    load: line.value.load ?? null,
    load_percent: line.value.load_percent ?? null,
    mode: resolveInitialChargeMode(),
    reps: line.value.reps ?? null,
  };
}

function applyPrescribedCharge() {
  capturePrescribed();
  if (!activeChargeMode.value) {
    activeChargeMode.value = prescribed.value.mode ?? resolveInitialChargeMode();
  }

  if (activeChargeMode.value === 'percent') {
    if (line.value.load_percent == null || line.value.load_percent === '') {
      const pct = plannedPercent();
      if (pct != null) {
        line.value.load_percent = pct;
        line.value.load = null;
        line.value.load_mode = 'percent';
      }
    }
    return;
  }

  if (line.value.load == null || line.value.load === '') {
    const kg = plannedKg();
    if (kg != null) {
      line.value.load = kg;
      line.value.load_percent = null;
      line.value.load_mode = 'kg';
    }
  }
}

const chargeValue = computed({
  get() {
    if (activeChargeMode.value === 'kg') {
      return line.value.load ?? null;
    }
    return line.value.load_percent ?? null;
  },
  set(value) {
    if (activeChargeMode.value === 'kg') {
      line.value.load = value;
      line.value.load_percent = null;
      line.value.load_mode = 'kg';
      return;
    }
    line.value.load_percent = value;
    line.value.load = null;
    line.value.load_mode = 'percent';
  },
});

const repsValue = computed({
  get() {
    return line.value.reps ?? null;
  },
  set(value) {
    line.value.reps = value;
  },
});

const rpeValue = computed({
  get() {
    return line.value.rpe ?? null;
  },
  set(value) {
    line.value.rpe = value;
  },
});

watch(
  () => props.expanded,
  (open) => {
    if (open && !fullyValidated.value && !isCluster.value) {
      applyPrescribedCharge();
    }
  },
  { immediate: true, flush: 'sync' },
);

watch(
  () => props.validatedSetsCount,
  () => {
    // Après une série validée (ramp / multi-séries), re-synchronise la charge prévue.
    capturePrescribed({ force: true });
    activeChargeMode.value = resolveInitialChargeMode();
  },
);

watch(
  () => [line.value.load, line.value.load_percent, line.value.load_mode],
  () => {
    if (props.expanded) {
      return;
    }
    const mode = inferLoadMode(line.value);
    if (mode === 'percent') {
      activeChargeMode.value = 'percent';
    } else if (mode === 'kg') {
      activeChargeMode.value = 'kg';
    }
  },
);

function setChargeMode(mode) {
  if (mode === activeChargeMode.value) {
    return;
  }

  capturePrescribed();

  if (mode === 'kg') {
    const kg =
      snapToKg(resolveLoadKg(line.value, props.oneRm, props.mainLift))
      ?? plannedKg()
      ?? 60;
    line.value.load = kg;
    line.value.load_percent = null;
    line.value.load_mode = 'kg';
  } else {
    let pct = null;
    if (line.value.load != null && line.value.load !== '') {
      const orm = oneRmForLine();
      if (orm) {
        pct = snapToPercent((Number(line.value.load) / orm) * 100);
      }
    }
    pct = pct ?? plannedPercent() ?? 70;
    line.value.load_percent = pct;
    line.value.load = null;
    line.value.load_mode = 'percent';
  }

  activeChargeMode.value = mode;
}

function formatChargeOption(value) {
  return Number.isInteger(value) ? String(value) : String(value);
}

function formatRpeOption(value) {
  return Number.isInteger(value) ? String(value) : String(value);
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

const canValidate = computed(() => {
  if (isCluster.value) {
    return line.value.reps != null && line.value.reps !== '';
  }
  return hasCharge.value && hasRpe.value;
});

const pickerHeight = 32;
const pickerVisible = 3;
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
        <div class="space-y-0.5">
          <p
            v-for="(recapLine, index) in collapsedRecapLines"
            :key="`${index}-${recapLine}`"
            class="truncate text-sm font-medium text-slate-100"
          >
            {{ recapLine }}
          </p>
        </div>
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
        {{ t('athleteUi.todaySessionSet.validated') }}
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
        <div
          v-if="validatedSetsCount > 0 && collapsedRecapLines.length"
          class="mb-3 space-y-1 rounded-lg border border-emerald-500/20 bg-emerald-950/20 px-2.5 py-2"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-300/90">
            {{ t('athleteUi.todaySessionSet.validatedSets') }}
          </p>
          <p
            v-for="(recapLine, index) in collapsedRecapLines"
            :key="`validated-${index}-${recapLine}`"
            class="text-xs font-medium text-slate-200"
          >
            {{ recapLine }}
          </p>
        </div>

        <p v-if="clusterTarget" class="mb-3 rounded-lg border border-violet-500/30 bg-violet-950/20 px-2.5 py-2 text-xs font-medium text-violet-200">
          {{ t('athleteUi.todaySessionSet.clusterTarget', { target: clusterTarget }) }}
        </p>

        <p v-else-if="totalSets > 1" class="mb-3 text-xs font-medium text-blue-300/90">
          <template v-if="isRamp">{{ t('athleteUi.todaySessionSet.rampProgress', { current: currentSetNumber, total: totalSets }) }}</template>
          <template v-else>{{ t('athleteUi.todaySessionSet.setProgress', { current: currentSetNumber, total: totalSets }) }}</template>
        </p>

        <div :class="isCluster ? 'grid grid-cols-2 gap-2' : 'grid grid-cols-3 gap-1.5'">
          <div class="min-w-0">
            <p class="mb-1 flex h-6 items-center justify-center text-center text-[10px] font-medium text-slate-400">
              {{ isCluster ? t('athleteUi.todaySessionSet.repsDone') : t('athleteUi.todaySessionSet.reps') }}
            </p>
            <ScrollPicker
              v-model="repsValue"
              :options="REP_OPTIONS"
              :item-height="pickerHeight"
              :visible-count="pickerVisible"
              :aria-label="t('athleteUi.todaySessionSet.reps')"
            />
          </div>

          <div v-if="!isCluster" class="min-w-0">
            <div class="mb-1 flex h-6 items-center justify-center gap-0.5">
              <button
                v-for="mode in chargeModes"
                :key="mode.id"
                type="button"
                class="rounded px-1.5 py-0.5 text-[10px] font-semibold transition"
                :class="
                  activeChargeMode === mode.id
                    ? 'bg-blue-600 text-white'
                    : 'border border-slate-700 text-slate-400'
                "
                @click="setChargeMode(mode.id)"
              >
                {{ mode.label }}
              </button>
            </div>
            <ScrollPicker
              v-model="chargeValue"
              :options="chargeOptions"
              :format="formatChargeOption"
              :item-height="pickerHeight"
              :visible-count="pickerVisible"
              :aria-label="t('athleteUi.todaySessionSet.load')"
            />
          </div>
          <div v-else class="min-w-0">
            <p class="mb-1 flex h-6 items-center justify-center text-center text-[10px] font-medium text-slate-400">
              {{ t('athleteUi.todaySessionSet.plannedDuration') }}
            </p>
            <div class="flex h-[96px] items-center justify-center rounded-lg border border-slate-700 bg-slate-950 px-2 text-xs font-medium text-slate-200">
              {{ t('athleteUi.todaySessionSet.minutes', { count: line.scheme_config?.duration_minutes ?? '—' }) }}
            </div>
          </div>

          <div v-if="!isCluster" class="min-w-0">
            <p class="mb-1 flex h-6 items-center justify-center text-center text-[10px] font-medium text-slate-400">
              RPE
            </p>
            <ScrollPicker
              v-model="rpeValue"
              :options="RPE_OPTIONS"
              :format="formatRpeOption"
              :item-height="pickerHeight"
              :visible-count="pickerVisible"
              allow-empty
              :aria-label="t('athleteUi.todaySessionSet.rpeLabel')"
            />
          </div>
        </div>

        <button
          type="button"
          class="mt-3 w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="saving || !canValidate"
          @click="validateLine"
        >
          {{ validateButtonLabel }}
        </button>
      </template>

      <div v-else class="space-y-3">
        <div
          v-if="collapsedRecapLines.length"
          class="space-y-1 rounded-lg border border-emerald-500/20 bg-emerald-950/20 px-2.5 py-2"
        >
          <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-300/90">
            {{ t('athleteUi.todaySessionSet.validatedSets') }}
          </p>
          <p
            v-for="(recapLine, index) in collapsedRecapLines"
            :key="`done-${index}-${recapLine}`"
            class="text-xs font-medium text-slate-200"
          >
            {{ recapLine }}
          </p>
        </div>
        <p class="text-xs text-emerald-300/90">
          {{ t('athleteUi.todaySessionSet.exerciseValidated') }}
        </p>
        <label class="block text-xs font-medium text-slate-400">
          {{ t('athleteUi.todaySessionSet.noteOnExercise') }}
          <textarea
            :value="line.athlete_note ?? ''"
            rows="3"
            maxlength="1000"
            :placeholder="t('athleteUi.todaySessionSet.notePlaceholder')"
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
          {{ saving ? t('athleteUi.todaySessionSet.saving') : t('athleteUi.todaySessionSet.saveNote') }}
        </button>
      </div>
    </div>
  </article>
</template>
