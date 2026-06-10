<script setup>
import ProgramTableDayCard from './ProgramTableDayCard.vue';
import ProgramTableRestDaySlot from './ProgramTableRestDaySlot.vue';
import { ALL_WEEKDAYS } from '../composables/useProgramTableBuilder';
import { weekdayShortLabel } from '../utils/programBuilder';

const props = defineProps({
  weekNumber: {
    type: Number,
    required: true,
  },
  activeBlock: {
    type: Object,
    required: true,
  },
  layoutVariant: {
    type: String,
    default: 'stacked',
    validator: (value) => ['stacked', 'spaced'].includes(value),
  },
  builderTab: {
    type: String,
    default: 'table',
  },
  clipboardWeek: {
    type: Object,
    default: null,
  },
  clipboardSession: {
    type: Object,
    default: null,
  },
  pasting: {
    type: Boolean,
    default: false,
  },
  isBusy: {
    type: Boolean,
    default: false,
  },
  sessionFor: {
    type: Function,
    required: true,
  },
  headingFor: {
    type: Function,
    required: true,
  },
  hasSessionsInWeek: {
    type: Function,
    required: true,
  },
  isDraggingSource: {
    type: Function,
    required: true,
  },
  isDropTarget: {
    type: Function,
    required: true,
  },
});

