<script setup>
import { computed } from 'vue';

const props = defineProps({
  trainingSessions: {
    type: Array,
    default: () => [],
  },
});

const trainingPrs = computed(() =>
  props.trainingSessions.reduce(
    (acc, session) => ({
      squat: Math.max(acc.squat, Number(session.squat ?? 0)),
      bench: Math.max(acc.bench, Number(session.bench ?? 0)),
      deadlift: Math.max(acc.deadlift, Number(session.deadlift ?? 0)),
    }),
    { squat: 0, bench: 0, deadlift: 0 },
  ),
);

const lifts = computed(() => [
  { key: 'squat', label: 'Squat', value: trainingPrs.value.squat },
  { key: 'bench', label: 'Bench', value: trainingPrs.value.bench },
  { key: 'deadlift', label: 'Terre', value: trainingPrs.value.deadlift },
]);

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}
</script>

<template>
  <article
    class="rounded-xl border px-3 py-2.5"
    :style="{
      borderColor: 'rgba(147, 197, 253, 0.28)',
      background: 'linear-gradient(145deg, rgba(15, 23, 42, 0.92) 0%, rgba(2, 6, 23, 0.78) 100%)',
      boxShadow: '0 0 14px rgba(147, 197, 253, 0.12)',
    }"
  >
    <p class="text-[10px] uppercase tracking-wide text-slate-500">PR à l'entraînement</p>
    <ul class="mt-2 space-y-1.5">
      <li
        v-for="lift in lifts"
        :key="lift.key"
        class="flex items-center justify-between gap-2 text-sm"
      >
        <span class="text-slate-400">{{ lift.label }}</span>
        <span class="font-semibold tabular-nums text-white">{{ formatKg(lift.value) }}</span>
      </li>
    </ul>
    <p class="mt-2 text-[10px] text-slate-500">Meilleures barres enregistrées en séance</p>
  </article>
</template>
