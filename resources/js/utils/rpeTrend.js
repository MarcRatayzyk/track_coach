const SBD_PATTERNS = {
  squat: /squat|back squat|front squat/i,
  bench: /bench|développé|developpe/i,
  deadlift: /deadlift|soulevé|souleve|terre|rdl|romanian/i,
};

function parseRpe(value) {
  const numeric = Number(value);
  if (!Number.isFinite(numeric) || numeric < 1 || numeric > 10) {
    return null;
  }
  return Math.round(numeric * 2) / 2;
}

function normalizeExerciseKey(name) {
  const label = String(name ?? '').trim();
  if (!label) {
    return 'other';
  }
  if (SBD_PATTERNS.squat.test(label)) {
    return 'squat';
  }
  if (SBD_PATTERNS.bench.test(label)) {
    return 'bench';
  }
  if (SBD_PATTERNS.deadlift.test(label)) {
    return 'deadlift';
  }
  return 'other';
}

function lineWeight(item) {
  const sets = Math.max(1, Number(item?.sets ?? item?.set_count ?? 1) || 1);
  return sets;
}

function sessionItems(session) {
  return session?.items ?? [];
}

function averageRpeForItems(items, exerciseFilter = 'all') {
  let weightedSum = 0;
  let totalWeight = 0;

  items.forEach((item) => {
    const rpe = parseRpe(item?.rpe);
    if (rpe === null) {
      return;
    }

    const exerciseKey = normalizeExerciseKey(item?.exercise_name ?? item?.name ?? item?.label);
    if (exerciseFilter !== 'all' && exerciseKey !== exerciseFilter) {
      return;
    }

    const weight = lineWeight(item);
    weightedSum += rpe * weight;
    totalWeight += weight;
  });

  if (totalWeight === 0) {
    return null;
  }

  return Math.round((weightedSum / totalWeight) * 2) / 2;
}

export function buildRpeTrendSeries(trainingSessions, { exerciseFilter = 'all' } = {}) {
  const points = (trainingSessions ?? [])
    .map((session) => {
      const averageRpe = averageRpeForItems(sessionItems(session), exerciseFilter);
      if (averageRpe === null) {
        return null;
      }

      return {
        session_date: String(session.session_date ?? '').slice(0, 10),
        average_rpe: averageRpe,
      };
    })
    .filter(Boolean)
    .sort((a, b) => a.session_date.localeCompare(b.session_date));

  return points;
}

export function filterRpeTrendByRange(points, rangeKey, referenceDate = new Date()) {
  if (rangeKey === 'all') {
    return points;
  }

  const end = new Date(referenceDate.getFullYear(), referenceDate.getMonth(), referenceDate.getDate());
  let start = new Date(end);

  if (rangeKey === '7d') {
    start.setDate(start.getDate() - 6);
  } else if (rangeKey === '1m') {
    start.setMonth(start.getMonth() - 1);
  } else if (rangeKey === '3m') {
    start.setMonth(start.getMonth() - 3);
  } else if (rangeKey === '6m') {
    start.setMonth(start.getMonth() - 6);
  } else {
    return points;
  }

  const startKey = start.toISOString().slice(0, 10);
  const endKey = end.toISOString().slice(0, 10);

  return points.filter((point) => point.session_date >= startKey && point.session_date <= endKey);
}

export const RPE_EXERCISE_FILTERS = [
  { value: 'all', label: 'Toutes séries' },
  { value: 'squat', label: 'Squat' },
  { value: 'bench', label: 'Bench' },
  { value: 'deadlift', label: 'Terre' },
  { value: 'other', label: 'Accessoires' },
];
