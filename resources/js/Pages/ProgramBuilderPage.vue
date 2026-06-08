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
import ProgramBlockTableBuilder from '../Components/ProgramBlockTableBuilder.vue';

import ProgramBlockStatsTab from '../Components/ProgramBlockStatsTab.vue';
import SessionEditorPanel from '../Components/SessionEditorPanel.vue';
import DayTableLayoutModal from '../Components/DayTableLayoutModal.vue';

import { cellKey } from '../utils/programBuilder';

import {

  clipboardSessionToOperation,

  clipboardWeekToOperations,

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
const activeTab = ref(
  ['calendar', 'table', 'stats'].includes(initialTab) ? initialTab : 'calendar',
);



const clipboardSession = ref(null);

const clipboardWeek = ref(null);



const showCalendar = computed(() => Boolean(props.activeBlock));



const isAssigned = computed(() => props.activeBlock?.status === 'active');



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
    activeTab.value === 'table' ? { tab: 'table' } : {},
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



function pasteSessionToCell(cell) {

  if (!clipboardSession.value || !props.activeBlock?.id || pasting.value) {

    return;

  }



  pasting.value = true;

  router.post(

    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,

    {

      operations: [clipboardSessionToOperation(clipboardSession.value, cell.weekNumber, cell.weekday)],

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



  pasting.value = true;

  router.post(

    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,

    {

      operations: clipboardWeekToOperations(clipboardWeek.value, targetWeekNumber),

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

          <template v-if="activeTab === 'table'">
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

      <button

        v-if="showCalendar"

        type="button"

        class="text-sm font-medium text-slate-400 hover:text-white"

        @click="backToSetup"

      >

        ← Nouveau bloc

      </button>

    </div>



    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800">
      <div class="flex gap-2">
      <button
        type="button"
        class="border-b-2 px-4 py-2.5 text-sm font-medium transition"
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
        class="border-b-2 px-4 py-2.5 text-sm font-medium transition"
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
        class="border-b-2 px-4 py-2.5 text-sm font-medium transition"
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
        v-if="!showCalendar"
        type="button"
        class="rounded-xl border border-slate-700 px-3 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800"
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

      <ProgramBlockTableBuilder
        v-else-if="activeTab === 'table'"
        :active-block="activeBlock"
      />

      <section
        v-else
        class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6"
      >

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

        </p>



        <ProgramBlockCalendar

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



        <SessionEditorPanel

          v-if="selectedCell && !pasteMode"

          :active-block="activeBlock"

          :selected-cell="selectedCell"

          :session="selectedSession"

          @close="closeEditor"

          @saved="afterSessionChange"

          @cleared="onSessionCleared"

        />

      </section>

    </template>

    <DayTableLayoutModal
      :open="layoutModalOpen"
      :layouts="dayTableLayouts"
      :default-layout-id="defaultDayTableLayoutId"
      @close="layoutModalOpen = false"
    />

  </div>

</template>

