import i18n from '../i18n';
import { cellDate, cellKey, WEEKDAY_LABELS } from './programBuilder';
import { formatCalendarFr } from './formatDates';
import { localizedExerciseName } from './exerciseNames';

function tt(key, params) {
  return i18n.global.t(key, params);
}

const LIFTS = ['squat', 'bench', 'deadlift'];

export const REP_FORMAT_OPTIONS = [
  { value: 'all', get label() { return tt('config.lifts.all'); } },
  { value: 'single', get label() { return tt('config.repFormats.single'); } },
  { value: 'double', get label() { return tt('config.repFormats.double'); } },
  { value: 'triple', get label() { return tt('config.repFormats.triple'); } },
  { value: '4', get label() { return tt('config.repFormats.four'); } },
  { value: '5', get label() { return tt('config.repFormats.five'); } },
  { value: '6plus', get label() { return tt('config.repFormats.sixPlus'); } },
];

export const MAIN_LIFT_FILTER_OPTIONS = [
  { value: 'all', get label() { return tt('config.lifts.all'); } },
  { value: 'squat', get label() { return tt('config.lifts.squat'); } },
  { value: 'bench', get label() { return tt('config.lifts.bench'); } },
  { value: 'deadlift', get label() { return tt('config.lifts.deadlift'); } },
];

export function repFormatBucket(reps) {
  const n = Number(reps);
  if (!Number.isFinite(n) || n < 1) {
    return null;
  }
  if (n === 1) {
    return 'single';
  }
  if (n === 2) {
    return 'double';
  }
  if (n === 3) {
    return 'triple';
  }
  if (n === 4) {
    return '4';
  }
  if (n === 5) {
    return '5';
  }
  return '6plus';
}

function inferLoadMode(line) {
  // Aligné sur programBuilder / chartBuilderEngine :
  // RPE cible peut coexister avec une charge en kg/% — ne pas l'écraser.
  const explicit = line?.load_mode;
  if (explicit === 'kg' || explicit === 'percent' || explicit === 'rpe') {
    return explicit;
  }
  if (line?.load != null && line.load !== '') {
    return 'kg';
  }
  if (line?.load_percent != null && line.load_percent !== '') {
    return 'percent';
  }
  if (line?.rpe != null && line.rpe !== '') {
    return 'rpe';
  }
  return null;
}

function resolveLift(line, fallbackLift = 'squat') {
  const lift = line?.lift ?? fallbackLift;
  return LIFTS.includes(lift) ? lift : 'squat';
}

/**
 * @param {object} line
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 * @param {string} [fallbackLift]
 * @returns {number|null}
 */
export function resolveLoadKg(line, oneRm = {}, fallbackLift = 'squat') {
  if (!line) {
    return null;
  }

  const mode = inferLoadMode(line);
  const lift = resolveLift(line, fallbackLift);

  if (mode === 'rpe') {
    return null;
  }

  if (mode === 'kg') {
    const load = Number(line.load);
    return Number.isFinite(load) && load > 0 ? load : null;
  }

  if (mode === 'percent') {
    const pct = Number(line.load_percent);
    const rm = Number(oneRm[lift] ?? 0);
    if (!Number.isFinite(pct) || pct <= 0 || !Number.isFinite(rm) || rm <= 0) {
      return null;
    }
    return (pct / 100) * rm;
  }

  return null;
}

/**
 * @param {object} line
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 * @param {string} [sessionMainLift]
 * @returns {number|null}
 */
