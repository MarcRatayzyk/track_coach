<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import BarChart from './charts/BarChart.vue';
import DoughnutChart from './charts/DoughnutChart.vue';
import LineChart from './charts/LineChart.vue';
import {
  CHART_PRESETS,
  CHART_TYPE_OPTIONS,
  GROUP_BY_OPTIONS,
  MAIN_LIFT_FILTER_OPTIONS,
  METRIC_OPTIONS,
  REP_FORMAT_OPTIONS,
  SECTION_FILTER_OPTIONS,
  SERIES_LIFT_OPTIONS,
  defaultChartConfig,
} from '../config/chartBuilderOptions';
import { buildChartFromConfig, listExerciseNames } from '../utils/chartBuilderEngine';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  templates: {
    type: Array,
    default: () => [],
  },
  editingTemplate: {
    type: Object,
    default: null,
  },
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
  assignmentId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close']);

const defaults = defaultChartConfig();

const form = useForm({
  name: '',
  chartType: defaults.chartType,
  metric: defaults.metric,
  groupBy: defaults.groupBy,
  series: [...defaults.series],
  stacked: defaults.stacked,
  filters: { ...defaults.filters },
  add_to_dashboard: false,
  assignment: props.assignmentId,
});

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    if (props.editingTemplate) {
      loadTemplate(props.editingTemplate);
      return;
    }

    resetForm();
  },
);

watch(
  () => props.assignmentId,
  (value) => {
    form.assignment = value;
  },
);

function resetForm() {
  const config = defaultChartConfig();
  form.defaults({
    name: '',
    chartType: config.chartType,
    metric: config.metric,
    groupBy: config.groupBy,
    series: [...config.series],
    stacked: config.stacked,
    filters: { ...config.filters },
    add_to_dashboard: false,
    assignment: props.assignmentId,
  });
  form.reset();
}

function loadTemplate(template) {
  const config = template.config ?? defaultChartConfig();
  form.defaults({
    name: template.name ?? '',
    chartType: config.chartType,
    metric: config.metric,
    groupBy: config.groupBy,
    series: [...(config.series ?? defaults.series)],
    stacked: Boolean(config.stacked),
    filters: { ...defaults.filters, ...(config.filters ?? {}) },
    add_to_dashboard: false,
    assignment: props.assignmentId,
  });
  form.reset();
}

function closeModal() {
  emit('close');
}

function toggleSeries(lift) {
  if (form.series.includes(lift)) {
    form.series = form.series.filter((item) => item !== lift);
    return;
  }
  form.series = [...form.series, lift];
}

function applyPreset(preset) {
  const config = preset.config;
  form.chartType = config.chartType;
  form.metric = config.metric;
  form.groupBy = config.groupBy;
  form.series = [...config.series];
  form.stacked = Boolean(config.stacked);
  form.filters = { ...defaults.filters, ...(config.filters ?? {}) };
}

const previewConfig = computed(() => ({
  chartType: form.chartType,
  metric: form.metric,
  groupBy: form.groupBy,
  series: form.series,
  stacked: form.stacked,
  filters: form.filters,
}));

const preview = computed(() =>
  buildChartFromConfig(previewConfig.value, props.sessions, props.dateStart, props.athleteOneRm),
);

const exerciseOptions = computed(() => listExerciseNames(props.sessions, props.dateStart));

const showSeriesPicker = computed(
  () => form.chartType !== 'doughnut' && ['week', 'day'].includes(form.groupBy),
);

const showStackedOption = computed(() => form.chartType === 'bar' && showSeriesPicker.value);

function submit(addToDashboard = false) {
  form.add_to_dashboard = addToDashboard;
  form.assignment = props.assignmentId;

  if (props.editingTemplate?.id) {
    form.put(`/coach/chart-templates/${props.editingTemplate.id}`, {
      preserveScroll: true,
      onSuccess: closeModal,
    });
    return;
  }

  form.post('/coach/chart-templates', {
    preserveScroll: true,
    onSuccess: closeModal,
  });
}

