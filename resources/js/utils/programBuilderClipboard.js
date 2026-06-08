import { cellKey, sessionToDay } from './programBuilder';

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

export function clipboardSessionToOperation(payload, weekNumber, weekday) {
  return {
    week_number: weekNumber,
    weekday,
    main_lift: payload.main_lift ?? 'squat',
    session_label: payload.session_label,
    items: payload.items ?? [],
    blocks: [],
  };
}

export function clipboardWeekToOperations(weekClipboard, targetWeekNumber) {
  return Object.entries(weekClipboard.sessions).map(([weekday, payload]) =>
    clipboardSessionToOperation(payload, targetWeekNumber, Number(weekday)),
  );
}

function roundLoad(value) {
  return Number.parseFloat((Math.round(value * 100) / 100).toFixed(2));
}

function incrementClipboardItemLoad(item, incrementKg) {
  const load = item?.load;

  if (load === null || load === '' || typeof load === 'undefined') {
    return { ...item };
  }

  const numericLoad = Number(load);
  if (!Number.isFinite(numericLoad)) {
    return { ...item };
  }

  return {
    ...item,
    load: roundLoad(numericLoad + incrementKg),
  };
}

export function incrementClipboardSessionLoads(payload, incrementKg = 0) {
  return {
    ...payload,
    items: (payload?.items ?? []).map((item) => incrementClipboardItemLoad(item, incrementKg)),
  };
}

export function incrementClipboardWeekLoads(weekClipboard, incrementKg = 0) {
  return {
    ...weekClipboard,
    sessions: Object.fromEntries(
      Object.entries(weekClipboard?.sessions ?? {}).map(([weekday, payload]) => [
        weekday,
        incrementClipboardSessionLoads(payload, incrementKg),
      ]),
    ),
  };
}
