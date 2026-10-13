<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { useI18n } from 'vue-i18n';
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { formatCalendarFr } from '../utils/formatDates';
import { BLOCK_TYPES, findCalendarCellByDate } from '../utils/programBuilder';
import AthleteProgramSessionPanel from '../Components/AthleteProgramSessionPanel.vue';
import AthleteProgramTableView from '../Components/AthleteProgramTableView.vue';
import ProgramBlockCalendar from '../Components/ProgramBlockCalendar.vue';
import ProgramBlockStatsTab from '../Components/ProgramBlockStatsTab.vue';
const { t } = useI18n();
const page = usePage();
const isSelfCoached = computed(() => Boolean(page.props.auth?.user?.self_coached));

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

function readViewWeek() {
  const max = Number(props.programBlock?.week_count ?? 0);
  const fromQuery = Number(
    typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('week') : NaN,
  );
  if (Number.isFinite(fromQuery) && fromQuery >= 1 && (!max || fromQuery <= max)) {
    return fromQuery;
  }
  const current = Number(props.blockProgress?.week_current);
  if (Number.isFinite(current) && current >= 1 && (!max || current <= max)) {
    return current;
  }
  return 1;
}

const viewWeek = ref(readViewWeek());

const hasProgram = computed(() => Boolean(props.programBlock));

const programUpcomingLabel = computed(() => {
  if (!props.programBlock?.starts_in_future || !props.programBlock?.date_start) {
    return null;
  }

  const days = Number(props.programBlock.days_until_start ?? 0);
  const dateLabel = formatCalendarFr(props.programBlock.date_start, 'medium');

  if (days <= 1) {
    return t('app.athleteProgram.startsTomorrow', { date: dateLabel });
  }

  return t('app.athleteProgram.startsOn', { date: dateLabel });
});

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

const editWeek = computed(() => {
  if (activeTab.value === 'calendar' && selectedCell.value?.weekNumber) {
    return selectedCell.value.weekNumber;
  }
  return viewWeek.value;
});

const editHref = computed(() => {
  const id = props.programBlock?.id;
  if (!id) {
    return '/program-builder';
  }
  return `/program-builder?assignment=${id}&week=${editWeek.value}`;
});

function writeProgramUrl() {
  if (typeof window === 'undefined') {
    return;
  }
  const url = new URL(window.location.href);
  if (activeTab.value === 'calendar') {
    url.searchParams.delete('tab');
  } else {
    url.searchParams.set('tab', activeTab.value);
  }
  url.searchParams.set('week', String(viewWeek.value || 1));
  window.history.replaceState({}, '', url.toString());
}

function setTab(tabId) {
  activeTab.value = tabId;
  writeProgramUrl();
}

watch(viewWeek, () => {
  writeProgramUrl();
});

function onSelectCell(cell) {
  selectedCell.value = {
    weekNumber: cell.weekNumber,
    weekday: cell.weekday,
    date: cell.date,
    key: cell.key,
  };
  if (cell.weekNumber) {
    viewWeek.value = cell.weekNumber;
  }
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
      activeTab.value = 'calendar';
      onSelectCell(cell);
    }
    return;
  }

  if (
    typeof window !== 'undefined'
    && window.matchMedia('(max-width: 1023px)').matches
    && props.programBlock?.date_start
    && props.programBlock?.week_count
  ) {
    const today = new Date().toISOString().slice(0, 10);
    const cell = findCalendarCellByDate(
      props.programBlock.week_count,
      props.programBlock.date_start,
      today,
    );
    if (cell) {
      onSelectCell(cell);
    }
  }
});
</script>

