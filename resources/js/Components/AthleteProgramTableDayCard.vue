<script setup>
import { computed } from 'vue';
import { classicTableLayout, normalizeTableLayout, resolveVisibleColumns } from '../config/dayTableColumns';
import { PROGRAM_TABLE_SECTIONS } from '../config/programTableSections';
import { formatPrescription } from '../utils/programBuilder';

const props = defineProps({
  weekNumber: {
    type: Number,
    required: true,
  },
  weekday: {
    type: Number,
    required: true,
  },
  session: {
    type: Object,
    default: null,
  },
  tableLayout: {
    type: Object,
    default: null,
  },
});

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout ?? classicTableLayout()));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));

const sessionHeading = computed(() => {
  const label = props.session?.session_label?.trim();
  if (label) {
    return label;
  }
  return `Jour ${props.weekday}`;
});

const rows = computed(() => {
  const items = props.session?.items ?? [];
  if (!items.length) {
    return [];
  }
  return items;
});

const sectionLabel = (section) =>
  PROGRAM_TABLE_SECTIONS.find((item) => item.value === section)?.label ?? section;

function cellValue(row, columnId) {
  switch (columnId) {
    case 'exercise':
      return row.exercise_name || '—';
    case 'main_lift':
      return row.lift ? String(row.lift).toUpperCase() : '—';
    case 'variant':
      return row.exercise_variant_id ? 'Variante' : '—';
    case 'section':
      return sectionLabel(row.section);
    case 'sets':
      return row.sets ?? '—';
    case 'reps':
      return row.reps ?? '—';
    case 'load':
      if (row.load != null && row.load !== '') {
        return `${row.load} kg`;
      }
      if (row.load_percent != null && row.load_percent !== '') {
        return `${row.load_percent}%`;
      }
      if (row.rpe != null && row.rpe !== '') {
        return `RPE ${row.rpe}`;
      }
      return '—';
    case 'rest':
      return row.rest_seconds != null ? `${row.rest_seconds}s` : '—';
    case 'muscles':
      return row.movement_pattern || '—';
    default:
      return '—';
  }
}
</script>

<template>
  <article
    class="flex h-full w-[28rem] shrink-0 flex-col overflow-hidden rounded-xl border border-slate-700 bg-slate-950 shadow-lg"
  >
    <div class="border-l-2 border-amber-400 bg-black px-3 py-2">
      <p class="text-center text-xs font-semibold uppercase tracking-wide text-amber-300">
        S{{ weekNumber }} · {{ sessionHeading }}
      </p>
    </div>

    <div v-if="rows.length" class="flex-1 overflow-x-auto">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-slate-950">
          <tr class="text-center text-[11px] font-medium uppercase tracking-wide text-slate-300">
            <th
              v-for="(column, index) in visibleColumns"
              :key="column.id"
              class="border-b border-t border-slate-700 px-1.5 py-1.5"
              :class="[
                index < visibleColumns.length - 1 ? 'border-r' : '',
                column.widthClass,
                column.align === 'left' ? 'text-left px-2' : 'text-center',
              ]"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, index) in rows"
            :key="index"
            class="border-b border-slate-800 text-xs text-slate-200"
          >
            <td
              v-for="(column, colIndex) in visibleColumns"
              :key="column.id"
              class="px-1.5 py-2"
              :class="[
                colIndex < visibleColumns.length - 1 ? 'border-r border-slate-800' : '',
                column.align === 'left' ? 'text-left px-2' : 'text-center',
              ]"
            >
              {{ cellValue(row, column.id) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="flex flex-1 items-center justify-center px-4 py-8 text-center text-xs text-slate-500">
      Aucun exercice programmé.
    </div>

    <div
      v-if="rows.length === 1 && session"
      class="border-t border-slate-800 bg-slate-950/80 px-3 py-2 text-xs text-slate-400"
    >
      {{ formatPrescription(rows[0]) }}
    </div>
  </article>
</template>
