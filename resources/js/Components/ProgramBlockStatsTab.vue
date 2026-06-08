<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ChartBuilderModal from './ChartBuilderModal.vue';
import CoachChartTemplatesPanel from './CoachChartTemplatesPanel.vue';
import CustomChartCard from './CustomChartCard.vue';
import DefaultBuiltinChart from './DefaultBuiltinChart.vue';
import { BUILTIN_CHART_KEYS } from '../config/chartBuilderOptions';
import {
  countExcludedRpeLines,
  flattenBlockItems,
  hasPercentWithoutRm,
} from '../utils/trainingVolume';

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

const builderOpen = ref(false);
const templatesPanelOpen = ref(false);
const editingTemplate = ref(null);

const oneRm = computed(() => ({
  squat: Number(props.athleteOneRm?.squat ?? 0),
  bench: Number(props.athleteOneRm?.bench ?? 0),
  deadlift: Number(props.athleteOneRm?.deadlift ?? 0),
}));

const flatItems = computed(() => flattenBlockItems(props.sessions, props.dateStart));
const excludedRpeCount = computed(() => countExcludedRpeLines(flatItems.value));
const missingRmForPercent = computed(() => hasPercentWithoutRm(flatItems.value, oneRm.value));
const hasAnyRm = computed(
  () => oneRm.value.squat > 0 || oneRm.value.bench > 0 || oneRm.value.deadlift > 0,
);

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
  if (!window.confirm('Retirer ce graphique du tableau de bord ?')) {
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
    <div
      v-if="coachMode"
      class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
    >
      <p class="text-sm text-slate-400">Personnalise ton tableau de bord stats pour ce bloc.</p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
          @click="openTemplatesPanel"
        >
          Mes modèles
        </button>
        <button
          type="button"
          class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-500"
          @click="openBuilder"
        >
          Ajouter un graphique
        </button>
      </div>
    </div>

    <div
      class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-xs leading-relaxed text-slate-400"
    >
      <p>
        Volume programmé = charge (kg) × reps × séries. Les lignes en
        <span class="text-slate-300">RPE</span> sont exclues.
        <span v-if="hasAnyRm">
          Les charges en <span class="text-slate-300">% du 1RM</span> sont converties avec le
          dernier PR de l’athlète (S {{ oneRm.squat }} · B {{ oneRm.bench }} · T
          {{ oneRm.deadlift }} kg).
        </span>
        <span v-else class="text-amber-300/90">
          Aucun PR enregistré : les lignes en % du 1RM ne sont pas comptées dans le volume.
        </span>
      </p>
      <p v-if="excludedRpeCount > 0" class="mt-1 text-slate-500">
        {{ excludedRpeCount }} ligne{{ excludedRpeCount > 1 ? 's' : '' }} en RPE ignorée{{
          excludedRpeCount > 1 ? 's' : ''
        }}.
      </p>
      <p v-if="missingRmForPercent && hasAnyRm" class="mt-1 text-amber-300/80">
        Certaines lignes en % ne peuvent pas être converties (PR manquant pour ce lift).
      </p>
    </div>

    <p
      v-if="coachMode && dashboardItems.length === 0"
      class="rounded-xl border border-dashed border-slate-700 px-4 py-10 text-center text-sm text-slate-500"
    >
      Aucun graphique affiché. Ajoute un graphique par défaut via « Mes modèles » ou crée-en un nouveau.
    </p>

    <div v-else class="grid gap-4 lg:grid-cols-2">
      <div v-for="(item, index) in dashboardItems" :key="item.id" class="relative">
        <div
          v-if="coachMode"
          class="absolute right-3 top-3 z-10 flex items-center gap-1 rounded-lg border border-slate-700/80 bg-slate-900/90 p-1 shadow-lg"
        >
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-800 hover:text-white disabled:opacity-30"
            :disabled="index === 0"
            title="Monter"
            @click="moveDashboardItem(item, 'up')"
          >
            ↑
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-800 hover:text-white disabled:opacity-30"
            :disabled="index === dashboardItems.length - 1"
            title="Descendre"
            @click="moveDashboardItem(item, 'down')"
          >
            ↓
          </button>
          <button
            type="button"
            class="rounded px-2 py-1 text-xs text-red-400 hover:bg-red-950/40"
            title="Retirer"
            @click="removeDashboardItem(item)"
          >
            ✕
          </button>
        </div>

        <DefaultBuiltinChart
          v-if="item.item_type === 'builtin'"
          :builtin-key="item.builtin_key"
          :sessions="sessions"
          :date-start="dateStart"
          :athlete-one-rm="athleteOneRm"
        />

        <CustomChartCard
          v-else-if="item.template"
          :name="item.template.name"
          :config="item.template.config"
          :sessions="sessions"
          :date-start="dateStart"
          :athlete-one-rm="athleteOneRm"
        />
      </div>
    </div>

    <ChartBuilderModal
      :open="builderOpen"
      :templates="chartTemplates"
      :editing-template="editingTemplate"
      :sessions="sessions"
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
