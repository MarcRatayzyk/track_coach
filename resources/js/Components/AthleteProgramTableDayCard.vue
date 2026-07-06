<script setup>
import { computed, ref } from 'vue';
import AthleteSessionNotesModal from './AthleteSessionNotesModal.vue';
import {
  athleteColumnHeaderLabel,
  athleteSpacedColumnPercent,
  classicTableLayout,
  normalizeTableLayout,
  resolveVisibleColumns,
} from '../config/dayTableColumns';
import { sectionOption } from '../config/programTableSections';

const props = defineProps({
  weekNumber: {
    type: Number,
    required: true,
  },
  weekday: {
    type: Number,
    required: true,
  },
  dayOrdinal: {
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

const notesModalOpen = ref(false);

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout ?? classicTableLayout()));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));

const sessionLabel = computed(() => props.session?.session_label?.trim() ?? '');
const sessionNotes = computed(() => props.session?.notes?.trim() ?? '');
const hasSessionNotes = computed(() => sessionNotes.value.length > 0);

const rows = computed(() => props.session?.items ?? []);

const notesModalTitle = computed(() => {
  const base = `Jour ${props.dayOrdinal} · S${props.weekNumber}`;
  return sessionLabel.value ? `${base} — ${sessionLabel.value}` : base;
});

function columnStyle(column) {
  return { width: athleteSpacedColumnPercent(column.id, visibleColumns.value) };
}

function sectionLabel(section) {
  return sectionOption(section).compactLabel;
}

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

function cellTitle(row, columnId) {
  if (columnId === 'exercise') {
    return cellValue(row, columnId);
  }

  if (columnId === 'section') {
    return sectionOption(row.section).label;
  }

  return undefined;
}
</script>

<template>
  <article class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
    <div class="border-l-2 border-amber-400 bg-black px-2.5 py-1.5 sm:px-3 sm:py-2">
      <p class="text-center text-[11px] font-semibold uppercase tracking-wide text-amber-300 sm:text-xs">
        Jour {{ dayOrdinal }} · S{{ weekNumber }}
        <span v-if="sessionLabel" class="font-normal normal-case text-amber-200/80">
          — {{ sessionLabel }}
        </span>
      </p>
    </div>

    <div v-if="rows.length" class="overflow-hidden">
      <table class="w-full table-fixed border-collapse">
        <colgroup>
          <col v-if="hasSessionNotes" style="width: 1.5rem" />
          <col
            v-for="column in visibleColumns"
            :key="`col-${column.id}`"
            :style="columnStyle(column)"
          />
        </colgroup>
        <thead class="bg-slate-950">
          <tr class="text-center text-[9px] font-medium uppercase tracking-wide text-slate-300 sm:text-[10px]">
            <th
              v-if="hasSessionNotes"
              class="border-b border-t border-r border-slate-700 px-0 py-1"
              aria-label="Instructions"
            />
            <th
              v-for="(column, index) in visibleColumns"
              :key="column.id"
              class="border-b border-t border-slate-700 px-0.5 py-1 whitespace-nowrap sm:px-1 sm:py-1.5"
              :class="[
                index < visibleColumns.length - 1 ? 'border-r' : '',
                column.align === 'left' ? 'text-left' : 'text-center',
                column.id === 'exercise' || column.id === 'variant' || column.id === 'muscles'
                  ? 'text-[8px] sm:text-[9px]'
                  : 'text-[9px] sm:text-[10px]',
              ]"
            >
              {{ athleteColumnHeaderLabel(column.id, column.label) }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(row, index) in rows"
            :key="index"
            class="border-b border-slate-800 text-[10px] text-slate-200 sm:text-xs"
          >
            <td
              v-if="hasSessionNotes"
              class="border-r border-slate-800 px-0 py-1 text-center align-middle"
            >
              <button
                v-if="index === 0"
                type="button"
                class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-400/20 text-[10px] font-bold leading-none text-amber-300 hover:bg-amber-400/35"
                title="Voir les instructions"
                aria-label="Voir les instructions de séance"
                @click="notesModalOpen = true"
              >
                !
              </button>
            </td>
            <td
              v-for="(column, colIndex) in visibleColumns"
              :key="column.id"
              class="px-0.5 py-1.5 sm:px-1 sm:py-2"
              :class="[
                colIndex < visibleColumns.length - 1 ? 'border-r border-slate-800' : '',
                column.align === 'left' ? 'text-left' : 'text-center',
                column.id === 'exercise' || column.id === 'variant' || column.id === 'muscles'
                  ? 'max-w-[5.5rem] truncate text-[9px] sm:max-w-none sm:text-[10px]'
                  : '',
                column.id === 'section' || column.id === 'sets' ? 'text-[9px] sm:text-[10px]' : '',
              ]"
              :title="cellTitle(row, column.id)"
            >
              {{ cellValue(row, column.id) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else class="px-3 py-6 text-center text-[11px] text-slate-500 sm:text-xs">
      Aucun exercice programmé.
    </p>

    <AthleteSessionNotesModal
      :open="notesModalOpen"
      :title="notesModalTitle"
      :notes="sessionNotes"
      @close="notesModalOpen = false"
    />
  </article>
</template>
