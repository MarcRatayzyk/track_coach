<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { formatCalendarFr } from '../utils/formatDates';
import { localizedExerciseName } from '../utils/exerciseNames';
import {
  formatLineRecap,
  sessionDayOrdinalInWeek,
} from '../utils/programBuilder';
import { buildComparisonRows } from '../utils/sessionComparison';

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

const comparisonRows = computed(() => {
  const order = { topset: 0, backoff: 1, accessory: 2 };
  const plannedItems = [...(props.session?.items ?? [])]
    .filter((item) => (item.section ?? item.line?.section) !== 'warmup')
    .sort((a, b) => (order[a.section ?? a.line?.section] ?? 9) - (order[b.section ?? b.line?.section] ?? 9));

  const date = props.selectedCell?.date;
  const loggedItems = (props.programBlock?.training_sessions ?? [])
    .filter((entry) => String(entry.session_date ?? '').slice(0, 10) === date)
    .flatMap((entry) => entry.items ?? []);

  return buildComparisonRows(plannedItems, loggedItems, localizedExerciseName);
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