<template>
  <div class="space-y-4">
    <div v-if="hasProgram" class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3 sm:p-4">
      <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-start sm:justify-between sm:gap-3">
        <div class="min-w-0">
          <h1 class="text-lg font-bold text-white sm:text-xl">{{ programBlock.name }}</h1>
          <p class="mt-1 text-xs leading-relaxed text-slate-500">
            {{ t('app.athleteProgram.fromTo', { start: formatCalendarFr(programBlock.date_start, 'medium') }) }}
            <template v-if="programBlock.date_end">
              {{ t('app.athleteProgram.to', { end: formatCalendarFr(programBlock.date_end, 'medium') }) }}
            </template>
            <template v-if="!isSelfCoached">
              · {{ t('app.athleteProgram.weeks', programBlock.week_count) }}
            </template>
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <span class="rounded-lg border border-slate-700 bg-slate-950/60 px-2.5 py-1 text-xs font-medium text-slate-300">
            {{ blockTypeLabel }}
          </span>
          <Link
            v-if="isSelfCoached"
            :href="editHref"
            class="rounded-lg bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-blue-500"
          >
            {{ t('app.athleteProgram.editThisWeek') }}
          </Link>
        </div>
      </div>

      <div v-if="blockProgress?.week_current" class="mt-3">
        <div class="flex items-center justify-between text-xs text-slate-500">
          <span>{{ t('app.athleteProgram.weekProgress', { current: blockProgress.week_current, total: blockProgress.week_count }) }}</span>
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
      <h1 class="text-lg font-semibold text-white">{{ t('app.athleteProgram.emptyTitle') }}</h1>
      <p class="mt-2 text-sm text-slate-500">
        {{ isSelfCoached ? t('app.athleteProgram.emptyBodySelf') : t('app.athleteProgram.emptyBody') }}
      </p>
      <Link
        v-if="isSelfCoached"
        href="/program-builder"
        class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
      >
        {{ t('nav.build') }}
      </Link>
    </div>

    <div
      v-if="hasProgram && programBlock?.starts_in_future"
      class="rounded-xl border border-blue-500/30 bg-blue-950/20 px-4 py-3 text-sm text-blue-200"
    >
      {{ programUpcomingLabel }}
      <span class="text-blue-300/80">{{ t('app.athleteProgram.calendarReady') }}</span>
    </div>

    <template v-if="hasProgram">
      <div class="-mx-3 flex overflow-x-auto border-b border-slate-800 px-3 sm:mx-0 sm:px-0">
        <button
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'calendar'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="setTab('calendar')"
        >
          {{ t('app.athleteProgram.tabCalendar') }}
        </button>
        <button
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'table'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="setTab('table')"
        >
          {{ t('app.athleteProgram.tabSpreadsheet') }}
        </button>
        <button
          type="button"
          class="shrink-0 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-medium transition sm:px-4"
          :class="
            activeTab === 'stats'
              ? 'border-blue-500 text-blue-300'
              : 'border-transparent text-slate-400 hover:text-white'
          "
          @click="setTab('stats')"
        >
          {{ t('app.athleteProgram.tabStats') }}
        </button>
      </div>

      <section
        v-if="activeTab === 'stats'"
        class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 lg:p-5"
      >
        <ProgramBlockStatsTab
          :sessions="programBlock.sessions"
          :training-sessions="programBlock.training_sessions ?? []"
          :date-start="programBlock.date_start"
          :athlete-one-rm="programBlock.athlete_one_rm"
          :week-count="programBlock.week_count"
          :days-per-week="programBlock.days_per_week"
        />
      </section>

      <AthleteProgramTableView
        v-else-if="activeTab === 'table'"
        v-model:week="viewWeek"
        :program-block="programBlock"
      />

      <div v-else class="grid gap-4" :class="selectedCell ? 'lg:grid-cols-[minmax(0,1.2fr)_minmax(18rem,0.8fr)]' : ''">
        <section class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/50 p-3 sm:p-4">
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
          v-if="selectedCell"
          :program-block="programBlock"
          :selected-cell="selectedCell"
          :session="selectedSession"
          @close="clearSelection"
        />
      </div>
    </template>
  </div>
</template>
