import i18n from '../i18n';
import { LIFT_LABELS } from '../utils/chartTheme';
import { MAIN_LIFT_FILTER_OPTIONS, REP_FORMAT_OPTIONS } from '../utils/trainingVolume';

function tt(key) {
  return i18n.global.t(key);
}

export const CHART_TYPE_OPTIONS = [
  { value: 'bar', get label() { return tt('config.charts.types.bar'); } },
  { value: 'line', get label() { return tt('config.charts.types.line'); } },
  { value: 'doughnut', get label() { return tt('config.charts.types.doughnut'); } },
];

export const METRIC_OPTIONS = [
  { value: 'volume', get label() { return tt('config.charts.metrics.volume'); }, unit: 'kg·reps' },
  { value: 'tonnage', get label() { return tt('config.charts.metrics.tonnage'); }, unit: 'kg' },
  { value: 'avgLoad', get label() { return tt('config.charts.metrics.avgLoad'); }, unit: 'kg' },
  { value: 'e1rm', get label() { return tt('config.charts.metrics.e1rm'); }, unit: 'kg' },
  { value: 'avgRpe', get label() { return tt('config.charts.metrics.avgRpe'); }, unit: 'RPE' },
  { value: 'setsCount', get label() { return tt('config.charts.metrics.setsCount'); }, unit: 'séries' },
  { value: 'totalReps', get label() { return tt('config.charts.metrics.totalReps'); }, unit: 'reps' },
];

export const GROUP_BY_OPTIONS = [
  { value: 'week', get label() { return tt('config.charts.groupBy.week'); } },
  { value: 'day', get label() { return tt('config.charts.groupBy.day'); } },
  { value: 'lift', get label() { return tt('config.charts.groupBy.lift'); } },
  { value: 'section', get label() { return tt('config.charts.groupBy.section'); } },
  { value: 'exercise', get label() { return tt('config.charts.groupBy.exercise'); } },
];

export const SECTION_FILTER_OPTIONS = [
  { value: 'all', get label() { return tt('config.charts.sections.all'); } },
  { value: 'topset', get label() { return tt('config.charts.sections.topset'); } },
  { value: 'backoff', get label() { return tt('config.charts.sections.backoff'); } },
  { value: 'accessory', get label() { return tt('config.charts.sections.accessory'); } },
  { value: 'warmup', get label() { return tt('config.charts.sections.warmup'); } },
];

export const SERIES_LIFT_OPTIONS = [
  { value: 'squat', get label() { return LIFT_LABELS.squat; } },
  { value: 'bench', get label() { return LIFT_LABELS.bench; } },
  { value: 'deadlift', get label() { return LIFT_LABELS.deadlift; } },
];

export const BUILTIN_CHART_KEYS = {
  VOLUME_WEEKLY: 'volume_weekly',
  TOPSET_E1RM: 'topset_e1rm',
  VOLUME_DISTRIBUTION: 'volume_distribution',
  AVG_LOAD_WEEKLY: 'avg_load_weekly',
};

export const BUILTIN_CHART_META = {
  [BUILTIN_CHART_KEYS.VOLUME_WEEKLY]: {
    get title() { return tt('config.charts.builtin.volumeWeekly'); },
  },
  [BUILTIN_CHART_KEYS.TOPSET_E1RM]: {
    get title() { return tt('config.charts.builtin.topsetE1rm'); },
  },
  [BUILTIN_CHART_KEYS.VOLUME_DISTRIBUTION]: {
    get title() { return tt('config.charts.builtin.volumeDistribution'); },
  },
  [BUILTIN_CHART_KEYS.AVG_LOAD_WEEKLY]: {
    get title() { return tt('config.charts.builtin.avgLoadWeekly'); },
  },
};

export const CHART_PRESETS = [
  {
    get label() { return tt('config.charts.presets.volumeWeekly'); },
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
    get label() { return tt('config.charts.presets.e1rmTopsets'); },
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
    get label() { return tt('config.charts.presets.byLift'); },
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
    get label() { return tt('config.charts.presets.avgLoad'); },
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
