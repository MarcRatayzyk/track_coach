import { cellKey, sessionToDay } from './programBuilder';

export const DEFAULT_INCREMENT_SECTIONS = ['topset', 'backoff', 'accessory'];
export const DEFAULT_INCREMENT_LIFTS = ['squat', 'bench', 'deadlift'];

export function defaultIncrementOptions() {
  return {
    incrementKg: 0,
    incrementPercent: 0,
    incrementRpe: 0,
    sections: [...DEFAULT_INCREMENT_SECTIONS],
    lifts: [...DEFAULT_INCREMENT_LIFTS],
    exerciseNames: [],
  };
}

export function sessionHasClipboardContent(payload) {
  if (!payload) {
    return false;
  }
  return Boolean(
    payload.session_label?.trim() || (payload.items ?? []).some((item) => item.exercise_name?.trim()),
  );
}

/** @param {object|null} session */
export function sessionToClipboardPayload(session) {
  if (!session) {
    return null;
  }

  const day = sessionToDay(session);
  const items = (day.items ?? [])
    .map((item) => ({
      section: item.section,
      exercise_variant_id: item.line.exercise_variant_id,
      exercise_name: item.line.exercise_name,
      lift: item.line.lift,
      sets: item.line.sets,
      reps: item.line.reps,
      load: item.line.load,
      load_percent: item.line.load_percent,
      rpe: item.line.rpe,
      rest_seconds: item.line.rest_seconds,
    }))
    .filter((line) => line.exercise_name?.trim());

  if (!items.length && !day.session_label?.trim()) {
    return null;
  }

  return {
    main_lift: day.lift ?? 'squat',
    session_label: day.session_label?.trim() || null,
    notes: day.notes?.trim() || null,
    items,
  };
}

export function weekSessionsToClipboard(sessions, weekNumber) {
  const days = {};

  for (let weekday = 1; weekday <= 7; weekday++) {
    const key = cellKey(weekNumber, weekday);
    const payload = sessionToClipboardPayload(sessions[key]);
    if (payload) {
      days[weekday] = payload;
    }
  }

  return Object.keys(days).length > 0 ? { weekNumber, sessions: days } : null;
}

export function resolvePasteSessionLabel(copiedLabel, options = {}) {
  const label = (options.sessionLabel ?? '').trim() || (copiedLabel ?? '').trim();

  if (!label) {
    return null;
  }

  return label.toUpperCase();
}

export function resolvePasteSessionNotes(sessionNotes) {
  const notes = (sessionNotes ?? '').trim();

  return notes !== '' ? notes : null;
}

export function clipboardSessionToOperation(payload, weekNumber, weekday) {
  return {
    week_number: weekNumber,
    weekday,
    main_lift: payload.main_lift ?? 'squat',
    session_label: payload.session_label,
    notes: payload.notes ?? null,
    items: payload.items ?? [],
    blocks: [],
  };
}

export function clipboardWeekToOperations(weekClipboard, targetWeekNumber) {
  return Object.entries(weekClipboard.sessions).map(([weekday, payload]) =>
    clipboardSessionToOperation(payload, targetWeekNumber, Number(weekday)),
  );
}

function normalizeExerciseName(name) {
  return (name ?? '').trim().toLowerCase();
}

export function collectClipboardExerciseNames(source) {
  const names = new Set();

  if (source?.items) {
    for (const item of source.items) {
      const name = item.exercise_name?.trim();
      if (name) {
        names.add(name);
      }
    }
  } else if (source?.sessions) {
    for (const payload of Object.values(source.sessions)) {
      for (const item of payload?.items ?? []) {
        const name = item.exercise_name?.trim();
        if (name) {
          names.add(name);
        }
      }
    }
  }

  return [...names].sort((a, b) => a.localeCompare(b, 'fr'));
}

function roundLoad(value) {
  return Number.parseFloat((Math.round(value * 100) / 100).toFixed(2));
}

export function roundPercent(value) {
  return roundLoad(value);
}

export function roundRpe(value) {
  return Number.parseFloat((Math.round(value * 2) / 2).toFixed(1));
}

function hasNumericValue(value) {
  if (value === null || value === '' || typeof value === 'undefined') {
    return false;
  }

  return Number.isFinite(Number(value));
}

export function itemMatchesIncrementFilters(item, filters) {
  const sections = filters.sections ?? DEFAULT_INCREMENT_SECTIONS;
  const lifts = filters.lifts ?? DEFAULT_INCREMENT_LIFTS;
  const exerciseNames = filters.exerciseNames ?? [];

  if (!sections.includes(item.section ?? 'accessory')) {
    return false;
  }

  const itemLift = item.lift;
  if (!itemLift) {
    return false;
  }

  if (!lifts.includes(itemLift)) {
    return false;
  }

  const normalizedName = normalizeExerciseName(item.exercise_name);
  if (!normalizedName) {
    return false;
  }

  if (exerciseNames.length > 0) {
    const allowed = new Set(exerciseNames.map(normalizeExerciseName));
    if (!allowed.has(normalizedName)) {
      return false;
    }
  }

  return true;
}

function incrementClipboardItem(item, options) {
  if (!itemMatchesIncrementFilters(item, options)) {
    return { ...item };
  }

  const next = { ...item };

  if (options.incrementKg !== 0 && hasNumericValue(item.load)) {
    next.load = roundLoad(Number(item.load) + options.incrementKg);
  }

  if (options.incrementPercent !== 0 && hasNumericValue(item.load_percent)) {
    next.load_percent = roundPercent(Number(item.load_percent) + options.incrementPercent);
  }

  if (options.incrementRpe !== 0 && hasNumericValue(item.rpe)) {
    next.rpe = roundRpe(Number(item.rpe) + options.incrementRpe);
  }

  return next;
}

export function applyClipboardSessionIncrements(payload, options = defaultIncrementOptions()) {
  return {
    ...payload,
    items: (payload?.items ?? []).map((item) => incrementClipboardItem(item, options)),
  };
}

export function prepareClipboardSessionForPaste(payload, options = defaultIncrementOptions()) {
  const withIncrements = applyClipboardSessionIncrements(payload, options);

  return {
    ...withIncrements,
    session_label: resolvePasteSessionLabel(withIncrements.session_label, options),
    notes: resolvePasteSessionNotes(options.sessionNotes),
  };
}

export function applyClipboardWeekIncrements(weekClipboard, options = defaultIncrementOptions()) {
  return {
    ...weekClipboard,
    sessions: Object.fromEntries(
      Object.entries(weekClipboard?.sessions ?? {}).map(([weekday, payload]) => [
        weekday,
        prepareClipboardSessionForPaste(payload, options),
      ]),
    ),
  };
}

export function incrementClipboardSessionLoads(payload, incrementKg = 0) {
  return applyClipboardSessionIncrements(payload, {
    ...defaultIncrementOptions(),
    incrementKg,
  });
}

export function incrementClipboardWeekLoads(weekClipboard, incrementKg = 0) {
  return applyClipboardWeekIncrements(weekClipboard, {
    ...defaultIncrementOptions(),
    incrementKg,
  });
}
