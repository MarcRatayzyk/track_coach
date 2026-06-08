import { cellDate } from './programBuilder';
import {
  LIFTS,
  countExcludedRpeLines,
  hasPercentWithoutRm,
  lineVolume,
  resolveLoadKg,
} from './trainingVolume';

const LIFT_LABELS = {
  squat: 'Squat',
  bench: 'Bench',
  deadlift: 'Deadlift',
};

const DAY_MS = 24 * 60 * 60 * 1000;

function parseIsoDate(value) {
  if (!value) {
    return null;
  }

  const match = String(value).slice(0, 10).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return null;
  }

  const [, year, month, day] = match;
  const date = new Date(Number(year), Number(month) - 1, Number(day), 12, 0, 0, 0);
  return Number.isFinite(date.getTime()) ? date : null;
}

function dateKey(value) {
  const date = value instanceof Date ? value : parseIsoDate(value);
  if (!date) {
    return null;
  }

  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function startOfWeek(value) {
  const date = value instanceof Date ? new Date(value) : parseIsoDate(value);
  if (!date) {
    return null;
  }

  const day = date.getDay();
  const diff = day === 0 ? -6 : 1 - day;
  date.setDate(date.getDate() + diff);
  date.setHours(12, 0, 0, 0);
  return date;
}

function diffInDays(from, to) {
  return Math.floor((to.getTime() - from.getTime()) / DAY_MS);
}

function normalizeLift(lift, fallback = 'squat') {
  if (LIFTS.includes(lift)) {
    return lift;
  }

  if (LIFTS.includes(fallback)) {
    return fallback;
  }

  return 'squat';
}

function normalizeExerciseName(value) {
  return String(value ?? '').trim().toLowerCase();
}

function hasExerciseName(line) {
  return Boolean(String(line?.exercise_name ?? '').trim());
}

function hasNumericValue(value) {
  const parsed = Number(value);
  return Number.isFinite(parsed);
}

function hasExplicitLoadTarget(line) {
  return (
    (line?.load != null && line.load !== '') ||
    (line?.load_percent != null && line.load_percent !== '') ||
    (line?.rpe != null && line.rpe !== '')
  );
}

function valuesMatch(a, b) {
  if (!hasNumericValue(a) || !hasNumericValue(b)) {
    return false;
  }

  return Math.abs(Number(a) - Number(b)) < 0.05;
}

function loadsMatch(plannedLine, actualLine, oneRm = {}, fallbackLift = 'squat') {
  const plannedKg = resolveLoadKg(plannedLine, oneRm, fallbackLift);
  const actualKg = resolveLoadKg(actualLine, oneRm, fallbackLift);

  if (plannedKg != null && actualKg != null) {
    return Math.abs(plannedKg - actualKg) < 0.25;
  }

  if (plannedLine?.rpe != null || actualLine?.rpe != null) {
    return valuesMatch(plannedLine?.rpe, actualLine?.rpe);
  }

  if (plannedLine?.load_percent != null || actualLine?.load_percent != null) {
    return valuesMatch(plannedLine?.load_percent, actualLine?.load_percent);
  }

  return valuesMatch(plannedLine?.load, actualLine?.load);
}

function formatShortDate(value) {
  const date = value instanceof Date ? value : parseIsoDate(value);
  if (!date) {
    return '—';
  }

  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
  });
}

function formatFullDate(value) {
  const date = value instanceof Date ? value : parseIsoDate(value);
  if (!date) {
    return '—';
  }

  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  });
}

function flattenTrainingSessions(trainingSessions = [], oneRm = {}) {
  const rows = [];

  for (const session of trainingSessions ?? []) {
    const mainLift = normalizeLift(session?.main_lift);

    for (const item of session?.items ?? []) {
      if (!hasExerciseName(item)) {
        continue;
      }

      const line = {
        ...item,
        lift: normalizeLift(item?.lift, mainLift),
      };

      rows.push({
        sessionId: session?.id ?? null,
        sessionDate: String(session?.session_date ?? '').slice(0, 10),
        mainLift,
        lift: line.lift,
        line,
        loadKg: resolveLoadKg(line, oneRm, mainLift),
        volume: lineVolume(line, oneRm, mainLift),
      });
    }
  }

  return rows.sort((a, b) => String(a.sessionDate).localeCompare(String(b.sessionDate)));
}

function buildWeeklyTonnage(flatItems = [], weeksToKeep = 6) {
  const buckets = new Map();

  for (const row of flatItems) {
    if (!Number.isFinite(row.volume) || row.volume <= 0) {
      continue;
    }

    const weekStart = startOfWeek(row.sessionDate);
    const weekKey = dateKey(weekStart);
    if (!weekKey) {
      continue;
    }

    if (!buckets.has(weekKey)) {
      buckets.set(weekKey, {
        weekKey,
        weekStart,
        label: formatShortDate(weekStart),
        squat: 0,
        bench: 0,
        deadlift: 0,
        total: 0,
      });
    }

    const bucket = buckets.get(weekKey);
    bucket[row.lift] += row.volume;
    bucket.total += row.volume;
  }

  const points = [...buckets.values()]
    .sort((a, b) => a.weekStart - b.weekStart)
    .slice(-weeksToKeep)
    .map((point) => ({
      ...point,
      squat: Math.round(point.squat),
      bench: Math.round(point.bench),
      deadlift: Math.round(point.deadlift),
      total: Math.round(point.total),
    }));

  const current = points.at(-1) ?? null;
  const previous = points.at(-2) ?? null;

  return {
    points,
    currentWeekTotal: current?.total ?? 0,
    previousWeekTotal: previous?.total ?? 0,
    delta: current && previous ? current.total - previous.total : null,
  };
}

