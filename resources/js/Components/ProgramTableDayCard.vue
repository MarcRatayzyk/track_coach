<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ProgramTableExerciseRow from './ProgramTableExerciseRow.vue';
import { classicTableLayout, normalizeTableLayout, resolveVisibleColumns } from '../config/dayTableColumns';

const props = defineProps({
  assignmentId: {
    type: Number,
    required: true,
  },
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
  defaultSessionLabel: {
    type: String,
    required: true,
  },
  tableLayout: {
    type: Object,
    default: null,
  },
  hasSessionClipboard: {
    type: Boolean,
    default: false,
  },
  isPasting: {
    type: Boolean,
    default: false,
  },
  reorderable: {
    type: Boolean,
    default: false,
  },
  isDragging: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['copy-session', 'paste-session', 'delete-session', 'drag-start', 'drag-end']);

let nextRowId = 1;

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout ?? classicTableLayout()));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));

function makeRow(overrides = {}) {
  nextRowId += 1;

  return {
    id: nextRowId,
    section: 'accessory',
    exercise_variant_id: null,
    exercise_name: '',
    lift: null,
    sets: '',
    reps: '',
    load: '',
    load_percent: '',
    rpe: '',
    rest_seconds: '',
    movement_pattern: '',
    load_mode: normalizedLayout.value.load_mode,
    ...overrides,
  };
}

function normaliseRows(items = []) {
  if (!Array.isArray(items) || items.length === 0) {
    return [makeRow({ section: 'topset' })];
  }

  return items.map((item) =>
    makeRow({
      section: item.section ?? 'accessory',
      exercise_variant_id: item.exercise_variant_id ?? null,
      exercise_name: item.exercise_name ?? '',
      lift: item.lift ?? null,
      sets: item.sets ?? '',
      reps: item.reps ?? '',
      load: item.load ?? '',
      load_percent: item.load_percent ?? '',
      rpe: item.rpe ?? '',
      rest_seconds: item.rest_seconds ?? '',
      movement_pattern: item.movement_pattern ?? '',
      load_mode: normalizedLayout.value.load_mode,
    }),
  );
}

const rows = ref([]);
const sessionLabel = ref(props.defaultSessionLabel);

const form = useForm({
  week_number: props.weekNumber,
  weekday: props.weekday,
  session_label: props.defaultSessionLabel,
  main_lift: 'squat',
  items: [],
  blocks: [],
  builder_tab: 'table',
});

watch(
  () => props.session,
  (session) => {
    rows.value = normaliseRows(session?.items ?? []);
    sessionLabel.value = session?.session_label ?? props.defaultSessionLabel;
  },
  { immediate: true },
);

const populatedRows = computed(() =>
  rows.value.filter((row) => String(row.exercise_name ?? '').trim() !== ''),
);

const sessionHeading = computed(() => sessionLabel.value?.trim() || props.defaultSessionLabel);
const hasSession = computed(() => Boolean(props.session));
const primaryLift = computed(() => {
  const firstLift = rows.value.find((row) =>
    ['squat', 'bench', 'deadlift'].includes(row.lift),
  )?.lift;

  return firstLift ?? props.session?.main_lift ?? 'squat';
});

function addRow() {
  rows.value.push(makeRow());
}

function updateRow(index, value) {
  rows.value.splice(index, 1, value);
}

function removeRow(index) {
  rows.value.splice(index, 1);
  if (rows.value.length === 0) {
    rows.value = [makeRow()];
  }
}

function nullableNumber(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return null;
  }

  const parsed = Number(value);

  return Number.isNaN(parsed) ? null : parsed;
}

function buildItemsPayload() {
  return populatedRows.value.map((row) => ({
    section: row.section ?? 'accessory',
    exercise_variant_id: row.exercise_variant_id ?? null,
    exercise_name: String(row.exercise_name).trim(),
    lift: row.lift ?? primaryLift.value,
    sets: Number(row.sets),
    reps: Number(row.reps),
    load: row.load === '' ? null : Number(row.load),
    load_percent: row.load_percent === '' ? null : Number(row.load_percent),
    rpe: row.rpe === '' ? null : Number(row.rpe),
    rest_seconds: nullableNumber(row.rest_seconds),
  }));
}

