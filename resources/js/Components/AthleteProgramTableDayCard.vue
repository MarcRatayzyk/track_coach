<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AthleteSessionNotesModal from './AthleteSessionNotesModal.vue';
import {
  athleteColumnHeaderLabel,
  athleteSpacedColumnPercent,
  classicTableLayout,
  normalizeTableLayout,
  resolveVisibleColumns,
} from '../config/dayTableColumns';
import { sectionOption } from '../config/programTableSections';
import {
  formatRestMinutesLabel,
  formatSchemePrescription,
  plannedSetsForLine,
} from '../utils/programBuilder';

const { t } = useI18n();

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
  const base = t('athleteUi.programTableDay.dayWeek', { day: props.dayOrdinal, week: props.weekNumber });
  return sessionLabel.value ? `${base} — ${sessionLabel.value}` : base;
});

function columnStyle(column) {
  return { width: athleteSpacedColumnPercent(column.id, visibleColumns.value) };
}

function sectionLabel(section) {
  return sectionOption(section).compactLabel;
}

function cellValue(row, columnId) {
  const scheme = row.set_scheme ?? 'standard';
  const schemeText = formatSchemePrescription(row);

  switch (columnId) {
    case 'exercise':
      return row.exercise_name || '—';
    case 'main_lift':
      return row.lift ? String(row.lift).toUpperCase() : '—';
    case 'variant':
      return row.exercise_variant_id ? t('athleteUi.programTableDay.variant') : '—';
    case 'section':
      return sectionLabel(row.section);
    case 'sets':
      if (scheme === 'ramp' || scheme === 'cluster') {
        return plannedSetsForLine(row);
      }
      return row.sets ?? '—';
    case 'reps':
      if (scheme === 'cluster') {
        return row.scheme_config?.reps ?? row.reps ?? '—';
      }
      if (scheme === 'ramp') {
        return '—';
      }
      return row.reps ?? '—';
    case 'load':
      if (schemeText) {
        return schemeText;
      }
      return loadLabel(row);
    case 'rpe':
      return row.rpe != null && row.rpe !== '' ? String(row.rpe) : '—';
    case 'rest':
      return formatRestMinutesLabel(row.rest_seconds) ?? '—';
    case 'muscles':
      return row.movement_pattern || '—';
    default:
      return '—';
  }
}

function loadLabel(row) {
  if (row.load != null && row.load !== '') {
    return `${row.load} kg`;
  }
  if (row.load_percent != null && row.load_percent !== '') {
    return `${row.load_percent}%`;
  }
  if (
    row.rpe != null
    && row.rpe !== ''
    && (
      row.load_mode === 'rpe'
      || ((row.load == null || row.load === '') && (row.load_percent == null || row.load_percent === ''))
    )
  ) {
    return `RPE ${row.rpe}`;
  }
  return '—';
}

function prescriptionSummary(row) {
  const parts = [];
  const type = sectionOption(row.section).label;
  if (type) {
    parts.push(type);
  }

  const schemeText = formatSchemePrescription(row);
  if (schemeText) {
    parts.push(schemeText);
  } else {
    const sets = row.sets != null && row.sets !== '' ? row.sets : null;
    const reps = row.reps != null && row.reps !== '' ? row.reps : null;
    if (sets != null && reps != null) {
      parts.push(`${sets}×${reps}`);
    } else if (sets != null) {
      parts.push(t('athleteUi.programTableDay.setsShort', { count: sets }));
    } else if (reps != null) {
      parts.push(`${reps} reps`);
    }

    const load = loadLabel(row);
    if (load !== '—') {
      parts.push(load);
    }
  }

  if (row.rest_seconds != null && row.rest_seconds !== '') {
    const restLabel = formatRestMinutesLabel(row.rest_seconds);
    if (restLabel) {
      parts.push(restLabel);
    }
  }

  return parts.join(' · ');
}

function exerciseTitle(row) {
  if (normalizedLayout.value.exercise_mode === 'split_lift') {
    const lift = row.lift ? String(row.lift).toUpperCase() : '';
    const name = row.exercise_name || '';
    return [lift, name].filter(Boolean).join(' — ') || '—';
  }
  return row.exercise_name || '—';
}