function countSessionsWithinDays(trainingSessions = [], dayCount = 7) {
  const today = new Date();
  today.setHours(12, 0, 0, 0);

  return (trainingSessions ?? []).filter((session) => {
    const date = parseIsoDate(session?.session_date);
    if (!date) {
      return false;
    }

    const daysAgo = diffInDays(date, today);
    return daysAgo >= 0 && daysAgo < dayCount;
  }).length;
}

function buildDistribution(flatItems = [], recentDays = 30) {
  const today = new Date();
  today.setHours(12, 0, 0, 0);

  const totals = { squat: 0, bench: 0, deadlift: 0 };
  const lineCounts = { squat: 0, bench: 0, deadlift: 0 };

  for (const row of flatItems) {
    const date = parseIsoDate(row.sessionDate);
    if (!date) {
      continue;
    }

    const daysAgo = diffInDays(date, today);
    if (daysAgo < 0 || daysAgo >= recentDays) {
      continue;
    }

    lineCounts[row.lift] += 1;
    if (Number.isFinite(row.volume) && row.volume > 0) {
      totals[row.lift] += row.volume;
    }
  }

  const useVolume = Object.values(totals).some((value) => value > 0);
  const source = useVolume ? totals : lineCounts;
  const grandTotal = Object.values(source).reduce((sum, value) => sum + value, 0);

  return {
    basis: useVolume ? 'volume' : 'lines',
    items: LIFTS.map((lift) => {
      const value = Math.round(source[lift]);
      return {
        lift,
        label: LIFT_LABELS[lift],
        value,
        share: grandTotal > 0 ? Math.round((value / grandTotal) * 100) : 0,
      };
    }),
  };
}

function buildRecentActivity(trainingSessions = [], flatItems = []) {
  const latestSession = [...(trainingSessions ?? [])]
    .filter((session) => parseIsoDate(session?.session_date))
    .sort((a, b) => String(b.session_date).localeCompare(String(a.session_date)))[0];

  if (!latestSession) {
    return null;
  }

  const sessionRows = flatItems.filter((row) => row.sessionId === latestSession.id);
  const exerciseCount = (latestSession.items ?? []).filter(hasExerciseName).length;
  const tonnage = Math.round(
    sessionRows.reduce((sum, row) => sum + (Number.isFinite(row.volume) ? row.volume : 0), 0),
  );

  return {
    date: latestSession.session_date,
    dateLabel: formatFullDate(latestSession.session_date),
    sessionLabel: latestSession.session_label?.trim() || 'Séance libre',
    mainLift: LIFT_LABELS[normalizeLift(latestSession.main_lift)],
    exerciseCount,
    tonnage,
    topLoads: [
      { label: 'Squat', value: Number(latestSession.squat ?? 0) || null },
      { label: 'Bench', value: Number(latestSession.bench ?? 0) || null },
      { label: 'Deadlift', value: Number(latestSession.deadlift ?? 0) || null },
    ],
  };
}

function mergeSessionsByDate(trainingSessions = []) {
  const map = new Map();

  for (const session of trainingSessions ?? []) {
    const key = dateKey(session?.session_date);
    if (!key) {
      continue;
    }

    if (!map.has(key)) {
      map.set(key, {
        sessionDate: key,
        mainLift: normalizeLift(session?.main_lift),
        items: [],
      });
    }

    const bucket = map.get(key);
    bucket.items.push(
      ...(session?.items ?? []).filter(hasExerciseName).map((item) => ({
        ...item,
        lift: normalizeLift(item?.lift, bucket.mainLift),
      })),
    );
  }

  return map;
}

function buildPlannedSessions(programBlock, today = new Date()) {
  if (!programBlock?.sessions || !programBlock?.date_start) {
    return [];
  }

  today.setHours(12, 0, 0, 0);

  return Object.entries(programBlock.sessions)
    .map(([key, session]) => {
      const [weekNumber, weekday] = key.split('-').map(Number);
      const date = cellDate(programBlock.date_start, weekNumber, weekday);
      const sessionDate = dateKey(date);
      const items = (session?.items ?? []).filter(hasExerciseName);

      return {
        key,
        weekNumber,
        weekday,
        sessionDate,
        mainLift: normalizeLift(session?.main_lift),
        sessionLabel: session?.session_label ?? null,
        items,
      };
    })
    .filter((session) => session.items.length && parseIsoDate(session.sessionDate) <= today)
    .sort((a, b) => String(a.sessionDate).localeCompare(String(b.sessionDate)));
}

