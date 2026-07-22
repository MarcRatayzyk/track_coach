<script setup>
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import { resolveOptionColor, resolveOptionLabel } from '../config/readinessFormFields';

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

const DAY_LABELS = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

const sortedFields = computed(() =>
  [...(props.fields ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
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
      label: DAY_LABELS[date.getDay()],
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
  if (!color) {
    return {};
  }
  return {
    backgroundColor: `${color}55`,
    color: '#0f172a',
    fontWeight: 600,
  };
}

function cellText(field, value) {
  if (value == null || value === '') {
    return '—';
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
              Jour
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
              class="max-w-[9rem] truncate px-2 py-2 text-slate-200"
              :style="cellStyle(field, cellValue(row, field))"
              :title="String(cellText(field, cellValue(row, field)))"
            >
              {{ cellText(field, cellValue(row, field)) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <p
      v-if="!hasAnyValue"
      class="mt-2 text-sm text-slate-500"
    >
      Aucune saisie cette semaine.
    </p>
  </div>
</template>