export function lineVolume(line, oneRm = {}, sessionMainLift = 'squat') {
  if (!line) {
    return null;
  }

  // Ramp : sommer chaque palier (sets plats seuls sous-estiment ou sont vides en UI).
  if ((line.set_scheme ?? 'standard') === 'ramp') {
    const steps = Array.isArray(line.scheme_config?.steps) ? line.scheme_config.steps : [];
    if (steps.length > 0) {
      let total = 0;
      let any = false;
      for (const step of steps) {
        const stepVol = lineVolume(
          {
            ...line,
            set_scheme: 'standard',
            scheme_config: null,
            load_mode: null,
            sets: 1,
            reps: step?.reps,
            load: step?.load,
            load_percent: step?.load_percent,
            rpe: step?.rpe,
          },
          oneRm,
          sessionMainLift,
        );
        if (stepVol != null) {
          total += stepVol;
          any = true;
        }
      }
      return any ? total : null;
    }
  }

  const loadKg = resolveLoadKg(line, oneRm, sessionMainLift);
  const sets = Number(line?.sets ?? 0);
  const reps = Number(line?.reps ?? 0);

  if (loadKg == null || !Number.isFinite(sets) || sets <= 0 || !Number.isFinite(reps) || reps <= 0) {
    return null;
  }

  return loadKg * sets * reps;
}

function parseCellKey(key) {
  const [week, weekday] = String(key).split('-').map(Number);
  return { weekNumber: week, weekday };
}

/**
 * @param {Record<string, object>} sessions
 * @param {string} dateStart
 * @returns {Array<object>}
 */
export function flattenBlockItems(sessions = {}, dateStart = '') {
  const rows = [];

  for (const [key, session] of Object.entries(sessions ?? {})) {
    const { weekNumber, weekday } = parseCellKey(key);
    if (!weekNumber || !weekday) {
      continue;
    }

    const mainLift = session?.main_lift ?? 'squat';
    const items = session?.items ?? [];

    for (const item of items) {
      if (!item?.exercise_name?.trim()) {
        continue;
      }
      if (item.section === 'warmup') {
        continue;
      }

      const line = { ...item, lift: item.lift ?? mainLift };
      rows.push({
        key,
        weekNumber,
        weekday,
        date: dateStart ? cellDate(dateStart, weekNumber, weekday) : null,
        sessionLabel: session?.session_label ?? null,
        mainLift,
        section: item.section ?? null,
        line,
        lift: resolveLift(line, mainLift),
        format: repFormatBucket(line.reps),
      });
    }
  }

  return rows.sort((a, b) => {
    if (a.weekNumber !== b.weekNumber) {
      return a.weekNumber - b.weekNumber;
    }
    return a.weekday - b.weekday;
  });
}

/**
 * Mappe les séances loguées par l’athlète sur la grille semaine/jour du bloc.
 * @param {Array<object>} trainingSessions
 * @param {string} dateStart
 * @param {number} weekCount
 * @param {number} [daysPerWeek=7]
 * @returns {Record<string, object>}
 */
export function mapTrainingSessionsToBlockSessions(
  trainingSessions = [],
  dateStart = '',
  weekCount = 0,
  daysPerWeek = 7,
) {
  if (!dateStart || !weekCount) {
    return {};
  }

  const maxDays = Math.max(1, Number(daysPerWeek) || 7);
  const dateToKey = new Map();

  for (let week = 1; week <= weekCount; week += 1) {
    for (let weekday = 1; weekday <= maxDays; weekday += 1) {
      dateToKey.set(cellDate(dateStart, week, weekday), `${week}-${weekday}`);
    }
  }

  const sessions = {};

  for (const trainingSession of trainingSessions ?? []) {
    const date = String(trainingSession?.session_date ?? '').slice(0, 10);
    const key = dateToKey.get(date);
    if (!key) {
      continue;
    }

    const items = Array.isArray(trainingSession.items) ? trainingSession.items : [];

    if (sessions[key]) {
      sessions[key].items = [...(sessions[key].items ?? []), ...items];
      continue;
    }

    sessions[key] = {
      session_label: trainingSession.session_label ?? null,
      main_lift: trainingSession.main_lift ?? 'squat',
      items: [...items],
      notes: trainingSession.notes ?? null,
    };
  }

  return sessions;
}

