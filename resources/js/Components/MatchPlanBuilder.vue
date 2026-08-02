<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  ATTEMPT_KEYS,
  ATTEMPT_LABELS,
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

function removeWarmupBar(lift, index) {
  const current = [...(warmups.value[lift] ?? [])];
  current.splice(index, 1);
  updateWarmups({ ...warmups.value, [lift]: current });
}

function warmupSlots(lift) {
  const bars = warmups.value[lift] ?? [];
  if (bars.length >= MAX_WARMUP_BARS) {
    return bars.map((v) => (v == null ? '' : String(v)));
  }
  return [...bars.map((v) => (v == null ? '' : String(v))), ''];
}

function onWarmupInput(lift, index, raw) {
  const cleaned = String(raw).trim().replace(',', '.');
  const bars = [...(warmups.value[lift] ?? [])];
  if (cleaned === '') {
    if (index < bars.length) {
      bars.splice(index, 1);
      updateWarmups({ ...warmups.value, [lift]: bars });
    }
    return;
  }
  const val = Number(cleaned);
  if (!Number.isFinite(val)) {
    return;
  }
  if (index < bars.length) {
    bars[index] = val;
  } else {
    bars.push(val);
  }
  updateWarmups({
    ...warmups.value,
    [lift]: bars.slice(0, MAX_WARMUP_BARS),
  });
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
                  {{ ATTEMPT_LABELS[key] }}
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
              class="relative"
            >
              <input
                :value="slot"
                type="text"
                inputmode="decimal"
                autocomplete="off"
                class="match-plan-weight-input w-14 rounded-lg border border-slate-700 bg-slate-950 px-1 py-1 text-center font-mono text-xs text-white sm:w-16"
                :placeholder="index === 0 ? 'kg' : ''"
                @input="onWarmupInput(lift, index, $event.target.value)"
              />
              <button
                v-if="slot !== '' && index < (warmups[lift]?.length ?? 0)"
                type="button"
                class="absolute -right-1 -top-1 hidden h-4 w-4 items-center justify-center rounded-full bg-slate-700 text-[9px] text-slate-300 hover:bg-red-600 hover:text-white sm:flex"
                :aria-label="t('common.delete')"
                @click="removeWarmupBar(lift, index)"
              >
                ×
              </button>
            </div>
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