function saveSession() {
  form.week_number = props.weekNumber;
  form.weekday = props.weekday;
  form.session_label = sessionLabel.value?.trim() || props.defaultSessionLabel;
  form.main_lift = primaryLift.value;
  form.items = buildItemsPayload();
  form.blocks = [];

  form.put(`/coach/program-blocks/${props.assignmentId}/sessions`, {
    preserveScroll: true,
    preserveState: true,
  });
}

function copySession() {
  emit('copy-session', { weekNumber: props.weekNumber, weekday: props.weekday });
}

function pasteSession() {
  emit('paste-session', { weekNumber: props.weekNumber, weekday: props.weekday });
}

function deleteSession() {
  emit('delete-session', { weekNumber: props.weekNumber, weekday: props.weekday });
}
</script>

<template>
  <article
    class="flex h-full w-[28rem] shrink-0 flex-col overflow-visible rounded-xl border border-slate-700 bg-slate-950 shadow-lg transition-shadow duration-200"
    :class="isDragging ? 'shadow-none' : ''"
  >
    <div
      v-if="reorderable"
      draggable="true"
      role="button"
      tabindex="0"
      aria-label="Glisser pour réordonner ce jour"
      class="flex cursor-grab items-center justify-center gap-1.5 border-b border-slate-800 bg-slate-900/90 py-1.5 text-slate-500 transition hover:bg-slate-800/90 hover:text-slate-300 active:cursor-grabbing"
      @dragstart.stop="$emit('drag-start', $event)"
      @dragend.stop="$emit('drag-end', $event)"
    >
      <svg
        class="h-4 w-4 shrink-0"
        viewBox="0 0 16 16"
        fill="currentColor"
        aria-hidden="true"
      >
        <circle cx="5" cy="4" r="1.25" />
        <circle cx="11" cy="4" r="1.25" />
        <circle cx="5" cy="8" r="1.25" />
        <circle cx="11" cy="8" r="1.25" />
        <circle cx="5" cy="12" r="1.25" />
        <circle cx="11" cy="12" r="1.25" />
      </svg>
      <span class="text-[10px] font-medium uppercase tracking-wide">Glisser</span>
    </div>
    <div class="border-l-2 border-amber-400 bg-black px-3 py-2">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <p class="text-center text-xs font-semibold uppercase tracking-wide text-amber-300">
            {{ sessionHeading }}
          </p>
        </div>
        <div class="flex flex-wrap justify-end gap-1.5">
          <button
            type="button"
            :disabled="!hasSession"
            class="rounded-md border border-slate-600 px-2 py-1 text-[10px] text-slate-300 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
            @click="copySession"
          >
            Copier
          </button>
          <button
            type="button"
            :disabled="!hasSessionClipboard || isPasting"
            class="rounded-md border border-slate-600 px-2 py-1 text-[10px] text-slate-300 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
            @click="pasteSession"
          >
            Coller
          </button>
          <button
            type="button"
            class="rounded-md border border-red-900 px-2 py-1 text-[10px] text-red-300 hover:bg-red-950/40 disabled:cursor-not-allowed disabled:opacity-40"
            @click="deleteSession"
          >
            Supprimer le jour
          </button>
        </div>
      </div>
    </div>

    <div class="flex-1 overflow-x-hidden overflow-y-visible">
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
          <ProgramTableExerciseRow
            v-for="(row, index) in rows"
            :key="row.id"
            :row="row"
            :table-layout="normalizedLayout"
            :default-lift="primaryLift"
            :removable="rows.length > 1"
            @update="updateRow(index, $event)"
            @remove="removeRow(index)"
          />
        </tbody>
      </table>
    </div>

    <div class="border-t border-slate-800 bg-slate-950/80 px-3 py-2.5">
      <p v-if="Object.keys(form.errors).length" class="mb-3 text-xs text-red-400">
        {{ Object.values(form.errors).flat().join(' ') }}
      </p>

      <div class="flex flex-wrap justify-between gap-2">
        <button
          type="button"
          class="rounded-md border border-slate-700 px-2.5 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800"
          @click="addRow"
        >
          Ajouter une ligne
        </button>

        <button
          type="button"
          :disabled="form.processing"
          class="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          @click="saveSession"
        >
          {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
        </button>
      </div>
    </div>
  </article>
</template>
