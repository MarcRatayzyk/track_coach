<script setup>
import { computed } from 'vue';

const props = defineProps({
  exercises: { type: Array, default: () => [] },
});

function formatLoad(metrics) {
  if (!metrics) return null;
  if (metrics.load !== null && metrics.load !== undefined && metrics.load !== '') {
    return `${metrics.load} kg`;
  }
  if (metrics.load_percent !== null && metrics.load_percent !== undefined && metrics.load_percent !== '') {
    return `${metrics.load_percent} %`;
  }
  return null;
}

function formatMetric(value) {
  if (value === null || value === undefined || value === '') return null;
  return String(value);
}

function parseNum(value) {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(String(value).replace(',', '.').replace(/[^\d.-]/g, ''));
  return Number.isFinite(n) ? n : null;
}

function title(row) {
  return row.exercise_name || 'Exercice';
}

function plannedLine(row) {
  const m = row.planned ?? row;
  const sets = formatMetric(m?.sets) ?? '—';
  const reps = formatMetric(m?.reps) ?? '—';
  const load = formatLoad(m) ?? '—';
  const rpe = formatMetric(m?.rpe) ?? '—';
  return `${sets}×${reps} · ${load} · RPE ${rpe}`;
}

function actualLine(row) {
  if (!row.actual) return null;
  const sets = formatMetric(row.actual.sets) ?? '—';
  const reps = formatMetric(row.actual.reps) ?? '—';
  const load = formatLoad(row.actual) ?? '—';
  const rpe = formatMetric(row.actual.rpe) ?? '—';
  return `${sets}×${reps} · ${load} · RPE ${rpe}`;
}

function statusFor(row) {
  if (!row.actual) {
    return { key: 'missing', icon: '❌', label: 'Non réalisé', class: 'text-rose-300 bg-rose-500/10 border-rose-500/25' };
  }

  const plannedLoad = parseNum(row.planned?.load ?? row.load);
  const actualLoad = parseNum(row.actual.load);
  const matches = row.matches || {};
  const allMatch =
    matches.sets !== false &&
    matches.reps !== false &&
    matches.load !== false &&
    matches.rpe !== false;

  if (allMatch && matches.load === true) {
    return { key: 'ok', icon: '✔', label: 'Conforme', class: 'text-emerald-300 bg-emerald-500/10 border-emerald-500/25' };
  }

  if (plannedLoad !== null && actualLoad !== null) {
    if (actualLoad > plannedLoad) {
      return { key: 'heavier', icon: '⬆', label: 'Plus lourd', class: 'text-sky-300 bg-sky-500/10 border-sky-500/25' };
    }
    if (actualLoad < plannedLoad) {
      return { key: 'lighter', icon: '⬇', label: 'Plus léger', class: 'text-amber-300 bg-amber-500/10 border-amber-500/25' };
    }
  }

  if (matches.load === false || matches.rpe === false || matches.sets === false || matches.reps === false) {
    return { key: 'diff', icon: '⚠', label: 'Écart', class: 'text-amber-300 bg-amber-500/10 border-amber-500/25' };
  }

  return { key: 'ok', icon: '✔', label: 'Conforme', class: 'text-emerald-300 bg-emerald-500/10 border-emerald-500/25' };
}

function deltaLabel(row) {
  const plannedLoad = parseNum(row.planned?.load ?? row.load);
  const actualLoad = parseNum(row.actual?.load);
  if (plannedLoad === null || actualLoad === null || plannedLoad === actualLoad) {
    const plannedRpe = parseNum(row.planned?.rpe ?? row.rpe);
    const actualRpe = parseNum(row.actual?.rpe);
    if (plannedRpe !== null && actualRpe !== null && plannedRpe !== actualRpe) {
      const d = actualRpe - plannedRpe;
      return `RPE ${d > 0 ? '+' : ''}${d}`;
    }
    return null;
  }
  const d = Math.round((actualLoad - plannedLoad) * 10) / 10;
  return `${d > 0 ? '+' : ''}${d} kg`;
}

const rows = computed(() => props.exercises || []);

const summary = computed(() => {
  let ok = 0;
  let missing = 0;
  let heavier = 0;
  let lighter = 0;
  for (const row of rows.value) {
    const s = statusFor(row);
    if (s.key === 'ok') ok += 1;
    else if (s.key === 'missing') missing += 1;
    else if (s.key === 'heavier') heavier += 1;
    else if (s.key === 'lighter') lighter += 1;
  }
  return { ok, missing, heavier, lighter, total: rows.value.length };
});
</script>

<template>
  <div v-if="rows.length" class="space-y-3">
    <div class="flex flex-wrap gap-2 text-[11px]">
      <span class="rounded-full border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-emerald-300">
        ✔ {{ summary.ok }} conforme{{ summary.ok > 1 ? 's' : '' }}
      </span>
      <span
        v-if="summary.heavier"
        class="rounded-full border border-sky-500/25 bg-sky-500/10 px-2.5 py-1 text-sky-300"
      >
        ⬆ {{ summary.heavier }} plus lourd
      </span>
      <span
        v-if="summary.lighter"
        class="rounded-full border border-amber-500/25 bg-amber-500/10 px-2.5 py-1 text-amber-300"
      >
        ⬇ {{ summary.lighter }} plus léger
      </span>
      <span
        v-if="summary.missing"
        class="rounded-full border border-rose-500/25 bg-rose-500/10 px-2.5 py-1 text-rose-300"
      >
        ❌ {{ summary.missing }} non réalisé{{ summary.missing > 1 ? 's' : '' }}
      </span>
    </div>

    <div class="overflow-hidden rounded-[18px] border border-slate-800/80 bg-slate-950/40">
      <div class="grid grid-cols-[1fr_1fr_auto] gap-2 border-b border-slate-800 px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500 sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_7.5rem]">
        <span>Exercice</span>
        <span class="hidden sm:inline">Prévu</span>
        <span class="hidden sm:inline">Réalisé</span>
        <span class="sm:hidden">Comparaison</span>
        <span class="text-right">Statut</span>
      </div>

      <ul class="divide-y divide-slate-800/80">
        <li
          v-for="(row, index) in rows"
          :key="row.id ?? index"
          class="grid grid-cols-1 gap-2 px-3 py-3 transition duration-200 hover:bg-slate-900/40 sm:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)_7.5rem] sm:items-center"
        >
          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-slate-100" :title="title(row)">{{ title(row) }}</p>
            <p v-if="deltaLabel(row)" class="mt-0.5 text-[11px] font-medium text-blue-300/90">
              {{ deltaLabel(row) }}
            </p>
          </div>

          <div class="sm:hidden">
            <p class="text-[11px] text-slate-500">Prévu · {{ plannedLine(row) }}</p>
            <p class="mt-0.5 text-[11px] text-slate-300">
              Réalisé · {{ actualLine(row) || 'Non logué' }}
            </p>
          </div>

          <p class="hidden font-mono text-xs tabular-nums text-slate-400 sm:block">
            {{ plannedLine(row) }}
          </p>
          <p
            class="hidden font-mono text-xs tabular-nums sm:block"
            :class="row.actual ? 'text-slate-200' : 'text-slate-600'"
          >
            {{ actualLine(row) || 'Non logué' }}
          </p>

          <span
            class="inline-flex w-fit items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-semibold sm:justify-self-end"
            :class="statusFor(row).class"
          >
            <span aria-hidden="true">{{ statusFor(row).icon }}</span>
            {{ statusFor(row).label }}
          </span>
        </li>
      </ul>
    </div>
  </div>
</template>
