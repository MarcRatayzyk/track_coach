<script>

import AppLayout from '../Layouts/AppLayout.vue';



export default {

  layout: AppLayout,

};

</script>



<script setup>

import { computed, ref } from 'vue';

import { router } from '@inertiajs/vue3';

import BlockSetupCard from '../Components/BlockSetupCard.vue';
import BlockSetupTableCard from '../Components/BlockSetupTableCard.vue';

import ProgramBlockCalendar from '../Components/ProgramBlockCalendar.vue';
import AthleteProgramTableView from '../Components/AthleteProgramTableView.vue';
import ProgramBlockTableBuilderV2 from '../Components/ProgramBlockTableBuilderV2.vue';
import ProgramPasteIncrementModal from '../Components/ProgramPasteIncrementModal.vue';

import ProgramBlockStatsTab from '../Components/ProgramBlockStatsTab.vue';
import SessionEditorPanel from '../Components/SessionEditorPanel.vue';
import DayTableLayoutModal from '../Components/DayTableLayoutModal.vue';
import CustomExercisesModal from '../Components/CustomExercisesModal.vue';

import { cellKey, weekdayShortLabel } from '../utils/programBuilder';

import {

  applyClipboardWeekIncrements,

  prepareClipboardSessionForPaste,

  clipboardSessionToOperation,

  clipboardWeekToOperations,

  collectClipboardExerciseNames,

  sessionHasClipboardContent,

  sessionToClipboardPayload,

  weekSessionsToClipboard,

} from '../utils/programBuilderClipboard';



const props = defineProps({

  athletes: {

    type: Array,

    default: () => [],

  },

  existingBlocks: {

    type: Array,

    default: () => [],

  },

  activeBlock: {

    type: Object,

    default: null,

  },

  dayTableLayouts: {

    type: Array,

    default: () => [],

  },

  defaultDayTableLayoutId: {

    type: Number,

    default: null,

  },

  chartTemplates: {

    type: Array,

    default: () => [],

  },

  statsDashboardItems: {

    type: Array,

    default: () => [],

  },

});



const layoutModalOpen = ref(false);



const initialTab =
  typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('tab')
    : null;

const selectedCell = ref(null);

const assigning = ref(false);

const pasting = ref(false);

const pasteMode = ref(false);
const customExercisesOpen = ref(false);
const activeTab = ref(
  ['calendar', 'table', 'table_v2', 'stats'].includes(initialTab) ? initialTab : 'calendar',
);



const clipboardSession = ref(null);

const clipboardWeek = ref(null);

const incrementModalOpen = ref(false);

const incrementModalTitle = ref('');

const incrementModalHint = ref('');

const incrementModalExerciseNames = ref([]);

const incrementModalPasteKind = ref('session');

const incrementModalDefaultSessionLabel = ref('');

const incrementModalDefaultSessionNotes = ref('');

const pendingPasteAction = ref(null);



const showCalendar = computed(() => Boolean(props.activeBlock));

const isAssigned = computed(() => props.activeBlock?.status === 'active');

const athletePrSummary = computed(() => {
  const pr = props.activeBlock?.athlete_one_rm;
  if (!pr) {
    return null;
  }

  const squat = Number(pr.squat ?? 0);
  const bench = Number(pr.bench ?? 0);
  const deadlift = Number(pr.deadlift ?? 0);

  if (!squat && !bench && !deadlift) {
    return null;
  }

  return { squat, bench, deadlift };
});

const selectedSession = computed(() => {

  if (!props.activeBlock || !selectedCell.value) {

    return null;

  }

  const key = cellKey(selectedCell.value.weekNumber, selectedCell.value.weekday);

  return props.activeBlock.sessions?.[key] ?? null;

});



const canCopySession = computed(() => sessionHasClipboardContent(sessionToClipboardPayload(selectedSession.value)));

const selectedHasSession = computed(() => Boolean(selectedSession.value));

const deleting = ref(false);



