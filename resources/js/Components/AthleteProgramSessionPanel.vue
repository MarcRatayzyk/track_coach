<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatCalendarFr } from '../utils/formatDates';
import { localizedExerciseName } from '../utils/exerciseNames';
import {
  formatLineRecap,
  sessionDayOrdinalInWeek,
  validatedSetsFromLoggedItems,
} from '../utils/programBuilder';

const { t } = useI18n();

const props = defineProps({
  programBlock: {
    type: Object,
    required: true,
  },
  selectedCell: {
    type: Object,
    default: null,
  },
  session: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const headerTitle = computed(() => {
  const week = props.selectedCell.weekNumber;
  const dayNum = sessionDayOrdinalInWeek(
    props.programBlock.sessions,
    week,
    props.selectedCell.weekday,
  );
  const label = props.session?.session_label?.trim();

  if (label) {
    return t('athleteUi.programSession.weekDayLabelNamed', { week, day: dayNum, label });
  }

  return t('athleteUi.programSession.weekDayLabel', { week, day: dayNum });
});

const resolvedWarmup = computed(() => props.session?.warmup ?? null);

const hasWarmup = computed(() => {
  const warmup = resolvedWarmup.value;
  if (!warmup) {
    return false;
  }
  return Boolean(String(warmup.notes ?? '').trim()) || (warmup.items?.length ?? 0) > 0;
});

function normalizeName(value) {
  return String(value ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
}

function numberOrNull(value) {
  if (value == null || value === '') {
    return null;
  }
  const parsed = Number(String(value).replace(',', '.'));
  return Number.isNaN(parsed) ? null : parsed;
}

function plannedSets(line) {
  const scheme = line?.set_scheme ?? 'standard';
  if (scheme === 'ramp') {
    const steps = line?.scheme_config?.steps ?? [];
    if (steps.length) {
      return steps.map((step) => ({
        reps: numberOrNull(step.reps),
        load: numberOrNull(step.load),
        load_percent: numberOrNull(step.load_percent),
        rpe: numberOrNull(step.rpe),
      }));
    }
  }

  const count = scheme === 'cluster' ? 1 : Math.max(1, Number(line?.sets ?? 1) || 1);
  const reps = scheme === 'cluster'
    ? numberOrNull(line?.scheme_config?.reps ?? line?.reps)
    : numberOrNull(line?.reps);

  return Array.from({ length: count }, () => ({
    reps,
    load: numberOrNull(line?.load),
    load_percent: numberOrNull(line?.load_percent),
    rpe: numberOrNull(line?.rpe),
  }));
}

function formatSet(set) {
  if (!set) {
    return '—';
  }
  const reps = set.reps != null ? String(set.reps) : '—';
  if (set.load != null) {
    return `${reps} × ${set.load} kg`;
  }
  if (set.load_percent != null) {
    return `${reps} × ${set.load_percent}%`;
  }
  if (set.rpe != null) {
    return `${reps} × RPE ${set.rpe}`;
  }
  return reps;
}

function sameValue(left, right) {
  if (left == null && right == null) {
    return true;
  }
  if (left == null || right == null) {
    return false;
  }
  return Math.abs(Number(left) - Number(right)) < 0.051;
}

function setsMatch(planned, actual) {
  if (!planned || !actual) {
    return false;
  }
  if (!sameValue(planned.reps, actual.reps)) {
    return false;
  }
  if (planned.load != null || actual.load != null) {
    return sameValue(planned.load, actual.load);
  }
  if (planned.load_percent != null || actual.load_percent != null) {
    return sameValue(planned.load_percent, actual.load_percent);
  }
  if (planned.rpe != null || actual.rpe != null) {
    return sameValue(planned.rpe, actual.rpe);
  }
  return true;
}

const comparisonRows = computed(() => {
  const order = { topset: 0, backoff: 1, accessory: 2 };
  const plannedItems = [...(props.session?.items ?? [])]
    .filter((item) => (item.section ?? item.line?.section) !== 'warmup')
    .sort((a, b) => (order[a.section] ?? 9) - (order[b.section] ?? 9));

  const date = props.selectedCell?.date;
  const loggedItems = (props.programBlock?.training_sessions ?? [])
    .filter((entry) => entry.session_date === date)
    .flatMap((entry) => entry.items ?? []);
  const used = new Set();

  function takeActualSets(line) {
    const name = normalizeName(line?.exercise_name);
    const matches = [];
    loggedItems.forEach((item, index) => {
      if (used.has(index)) {
        return;
      }
      const loggedLine = item?.line ?? item;
      if (normalizeName(loggedLine?.exercise_name) !== name) {
        return;
      }
      used.add(index);
      matches.push(item);
    });
    return validatedSetsFromLoggedItems(matches);
  }

  const rows = [];

  plannedItems.forEach((item) => {
    const line = item?.line ?? item;
    const planned = plannedSets(line);
    const actual = takeActualSets(line);
    const count = Math.max(planned.length, actual.length, 1);
    const exercise = localizedExerciseName(line?.exercise_name) || line?.exercise_name || '—';

    for (let index = 0; index < count; index += 1) {
      const plannedSet = planned[index] ?? null;
      const actualSet = actual[index] ?? null;
      rows.push({
        key: `${exercise}-${index}-${rows.length}`,
        exercise: index === 0 ? exercise : '',
        setNumber: index + 1,
        plannedLabel: formatSet(plannedSet),
        actualLabel: formatSet(actualSet),
        match: setsMatch(plannedSet, actualSet),
      });
    }
  });

  loggedItems.forEach((item, index) => {
    if (used.has(index)) {
      return;
    }
    const line = item?.line ?? item;
    const actual = validatedSetsFromLoggedItems([item]);
    const exercise = localizedExerciseName(line?.exercise_name) || line?.exercise_name || '—';
    actual.forEach((set, setIndex) => {
      rows.push({
        key: `extra-${index}-${setIndex}`,
        exercise: setIndex === 0 ? exercise : '',
        setNumber: setIndex + 1,
        plannedLabel: '—',
        actualLabel: formatSet(set),
        match: false,
      });
    });
  });

  return rows;
});
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-white">{{ headerTitle }}</h3>
        <p v-if="selectedCell?.date" class="mt-1 text-xs text-slate-500">
          {{ formatCalendarFr(selectedCell.date, 'medium') }}
        </p>
      </div>
      <button
        v-if="selectedCell"
        type="button"
        class="text-xs text-slate-500 hover:text-slate-300"
        @click="emit('close')"
      >
        {{ t('common.close') }}
      </button>
    </div>

    <div
      v-if="hasWarmup"
      class="mt-4 space-y-2 rounded-xl border border-sky-500/25 bg-sky-950/20 px-3 py-3"
    >
      <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
        {{ t('athleteUi.programSession.warmup') }}
      </p>
      <p
        v-if="resolvedWarmup.notes?.trim()"
        class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
      >
        {{ resolvedWarmup.notes }}
      </p>
      <ul v-if="resolvedWarmup.items?.length" class="space-y-1">
        <li
          v-for="(item, index) in resolvedWarmup.items"
          :key="`${item.exercise_name}-${index}`"
          class="text-sm text-slate-300"
        >
          {{ formatLineRecap(item) || item.exercise_name }}
        </li>
      </ul>
    </div>

    <div v-if="comparisonRows.length" class="mt-4 overflow-x-auto border-t border-slate-800 pt-4">
      <table class="w-full min-w-[18rem] border-collapse text-left text-xs">
        <thead>
          <tr class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.exercise') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.set') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.planned') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.done') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in comparisonRows"
            :key="row.key"
            class="border-t border-slate-800/80"
            :class="row.match ? 'bg-emerald-500/15 text-emerald-100' : 'bg-red-500/15 text-red-100'"
          >
            <td class="px-2 py-2 font-medium text-white">{{ row.exercise }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.setNumber }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.plannedLabel }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.actualLabel }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p
      v-else-if="!hasWarmup"
      class="mt-4 border-t border-slate-800 pt-4 text-sm text-slate-500"
    >
      {{ t('athleteUi.programSession.noSession') }}
    </p>
  </section>
</template>
