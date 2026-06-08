import { WEEKDAY_LABELS } from './programBuilder';
import {
  epleyE1rm,
  flattenBlockItems,
  lineVolume,
  resolveLoadKg,
} from './trainingVolume';
import { LIFT_COLORS, LIFT_LABELS, baseChartOptions, doughnutChartOptions } from './chartTheme';
import { metricUnit } from '../config/chartBuilderOptions';

const LIFTS = ['squat', 'bench', 'deadlift'];
const SECTION_LABELS = {
  topset: 'Topset',
  backoff: 'Backoff',
  accessory: 'Accessoire',
};

const GENERIC_COLORS = [
  'rgb(96, 165, 250)',
  'rgb(52, 211, 153)',
  'rgb(251, 191, 36)',
  'rgb(167, 139, 250)',
  'rgb(244, 114, 182)',
  'rgb(34, 211, 238)',
  'rgb(248, 113, 113)',
  'rgb(163, 230, 53)',
];

function inferLoadMode(line) {
  if (line?.rpe != null && line.rpe !== '') {
    return 'rpe';
  }
  if (line?.load_percent != null && line.load_percent !== '') {
    return 'percent';
  }
  if (line?.load != null && line.load !== '') {
    return 'kg';
  }
  return line?.load_mode ?? null;
}

function isRpeLine(line) {
  return inferLoadMode(line) === 'rpe';
}

function applyFilters(flatItems, filters = {}) {
  return flatItems.filter((row) => {
    if (filters.mainLift && filters.mainLift !== 'all' && row.lift !== filters.mainLift) {
      return false;
    }
    if (filters.repFormat && filters.repFormat !== 'all' && row.format !== filters.repFormat) {
      return false;
    }
    const section = row.section ?? row.line?.section ?? '';
    if (filters.section && filters.section !== 'all' && section !== filters.section) {
      return false;
    }
    if (filters.weekFrom && row.weekNumber < Number(filters.weekFrom)) {
      return false;
    }
    if (filters.weekTo && row.weekNumber > Number(filters.weekTo)) {
      return false;
    }
    if (filters.exerciseName?.trim()) {
      const name = row.line?.exercise_name?.trim()?.toLowerCase() ?? '';
      if (name !== filters.exerciseName.trim().toLowerCase()) {
        return false;
      }
    }
    return true;
  });
}

function rowMetricValues(row, metric, oneRm) {
  const line = row.line;

  if (metric !== 'setsCount' && metric !== 'totalReps' && isRpeLine(line)) {
    return null;
  }

  switch (metric) {
    case 'volume':
      return lineVolume(line, oneRm, row.mainLift);
    case 'tonnage': {
      const load = resolveLoadKg(line, oneRm, row.mainLift);
      const sets = Number(line?.sets ?? 0);
      if (load == null || !Number.isFinite(sets) || sets <= 0) {
        return null;
      }
      return load * sets;
    }
    case 'avgLoad':
      return resolveLoadKg(line, oneRm, row.mainLift);
    case 'e1rm': {
      const load = resolveLoadKg(line, oneRm, row.mainLift);
      return epleyE1rm(load, line?.reps);
    }
    case 'setsCount': {
      const sets = Number(line?.sets ?? 0);
      return Number.isFinite(sets) && sets > 0 ? sets : null;
    }
    case 'totalReps': {
      const sets = Number(line?.sets ?? 0);
      const reps = Number(line?.reps ?? 0);
      if (!Number.isFinite(sets) || sets <= 0 || !Number.isFinite(reps) || reps <= 0) {
        return null;
      }
      return sets * reps;
    }
    default:
      return null;
  }
}

function groupKey(row, groupBy) {
  switch (groupBy) {
    case 'week':
      return `w-${row.weekNumber}`;
    case 'day':
      return `d-${row.weekNumber}-${row.weekday}`;
    case 'lift':
      return `l-${row.lift}`;
    case 'section':
      return `s-${row.section ?? row.line?.section ?? 'unknown'}`;
    case 'exercise':
      return `e-${row.line?.exercise_name?.trim() ?? 'unknown'}`;
    default:
      return `w-${row.weekNumber}`;
  }
}

