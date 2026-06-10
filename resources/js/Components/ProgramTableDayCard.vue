<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ProgramSessionInstructionsModal from './ProgramSessionInstructionsModal.vue';
import ProgramTableExerciseRow from './ProgramTableExerciseRow.vue';
import {
  classicTableLayout,
  normalizeTableLayout,
  resolveVisibleColumns,
  spacedColumnPercent,
} from '../config/dayTableColumns';
import { programSessionVisitOptions } from '../utils/programBuilderVisit';

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
  columnHeading: {
    type: String,
    default: '',
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
  isDropTarget: {
    type: Boolean,
    default: false,
  },
  builderTab: {
    type: String,
    default: 'table',
  },
  layoutVariant: {
    type: String,
    default: 'stacked',
    validator: (value) => ['stacked', 'spaced'].includes(value),
  },
});

const emit = defineEmits(['copy-session', 'paste-session', 'delete-session', 'drag-start', 'drag-end']);

let nextRowId = 1;

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout ?? classicTableLayout()));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));

function spacedColumnStyle(column) {
  return { width: spacedColumnPercent(column.id, visibleColumns.value) };
}

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
const sessionNotes = ref('');

const form = useForm({
  week_number: props.weekNumber,
  weekday: props.weekday,
  session_label: props.defaultSessionLabel,
  notes: null,
  main_lift: 'squat',
  items: [],
  blocks: [],
  builder_tab: props.builderTab,
});

watch(
  () => props.session,
  (session) => {
    rows.value = normaliseRows(session?.items ?? []);
    sessionLabel.value = session?.session_label ?? props.defaultSessionLabel;
    sessionNotes.value = session?.notes ?? '';
  },
  { immediate: true },
);

watch(
  () => props.builderTab,
  (tab) => {
    form.builder_tab = tab;
  },
);

const populatedRows = computed(() =>
  rows.value.filter((row) => String(row.exercise_name ?? '').trim() !== ''),
);

const hasSession = computed(() => Boolean(props.session));
const sessionHeading = computed(() => sessionLabel.value?.trim() || props.defaultSessionLabel);
const hasInstructions = computed(() => Boolean(sessionNotes.value?.trim()));
const instructionsModalOpen = ref(false);

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

function parseDecimal(value) {
  if (value === '' || value === null || typeof value === 'undefined') {
    return null;
  }

  const parsed = Number(String(value).replace(',', '.'));

  return Number.isNaN(parsed) ? null : parsed;
}

function nullableNumber(value) {
  return parseDecimal(value);
}

function buildItemsPayload() {
  return populatedRows.value.map((row) => ({
    section: row.section ?? 'accessory',
    exercise_variant_id: row.exercise_variant_id ?? null,
    exercise_name: String(row.exercise_name).trim(),
    lift: row.lift ?? primaryLift.value,
    sets: Number(row.sets),
    reps: Number(row.reps),
    load: parseDecimal(row.load),
    load_percent: parseDecimal(row.load_percent),
    rpe: parseDecimal(row.rpe),
    rest_seconds: nullableNumber(row.rest_seconds),
  }));
}

function saveSession() {
  form.builder_tab = props.builderTab;
  form.week_number = props.weekNumber;
  form.weekday = props.weekday;
  form.session_label = sessionLabel.value?.trim() || props.defaultSessionLabel;
  form.notes = sessionNotes.value?.trim() || null;
  form.main_lift = primaryLift.value;
  form.items = buildItemsPayload();
  form.blocks = [];

  form.put(`/coach/program-blocks/${props.assignmentId}/sessions`, programSessionVisitOptions());
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

function openInstructionsModal() {
  instructionsModalOpen.value = true;
}

function closeInstructionsModal() {
  instructionsModalOpen.value = false;
}

function saveInstructions({ sessionLabel: label, sessionNotes: notes }) {
  sessionLabel.value = label;
  sessionNotes.value = notes;
  saveSession();
  closeInstructionsModal();
}
</script>

<template>
  <article
    class="flex w-full flex-col overflow-visible rounded-xl border border-slate-700 bg-slate-950 shadow-lg transition-all duration-200"
    :class="[
      layoutVariant === 'stacked' ? 'min-w-[28rem]' : 'min-w-0',
      isDragging ? 'shadow-none' : '',
      isDropTarget
        ? 'z-10 scale-[1.02] shadow-xl shadow-blue-900/40 ring-2 ring-blue-400 ring-offset-2 ring-offset-slate-950'
        : '',
    ]"
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
        <div class="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
          <p class="text-xs font-semibold uppercase tracking-wide text-amber-300">
            {{ sessionHeading }}
          </p>
          <button
            type="button"
            class="text-[10px] font-medium text-slate-400 hover:text-slate-200"
            :class="hasInstructions ? 'text-amber-300/80' : ''"
            @click="openInstructionsModal"
          >
            + instructions
          </button>
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

    <div class="overflow-x-auto overflow-y-visible">
      <table
        class="w-full border-collapse"
        :class="layoutVariant === 'spaced' ? 'table-fixed' : 'table-auto'"
      >
        <colgroup v-if="layoutVariant === 'spaced'">
          <col
            v-for="column in visibleColumns"
            :key="`col-${column.id}`"
            :style="spacedColumnStyle(column)"
          />
        </colgroup>
        <thead class="bg-slate-950">
          <tr class="text-center text-[11px] font-medium uppercase tracking-wide text-slate-300">
            <th
              v-for="(column, index) in visibleColumns"
              :key="column.id"
              class="border-b border-t border-slate-700 px-1.5 py-1.5"
              :class="[
                index < visibleColumns.length - 1 ? 'border-r' : '',
                layoutVariant === 'stacked' ? column.widthClass : '',
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
            :layout-variant="layoutVariant"
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

    <ProgramSessionInstructionsModal
      :open="instructionsModalOpen"
      :session-label="sessionLabel"
      :session-notes="sessionNotes"
      :default-session-label="defaultSessionLabel"
      :processing="form.processing"
      @confirm="saveInstructions"
      @cancel="closeInstructionsModal"
    />
  </article>
</template>