function deleteTemplate() {
  if (!props.editingTemplate?.id) {
    return;
  }

  if (!window.confirm(`Supprimer le modèle « ${form.name} » ?`)) {
    return;
  }

  router.delete(`/coach/chart-templates/${props.editingTemplate.id}`, {
    data: { assignment: props.assignmentId },
    preserveScroll: true,
    onSuccess: closeModal,
  });
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div
        class="flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
      >
        <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
          <div>
            <h2 class="text-lg font-semibold text-white">
              {{ editingTemplate ? 'Modifier le modèle' : 'Nouveau graphique' }}
            </h2>
            <p class="mt-0.5 text-sm text-slate-400">Configure les données et prévisualise le résultat.</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <div class="grid flex-1 gap-0 overflow-hidden lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
          <div class="overflow-y-auto border-b border-slate-800 p-6 lg:border-b-0 lg:border-r">
            <div class="space-y-4">
              <label class="block text-sm text-slate-400">
                Nom du modèle
                <input
                  v-model="form.name"
                  type="text"
                  class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                  placeholder="Ex. Volume topsets S/B/T"
                />
              </label>

              <div>
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Presets</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    v-for="preset in CHART_PRESETS"
                    :key="preset.label"
                    type="button"
                    class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:border-blue-500/50 hover:text-white"
                    @click="applyPreset(preset)"
                  >
                    {{ preset.label }}
                  </button>
                </div>
              </div>

              <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm text-slate-400">
                  Type
                  <select
                    v-model="form.chartType"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                  >
                    <option v-for="opt in CHART_TYPE_OPTIONS" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </label>
                <label class="block text-sm text-slate-400">
                  Métrique
                  <select
                    v-model="form.metric"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                  >
                    <option v-for="opt in METRIC_OPTIONS" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </label>
                <label class="block text-sm text-slate-400">
                  Regroupement
                  <select
                    v-model="form.groupBy"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                  >
                    <option v-for="opt in GROUP_BY_OPTIONS" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </label>
                <label v-if="showStackedOption" class="flex items-end gap-2 pb-2 text-sm text-slate-300">
                  <input v-model="form.stacked" type="checkbox" class="rounded border-slate-600 bg-slate-950" />
                  Barres empilées
                </label>
              </div>

              <div v-if="showSeriesPicker">
                <p class="text-sm text-slate-400">Séries (lifts)</p>
                <div class="mt-2 flex flex-wrap gap-2">
                  <button
                    v-for="opt in SERIES_LIFT_OPTIONS"
                    :key="opt.value"
                    type="button"
                    class="rounded-lg border px-3 py-1.5 text-xs transition"
                    :class="
                      form.series.includes(opt.value)
                        ? 'border-blue-500/60 bg-blue-600/20 text-blue-200'
                        : 'border-slate-700 text-slate-400 hover:border-slate-600'
                    "
                    @click="toggleSeries(opt.value)"
                  >
                    {{ opt.label }}
                  </button>
                </div>
              </div>

              <div class="rounded-xl border border-slate-800 bg-slate-950/50 p-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Filtres</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <label class="block text-xs text-slate-400">
                    Main lift
                    <select
                      v-model="form.filters.mainLift"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    >
                      <option v-for="opt in MAIN_LIFT_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                  </label>
                  <label class="block text-xs text-slate-400">
                    Format reps
                    <select
                      v-model="form.filters.repFormat"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    >
                      <option v-for="opt in REP_FORMAT_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                  </label>
                  <label class="block text-xs text-slate-400">
                    Section
                    <select
                      v-model="form.filters.section"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    >
                      <option v-for="opt in SECTION_FILTER_OPTIONS" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                      </option>
                    </select>
                  </label>
                  <label class="block text-xs text-slate-400">
                    Exercice
                    <select
                      v-model="form.filters.exerciseName"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    >
                      <option :value="null">Tous</option>
                      <option v-for="name in exerciseOptions" :key="name" :value="name">
                        {{ name }}
                      </option>
                    </select>
                  </label>
                  <label class="block text-xs text-slate-400">
                    Semaine min
                    <input
                      v-model.number="form.filters.weekFrom"
                      type="number"
                      min="1"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    />
                  </label>
                  <label class="block text-xs text-slate-400">
                    Semaine max
                    <input
                      v-model.number="form.filters.weekTo"
                      type="number"
                      min="1"
                      class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
                    />
                  </label>
                </div>
              </div>
            </div>
          </div>

          <div class="flex flex-col overflow-y-auto p-6">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Aperçu</p>
            <div class="mt-3 flex-1 rounded-xl border border-slate-800 bg-slate-950/40 p-4">
              <div v-if="preview.hasData" class="relative h-72">
                <BarChart
                  v-if="form.chartType === 'bar'"
                  :chart-data="{ labels: preview.labels, datasets: preview.datasets }"
                  :stacked="form.stacked"
                  :options="preview.chartOptions"
                />
                <LineChart
                  v-else-if="form.chartType === 'line'"
                  :chart-data="{ labels: preview.labels, datasets: preview.datasets }"
                  :options="preview.chartOptions"
                />
                <DoughnutChart
                  v-else
                  :chart-data="{ labels: preview.labels, datasets: preview.datasets }"
                  :options="preview.chartOptions"
                />
              </div>
              <p v-else class="py-16 text-center text-sm text-slate-500">
                Programme des séances ou ajuste les filtres pour voir l’aperçu.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 px-6 py-4">
          <button
            v-if="editingTemplate"
            type="button"
            class="text-sm text-red-400 hover:text-red-300"
            @click="deleteTemplate"
          >
            Supprimer le modèle
          </button>
          <div v-else />

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
              @click="closeModal"
            >
              Annuler
            </button>
            <button
              type="button"
              class="rounded-lg border border-blue-500/40 px-4 py-2 text-sm text-blue-200 hover:bg-blue-600/10"
              :disabled="form.processing || !form.name.trim()"
              @click="submit(false)"
            >
              Enregistrer le modèle
            </button>
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
              :disabled="form.processing || !form.name.trim()"
              @click="submit(true)"
            >
              Enregistrer + ajouter
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