const clipboardStatus = computed(() => {

  if (clipboardWeek.value) {

    const count = Object.keys(clipboardWeek.value.sessions).length;

    return `Semaine ${clipboardWeek.value.weekNumber} copiée (${count} séance${count > 1 ? 's' : ''})`;

  }

  if (clipboardSession.value) {

    const label = clipboardSession.value.session_label;

    return label ? `Séance copiée : ${label}` : 'Séance copiée';

  }

  return null;

});



function onSelectCell(cell) {

  if (pasteMode.value && clipboardSession.value) {

    pasteSessionToCell(cell);

    return;

  }

  selectedCell.value = cell;

}



function closeEditor() {

  selectedCell.value = null;

}



function afterSessionChange() {

  router.reload({ only: ['activeBlock'], preserveScroll: true });

}



function onSessionCleared() {

  closeEditor();

  afterSessionChange();

}



function deleteSelectedSession() {

  if (!props.activeBlock?.id || !selectedCell.value || !selectedHasSession.value) {

    return;

  }



  if (

    !window.confirm(

      'Supprimer cette séance ? Tous les exercices programmés pour ce jour seront effacés.',

    )

  ) {

    return;

  }



  deleting.value = true;

  router.delete(`/coach/program-blocks/${props.activeBlock.id}/sessions`, {

    data: {

      week_number: selectedCell.value.weekNumber,

      weekday: selectedCell.value.weekday,

    },

    preserveScroll: true,

    onSuccess: onSessionCleared,

    onFinish: () => {

      deleting.value = false;

    },

  });

}



function backToSetup() {

  router.get(
    '/program-builder',
    ['table', 'table_v2'].includes(activeTab.value) ? { tab: activeTab.value } : {},
    { preserveState: false },
  );

}



function assignBlockToAthlete() {

  if (!props.activeBlock?.id || isAssigned.value) {

    return;

  }



  assigning.value = true;

  router.post(
    `/coach/program-blocks/${props.activeBlock.id}/assign`,
    { builder_tab: activeTab.value },
    {

      preserveScroll: true,

      onFinish: () => {

        assigning.value = false;

      },

    },
  );

}



function copySession() {

  const payload = sessionToClipboardPayload(selectedSession.value);

  if (!payload) {

    return;

  }

  clipboardSession.value = payload;

  clipboardWeek.value = null;

}



function copySessionFromCell(cell) {

  if (!props.activeBlock) {

    return;

  }

  const session = props.activeBlock.sessions?.[cell.key];

  const payload = sessionToClipboardPayload(session);

  if (!payload) {

    return;

  }

  clipboardSession.value = payload;

  clipboardWeek.value = null;

}



function openIncrementModal(action) {

  pendingPasteAction.value = action;

  incrementModalTitle.value = action.title;

  incrementModalHint.value = action.hint;

  incrementModalExerciseNames.value = action.exerciseNames ?? [];

  incrementModalPasteKind.value = action.pasteKind ?? 'session';

  incrementModalDefaultSessionLabel.value = action.defaultSessionLabel ?? '';

  incrementModalDefaultSessionNotes.value = action.defaultSessionNotes ?? '';

  incrementModalOpen.value = true;

}



function closeIncrementModal() {

  incrementModalOpen.value = false;

  incrementModalTitle.value = '';

  incrementModalHint.value = '';

  incrementModalExerciseNames.value = [];

  incrementModalPasteKind.value = 'session';

  incrementModalDefaultSessionLabel.value = '';

  incrementModalDefaultSessionNotes.value = '';

  pendingPasteAction.value = null;

}



function pasteSessionToCell(cell) {

  if (!clipboardSession.value || !props.activeBlock?.id || pasting.value) {

    return;

  }



  openIncrementModal({

    type: 'session',

    cell,

    title: `Coller la séance sur ${weekdayShortLabel(cell.weekday)}`,

    hint: 'Définis le titre, les notes, les incréments et les lignes concernées avant de coller.',

    pasteKind: 'session',

    defaultSessionLabel: clipboardSession.value.session_label ?? '',

    defaultSessionNotes: clipboardSession.value.notes ?? '',

    exerciseNames: collectClipboardExerciseNames(clipboardSession.value),

  });

}



