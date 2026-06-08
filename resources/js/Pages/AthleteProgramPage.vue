<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import { BLOCK_TYPES, findCalendarCellByDate } from '../utils/programBuilder';
import AthleteProgramSessionPanel from '../Components/AthleteProgramSessionPanel.vue';
import AthleteProgramTableView from '../Components/AthleteProgramTableView.vue';
import ProgramBlockCalendar from '../Components/ProgramBlockCalendar.vue';
import ProgramBlockStatsTab from '../Components/ProgramBlockStatsTab.vue';

const props = defineProps({
  programBlock: { type: Object, default: null },
  activeProgram: { type: Object, default: null },
  blockProgress: { type: Object, default: null },
});

const initialTab =
  typeof window !== 'undefined'
    ? new URLSearchParams(window.location.search).get('tab')
    : null;

const activeTab = ref(
  ['calendar', 'table', 'stats'].includes(initialTab) ? initialTab : 'calendar',
);

const selectedCell = ref(null);

const hasProgram = computed(() => Boolean(props.programBlock));

const blockTypeLabel = computed(() => {
  const value = props.blockProgress?.block_type;
  return BLOCK_TYPES.find((item) => item.value === value)?.label ?? value ?? '—';
});

const weekProgressPercent = computed(() => {
  const current = props.blockProgress?.week_current;
  const total = props.blockProgress?.week_count;
  if (!current || !total) {
    return 0;
  }
  return Math.min(100, Math.round((current / total) * 100));
});

const selectedSession = computed(() => {
  if (!selectedCell.value || !props.programBlock) {
    return null;
  }
  return props.programBlock.sessions?.[selectedCell.value.key] ?? null;
});

function setTab(tabId) {
  activeTab.value = tabId;
  const base = '/athlete/program';
  const url = tabId === 'calendar' ? base : `${base}?tab=${tabId}`;
  window.history.replaceState({}, '', url);
}

function onSelectCell(cell) {
  selectedCell.value = {
    weekNumber: cell.weekNumber,
    weekday: cell.weekday,
    date: cell.date,
    key: cell.key,
  };
}

function clearSelection() {
  selectedCell.value = null;
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const tab = params.get('tab');
  if (['calendar', 'table', 'stats'].includes(tab)) {
    activeTab.value = tab;
  }

  const date = params.get('date');
  if (date && props.programBlock?.date_start && props.programBlock?.week_count) {
    const cell = findCalendarCellByDate(
      props.programBlock.week_count,
      props.programBlock.date_start,
      date,
    );
    if (cell) {
      onSelectCell(cell);
      activeTab.value = 'calendar';
    }
  }
});
</script>

<template>
  <div class="space-y-4">
    <div v-if="hasProgram" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-xl font-bold text-white">{{ programBlock.name }}</h1>
          <p class="mt-1 text-xs text-slate-500">
            Du {{ formatCalendarFr(programBlock.date_start, 'medium') }}
            <template v-if="programBlock.date_end">
              au {{ formatCalendarFr(programBlock.date_end, 'medium') }}
            </template>
            · {{ programBlock.week_count }} semaine{{ programBlock.week_count > 1 ? 's' : '' }}
          </p>
        </div>
        <span class="rounded-lg border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs font-medium text-slate-300">
          {{ blockTypeLabel }}
        </span>
      </div>

      <div v-if="blockProgress?.week_current" class="mt-3">
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>Semaine {{ blockProgress.week_current }} / {{ blockProgress.week_count }}</span>
          <span>{{ weekProgressPercent }}%</span>
        </div>
        <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-800">
          <div
            class="h-full rounded-full bg-blue-500 transition-all"
            :style="{ width: `${weekProgressPercent}%` }"
          />
        </div>
      </div>
    </div>

    <div
      v-else
      class="rounded-2xl border border-dashed border-slate-700 bg-slate-900/30 p-8 text-center"
    >
      <h1 class="text-lg font-semibold text-white">Programme</h1>
      <p class="mt-2 text-sm text-slate-500">
        Aucun bloc actif pour le moment. Ton coach te l’assignera bientôt.
      </p>
    </div>

    <template v-if="hasProgram">
      <div class="flex flex-wrap items-center gap-2 border-b border-slate-800">
        <button
          type="button"
          class="border-b-2 px-4 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === 'calendar'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="setTab('calendar')"
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
          @click="setTab('table')"
        >
          Tableur
        </button>
        <button
          type="button"
          class="border-b-2 px-4 py-2.5 text-sm font-medium transition"
          :class="
            activeTab === 'stats'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="setTab('stats')"
        >
          Graphiques & stats
        </button>
      </div>

      <section
        v-if="activeTab === 'stats'"
        class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 lg:p-5"
      >
        <ProgramBlockStatsTab
          :sessions="programBlock.sessions"
          :date-start="programBlock.date_start"
          :athlete-one-rm="programBlock.athlete_one_rm"
          :week-count="programBlock.week_count"
        />
      </section>

      <AthleteProgramTableView
        v-else-if="activeTab === 'table'"
        :program-block="programBlock"
      />

      <div v-else class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]">
        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
          <p class="mb-3 text-xs text-slate-500">
            Clique sur un jour vert pour voir la séance programmée.
          </p>
          <ProgramBlockCalendar
            read-only
            :week-count="programBlock.week_count"
            :date-start="programBlock.date_start"
            :sessions="programBlock.sessions"
            :selected-cell="selectedCell"
            @select="onSelectCell"
          />
        </section>

        <AthleteProgramSessionPanel
          :program-block="programBlock"
          :selected-cell="selectedCell"
          :session="selectedSession"
          @close="clearSelection"
        />
      </div>
    </template>
  </div>
</template>