function scorePlannedLine(plannedLine, actualItems, usedIndices, oneRm, fallbackLift) {
  let bestIndex = -1;
  let bestMatchedChecks = 0;
  let bestTotalChecks = 1;

  const plannedName = normalizeExerciseName(plannedLine.exercise_name);

  for (let index = 0; index < actualItems.length; index += 1) {
    if (usedIndices.has(index)) {
      continue;
    }

    const actualLine = actualItems[index];
    const sameVariant =
      plannedLine.exercise_variant_id &&
      actualLine.exercise_variant_id &&
      Number(plannedLine.exercise_variant_id) === Number(actualLine.exercise_variant_id);
    const sameName = normalizeExerciseName(actualLine.exercise_name) === plannedName;

    if (!sameVariant && !sameName) {
      continue;
    }

    let matchedChecks = 1;
    let totalChecks = 1;

    if (hasNumericValue(plannedLine.sets)) {
      totalChecks += 1;
      if (valuesMatch(plannedLine.sets, actualLine.sets)) {
        matchedChecks += 1;
      }
    }

    if (hasNumericValue(plannedLine.reps)) {
      totalChecks += 1;
      if (valuesMatch(plannedLine.reps, actualLine.reps)) {
        matchedChecks += 1;
      }
    }

    if (hasExplicitLoadTarget(plannedLine)) {
      totalChecks += 1;
      if (loadsMatch(plannedLine, actualLine, oneRm, fallbackLift)) {
        matchedChecks += 1;
      }
    }

    const bestRatio = bestMatchedChecks / bestTotalChecks;
    const candidateRatio = matchedChecks / totalChecks;
    if (
      bestIndex === -1 ||
      candidateRatio > bestRatio ||
      (candidateRatio === bestRatio && matchedChecks > bestMatchedChecks)
    ) {
      bestIndex = index;
      bestMatchedChecks = matchedChecks;
      bestTotalChecks = totalChecks;
    }
  }

  if (bestIndex >= 0) {
    usedIndices.add(bestIndex);
  } else {
    let totalChecks = 1;
    if (hasNumericValue(plannedLine.sets)) {
      totalChecks += 1;
    }
    if (hasNumericValue(plannedLine.reps)) {
      totalChecks += 1;
    }
    if (hasExplicitLoadTarget(plannedLine)) {
      totalChecks += 1;
    }

    bestMatchedChecks = 0;
    bestTotalChecks = totalChecks;
  }

  return {
    matchedChecks: bestMatchedChecks,
    totalChecks: bestTotalChecks,
    exact: bestMatchedChecks === bestTotalChecks && bestTotalChecks > 0,
  };
}

function buildAdherence(programBlock, trainingSessions = [], oneRm = {}) {
  const plannedSessions = buildPlannedSessions(programBlock);
  if (!plannedSessions.length) {
    return null;
  }

  const actualByDate = mergeSessionsByDate(trainingSessions);
  let matchedChecks = 0;
  let totalChecks = 0;
  let exactLines = 0;
  let plannedLines = 0;
  let completedSessions = 0;

  for (const plannedSession of plannedSessions) {
    const actualSession = actualByDate.get(plannedSession.sessionDate);
    if (actualSession?.items?.length) {
      completedSessions += 1;
    }

    const usedIndices = new Set();
    const actualItems = actualSession?.items ?? [];

    for (const plannedLine of plannedSession.items) {
      plannedLines += 1;
      const score = scorePlannedLine(
        plannedLine,
        actualItems,
        usedIndices,
        oneRm,
        plannedSession.mainLift,
      );
      matchedChecks += score.matchedChecks;
      totalChecks += score.totalChecks;
      if (score.exact) {
        exactLines += 1;
      }
    }
  }

  return {
    percentage: totalChecks > 0 ? Math.round((matchedChecks / totalChecks) * 100) : null,
    plannedSessions: plannedSessions.length,
    completedSessions,
    sessionCoverage:
      plannedSessions.length > 0 ? Math.round((completedSessions / plannedSessions.length) * 100) : null,
    plannedLines,
    exactLines,
    exactLineCoverage:
      plannedLines > 0 ? Math.round((exactLines / plannedLines) * 100) : null,
  };
}

export function buildAthleteOverviewStats({
  trainingSessions = [],
  programBlock = null,
  oneRm = {},
} = {}) {
  const flatItems = flattenTrainingSessions(trainingSessions, oneRm);
  const weeklyTonnage = buildWeeklyTonnage(flatItems);
  const recentActivity = buildRecentActivity(trainingSessions, flatItems);
  const adherence = buildAdherence(programBlock, trainingSessions, oneRm);

  return {
    flatItems,
    weeklyTonnage,
    sessionCount7d: countSessionsWithinDays(trainingSessions, 7),
    sessionCount30d: countSessionsWithinDays(trainingSessions, 30),
    distribution30d: buildDistribution(flatItems, 30),
    recentActivity,
    adherence,
    excludedRpeLines: countExcludedRpeLines(flatItems),
    hasPercentWithoutRm: hasPercentWithoutRm(flatItems, oneRm),
  };
}
