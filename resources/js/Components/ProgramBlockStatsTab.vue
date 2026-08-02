<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import ChartBuilderModal from './ChartBuilderModal.vue';
import CoachChartTemplatesPanel from './CoachChartTemplatesPanel.vue';
import CustomChartCard from './CustomChartCard.vue';
import DefaultBuiltinChart from './DefaultBuiltinChart.vue';
import { BUILTIN_CHART_KEYS } from '../config/chartBuilderOptions';
import { mapTrainingSessionsToBlockSessions } from '../utils/trainingVolume';

const { t } = useI18n();

const ATHLETE_DEFAULT_KEYS = [
  BUILTIN_CHART_KEYS.VOLUME_WEEKLY,
  BUILTIN_CHART_KEYS.TOPSET_E1RM,
  BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION,
  BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY,
];

const props = defineProps({
  sessions: {
    type: Object,
    default: () => ({}),
  },
  trainingSessions: {
    type: Array,
    default: () => [],
  },
  dateStart: {
    type: String,
    default: '',
  },
  athleteOneRm: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
  weekCount: {
    type: Number,
    default: 0,
  },
  daysPerWeek: {
    type: Number,
    default: 7,
  },
  coachMode: {
    type: Boolean,
    default: false,
  },
  chartTemplates: {
    type: Array,
    default: () => [],
  },
  statsDashboardItems: {
    type: Array,
    default: () => [],
  },
  assignmentId: {
    type: Number,
    default: null,
  },
});

const statsMode = ref('cible');
const builderOpen = ref(false);
const templatesPanelOpen = ref(false);
const editingTemplate = ref(null);

const realizedSessions = computed(() =>
  mapTrainingSessionsToBlockSessions(
    props.trainingSessions,
    props.dateStart,
    props.weekCount,
    props.daysPerWeek || 7,
  ),
);

const chartSessions = computed(() =>
  statsMode.value === 'realise' ? realizedSessions.value : props.sessions,
);

const realizedSessionCount = computed(() => Object.keys(realizedSessions.value).length);

const dashboardItems = computed(() => {
  if (!props.coachMode) {
    return ATHLETE_DEFAULT_KEYS.map((builtinKey, index) => ({
      id: `builtin-${builtinKey}`,
      item_type: 'builtin',
      builtin_key: builtinKey,
      sort_order: index,
    }));
  }

  return [...props.statsDashboardItems].sort((a, b) => a.sort_order - b.sort_order);
});

function openBuilder() {
  editingTemplate.value = null;
  builderOpen.value = true;
}

function openTemplatesPanel() {
  templatesPanelOpen.value = true;
}

function editTemplate(template) {
  templatesPanelOpen.value = false;
  editingTemplate.value = template;
  builderOpen.value = true;
}

function closeBuilder() {
  builderOpen.value = false;
  editingTemplate.value = null;
}

function removeDashboardItem(item) {
  if (!window.confirm(t('programBuilder.statsTab.removeConfirm'))) {
    return;
  }

  router.delete(`/coach/stats-dashboard-items/${item.id}`, {
    data: { assignment: props.assignmentId },
    preserveScroll: true,
  });
}

function moveDashboardItem(item, direction) {
  router.patch(
    `/coach/stats-dashboard-items/${item.id}/move`,
    {
      direction,
      assignment: props.assignmentId,
    },
    { preserveScroll: true },
  );
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-3">
      <button
        type="button"
        class="rounded-lg px-3 py-2 text-sm font-medium transition"
        :class="
          statsMode === 'cible'
            ? 'bg-blue-600 text-white'
            : 'border border-slate-700 text-slate-300 hover:bg-slate-800'
        "
        @click="statsMode = 'cible'"
      >
        {{ t('programBuilder.statsTab.target') }}
      </button>
      <button
        type="button"
        class="rounded-lg px-3 py-2 text-sm font-medium transition"
        :class="
          statsMode === 'realise'
            ? 'bg-blue-600 text-white'
            : 'border border-slate-700 text-slate-300 hover:bg-slate-800'
        "
        @click="statsMode = 'realise'"
      >
        {{ t('programBuilder.statsTab.realized') }}
      </button>
    </div>

    <p class="text-sm text-slate-400">
      <template v-if="statsMode === 'cible'">
        {{ t('programBuilder.statsTab.targetHint') }}
      </template>
      <template v-else>
        {{ t('programBuilder.statsTab.realizedHint') }}
        <span v-if="realizedSessionCount">{{ t('programBuilder.statsTab.realizedSessions', realizedSessionCount, { named: { count: realizedSessionCount } }) }}</span>.
      </template>
    </p>

    <div
      v-if="coachMode"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
    >
      <p class="text-sm text-slate-400">{{ t('programBuilder.statsTab.customize') }}</p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
          @click="openTemplatesPanel"
        >
          {{ t('programBuilder.statsTab.myTemplates') }}
        </button>
        <button
          type="button"
          class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-500"
          @click="openBuilder"
        >
          {{ t('programBuilder.statsTab.addChart') }}
        </button>
      </div>
    </div>

    <p
      v-if="statsMode === 'realise' && realizedSessionCount === 0"
      class="rounded-xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500"
    >
      {{ t('programBuilder.statsTab.noRealized') }}
    </p>

    <p
      v-else-if="coachMode && dashboardItems.length === 0"
      class="rounded-xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500"
    >
      {{ t('programBuilder.statsTab.noCharts') }}
    </p>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <div v-for="(item, index) in dashboardItems" :key="`${statsMode}-${item.id}`" class="relative">
        <div
          v-if="coachMode"
          class="absolute right-3 top-3 z-10 flex items-center gap-1 rounded-lg border border-slate-700/80 bg-slate-900/90 p-1 shadow-lg"
        >
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-800 hover:text-white disabled:opacity-30"
            :disabled="index === 0"
            :title="t('programBuilder.statsTab.moveUp')"
            @click="moveDashboardItem(item, 'up')"
          >
            ↑
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-800 hover:text-white disabled:opacity-30"
            :disabled="index === dashboardItems.length - 1"
            :title="t('programBuilder.statsTab.moveDown')"
            @click="moveDashboardItem(item, 'down')"
          >
            ↓
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-red-400 hover:bg-red-950/40"
            :title="t('programBuilder.statsTab.remove')"
            @click="removeDashboardItem(item)"
          >
            ✕
          </button>
        </div>

        <DefaultBuiltinChart
          v-if="item.item_type === 'builtin'"
          :builtin-key="item.builtin_key"
          :sessions="chartSessions"
          :date-start="dateStart"
          :athlete-one-rm="athleteOneRm"
        />

        <CustomChartCard
          v-else-if="item.template"
          :name="item.template.name"
          :config="item.template.config"
          :sessions="chartSessions"
          :date-start="dateStart"
          :athlete-one-rm="athleteOneRm"
        />
      </div>
    </div>

    <ChartBuilderModal
      :open="builderOpen"
      :templates="chartTemplates"
      :editing-template="editingTemplate"
      :sessions="chartSessions"
      :date-start="dateStart"
      :athlete-one-rm="athleteOneRm"
      :assignment-id="assignmentId"
      @close="closeBuilder"
    />

    <CoachChartTemplatesPanel
      :open="templatesPanelOpen"
      :templates="chartTemplates"
      :assignment-id="assignmentId"
      @close="templatesPanelOpen = false"
      @edit="editTemplate"
    />
  </div>
</template>
