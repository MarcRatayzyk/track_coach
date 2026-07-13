import { resolveLoadKg } from './trainingVolume';

function normalizeExerciseName(value) {
  return String(value ?? '').trim().toLowerCase();
}

function normalizeLift(lift, fallback = 'squat') {
  const lifts = ['squat', 'bench', 'deadlift'];
  if (lifts.includes(lift)) {
    return lift;
  }
  return lifts.includes(fallback) ? fallback : 'squat';
}

export function hasNumericValue(value) {
  if (value === null || value === '') {
    return false;
  }
  return Number.isFinite(Number(value));
}

export function hasExplicitLoadTarget(line) {
  return (
    hasNumericValue(line?.load) ||
    hasNumericValue(line?.load_percent) ||
    hasNumericValue(line?.rpe)
  );
}

export function valuesMatch(a, b) {
  if (!hasNumericValue(a) || !hasNumericValue(b)) {
    return false;
  }
  return Math.abs(Number(a) - Number(b)) < 0.05;
}

function resolveLoadKgIgnoringRpe(line, oneRm, fallbackLift) {
  if (hasNumericValue(line?.load)) {
    const load = Number(line.load);
    return load > 0 ? load : null;
  }

  if (hasNumericValue(line?.load_percent)) {
    const lift = normalizeLift(line?.lift, fallbackLift);
    const pct = Number(line.load_percent);
    const rm = Number(oneRm?.[lift] ?? 0);
    if (pct > 0 && rm > 0) {
      return (pct / 100) * rm;
    }
  }

  return resolveLoadKg(line, oneRm, fallbackLift);
}

export function loadsMatch(plannedLine, actualLine, oneRm = {}, fallbackLift = 'squat') {
  const plannedLift = normalizeLift(plannedLine?.lift, fallbackLift);
  const actualLift = normalizeLift(actualLine?.lift, fallbackLift);
  const plannedKg = resolveLoadKgIgnoringRpe(plannedLine, oneRm, plannedLift);
  const actualKg = resolveLoadKgIgnoringRpe(actualLine, oneRm, actualLift);

  if (plannedKg != null && actualKg != null) {
    return Math.abs(plannedKg - actualKg) < 0.25;
  }

  if (hasNumericValue(plannedLine?.rpe) || hasNumericValue(actualLine?.rpe)) {
    return valuesMatch(plannedLine?.rpe, actualLine?.rpe);
  }

  if (hasNumericValue(plannedLine?.load_percent) || hasNumericValue(actualLine?.load_percent)) {
    return valuesMatch(plannedLine?.load_percent, actualLine?.load_percent);
  }

  return valuesMatch(plannedLine?.load, actualLine?.load);
}

function sectionsCompatible(plannedLine, actualLine) {
  const plannedSection = String(plannedLine?.section ?? '').trim();
  const actualSection = String(actualLine?.section ?? '').trim();

  if (!plannedSection || !actualSection) {
    return true;
  }

  return plannedSection === actualSection;
}

export function scorePlannedLine(plannedLine, actualItems, usedIndices, oneRm, fallbackLift) {
  let bestIndex = -1;
  let bestMatchedChecks = 0;
  let bestTotalChecks = 1;

  const plannedName = normalizeExerciseName(plannedLine.exercise_name);
  const plannedLift = normalizeLift(plannedLine?.lift, fallbackLift);

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
    const sameSection = sectionsCompatible(plannedLine, actualLine);

    if (!sameSection || (!sameVariant && !sameName)) {
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
      if (loadsMatch(plannedLine, actualLine, oneRm, plannedLift)) {
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

export function scoreDayAdherence(plannedItems = [], actualItems = [], oneRm = {}, fallbackLift = 'squat') {
  const usedIndices = new Set();
  let matchedChecks = 0;
  let totalChecks = 0;
  let exactLines = 0;

  for (const plannedLine of plannedItems) {
    const score = scorePlannedLine(plannedLine, actualItems, usedIndices, oneRm, fallbackLift);
    matchedChecks += score.matchedChecks;
    totalChecks += score.totalChecks;
    if (score.exact) {
      exactLines += 1;
    }
  }

  return {
    percentage: totalChecks > 0 ? Math.round((matchedChecks / totalChecks) * 100) : null,
    matchedChecks,
    totalChecks,
    plannedLines: plannedItems.length,
    exactLines,
    exactLineCoverage:
      plannedItems.length > 0 ? Math.round((exactLines / plannedItems.length) * 100) : null,
  };
}