const emit = defineEmits([
  'copy-week',
  'paste-week',
  'drag-start',
  'drag-end',
  'drag-over',
  'drag-leave',
  'drop',
  'copy-session',
  'paste-session',
  'delete-session',
  'add-day',
]);
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-950/30 p-4">
    <div class="mb-4 flex items-center justify-between gap-3">
      <div>
        <h3 class="text-base font-semibold text-white">Semaine {{ weekNumber }}</h3>
        <p class="mt-1 text-xs text-slate-500">
          Glisse une séance vers un autre jour pour la déplacer ou l'échanger.
        </p>
      </div>
      <div class="flex flex-wrap items-center justify-end gap-2">
        <button
          type="button"
          :disabled="!hasSessionsInWeek(weekNumber)"
          class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          @click="emit('copy-week', weekNumber)"
        >
          Copier la semaine
        </button>
        <button
          type="button"
          :disabled="!clipboardWeek || pasting"
          class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
          @click="emit('paste-week', weekNumber)"
        >
          Coller la semaine
        </button>
      </div>
    </div>

    <!-- V1 : scroll horizontal, colonnes fixes -->
    <div
      v-if="layoutVariant === 'stacked'"
      class="tc-scrollbar overflow-x-auto pb-3"
    >
      <div class="inline-flex min-w-full flex-col gap-2">
        <div class="flex flex-nowrap gap-2">
          <div
            v-for="weekday in ALL_WEEKDAYS"
            :key="`header-${weekNumber}-${weekday}`"
            :title="headingFor(weekNumber, weekday)"
            class="shrink-0"
            :class="
              sessionFor(weekNumber, weekday)
                ? 'w-[28rem] px-1 text-center text-xs font-medium uppercase tracking-wide text-slate-500'
                : 'flex w-14 items-center justify-center py-1'
            "
          >
            <span v-if="sessionFor(weekNumber, weekday)">
              {{ headingFor(weekNumber, weekday) }}
            </span>
            <span
              v-else
              class="text-[10px] font-medium uppercase tracking-wide text-slate-500"
              style="writing-mode: vertical-rl; text-orientation: mixed"
            >
              {{ weekdayShortLabel(weekday) }}
            </span>
          </div>
        </div>

        <div class="flex flex-nowrap items-stretch gap-2">
          <div
            v-for="weekday in ALL_WEEKDAYS"
            :key="`${weekNumber}-${weekday}`"
            data-day-card
            class="shrink-0 transition-all duration-200 ease-out"
            :class="[
              sessionFor(weekNumber, weekday) ? 'w-[28rem]' : 'w-14',
              {
                'opacity-45 scale-[0.97] blur-[0.5px]': isDraggingSource(weekNumber, weekday),
              },
            ]"
            @dragover.prevent="emit('drag-over', weekNumber, weekday)"
            @dragleave="emit('drag-leave', weekNumber, weekday)"
            @drop.prevent="emit('drop', weekNumber, weekday)"
          >
            <ProgramTableDayCard
              v-if="sessionFor(weekNumber, weekday)"
              :assignment-id="activeBlock.id"
              :week-number="weekNumber"
              :weekday="weekday"
              :session="sessionFor(weekNumber, weekday)"
              :table-layout="activeBlock.table_layout"
              :column-heading="headingFor(weekNumber, weekday)"
              :default-session-label="weekdayShortLabel(weekday)"
              :builder-tab="builderTab"
              :has-session-clipboard="Boolean(clipboardSession)"
              :is-pasting="isBusy"
              :reorderable="!isBusy"
              :is-dragging="isDraggingSource(weekNumber, weekday)"
              :is-drop-target="isDropTarget(weekNumber, weekday)"
              @drag-start="emit('drag-start', weekNumber, weekday, $event)"
              @drag-end="emit('drag-end')"
              @copy-session="emit('copy-session', $event)"
              @paste-session="emit('paste-session', $event)"
              @delete-session="emit('delete-session', $event)"
            />
            <ProgramTableRestDaySlot
              v-else
              layout-variant="stacked"
              :column-heading="headingFor(weekNumber, weekday)"
              :is-drop-target="isDropTarget(weekNumber, weekday)"
              :has-session-clipboard="Boolean(clipboardSession)"
              :disabled="isBusy"
              @add-session="emit('add-day', weekNumber, weekday)"
              @paste-session="emit('paste-session', { weekNumber, weekday })"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- V2 : semaine en colonne, jours empilés verticalement (lun → mar → …) -->
    <div
      v-else
      class="flex flex-col gap-2"
    >
      <div
        v-for="weekday in ALL_WEEKDAYS"
        :key="`day-${weekNumber}-${weekday}`"
        class="w-full"
        :class="sessionFor(weekNumber, weekday) ? 'mb-4 flex flex-col gap-2' : ''"
      >
        <div
          v-if="sessionFor(weekNumber, weekday)"
          :title="headingFor(weekNumber, weekday)"
          class="text-sm font-semibold uppercase tracking-wide text-slate-400"
        >
          {{ headingFor(weekNumber, weekday) }}
        </div>

        <div
          data-day-card
          class="w-full transition-all duration-200 ease-out"
          :class="{
            'opacity-45 scale-[0.99] blur-[0.5px]': isDraggingSource(weekNumber, weekday),
          }"
          @dragover.prevent="emit('drag-over', weekNumber, weekday)"
          @dragleave="emit('drag-leave', weekNumber, weekday)"
          @drop.prevent="emit('drop', weekNumber, weekday)"
        >
          <ProgramTableDayCard
            v-if="sessionFor(weekNumber, weekday)"
            :assignment-id="activeBlock.id"
            :week-number="weekNumber"
            :weekday="weekday"
            :session="sessionFor(weekNumber, weekday)"
            :table-layout="activeBlock.table_layout"
            :column-heading="headingFor(weekNumber, weekday)"
            :default-session-label="weekdayShortLabel(weekday)"
            :builder-tab="builderTab"
            layout-variant="spaced"
            :has-session-clipboard="Boolean(clipboardSession)"
            :is-pasting="isBusy"
            :reorderable="!isBusy"
            :is-dragging="isDraggingSource(weekNumber, weekday)"
            :is-drop-target="isDropTarget(weekNumber, weekday)"
            @drag-start="emit('drag-start', weekNumber, weekday, $event)"
            @drag-end="emit('drag-end')"
            @copy-session="emit('copy-session', $event)"
            @paste-session="emit('paste-session', $event)"
            @delete-session="emit('delete-session', $event)"
          />
          <ProgramTableRestDaySlot
            v-else
            collapsed
            layout-variant="spaced"
            :column-heading="headingFor(weekNumber, weekday)"
            :is-drop-target="isDropTarget(weekNumber, weekday)"
            :has-session-clipboard="Boolean(clipboardSession)"
            :disabled="isBusy"
            @add-session="emit('add-day', weekNumber, weekday)"
            @paste-session="emit('paste-session', { weekNumber, weekday })"
          />
        </div>
      </div>
    </div>
  </section>
</template>
