<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  onboarding: {
    type: Object,
    default: () => ({ steps: [], completed_count: 0, total: 0 }),
  },
});

const steps = computed(() => props.onboarding?.steps ?? []);
const completed = computed(() => props.onboarding?.completed_count ?? 0);
const total = computed(() => props.onboarding?.total ?? steps.value.length);
const allDone = computed(() => total.value > 0 && completed.value >= total.value);
const progressPercent = computed(() =>
  total.value === 0 ? 0 : Math.round((completed.value / total.value) * 100),
);
</script>

<template>
  <section
    v-if="!allDone"
    class="rounded-2xl border border-blue-500/30 bg-blue-950/20 p-5 sm:p-6"
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-white">Premiers pas avec Power Roster</h2>
        <p class="mt-1 text-sm text-slate-400">
          Termine ces étapes pour tirer le meilleur de ton espace coach.
        </p>
      </div>
      <span class="text-sm font-semibold text-blue-300">{{ completed }}/{{ total }}</span>
    </div>

    <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-slate-800">
      <div
        class="h-full rounded-full bg-blue-500 transition-all"
        :style="{ width: `${progressPercent}%` }"
      />
    </div>

    <ul class="mt-5 space-y-2">
      <li
        v-for="step in steps"
        :key="step.key"
        class="flex items-center gap-3 rounded-xl border border-slate-700/60 bg-slate-900/40 px-4 py-3"
      >
        <span
          class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
          :class="step.done ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700/60 text-slate-400'"
        >
          <UiIcon v-if="step.done" name="arrow-trend-up" class="h-3.5 w-3.5" />
          <span v-else class="text-xs font-bold">•</span>
        </span>

        <div class="min-w-0 flex-1">
          <p class="text-sm font-medium" :class="step.done ? 'text-slate-400 line-through' : 'text-white'">
            {{ step.label }}
          </p>
          <p class="truncate text-xs text-slate-500">{{ step.description }}</p>
        </div>

        <Link
          v-if="!step.done"
          :href="step.href"
          class="shrink-0 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-500"
        >
          Commencer
        </Link>
      </li>
    </ul>
  </section>
</template>
