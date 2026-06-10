<script setup>
import { computed, ref, watch } from 'vue';
import ProgramPasteIncrementModal from './ProgramPasteIncrementModal.vue';
import ProgramTableWeekSection from './ProgramTableWeekSection.vue';
import { useProgramTableBuilder } from '../composables/useProgramTableBuilder';

const props = defineProps({
  activeBlock: {
    type: Object,
    required: true,
  },
});

function initialWeek() {
  if (typeof window === 'undefined') {
    return 1;
  }

  const week = Number(new URLSearchParams(window.location.search).get('week'));
  const weekCount = Number(props.activeBlock?.week_count ?? 0);

  if (Number.isFinite(week) && week >= 1 && week <= weekCount) {
    return week;
  }

  return 1;
}

const selectedWeek = ref(initialWeek());

const {
  weekNumbers,
  maxSessionsPerWeek,
  clipboardStatus,
  clipboardSession,
  clipboardWeek,
  pasting,
  isBusy,
  incrementModalOpen,
  incrementModalTitle,
  incrementModalHint,
  incrementModalExerciseNames,
  incrementModalPasteKind,
  incrementModalDefaultSessionLabel,
  incrementModalDefaultSessionNotes,
  sessionFor,
  headingFor,
  hasSessionsInWeek,
  copySession,
  pasteSession,
  deleteSession,
  addDay,
  copyWeek,
  pasteWeek,
  isDraggingSource,
  isDropTarget,
  onDragStart,
  onDragEnd,
  onDragOver,
  onDragLeave,
  onDrop,
  closeIncrementModal,
  confirmIncrementModal,
} = useProgramTableBuilder(() => props.activeBlock, 'table_v2');

watch(
  weekNumbers,
  (weeks) => {
    if (weeks.length === 0) {
      selectedWeek.value = 1;
      return;
    }

    if (!weeks.includes(selectedWeek.value)) {
      selectedWeek.value = weeks[0];
    }
  },
  { immediate: true },
);

watch(selectedWeek, (week) => {
  if (typeof window === 'undefined') {
    return;
  }

  const url = new URL(window.location.href);
  url.searchParams.set('week', String(week));
  window.history.replaceState({}, '', url.toString());
});

const activeWeekNumber = computed(() => selectedWeek.value);
</script>

<template>
  <section class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-white">Builder tableur V2</h2>
        <p class="mt-2 text-sm text-slate-400">
          Une semaine à la fois, jours empilés du lundi au dimanche pour des séances en pleine largeur.
        </p>
      </div>
      <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-sm text-slate-400">
        Jusqu'à
        <span class="font-medium text-white">{{ maxSessionsPerWeek }}</span>
        séance{{ maxSessionsPerWeek > 1 ? 's' : '' }} par semaine
      </div>
    </div>

    <div
      v-if="clipboardStatus"
      class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-sm text-slate-400"
    >
      <span class="font-medium text-white">{{ clipboardStatus }}</span>
      <span class="ml-2 text-slate-500">Le collage demandera des incréments (kg, %, RPE).</span>
    </div>

    <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
      <button
        v-for="weekNumber in weekNumbers"
        :key="`tab-${weekNumber}`"
        type="button"
        class="rounded-lg border px-4 py-2 text-sm font-medium transition"
        :class="
          selectedWeek === weekNumber
            ? 'border-blue-500 bg-blue-950/40 text-blue-300'
            : 'border-slate-700 text-slate-400 hover:border-slate-500 hover:text-white'
        "
        @click="selectedWeek = weekNumber"
      >
        Semaine {{ weekNumber }}
      </button>
    </div>

    <ProgramTableWeekSection
      :week-number="activeWeekNumber"
      :active-block="activeBlock"
      layout-variant="spaced"
      builder-tab="table_v2"
      :clipboard-week="clipboardWeek"
      :clipboard-session="clipboardSession"
      :pasting="pasting"
      :is-busy="isBusy"
      :session-for="sessionFor"
      :heading-for="headingFor"
      :has-sessions-in-week="hasSessionsInWeek"
      :is-dragging-source="isDraggingSource"
      :is-drop-target="isDropTarget"
      @copy-week="copyWeek"
      @paste-week="pasteWeek"
      @drag-start="onDragStart"
      @drag-end="onDragEnd"
      @drag-over="onDragOver"
      @drag-leave="onDragLeave"
      @drop="onDrop"
      @copy-session="copySession"
      @paste-session="pasteSession"
      @delete-session="deleteSession"
      @add-day="addDay"
    />

    <ProgramPasteIncrementModal
      :open="incrementModalOpen"
      :title="incrementModalTitle"
      :hint="incrementModalHint"
      :exercise-names="incrementModalExerciseNames"
      :paste-kind="incrementModalPasteKind"
      :default-session-label="incrementModalDefaultSessionLabel"
      :default-session-notes="incrementModalDefaultSessionNotes"
      @confirm="confirmIncrementModal"
      @cancel="closeIncrementModal"
    />
  </section>
</template>

<style>
body.tc-day-dragging,
body.tc-day-dragging * {
  cursor: grabbing !important;
}
</style>
