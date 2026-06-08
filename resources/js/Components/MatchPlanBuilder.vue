<script setup>
import { computed } from 'vue';
import {
  ATTEMPT_KEYS,
  ATTEMPT_LABELS,
  LIFTS,
  LIFT_LABELS,
  defaultStructuredPlan,
  emptyScenario,
  formatWeight,
  normalizePlan,
  scenarioTotal,
} from '../utils/matchPlan';

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

function updateScenarios(scenarios) {
  plan.value = { mode: 'structured', scenarios };
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
  updateScenarios([
    ...plan.value.scenarios,
    emptyScenario(`Scénario ${plan.value.scenarios.length + 1}`),
  ]);
}

function removeScenario(index) {
  if (plan.value.scenarios.length <= 1) {
    return;
  }
  updateScenarios(plan.value.scenarios.filter((_, i) => i !== index));
}

function convertLegacyToStructured() {
  plan.value = defaultStructuredPlan();
}
</script>

<template>
  <div class="space-y-4">
    <template v-if="isLegacyText">
      <p class="text-xs text-slate-500">
        Ancien plan en texte. Tu peux le conserver tel quel ou passer au format structuré.
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
        Passer au plan structuré
      </button>
    </template>

    <template v-else>
      <div
        v-for="(scenario, index) in plan.scenarios"
        :key="scenario.id"
        class="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <label class="min-w-0 flex-1 text-sm text-slate-400">
            Nom du scénario
            <input
              :value="scenario.name"
              type="text"
              class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              placeholder="Ex. Objectif total, Plan B…"
              @input="updateScenarioName(index, $event.target.value)"
            />
          </label>
          <div class="flex items-center gap-2 pt-6">
            <span
              v-if="scenarioTotal(scenario) != null"
              class="rounded-lg bg-rose-500/15 px-2 py-1 text-xs text-rose-200"
            >
              {{ formatWeight(scenarioTotal(scenario)) }} kg
            </span>
            <button
              v-if="plan.scenarios.length > 1"
              type="button"
              class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-red-500/50 hover:text-red-300"
              @click="removeScenario(index)"
            >
              Supprimer
            </button>
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[300px] text-xs">
            <thead>
              <tr class="text-slate-500">
                <th class="pb-2 pr-2 text-left font-medium">Mouvement</th>
                <th
                  v-for="key in ATTEMPT_KEYS"
                  :key="key"
                  class="pb-2 px-1 text-center font-medium"
                >
                  {{ ATTEMPT_LABELS[key] }}
                </th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="lift in LIFTS" :key="lift" class="border-t border-slate-800/80">
                <td class="py-2 pr-2 font-medium text-slate-300">{{ LIFT_LABELS[lift] }}</td>
                <td v-for="key in ATTEMPT_KEYS" :key="key" class="px-1 py-2">
                  <input
                    :value="scenario.lifts[lift][key] ?? ''"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    class="match-plan-weight-input w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-center font-mono text-white placeholder:text-transparent"
                    @input="updateAttempt(index, lift, key, $event.target.value)"
                  />
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <button
        type="button"
        class="w-full rounded-xl border border-dashed border-slate-600 py-2.5 text-sm font-medium text-slate-300 hover:border-rose-500/40 hover:text-rose-200"
        @click="addScenario"
      >
        + Ajouter un scénario
      </button>
    </template>
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