function groupLabel(key, groupBy) {
  switch (groupBy) {
    case 'week':
      return `S${key.replace('w-', '')}`;
    case 'day': {
      const [, week, weekday] = key.match(/^d-(\d+)-(\d+)$/) ?? [];
      const dayLabel = WEEKDAY_LABELS[Number(weekday) - 1] ?? `J${weekday}`;
      return `${dayLabel} · S${week}`;
    }
    case 'lift':
      return LIFT_LABELS[key.replace('l-', '')] ?? key;
    case 'section':
      return SECTION_LABELS[key.replace('s-', '')] ?? key.replace('s-', '');
    case 'exercise':
      return key.replace('e-', '');
    default:
      return key;
  }
}

function sortGroupKeys(keys, groupBy) {
  if (groupBy === 'week') {
    return [...keys].sort((a, b) => Number(a.replace('w-', '')) - Number(b.replace('w-', '')));
  }
  if (groupBy === 'day') {
    return [...keys].sort((a, b) => {
      const [, weekA, dayA] = a.match(/^d-(\d+)-(\d+)$/) ?? [];
      const [, weekB, dayB] = b.match(/^d-(\d+)-(\d+)$/) ?? [];
      if (Number(weekA) !== Number(weekB)) {
        return Number(weekA) - Number(weekB);
      }
      return Number(dayA) - Number(dayB);
    });
  }
  if (groupBy === 'lift') {
    const order = { 'l-squat': 0, 'l-bench': 1, 'l-deadlift': 2 };
    return [...keys].sort((a, b) => (order[a] ?? 99) - (order[b] ?? 99));
  }
  return [...keys].sort((a, b) => groupLabel(a, groupBy).localeCompare(groupLabel(b, groupBy), 'fr'));
}

function aggregateMetric(values, metric) {
  const valid = values.filter((value) => value != null && Number.isFinite(value));
  if (valid.length === 0) {
    return null;
  }

  if (metric === 'avgLoad') {
    const sum = valid.reduce((acc, value) => acc + value, 0);
    return Math.round((sum / valid.length) * 10) / 10;
  }

  if (metric === 'e1rm') {
    return Math.round(Math.max(...valid) * 10) / 10;
  }

  const sum = valid.reduce((acc, value) => acc + value, 0);
  return Math.round(sum * 10) / 10;
}

function colorForGroup(key, groupBy, index) {
  if (groupBy === 'lift') {
    const lift = key.replace('l-', '');
    return LIFT_COLORS[lift]?.border ?? GENERIC_COLORS[index % GENERIC_COLORS.length];
  }
  return GENERIC_COLORS[index % GENERIC_COLORS.length];
}

function usesMultiSeries(config) {
  return (
    config.chartType !== 'doughnut'
    && ['week', 'day'].includes(config.groupBy)
    && (config.series?.length ?? 0) > 0
  );
}

function buildMultiSeriesChart(filtered, config, oneRm) {
  const seriesLifts = (config.series ?? LIFTS).filter((lift) => LIFTS.includes(lift));
  const groupKeys = sortGroupKeys(
    [...new Set(filtered.map((row) => groupKey(row, config.groupBy)))],
    config.groupBy,
  );

  const datasets = seriesLifts.map((lift) => {
    const data = groupKeys.map((key) => {
      const rows = filtered.filter(
        (row) => groupKey(row, config.groupBy) === key && row.lift === lift,
      );
      const values = rows
        .map((row) => rowMetricValues(row, config.metric, oneRm))
        .filter((value) => value != null);
      return aggregateMetric(values, config.metric);
    });

    const colors = LIFT_COLORS[lift] ?? LIFT_COLORS.squat;

    if (config.chartType === 'bar') {
      return {
        label: LIFT_LABELS[lift],
        data,
        backgroundColor: colors.bg,
        borderColor: colors.border,
        borderWidth: 1,
      };
    }

    return {
      label: LIFT_LABELS[lift],
      data,
      borderColor: colors.border,
      backgroundColor: colors.bg,
      tension: 0.25,
      spanGaps: true,
      pointRadius: 4,
    };
  });

  const hasData = datasets.some((dataset) => dataset.data.some((value) => value != null && value > 0));

  return {
    labels: groupKeys.map((key) => groupLabel(key, config.groupBy)),
    datasets,
    hasData,
  };
}

