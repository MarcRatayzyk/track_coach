<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

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
  return row.exercise_name || t('app.feedbacks.exercise');
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
    return {
      key: 'missing',
      icon: '❌',
      label: t('app.feedbacks.notDone'),
      class: 'text-rose-300 bg-rose-500/10 border-rose-500/25',
    };
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
    return {
      key: 'ok',
      icon: '✔',
      label: t('app.feedbacks.conforme', 1),
      class: 'text-emerald-300 bg-emerald-500/10 border-emerald-500/25',
    };
  }

  if (plannedLoad !== null && actualLoad !== null) {
    if (actualLoad > plannedLoad) {
      return {
        key: 'heavier',
        icon: '⬆',
        label: t('app.feedbacks.heavier'),
        class: 'text-sky-300 bg-sky-500/10 border-sky-500/25',
      };
    }
    if (actualLoad < plannedLoad) {
      return {
        key: 'lighter',
        icon: '⬇',
        label: t('app.feedbacks.lighter'),
        class: 'text-amber-300 bg-amber-500/10 border-amber-500/25',
      };
    }
  }

  if (matches.load === false || matches.rpe === false || matches.sets === false || matches.reps === false) {
    return {
      key: 'diff',
      icon: '⚠',
      label: t('app.feedbacks.diff'),
      class: 'text-amber-300 bg-amber-500/10 border-amber-500/25',
    };
  }

  return {
    key: 'ok',
    icon: '✔',
    label: t('app.feedbacks.conforme', 1),
    class: 'text-emerald-300 bg-emerald-500/10 border-emerald-500/25',
  };
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
        ✔ {{ t('app.feedbacks.conformeCount', summary.ok, { n: summary.ok }) }}
      </span>
      <span
        v-if="summary.heavier"
        class="rounded-full border border-sky-500/25 bg-sky-500/10 px-2.5 py-1 text-sky-300"
      >
        ⬆ {{ t('app.feedbacks.heavierCount', summary.heavier, { n: summary.heavier }) }}
      </span>
      <span
        v-if="summary.lighter"
        class="rounded-full border border-amber-500/25 bg-amber-500/10 px-2.5 py-1 text-amber-300"
      >
        ⬇ {{ t('app.feedbacks.lighterCount', summary.lighter, { n: summary.lighter }) }}
      </span>
      <span
        v-if="summary.missing"
        class="rounded-full border border-rose-500/25 bg-rose-500/10 px-2.5 py-1 text-rose-300"
      >
        ❌ {{ t('app.feedbacks.notDoneCount', summary.missing, { n: summary.missing }) }}
      </span>
    </div>

    <div class="overflow-x-auto rounded-[18px] border border-slate-800/80 bg-slate-950/40 [scrollbar-width:thin]">
      <div class="min-w-[32rem]">
        <div
          class="grid grid-cols-[minmax(4.5rem,0.65fr)_minmax(0,1.25fr)_minmax(0,1.25fr)_5.25rem] gap-2 border-b border-slate-800 px-3 py-2 text-[10px] font-semibold uppercase tracking-wide text-slate-500"
        >
          <span>{{ t('app.feedbacks.exercise') }}</span>
          <span>{{ t('app.feedbacks.planned') }}</span>
          <span>{{ t('app.feedbacks.actual') }}</span>
          <span class="text-right">{{ t('app.feedbacks.status') }}</span>
        </div>

        <ul class="divide-y divide-slate-800/80">
          <li
            v-for="(row, index) in rows"
            :key="row.id ?? index"
            class="grid grid-cols-[minmax(4.5rem,0.65fr)_minmax(0,1.25fr)_minmax(0,1.25fr)_5.25rem] items-center gap-2 px-3 py-2.5 transition duration-200 hover:bg-slate-900/40"
          >
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-slate-100" :title="title(row)">{{ title(row) }}</p>
              <p v-if="deltaLabel(row)" class="mt-0.5 truncate text-[11px] font-medium text-blue-300/90">
                {{ deltaLabel(row) }}
              </p>
            </div>

            <p class="min-w-0 truncate whitespace-nowrap font-mono text-[11px] tabular-nums text-slate-400">
              {{ plannedLine(row) }}
            </p>
            <p
              class="min-w-0 truncate whitespace-nowrap font-mono text-[11px] tabular-nums"
              :class="row.actual ? 'text-slate-200' : 'text-slate-600'"
            >
              {{ actualLine(row) || t('app.feedbacks.notLogged') }}
            </p>

            <span
              class="inline-flex w-fit max-w-full items-center gap-1 justify-self-end truncate rounded-full border px-1.5 py-0.5 text-[10px] font-semibold"
              :class="statusFor(row).class"
            >
              <span aria-hidden="true">{{ statusFor(row).icon }}</span>
              {{ statusFor(row).label }}
            </span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>
