<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ATTEMPT_KEYS, LIFTS, LIFT_LABELS, formatWeight } from '../utils/matchPlan';

const props = defineProps({
  mode: {
    type: String,
    required: true,
    validator: (value) => ['planned', 'live'].includes(value),
  },
  scenario: {
    type: Object,
    default: null,
  },
  liveResult: {
    type: Object,
    default: null,
  },
  href: {
    type: String,
    default: null,
  },
});

const shortLiftLabels = {
  squat: 'S',
  bench: 'B',
  deadlift: 'D',
};

const rows = computed(() => {
  if (props.mode === 'planned') {
    if (!props.scenario?.lifts) {
      return null;
    }
    return LIFTS.map((lift) => ({
      lift,
      attempts: ATTEMPT_KEYS.map((key, index) => ({
        n: index + 1,
        weight: props.scenario.lifts[lift]?.[key] ?? null,
        success: null,
        emphasize: key === 'attempt3',
      })),
    }));
  }

  if (!props.liveResult?.attempts) {
    return null;
  }

  return LIFTS.map((lift) => {
    const raw = props.liveResult.attempts[lift] ?? [];
    const byN = Object.fromEntries(raw.map((a) => [a.n, a]));
    return {
      lift,
      attempts: [1, 2, 3].map((n) => {
        const attempt = byN[n] ?? raw[n - 1] ?? null;
        return {
          n,
          weight: attempt?.weight ?? null,
          success: attempt?.success ?? null,
          emphasize: false,
        };
      }),
    };
  });
});

function attemptClass(attempt) {
  if (props.mode === 'planned') {
    return attempt.emphasize
      ? 'text-base font-extrabold text-white sm:text-lg'
      : 'text-sm font-normal text-slate-300';
  }

  if (attempt.success === true) {
    return 'text-sm font-semibold text-emerald-300';
  }
  if (attempt.success === false) {
    return 'text-sm font-normal text-rose-400 line-through';
  }
  return 'text-sm font-normal text-slate-500';
}

const shellClass =
  'inline-flex items-baseline gap-x-6 whitespace-nowrap px-1 py-0.5 font-mono tabular-nums';
</script>

<template>
  <div v-if="!rows" class="text-slate-600">—</div>
  <component
    :is="href ? Link : 'div'"
    v-else
    v-bind="href ? { href } : {}"
    :class="[
      shellClass,
      href ? 'rounded-md transition hover:bg-slate-800/60 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500' : '',
    ]"
  >
    <div
      v-for="row in rows"
      :key="row.lift"
      class="inline-flex items-baseline gap-x-2"
    >
      <span
        class="shrink-0 text-sm font-semibold text-slate-500"
        :title="LIFT_LABELS[row.lift]"
      >{{ shortLiftLabels[row.lift] }}</span>
      <span
        v-for="attempt in row.attempts"
        :key="`${row.lift}-${attempt.n}`"
        :class="attemptClass(attempt)"
      >
        <template v-if="attempt.weight != null">{{ formatWeight(attempt.weight) }}</template>
        <template v-else>—</template>
      </span>
    </div>
  </component>
</template>