function buildSingleSeriesChart(filtered, config, oneRm) {
  const buckets = new Map();

  for (const row of filtered) {
    const key = groupKey(row, config.groupBy);
    if (!buckets.has(key)) {
      buckets.set(key, []);
    }
    const value = rowMetricValues(row, config.metric, oneRm);
    if (value != null) {
      buckets.get(key).push(value);
    }
  }

  const groupKeys = sortGroupKeys([...buckets.keys()], config.groupBy);
  const data = groupKeys.map((key) => aggregateMetric(buckets.get(key) ?? [], config.metric));
  const hasData = data.some((value) => value != null && value > 0);

  if (config.chartType === 'doughnut') {
    return {
      labels: groupKeys.map((key) => groupLabel(key, config.groupBy)),
      datasets: [
        {
          data,
          backgroundColor: groupKeys.map((key, index) => colorForGroup(key, config.groupBy, index)),
          borderColor: groupKeys.map((key, index) => colorForGroup(key, config.groupBy, index)),
          borderAlign: 'inner',
          borderWidth: 1,
          spacing: 0,
        },
      ],
      hasData,
    };
  }

  const barColor = LIFT_COLORS.total;

  if (config.chartType === 'bar') {
    return {
      labels: groupKeys.map((key) => groupLabel(key, config.groupBy)),
      datasets: [
        {
          label: metricUnit(config.metric),
          data,
          backgroundColor: barColor.bg,
          borderColor: barColor.border,
          borderWidth: 1,
        },
      ],
      hasData,
    };
  }

  return {
    labels: groupKeys.map((key) => groupLabel(key, config.groupBy)),
    datasets: [
      {
        label: metricUnit(config.metric),
        data,
        borderColor: barColor.border,
        backgroundColor: barColor.bg,
        tension: 0.25,
        spanGaps: true,
        pointRadius: 4,
      },
    ],
    hasData,
  };
}

export function buildChartFromConfig(config, sessions, dateStart, athleteOneRm = {}) {
  const oneRm = {
    squat: Number(athleteOneRm?.squat ?? 0),
    bench: Number(athleteOneRm?.bench ?? 0),
    deadlift: Number(athleteOneRm?.deadlift ?? 0),
  };

  const flatItems = flattenBlockItems(sessions, dateStart);
  const filtered = applyFilters(flatItems, config.filters ?? {});

  const chartData = usesMultiSeries(config)
    ? buildMultiSeriesChart(filtered, config, oneRm)
    : buildSingleSeriesChart(filtered, config, oneRm);

  return {
    ...chartData,
    chartOptions: buildChartOptions(config),
  };
}

export function buildChartOptions(config) {
  const unit = metricUnit(config.metric);
  const yTitle = unit ? `${config.metric === 'volume' ? 'Volume' : 'Valeur'} (${unit})` : 'Valeur';

  if (config.chartType === 'doughnut') {
    return doughnutChartOptions({
      plugins: {
        tooltip: {
          callbacks: {
            label(context) {
              const value = context.parsed ?? 0;
              const total = context.dataset.data.reduce((acc, item) => acc + (item ?? 0), 0);
              const pct = total > 0 ? Math.round((value / total) * 100) : 0;
              return `${context.label}: ${Math.round(value).toLocaleString('fr-FR')} (${pct} %)`;
            },
          },
        },
      },
    });
  }

  const stacked = config.chartType === 'bar' && config.stacked && usesMultiSeries(config);

  return baseChartOptions({
    scales: {
      x: stacked ? { stacked: true } : undefined,
      y: {
        ...(stacked ? { stacked: true } : {}),
        title: { display: true, text: yTitle, color: 'rgb(148, 163, 184)' },
      },
    },
    plugins: {
      tooltip: {
        callbacks: {
          label(context) {
            const value = context.parsed?.y;
            if (value == null) {
              return `${context.dataset.label}: —`;
            }
            return `${context.dataset.label}: ${value.toLocaleString('fr-FR')}${unit ? ` ${unit}` : ''}`;
          },
        },
      },
    },
  });
}

export function listExerciseNames(sessions, dateStart = '') {
  const names = new Set();
  for (const row of flattenBlockItems(sessions, dateStart)) {
    const name = row.line?.exercise_name?.trim();
    if (name) {
      names.add(name);
    }
  }
  return [...names].sort((a, b) => a.localeCompare(b, 'fr'));
}

export { applyFilters, flattenBlockItems };
