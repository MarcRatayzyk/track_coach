import { LIFT_LABELS } from '../utils/chartTheme';
import { MAIN_LIFT_FILTER_OPTIONS, REP_FORMAT_OPTIONS } from '../utils/trainingVolume';

export const CHART_TYPE_OPTIONS = [
  { value: 'bar', label: 'Barres' },
  { value: 'line', label: 'Courbe' },
  { value: 'doughnut', label: 'Donut' },
];

export const METRIC_OPTIONS = [
  { value: 'volume', label: 'Volume (kg·reps)', unit: 'kg·reps' },
  { value: 'tonnage', label: 'Tonnage (kg×séries)', unit: 'kg' },
  { value: 'avgLoad', label: 'Charge moyenne (kg)', unit: 'kg' },
  { value: 'e1rm', label: 'e1RM Epley (kg)', unit: 'kg' },
  { value: 'setsCount', label: 'Nombre de séries', unit: 'séries' },
  { value: 'totalReps', label: 'Total reps', unit: 'reps' },
];

export const GROUP_BY_OPTIONS = [
  { value: 'week', label: 'Par semaine' },
  { value: 'day', label: 'Par jour' },
  { value: 'lift', label: 'Par lift (S/B/T)' },
  { value: 'section', label: 'Par type (topset/backoff/accessoire)' },
  { value: 'exercise', label: 'Par exercice' },
];

export const SECTION_FILTER_OPTIONS = [
  { value: 'all', label: 'Toutes sections' },
  { value: 'topset', label: 'Topset' },
  { value: 'backoff', label: 'Backoff' },
  { value: 'accessory', label: 'Accessoire' },
  { value: 'warmup', label: 'Échauffement' },
];

export const SERIES_LIFT_OPTIONS = [
  { value: 'squat', label: LIFT_LABELS.squat },
  { value: 'bench', label: LIFT_LABELS.bench },
  { value: 'deadlift', label: LIFT_LABELS.deadlift },
];

export const BUILTIN_CHART_KEYS = {
  VOLUME_WEEKLY: 'volume_weekly',
  TOPSET_E1RM: 'topset_e1rm',
  VOLUME_DISTRIBUTION: 'volume_distribution',
  AVG_LOAD_WEEKLY: 'avg_load_weekly',
};

export const BUILTIN_CHART_META = {
  [BUILTIN_CHART_KEYS.VOLUME_WEEKLY]: {
    title: 'Évolution du volume par semaine',
  },
  [BUILTIN_CHART_KEYS.TOPSET_E1RM]: {
    title: 'Évolution du topset (e1RM Epley)',
  },
  [BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION]: {
    title: 'Répartition du volume',
  },
  [BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY]: {
    title: 'Charge moyenne par semaine',
  },
};

export const CHART_PRESETS = [
  {
    label: 'Volume hebdomadaire',
    config: {
      chartType: 'bar',
      metric: 'volume',
      groupBy: 'week',
      series: ['squat', 'bench', 'deadlift'],
      stacked: true,
      filters: defaultFilters(),
    },
  },
  {
    label: 'e1RM topsets',
    config: {
      chartType: 'line',
      metric: 'e1rm',
      groupBy: 'week',
      series: ['squat', 'bench', 'deadlift'],
      stacked: false,
      filters: { ...defaultFilters(), section: 'topset' },
    },
  },
  {
    label: 'Répartition par lift',
    config: {
      chartType: 'doughnut',
      metric: 'volume',
      groupBy: 'lift',
      series: ['squat', 'bench', 'deadlift'],
      stacked: false,
      filters: defaultFilters(),
    },
  },
  {
    label: 'Charge moyenne',
    config: {
      chartType: 'line',
      metric: 'avgLoad',
      groupBy: 'week',
      series: ['squat', 'bench', 'deadlift'],
      stacked: false,
      filters: defaultFilters(),
    },
  },
];

export function defaultFilters() {
  return {
    mainLift: 'all',
    repFormat: 'all',
    section: 'all',
    weekFrom: null,
    weekTo: null,
    exerciseName: null,
  };
}

export function defaultChartConfig() {
  return {
    chartType: 'bar',
    metric: 'volume',
    groupBy: 'week',
    series: ['squat', 'bench', 'deadlift'],
    stacked: true,
    filters: defaultFilters(),
  };
}

export function metricLabel(metric) {
  return METRIC_OPTIONS.find((option) => option.value === metric)?.label ?? metric;
}

export function metricUnit(metric) {
  return METRIC_OPTIONS.find((option) => option.value === metric)?.unit ?? '';
}

export {
  MAIN_LIFT_FILTER_OPTIONS,
  REP_FORMAT_OPTIONS,
};
