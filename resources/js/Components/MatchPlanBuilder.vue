<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  ATTEMPT_KEYS,
  LIFTS,
  LIFT_LABELS,
  MAX_WARMUP_BARS,
  defaultStructuredPlan,
  emptyScenario,
  emptyWarmups,
  formatWeight,
  normalizePlan,
  scenarioTotal,
} from '../utils/matchPlan';

const { t } = useI18n();

const props = defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
  /** En mode compact : un seul scénario, sans ajout */
  singleScenario: {
    type: Boolean,
    default: false,
  },
});

const model = defineModel({
  type: Object,
  default: () => defaultStructuredPlan(),
});

const plan = computed({
  get: () => normalizePlan(model.value),
  set: (value) => {
    model.value = normalizePlan(value);
  },
});

const isLegacyText = computed(() => plan.value.mode === 'text');

const warmups = computed(() => plan.value.warmups ?? emptyWarmups());

function updateScenarios(scenarios) {
  plan.value = { ...plan.value, mode: 'structured', scenarios };
}

function updateWarmups(next) {
  plan.value = { ...plan.value, warmups: next };
}

function updateScenarioName(index, name) {
  updateScenarios(
    plan.value.scenarios.map((s, i) => (i === index ? { ...s, name } : s)),
  );
}

function updateAttempt(index, lift, key, raw) {
  const cleaned = String(raw).trim().replace(',', '.');
  const val = cleaned === '' ? null : Number(cleaned);
  if (val !== null && !Number.isFinite(val)) {
    return;
  }
  updateScenarios(
    plan.value.scenarios.map((s, i) => {
      if (i !== index) {
        return s;
      }
      return {
        ...s,
        lifts: {
          ...s.lifts,
          [lift]: { ...s.lifts[lift], [key]: val },
        },
      };
    }),
  );
}

function addScenario() {
  if (props.singleScenario) {
    return;
  }
  updateScenarios([
    ...plan.value.scenarios,
    emptyScenario(t('programBuilder.matchPlan.scenarioName', { n: plan.value.scenarios.length + 1 })),
  ]);
}

function removeScenario(index) {
  if (plan.value.scenarios.length <= 1) {
    return;
  }
  updateScenarios(plan.value.scenarios.filter((_, i) => i !== index));
}

function convertLegacyToStructured() {
  plan.value = {
    ...defaultStructuredPlan(),
    warmups: plan.value.warmups ?? emptyWarmups(),
  };
}

function warmupSlots(lift) {
  return (warmups.value[lift] ?? []).map((bar) => ({
    weight: bar.weight == null ? '' : String(bar.weight),
    reps: bar.reps == null ? '' : String(bar.reps),
  }));
}

function canAddWarmupBar(lift) {
  return (warmups.value[lift]?.length ?? 0) < MAX_WARMUP_BARS;
}

function addWarmupBar(lift) {
  if (!canAddWarmupBar(lift)) {
    return;
  }
  updateWarmups({
    ...warmups.value,
    [lift]: [...(warmups.value[lift] ?? []), { weight: 0, reps: null }].slice(0, MAX_WARMUP_BARS),
  });
}

function onWarmupWeight(lift, index, raw) {
  const cleaned = String(raw).trim().replace(',', '.');
  const bars = [...(warmups.value[lift] ?? [])];
  if (index >= bars.length) {
    return;
  }
  if (cleaned === '') {
    bars.splice(index, 1);
    updateWarmups({ ...warmups.value, [lift]: bars });
    return;
  }
  const val = Number(cleaned);
  if (!Number.isFinite(val)) {
    return;
  }
  bars[index] = { ...bars[index], weight: val };
  updateWarmups({ ...warmups.value, [lift]: bars });
}

function onWarmupReps(lift, index, raw) {
  const cleaned = String(raw).trim();
  const bars = [...(warmups.value[lift] ?? [])];
  if (index >= bars.length) {
    return;
  }
  if (cleaned === '') {
    bars[index] = { ...bars[index], reps: null };
  } else {
    const val = Number(cleaned);
    if (!Number.isFinite(val)) {
      return;
    }
    const reps = Math.round(val);
    if (reps < 1 || reps > 50) {
      return;
    }
    bars[index] = { ...bars[index], reps };
  }
  updateWarmups({ ...warmups.value, [lift]: bars });
}
</script>