function cellTitle(row, columnId) {
  if (columnId === 'exercise' || columnId === 'variant') {
    return cellValue(row, columnId);
  }

  if (columnId === 'section') {
    return sectionOption(row.section).label;
  }

  return undefined;
}

function isPrescriptionColumn(columnId) {
  return ['sets', 'reps', 'load', 'rpe', 'rest', 'section'].includes(columnId);
}
</script>

<template>
  <article class="overflow-hidden rounded-xl border border-slate-700 bg-slate-950">
    <div class="flex items-center gap-2 border-l-2 border-amber-400 bg-slate-950 px-3 py-2">
      <p class="min-w-0 flex-1 text-[12px] font-semibold uppercase tracking-wide text-amber-300 sm:text-sm">
        {{ t('athleteUi.programTableDay.dayWeek', { day: dayOrdinal, week: weekNumber }) }}
        <span v-if="sessionLabel" class="font-normal normal-case text-amber-200/80">
          — {{ sessionLabel }}
        </span>
      </p>
      <button
        v-if="hasSessionNotes"
        type="button"
        class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-amber-400/20 text-xs font-bold leading-none text-amber-300 hover:bg-amber-400/35"
        :title="t('athleteUi.programTableDay.viewInstructions')"
        :aria-label="t('athleteUi.programTableDay.viewSessionInstructions')"
        @click="notesModalOpen = true"
      >
        !
      </button>
    </div>

    <!-- Mobile : cartes lisibles -->
    <div v-if="rows.length" class="sm:hidden">
      <ul class="divide-y divide-slate-800">
        <li
          v-for="(row, index) in rows"
          :key="index"
          class="px-3 py-3"
        >
          <p class="text-sm font-semibold leading-snug text-white">
            {{ exerciseTitle(row) }}
          </p>
          <p class="mt-1 text-sm tabular-nums tracking-tight text-slate-300">
            {{ prescriptionSummary(row) }}
          </p>
        </li>
      </ul>
    </div>

    <!-- Desktop / tablette : tableau aéré -->
    <div v-if="rows.length" class="hidden overflow-x-auto sm:block">
      <table class="w-full min-w-[28rem] table-fixed border-collapse">
        <colgroup>
          <col
            v-for="column in visibleColumns"
            :key="`col-${column.id}`"
            :style="columnStyle(column)"
          />
        </colgroup>
        <thead class="bg-slate-950">
          <tr class="text-center text-[11px] font-semibold uppercase tracking-wide text-slate-400">
            <th
              v-for="(column, index) in visibleColumns"
              :key="column.id"
              class="border-b border-slate-800 px-2 py-2 whitespace-nowrap"
              :class="[
                index < visibleColumns.length - 1 ? 'border-r border-slate-800/70' : '',
                column.align === 'left' ? 'text-left' : 'text-center',
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
            class="border-b border-slate-800/80 text-slate-200 last:border-b-0"
          >
            <td
              v-for="(column, colIndex) in visibleColumns"
              :key="column.id"
              class="px-2 py-2.5 align-middle"
              :class="[
                colIndex < visibleColumns.length - 1 ? 'border-r border-slate-800/60' : '',
                column.align === 'left' ? 'text-left' : 'text-center',
                column.id === 'exercise' || column.id === 'variant' || column.id === 'muscles'
                  ? 'text-[13px] font-medium leading-snug text-white'
                  : '',
                isPrescriptionColumn(column.id)
                  ? 'text-sm font-semibold tabular-nums tracking-tight text-white'
                  : '',
              ]"
              :title="cellTitle(row, column.id)"
            >
              <span
                v-if="column.id === 'exercise' || column.id === 'variant' || column.id === 'muscles'"
                class="line-clamp-2"
              >
                {{ cellValue(row, column.id) }}
              </span>
              <template v-else>
                {{ cellValue(row, column.id) }}
              </template>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <p v-else class="px-3 py-6 text-center text-sm text-slate-500">
      {{ t('athleteUi.programTableDay.noExercises') }}
    </p>

    <AthleteSessionNotesModal
      :open="notesModalOpen"
      :title="notesModalTitle"
      :notes="sessionNotes"
      @close="notesModalOpen = false"
    />
  </article>
</template>
