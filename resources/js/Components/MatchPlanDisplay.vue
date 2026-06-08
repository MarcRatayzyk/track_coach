<script setup>
import { computed } from 'vue';
import {
  ATTEMPT_KEYS,
  ATTEMPT_LABELS,
  LIFTS,
  LIFT_LABELS,
  formatWeight,
  hasMatchPlanContent,
  matchPlanFromCompetition,
  scenarioTotal,
} from '../utils/matchPlan';

const props = defineProps({
  competition: {
    type: Object,
    required: true,
  },
});

const plan = computed(() => matchPlanFromCompetition(props.competition));
const hasContent = computed(() => hasMatchPlanContent(props.competition));
</script>

<template>
  <div v-if="!hasContent" class="text-slate-500">Non renseigné</div>

  <p
    v-else-if="plan.mode === 'text'"
    class="whitespace-pre-wrap rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2.5 font-mono text-xs leading-relaxed text-slate-300"
  >
    {{ plan.text }}
  </p>

  <div v-else-if="plan.mode === 'structured'" class="space-y-4">
    <div
      v-for="scenario in plan.scenarios"
      :key="scenario.id"
      class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
    >
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h4 class="text-sm font-semibold text-white">{{ scenario.name }}</h4>
        <span
          v-if="scenarioTotal(scenario) != null"
          class="rounded-lg bg-rose-500/15 px-2.5 py-1 text-xs font-medium text-rose-200"
        >
          Total 3e essais : {{ formatWeight(scenarioTotal(scenario)) }} kg
        </span>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[280px] text-left text-xs">
          <thead>
            <tr class="text-slate-500">
              <th class="pb-2 pr-3 font-medium">Mouvement</th>
              <th
                v-for="key in ATTEMPT_KEYS"
                :key="key"
                class="pb-2 px-2 text-center font-medium"
              >
                {{ ATTEMPT_LABELS[key] }}
              </th>
            </tr>
          </thead>
          <tbody class="text-slate-200">
            <tr v-for="lift in LIFTS" :key="lift" class="border-t border-slate-800/80">
              <td class="py-2 pr-3 font-medium text-slate-300">{{ LIFT_LABELS[lift] }}</td>
              <td
                v-for="key in ATTEMPT_KEYS"
                :key="key"
                class="px-2 py-2 text-center font-mono"
              >
                <template v-if="scenario.lifts[lift][key] != null">
                  {{ formatWeight(scenario.lifts[lift][key]) }}
                  <span class="text-slate-500">kg</span>
                </template>
                <span v-else class="text-slate-600">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