<template>
  <div :class="compact ? 'space-y-3' : 'space-y-4'">
    <template v-if="isLegacyText">
      <p class="text-xs text-slate-500">
        {{ t('programBuilder.matchPlan.legacyHint') }}
      </p>
      <p
        class="whitespace-pre-wrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2 font-mono text-xs text-slate-400"
      >
        {{ plan.text }}
      </p>
      <button
        type="button"
        class="rounded-lg bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-500"
        @click="convertLegacyToStructured"
      >
        {{ t('programBuilder.matchPlan.convertStructured') }}
      </button>
    </template>

    <template v-else>
      <div
        v-for="(scenario, index) in plan.scenarios"
        :key="scenario.id"
        class="rounded-xl border border-slate-800 bg-slate-950/40"
        :class="compact ? 'p-3' : 'p-4'"
      >
        <div class="flex flex-wrap items-start justify-between gap-2">
          <label class="min-w-0 flex-1 text-xs text-slate-400" :class="compact ? '' : 'text-sm'">
            {{ t('programBuilder.matchPlan.scenarioLabel') }}
            <input
              :value="scenario.name"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 text-white"
              :class="compact ? 'py-1.5 text-sm' : 'mt-2 rounded-xl px-3 py-2'"
              :placeholder="t('programBuilder.matchPlan.scenarioPlaceholder')"
              @input="updateScenarioName(index, $event.target.value)"
            />
          </label>
          <div class="flex items-center gap-2" :class="compact ? 'pt-5' : 'pt-6'">
            <span
              v-if="scenarioTotal(scenario) != null"
              class="rounded-lg bg-rose-500/15 px-2 py-1 text-xs text-rose-200"
            >
              {{ formatWeight(scenarioTotal(scenario)) }} kg
            </span>
            <button
              v-if="!singleScenario && plan.scenarios.length > 1"
              type="button"
              class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-red-500/50 hover:text-red-300"
              @click="removeScenario(index)"
            >
              {{ t('common.delete') }}
            </button>
          </div>
        </div>

        <div class="mt-3 overflow-x-auto">
          <table class="w-full min-w-[280px] text-xs">
            <thead>
              <tr class="text-slate-500">
                <th class="pb-1.5 pr-2 text-left font-medium">{{ t('programBuilder.shared.movement') }}</th>
                <th
                  v-for="key in ATTEMPT_KEYS"
                  :key="key"
                  class="pb-1.5 px-1 text-center font-medium"
                >
                  {{ t(`programBuilder.matchPlan.${key}`) }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lift in LIFTS" :key="lift" class="border-t border-slate-800/80">
                <td class="py-1.5 pr-2 font-medium text-slate-300">{{ LIFT_LABELS[lift] }}</td>
                <td v-for="key in ATTEMPT_KEYS" :key="key" class="px-1 py-1.5">
                  <input
                    :value="scenario.lifts[lift][key] ?? ''"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="match-plan-weight-input w-full rounded-lg border border-slate-700 bg-slate-950 px-1.5 py-1 text-center font-mono text-white placeholder:text-transparent"
                    @input="updateAttempt(index, lift, key, $event.target.value)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <button
        v-if="!singleScenario"
        type="button"
        class="w-full rounded-xl border border-dashed border-slate-600 py-2 text-sm font-medium text-slate-300 hover:border-rose-500/40 hover:text-rose-200"
        :class="compact ? 'py-1.5 text-xs' : 'py-2.5'"
        @click="addScenario"
      >
        {{ t('programBuilder.matchPlan.addScenario') }}
      </button>
    </template>

    <!-- Barres d'échauffement -->
    <div
      class="rounded-xl border border-slate-800 bg-slate-950/40"
      :class="compact ? 'p-3' : 'p-4'"
    >
      <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">
          {{ t('programBuilder.matchPlan.warmupsTitle') }}
        </h3>
        <p class="text-[11px] text-slate-500">{{ t('programBuilder.matchPlan.warmupsHint') }}</p>
      </div>

      <div class="mt-3 space-y-2.5">
        <div
          v-for="lift in LIFTS"
          :key="`wu-${lift}`"
          class="flex flex-col gap-1.5 sm:flex-row sm:items-center sm:gap-3"
        >
          <span class="w-24 shrink-0 text-xs font-medium text-slate-300">{{ LIFT_LABELS[lift] }}</span>
          <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5">
            <div
              v-for="(slot, index) in warmupSlots(lift)"
              :key="`${lift}-${index}`"
              class="inline-flex items-center gap-0.5 rounded-lg border border-slate-700 bg-slate-950 px-1 py-0.5"
            >
              <input
                :value="slot.weight"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                class="match-plan-weight-input w-11 bg-transparent py-0.5 text-center font-mono text-xs text-white sm:w-12"
                :placeholder="index === 0 ? 'kg' : ''"
                :aria-label="t('programBuilder.matchPlan.warmupWeight')"
                @input="onWarmupWeight(lift, index, $event.target.value)"
              />
              <span class="text-[10px] text-slate-500">×</span>
              <input
                :value="slot.reps"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                class="match-plan-weight-input w-7 bg-transparent py-0.5 text-center font-mono text-xs text-white sm:w-8"
                :placeholder="index === 0 ? t('programBuilder.matchPlan.repsShort') : ''"
                :aria-label="t('programBuilder.matchPlan.warmupReps')"
                @input="onWarmupReps(lift, index, $event.target.value)"
              />
            </div>
            <button
              v-if="canAddWarmupBar(lift)"
              type="button"
              class="inline-flex h-7 w-7 items-center justify-center rounded-md text-base leading-none text-slate-500 transition hover:bg-slate-800/80 hover:text-slate-300"
              :aria-label="t('programBuilder.matchPlan.addWarmupBar')"
              @click="addWarmupBar(lift)"
            >
              +
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.match-plan-weight-input {
  -moz-appearance: textfield;
  appearance: textfield;
}

.match-plan-weight-input::-webkit-outer-spin-button,
.match-plan-weight-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
