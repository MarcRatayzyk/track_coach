function normalizeName(value) {
  return String(value ?? '').trim().toLowerCase().replace(/\s+/g, ' ');
}

function numberOrNull(value) {
  if (value == null || value === '') {
    return null;
  }
  const parsed = Number(String(value).replace(',', '.'));
  return Number.isNaN(parsed) ? null : parsed;
}

function lineOf(item) {
  return item?.line ?? item ?? {};
}

function sectionOf(item, line) {
  return item?.section ?? line?.section ?? '';
}

function variantId(line) {
  const value = line?.exercise_variant_id;
  if (value == null || value === '') {
    return null;
  }
  const parsed = Number(value);
  return Number.isNaN(parsed) ? null : parsed;
}

export function plannedSetsForComparison(line) {
  const scheme = line?.set_scheme ?? 'standard';
  if (scheme === 'ramp') {
    const steps = line?.scheme_config?.steps ?? [];
    if (steps.length) {
      return steps.map((step) => ({
        reps: numberOrNull(step.reps),
        load: numberOrNull(step.load),
        load_percent: numberOrNull(step.load_percent),
        rpe: numberOrNull(step.rpe),
      }));
    }
  }

  const count = scheme === 'cluster' ? 1 : Math.max(1, Number(line?.sets ?? 1) || 1);
  const reps = scheme === 'cluster'
    ? numberOrNull(line?.scheme_config?.reps ?? line?.reps)
    : numberOrNull(line?.reps);

  return Array.from({ length: count }, () => ({
    reps,
    load: numberOrNull(line?.load),
    load_percent: numberOrNull(line?.load_percent),
    rpe: numberOrNull(line?.rpe),
  }));
}

function loggedSnapshots(item) {
  const line = lineOf(item);
  const sets = Math.max(1, Number(line?.sets ?? 1) || 1);
  const snap = {
    reps: numberOrNull(line?.reps),
    load: numberOrNull(line?.load),
    load_percent: numberOrNull(line?.load_percent),
    rpe: numberOrNull(line?.rpe),
  };
  if (snap.reps == null && snap.load == null && snap.load_percent == null && snap.rpe == null) {
    return [];
  }
  return Array.from({ length: sets }, () => ({ ...snap }));
}

function sameExercise(left, right) {
  if (!left || !right || left.section !== right.section) {
    return false;
  }
  if (left.variant != null && right.variant != null) {
    return left.variant === right.variant;
  }
  return left.name !== '' && left.name === right.name;
}

function matchesEntry(entry, planned) {
  if (entry.consumed) {
    return false;
  }
  const sameVariant = planned.variant != null && entry.variant === planned.variant;
  const sameName = planned.name !== '' && entry.name === planned.name;
  if (!sameVariant && !sameName) {
    return false;
  }
  if (entry.section && planned.section && entry.section !== planned.section) {
    return false;
  }
  return true;
}

function formatSet(set) {
  if (!set) {
    return '—';
  }
  const reps = set.reps != null ? String(set.reps) : '—';
  if (set.load != null) {
    return `${reps} × ${set.load} kg`;
  }
  if (set.load_percent != null) {
    return `${reps} × ${set.load_percent}%`;
  }
  if (set.rpe != null) {
    return `${reps} × RPE ${set.rpe}`;
  }
  return reps;
}

function sameValue(left, right) {
  if (left == null && right == null) {
    return true;
  }
  if (left == null || right == null) {
    return false;
  }
  return Math.abs(Number(left) - Number(right)) < 0.051;
}

function setsMatch(planned, actual) {
  if (!planned || !actual) {
    return false;
  }
  if (!sameValue(planned.reps, actual.reps)) {
    return false;
  }
  if (planned.load != null || actual.load != null) {
    return sameValue(planned.load, actual.load);
  }
  if (planned.load_percent != null || actual.load_percent != null) {
    return sameValue(planned.load_percent, actual.load_percent);
  }
  if (planned.rpe != null || actual.rpe != null) {
    return sameValue(planned.rpe, actual.rpe);
  }
  return true;
}

function pushRow(rows, { exercise, setNumber, planned, actual }) {
  rows.push({
    key: `${exercise}-${setNumber}-${rows.length}`,
    exercise: setNumber === 1 ? exercise : '',
    setNumber,
    plannedLabel: formatSet(planned),
    actualLabel: formatSet(actual),
    match: setsMatch(planned, actual),
  });
}

/**
 * Une série faite occupe la ligne de la série prévue du même exercice.
 * Les séries en trop ne sont ajoutées qu'après ce compte, pas à la place d'un autre exercice.
 */
export function buildComparisonRows(plannedItems = [], loggedItems = [], localize = (name) => name) {
  const entries = [];
  loggedItems.forEach((item) => {
    const line = lineOf(item);
    const section = sectionOf(item, line);
    const name = normalizeName(line?.exercise_name);
    const variant = variantId(line);
    const exercise = localize(line?.exercise_name) || line?.exercise_name || '—';
    loggedSnapshots(item).forEach((set) => {
      entries.push({
        set,
        consumed: false,
        section,
        name,
        variant,
        exercise,
      });
    });
  });

  const planned = plannedItems.map((item) => {
    const line = lineOf(item);
    const section = sectionOf(item, line);
    return {
      line,
      section,
      name: normalizeName(line?.exercise_name),
      variant: variantId(line),
      exercise: localize(line?.exercise_name) || line?.exercise_name || '—',
      sets: plannedSetsForComparison(line),
    };
  });

  const rows = [];

  function take(plannedLine) {
    const entry = entries.find((candidate) => matchesEntry(candidate, plannedLine));
    if (!entry) {
      return null;
    }
    entry.consumed = true;
    return entry.set;
  }

  function flush(plannedLine, startAt) {
    let setNumber = startAt;
    entries.forEach((entry) => {
      if (!matchesEntry(entry, plannedLine)) {
        return;
      }
      entry.consumed = true;
      setNumber += 1;
      pushRow(rows, {
        exercise: plannedLine.exercise,
        setNumber,
        planned: null,
        actual: entry.set,
      });
    });
  }

  planned.forEach((plannedLine, index) => {
    plannedLine.sets.forEach((plannedSet, setIndex) => {
      pushRow(rows, {
        exercise: plannedLine.exercise,
        setNumber: setIndex + 1,
        planned: plannedSet,
        actual: take(plannedLine),
      });
    });

    const next = planned[index + 1];
    if (!sameExercise(plannedLine, next)) {
      flush(plannedLine, plannedLine.sets.length);
    }
  });

  const leftovers = entries.filter((entry) => !entry.consumed);
  leftovers.forEach((entry, index) => {
    const previous = leftovers[index - 1];
    const continues = previous
      && previous.exercise === entry.exercise
      && previous.section === entry.section;
    entry.consumed = true;
    pushRow(rows, {
      exercise: entry.exercise,
      setNumber: continues ? rows[rows.length - 1].setNumber + 1 : 1,
      planned: null,
      actual: entry.set,
    });
  });

  return rows;
}