function executePasteSession(cell, options) {

  const payload = prepareClipboardSessionForPaste(clipboardSession.value, options);

  pasting.value = true;

  router.post(

    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,

    {

      operations: [clipboardSessionToOperation(payload, cell.weekNumber, cell.weekday)],

    },

    {

      preserveScroll: true,

      onSuccess: () => {

        selectedCell.value = cell;

        afterSessionChange();

      },

      onFinish: () => {

        pasting.value = false;

      },

    },

  );

}



function pasteSessionHere() {

  if (!selectedCell.value) {

    return;

  }

  pasteSessionToCell(selectedCell.value);

}



function copyWeek(weekNumber) {

  const data = weekSessionsToClipboard(props.activeBlock.sessions, weekNumber);

  if (!data) {

    return;

  }

  clipboardWeek.value = data;

  clipboardSession.value = null;

}



function pasteWeek(targetWeekNumber) {

  if (!clipboardWeek.value || !props.activeBlock?.id || pasting.value) {

    return;

  }



  if (

    !window.confirm(

      `Coller la semaine ${clipboardWeek.value.weekNumber} sur la semaine ${targetWeekNumber} ? Les séances existantes sur ces jours seront remplacées.`,

    )

  ) {

    return;

  }



  openIncrementModal({

    type: 'week',

    targetWeekNumber,

    title: `Coller sur la semaine ${targetWeekNumber}`,

    hint: 'Définis les notes, les incréments et les lignes concernées pour toutes les séances collées.',

    pasteKind: 'week',

    defaultSessionNotes: '',

    exerciseNames: collectClipboardExerciseNames(clipboardWeek.value),

  });

}



function executePasteWeek(targetWeekNumber, options) {

  const payload = applyClipboardWeekIncrements(clipboardWeek.value, options);

  pasting.value = true;

  router.post(

    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,

    {

      operations: clipboardWeekToOperations(payload, targetWeekNumber),

    },

    {

      preserveScroll: true,

      onSuccess: afterSessionChange,

      onFinish: () => {

        pasting.value = false;

      },

    },

  );

}



function confirmIncrementModal(options) {

  if (!pendingPasteAction.value) {

    return;

  }



  const action = pendingPasteAction.value;

  closeIncrementModal();



  if (action.type === 'session') {

    executePasteSession(action.cell, options);

    return;

  }



  if (action.type === 'week') {

    executePasteWeek(action.targetWeekNumber, options);

  }

}



function togglePasteMode() {

  pasteMode.value = !pasteMode.value;

  if (!pasteMode.value) {

    return;

  }

  if (!clipboardSession.value && selectedCell.value && canCopySession.value) {

    copySessionFromCell(selectedCell.value);

  }

}



function clearClipboard() {

  clipboardSession.value = null;

  clipboardWeek.value = null;

  pasteMode.value = false;

}

</script>



