<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../i18n';
import { formatCalendarFr } from '../utils/formatDates';
import { localizeReadinessFields, resolveOptionColor, resolveOptionLabel } from '../config/readinessFormFields';

const { t, locale } = useI18n();

const props = defineProps({
  fields: {
    type: Array,
    default: () => [],
  },
  entries: {
    type: Array,
    default: () => [],
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const DAY_LABELS = computed(() => [
  t('athleteUi.readiness.days.sun'),
  t('athleteUi.readiness.days.mon'),
  t('athleteUi.readiness.days.tue'),
  t('athleteUi.readiness.days.wed'),
  t('athleteUi.readiness.days.thu'),
  t('athleteUi.readiness.days.fri'),
  t('athleteUi.readiness.days.sat'),
]);

const sortedFields = computed(() =>
  localizeReadinessFields(props.fields)
    .slice()
    .sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
);

/** Semaine en cours (lundi → dimanche). */
const dayRows = computed(() => {
  const byDate = new Map(
    (props.entries ?? []).map((entry) => [entry.entry_date, entry]),
  );

  const today = new Date();
  today.setHours(12, 0, 0, 0);

  const day = today.getDay();
  const mondayOffset = day === 0 ? -6 : 1 - day;
  const monday = new Date(today);
  monday.setDate(today.getDate() + mondayOffset);

  const rows = [];
  for (let i = 0; i < 7; i += 1) {
    const date = new Date(monday);
    date.setDate(monday.getDate() + i);
    const iso = date.toISOString().slice(0, 10);
    rows.push({
      date: iso,
      label: DAY_LABELS.value[date.getDay()],
      fullLabel: formatCalendarFr(iso, 'medium'),
      entry: byDate.get(iso) ?? null,
    });
  }

  return rows;
});

function cellValue(row, field) {
  const values = row.entry?.values ?? {};
  return values[field.id] ?? null;
}

function cellStyle(field, value) {
  const color = resolveOptionColor(field, value);
  if (!color || value == null || value === '') {
    return {};
  }
  return {
    color,
    fontWeight: 600,
  };
}

function cellDotStyle(field, value) {
  const color = resolveOptionColor(field, value);
  if (!color || value == null || value === '') {
    return null;
  }
  return { backgroundColor: color };
}

function cellText(field, value) {
  if (value == null || value === '') {
    return '—';
  }
  if (typeof value === 'number' || (typeof value === 'string' && value !== '' && Number.isFinite(Number(value)))) {
    const n = Number(value);
    if (Number.isFinite(n) && String(value).match(/^\d+([.,]\d+)?$/)) {
      return (Math.round(n * 100) / 100).toLocaleString(localeTag(locale.value), {
        maximumFractionDigits: 2,
      });
    }
  }
  return resolveOptionLabel(field, value);
}
const hasAnyValue = computed(() =>
  dayRows.value.some((row) => row.entry != null),
);
</script>

<template>
  <div :class="embedded ? '' : 'rounded-xl border border-slate-800 bg-slate-950/60 p-3'">
    <div class="overflow-x-auto">
      <table class="min-w-full border-collapse text-left text-xs">
        <thead>
          <tr class="border-b border-slate-800 text-slate-400">
            <th class="sticky left-0 z-10 bg-slate-900/95 px-2 py-2 font-semibold uppercase tracking-wide">
              {{ t('athleteUi.readiness.day') }}
            </th>
            <th
              v-for="field in sortedFields"
              :key="field.id"
              class="whitespace-nowrap px-2 py-2 font-semibold uppercase tracking-wide"
            >
              {{ field.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in dayRows"
            :key="row.date"
            class="border-b border-slate-800/80"
          >
            <td class="sticky left-0 z-10 whitespace-nowrap bg-slate-900/95 px-2 py-2 font-medium text-slate-200">
              <span class="block">{{ row.label }}</span>
              <span class="text-[10px] text-slate-500">{{ row.fullLabel }}</span>
            </td>
            <td
              v-for="field in sortedFields"
              :key="`${row.date}-${field.id}`"
              class="max-w-[10rem] px-2 py-2"
              :title="String(cellText(field, cellValue(row, field)))"
            >
              <span
                v-if="cellValue(row, field) != null && cellValue(row, field) !== ''"
                class="inline-flex max-w-full items-center gap-1.5"
              >
                <span
                  v-if="cellDotStyle(field, cellValue(row, field))"
                  class="h-2 w-2 shrink-0 rounded-full"
                  :style="cellDotStyle(field, cellValue(row, field))"
                  aria-hidden="true"
                />
                <span class="truncate" :style="cellStyle(field, cellValue(row, field))">
                  {{ cellText(field, cellValue(row, field)) }}
                </span>
              </span>
              <span v-else class="text-slate-600">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p
      v-if="!hasAnyValue"
      class="mt-2 text-sm text-slate-500"
    >
      {{ t('athleteUi.readiness.noEntryWeek') }}
    </p>
  </div>
</template>