function matchesFormatFilter(format, filter) {
  if (!filter || filter === 'all') {
    return true;
  }
  return format === filter;
}

function matchesLiftFilter(lift, filter) {
  if (!filter || filter === 'all') {
    return true;
  }
  return lift === filter;
}

/**
 * @param {Array<object>} flatItems
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 */
export function volumeByWeek(flatItems, oneRm = {}) {
  const weekMap = new Map();

  for (const row of flatItems) {
    const vol = lineVolume(row.line, oneRm, row.mainLift);
    if (vol == null || vol <= 0) {
      continue;
    }

    if (!weekMap.has(row.weekNumber)) {
      weekMap.set(row.weekNumber, { squat: 0, bench: 0, deadlift: 0, total: 0 });
    }

    const bucket = weekMap.get(row.weekNumber);
    bucket[row.lift] += vol;
    bucket.total += vol;
  }

  const weeks = [...weekMap.keys()].sort((a, b) => a - b);

  return {
    labels: weeks.map((w) => `S${w}`),
    weeks,
    squat: weeks.map((w) => Math.round(weekMap.get(w).squat)),
    bench: weeks.map((w) => Math.round(weekMap.get(w).bench)),
    deadlift: weeks.map((w) => Math.round(weekMap.get(w).deadlift)),
    total: weeks.map((w) => Math.round(weekMap.get(w).total)),
  };
}

/**
 * @param {Array<object>} flatItems
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 * @param {{ mainLift?: string, format?: string }} filters
 */
export function volumeForDonut(flatItems, oneRm = {}, filters = {}) {
  const totals = { squat: 0, bench: 0, deadlift: 0 };

  for (const row of flatItems) {
    if (!matchesLiftFilter(row.lift, filters.mainLift)) {
      continue;
    }
    if (!matchesFormatFilter(row.format, filters.format)) {
      continue;
    }

    const vol = lineVolume(row.line, oneRm, row.mainLift);
    if (vol == null || vol <= 0) {
      continue;
    }

    totals[row.lift] += vol;
  }

  return totals;
}

/**
 * @param {Array<object>} flatItems
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 */
export function avgLoadByWeekAndLift(flatItems, oneRm = {}) {
  const acc = new Map();

  for (const row of flatItems) {
    const loadKg = resolveLoadKg(row.line, oneRm, row.mainLift);
    if (loadKg == null || loadKg <= 0) {
      continue;
    }

    const mapKey = `${row.weekNumber}-${row.lift}`;
    if (!acc.has(mapKey)) {
      acc.set(mapKey, { weekNumber: row.weekNumber, lift: row.lift, sum: 0, count: 0 });
    }

    const entry = acc.get(mapKey);
    entry.sum += loadKg;
    entry.count += 1;
  }

  const weeks = [...new Set([...acc.values()].map((e) => e.weekNumber))].sort((a, b) => a - b);

  const series = {};
  for (const lift of LIFTS) {
    series[lift] = weeks.map((weekNumber) => {
      const entry = acc.get(`${weekNumber}-${lift}`);
      if (!entry || entry.count === 0) {
        return null;
      }
      return Math.round((entry.sum / entry.count) * 10) / 10;
    });
  }

  return {
    labels: weeks.map((w) => `S${w}`),
    weeks,
    series,
  };
}

export function countExcludedRpeLines(flatItems) {
  let count = 0;
  for (const row of flatItems) {
    if (inferLoadMode(row.line) === 'rpe') {
      count += 1;
    }
  }
  return count;
}

export function hasPercentWithoutRm(flatItems, oneRm = {}) {
  for (const row of flatItems) {
    if (inferLoadMode(row.line) !== 'percent') {
      continue;
    }
    const lift = row.lift;
    if (!Number(oneRm[lift] ?? 0)) {
      return true;
    }
  }
  return false;
}

/**
 * e1RM estimé via la formule d'Epley : charge × (1 + reps / 30).
 *
 * @param {number|null|undefined} loadKg
 * @param {number|null|undefined} reps
 * @returns {number|null}
 */