<template>

  <div class="space-y-8">

    <div class="flex flex-wrap items-start justify-between gap-4">

      <div>

        <h1 class="text-2xl font-bold text-white">Programmes</h1>

        <p v-if="!showCalendar" class="mt-3 max-w-3xl text-base leading-relaxed text-slate-400">

          <template v-if="activeTab === 'table' || activeTab === 'table_v2'">
            Crée un bloc puis remplis les séances dans un format tableur, jour par jour.
          </template>

          <template v-else>
            Crée un bloc pour un athlète, puis programme chaque séance sur le calendrier.
          </template>

        </p>

        <p v-else class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-2 text-base text-slate-400">

          <span class="font-medium text-white">{{ activeBlock.name }}</span>

          <span aria-hidden="true">·</span>

          <span>{{ activeBlock.athlete_name }}</span>

          <template v-if="athletePrSummary">
            <span aria-hidden="true">·</span>

            <span class="tabular-nums text-slate-300">
              S {{ athletePrSummary.squat }} · B {{ athletePrSummary.bench }} · D {{ athletePrSummary.deadlift }} kg
            </span>
          </template>

          <span aria-hidden="true">·</span>

          <span>{{ activeBlock.week_count }} semaines</span>

          <span aria-hidden="true">·</span>

          <span>du {{ activeBlock.date_start }}</span>

          <span

            v-if="!isAssigned"

            class="rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-300"

          >

            Brouillon

          </span>

          <button

            type="button"

            class="rounded-xl px-3 py-1.5 text-sm font-semibold transition disabled:opacity-50"

            :class="

              isAssigned

                ? 'border border-emerald-500/40 bg-emerald-500/10 text-emerald-300'

                : 'bg-blue-600 text-white hover:bg-blue-500'

            "

            :disabled="assigning || isAssigned"

            @click="assignBlockToAthlete"

          >

            {{

              assigning

                ? 'Assignation…'

                : isAssigned

                  ? 'Assigné à l\'athlète'

                  : 'Enregistrer et assigner à l\'athlète'

            }}

          </button>

        </p>

      </div>

      <div class="flex flex-wrap items-center gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-600 px-3 py-2 text-sm font-medium text-slate-200 hover:border-slate-500 hover:bg-slate-800/60"
          @click="customExercisesOpen = true"
        >
          Mes exercices
        </button>
        <a
          v-if="activeBlock?.id"
          :href="`/coach/program-blocks/${activeBlock.id}/export-pdf`"
          class="rounded-xl border border-slate-600 px-3 py-2 text-sm font-medium text-slate-200 hover:border-slate-500 hover:bg-slate-800/60"
        >
          Exporter PDF
        </a>

      <button

        v-if="showCalendar"

        type="button"

        class="text-sm font-medium text-slate-400 hover:text-white"

        @click="backToSetup"

      >

        ← Nouveau bloc

      </button>
      </div>

    </div>



    <div class="flex flex-col gap-3 border-b border-slate-800 sm:flex-row sm:items-center sm:justify-between">
      <div class="-mx-3 flex overflow-x-auto px-3 sm:mx-0 sm:px-0">
        <button
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'calendar'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="activeTab = 'calendar'"
        >
          Calendrier
        </button>
        <button
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'table'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="activeTab = 'table'"
        >
          Tableur
        </button>
        <button
          v-if="showCalendar"
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'table_v2'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="activeTab = 'table_v2'"
        >
          Tableur V2
        </button>
        <button
          v-if="showCalendar"
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'stats'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="activeTab = 'stats'"
        >
          Graphiques & stats
        </button>
      </div>

      <button
        v-if="showCalendar && activeTab === 'table_v2'"
        type="button"
        class="shrink-0 self-start rounded-xl border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800 sm:self-auto"
        @click="layoutModalOpen = true"
      >
        Mon tableau jour
      </button>
    </div>

    <BlockSetupCard
      v-if="!showCalendar && activeTab === 'calendar'"
      :athletes="athletes"
      :existing-blocks="existingBlocks"
    />

    <BlockSetupTableCard
      v-else-if="!showCalendar"
      :athletes="athletes"
      :existing-blocks="existingBlocks"
      :day-table-layouts="dayTableLayouts"
      :default-day-table-layout-id="defaultDayTableLayoutId"
    />

    <template v-else>
      <section
        v-if="activeTab === 'stats'"
        class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6"
      >
        <ProgramBlockStatsTab
          coach-mode
          :sessions="activeBlock.sessions"
          :date-start="activeBlock.date_start"
          :athlete-one-rm="activeBlock.athlete_one_rm"
          :week-count="activeBlock.week_count"
          :chart-templates="chartTemplates"
          :stats-dashboard-items="statsDashboardItems"
          :assignment-id="activeBlock.id"
        />
      </section>

      <AthleteProgramTableView
        v-else-if="activeTab === 'table'"
        :program-block="activeBlock"
      />

      <ProgramBlockTableBuilderV2
        v-else-if="activeTab === 'table_v2'"
        :active-block="activeBlock"
      />

      <div
        v-else
        class="grid gap-4"
        :class="selectedCell && !pasteMode ? 'lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]' : ''"
      >
      <section class="min-w-0 space-y-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-3 shadow-lg sm:p-4 lg:p-5">

        <div class="flex flex-wrap items-end justify-between gap-3">

          <p class="text-sm text-slate-400">

            <template v-if="pasteMode">

              Mode collage : clique sur les cases pour y coller la séance copiée.

            </template>

            <template v-else>

              Clique sur une case pour programmer une séance (jours en vert = déjà programmés).

            </template>

          </p>

          <div class="flex flex-wrap gap-2">

            <button

              type="button"

              class="rounded-lg border border-red-500/40 px-3 py-1.5 text-xs font-medium text-red-300 hover:bg-red-950/40 disabled:opacity-40"

              :disabled="!selectedHasSession || deleting"

              @click="deleteSelectedSession"

            >

              {{ deleting ? 'Suppression…' : 'Supprimer séance' }}

            </button>

            <button

              type="button"

              class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-medium text-white hover:border-slate-500 disabled:opacity-40"

              :disabled="!canCopySession"

              @click="copySession"

            >

              Copier séance

            </button>

            <button

              type="button"

              class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs font-medium text-white hover:border-slate-500 disabled:opacity-40"

              :disabled="!clipboardSession || pasting"

              @click="pasteSessionHere"

            >

              Coller ici

            </button>

            <button

              type="button"

              class="rounded-lg px-3 py-1.5 text-xs font-medium transition disabled:opacity-40"

              :class="

                pasteMode

                  ? 'bg-blue-600 text-white'

                  : 'border border-slate-600 text-white hover:border-slate-500'

              "

              :disabled="!clipboardSession && !canCopySession"

              @click="togglePasteMode"

            >

              {{ pasteMode ? 'Fin collage multiple' : 'Coller sur plusieurs cases' }}

            </button>

            <button

              v-if="clipboardStatus"

              type="button"

              class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-400 hover:text-white"

              title="Vider le presse-papiers"

              @click="clearClipboard"

            >

              Effacer

            </button>

          </div>

        </div>



        <p v-if="clipboardStatus" class="text-xs text-amber-300/90">

          {{ clipboardStatus }}

          <span class="text-amber-300/60">· Le collage demandera des incréments (kg, %, RPE).</span>

        </p>



        <ProgramBlockCalendar

          compact

          :week-count="activeBlock.week_count"

          :date-start="activeBlock.date_start"

          :sessions="activeBlock.sessions"

          :selected-cell="selectedCell"

          :paste-mode="pasteMode"

          :has-session-clipboard="Boolean(clipboardSession)"

          :has-week-clipboard="Boolean(clipboardWeek)"

          :copied-week-number="clipboardWeek?.weekNumber ?? null"

          @select="onSelectCell"

          @copy-week="copyWeek"

          @paste-week="pasteWeek"

        />

      </section>

        <SessionEditorPanel

          v-if="selectedCell && !pasteMode"

          :active-block="activeBlock"

          :selected-cell="selectedCell"

          :session="selectedSession"

          @close="closeEditor"

          @saved="afterSessionChange"

          @cleared="onSessionCleared"

        />
      </div>

    </template>

    <DayTableLayoutModal
      :open="layoutModalOpen"
      :layouts="dayTableLayouts"
      :default-layout-id="defaultDayTableLayoutId"
      @close="layoutModalOpen = false"
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

    <CustomExercisesModal v-model:open="customExercisesOpen" />

  </div>

</template>

