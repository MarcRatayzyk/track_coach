<script setup>
import ProgramPasteIncrementModal from './ProgramPasteIncrementModal.vue';
import ProgramTableWeekSection from './ProgramTableWeekSection.vue';
import { useProgramTableBuilder } from '../composables/useProgramTableBuilder';

const props = defineProps({
  activeBlock: {
    type: Object,
    required: true,
  },
});

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
} = useProgramTableBuilder(() => props.activeBlock, 'table');
</script>

<template>
  <section class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-white">Builder tableur</h2>
        <p class="mt-2 text-sm text-slate-400">
          7 colonnes = une semaine calendaire. Clique sur Repos pour ajouter une séance.
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

    <div class="space-y-8">
      <ProgramTableWeekSection
        v-for="weekNumber in weekNumbers"
        :key="weekNumber"
        :week-number="weekNumber"
        :active-block="activeBlock"
        layout-variant="stacked"
        builder-tab="table"
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
    </div>

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