export function epleyE1rm(loadKg, reps) {
  const weight = Number(loadKg);
  const repCount = Number(reps);

  if (!Number.isFinite(weight) || weight <= 0 || !Number.isFinite(repCount) || repCount <= 0) {
    return null;
  }

  return weight * (1 + repCount / 30);
}

function formatTopsetLoadPart(line, loadKg) {
  const mode = inferLoadMode(line);
  const roundedLoad = Math.round(loadKg * 10) / 10;

  if (mode === 'percent' && line?.load_percent != null && line.load_percent !== '') {
    return `@ ${line.load_percent}% (${roundedLoad} kg)`;
  }

  return `@ ${roundedLoad} kg`;
}

/**
 * @param {object} detail
 * @returns {string}
 */
export function formatTopsetDayLabel(detail) {
  if (!detail) {
    return '';
  }

  const weekday = WEEKDAY_LABELS[detail.weekday - 1] ?? `Jour ${detail.weekday}`;

  if (detail.date) {
    return `${weekday} · ${formatCalendarFr(detail.date, 'medium')}`;
  }

  return `${weekday} · semaine ${detail.weekNumber}`;
}

/**
 * @param {object} detail
 * @returns {string}
 */
export function formatTopsetSeriesLabel(detail) {
  if (!detail) {
    return '';
  }

  const sets = detail.sets ?? 1;
  const reps = detail.reps ?? 1;
  const loadPart = detail.loadKg != null ? ` ${formatTopsetLoadPart(detail.line, detail.loadKg)}` : '';
  const name = localizedExerciseName(detail.exerciseName);

  if (name) {
    return `${name} · ${sets}×${reps}${loadPart}`;
  }

  return `${sets}×${reps}${loadPart}`;
}

/**
 * Meilleur e1RM Epley sur les lignes topset, par semaine et par lift.
 *
 * @param {Array<object>} flatItems
 * @param {{ squat?: number, bench?: number, deadlift?: number }} oneRm
 */
export function topsetE1rmByWeek(flatItems, oneRm = {}) {
  const best = new Map();

  for (const row of flatItems) {
    const section = row.section ?? row.line?.section;
    if (section !== 'topset') {
      continue;
    }

    const loadKg = resolveLoadKg(row.line, oneRm, row.mainLift);
    const e1rm = epleyE1rm(loadKg, row.line?.reps);
    if (e1rm == null) {
      continue;
    }

    const mapKey = `${row.weekNumber}-${row.lift}`;
    const current = best.get(mapKey);
    if (current == null || e1rm > current.e1rm) {
      best.set(mapKey, { e1rm, row, loadKg });
    }
  }

  const weeks = [...new Set([...best.keys()].map((key) => Number(key.split('-')[0])))].sort(
    (a, b) => a - b,
  );

  const series = {};
  const details = {};

  for (const lift of LIFTS) {
    series[lift] = weeks.map((weekNumber) => {
      const entry = best.get(`${weekNumber}-${lift}`);
      if (entry == null) {
        return null;
      }
      return Math.round(entry.e1rm * 10) / 10;
    });

    details[lift] = weeks.map((weekNumber) => {
      const entry = best.get(`${weekNumber}-${lift}`);
      if (entry == null) {
        return null;
      }

      const { row, loadKg, e1rm } = entry;

      return {
        weekNumber,
        weekday: row.weekday,
        date: row.date,
        sessionLabel: row.sessionLabel,
        exerciseName: row.line?.exercise_name ?? '',
        sets: row.line?.sets,
        reps: row.line?.reps,
        loadKg,
        line: row.line,
        e1rm: Math.round(e1rm * 10) / 10,
      };
    });
  }

  return {
    labels: weeks.map((w) => `S${w}`),
    weeks,
    series,
    details,
  };
}

export { LIFTS, cellKey };
