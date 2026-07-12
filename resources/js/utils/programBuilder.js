import { epleyE1rm, resolveLoadKg } from './trainingVolume';

export const BLOCK_TYPES = [
  { value: 'volume', label: 'Volume' },
  { value: 'intensification', label: 'Intensification' },
  { value: 'peaking', label: 'Peaking' },
];

export const MAIN_LIFTS = [
  { value: 'squat', label: 'Squat' },
  { value: 'bench', label: 'Bench' },
  { value: 'deadlift', label: 'Deadlift' },
];

export function emptyExerciseLine(name = '') {
  return {
    exercise_variant_id: null,
    exercise_name: name,
    lift: null,
    sets: null,
    reps: null,
    load: null,
    load_percent: null,
    rpe: null,
    rest_seconds: null,
    load_mode: null,
  };
}

export const SET_OPTIONS = Array.from({ length: 10 }, (_, i) => i + 1);
export const REP_OPTIONS = Array.from({ length: 12 }, (_, i) => i + 1);

export const RPE_OPTIONS = (() => {
  const values = [];
  for (let v = 5; v <= 10; v += 0.5) {
    values.push(v);
  }
  return values;
})();

export const PERCENT_OPTIONS = (() => {
  const values = [];
  for (let v = 50; v <= 100; v += 2.5) {
    values.push(v);
  }
  return values;
})();

export function inferMainLift(day) {
  const lift = day?.topset?.lift ?? day?.main_lift;
  if (lift === 'squat' || lift === 'bench' || lift === 'deadlift') {
    return lift;
  }
  return 'squat';
}

export function emptyDay(dayNumber = 1, mainLift = 'squat') {
  return {
    day_number: dayNumber,
    main_lift: mainLift,
    topset: emptyExerciseLine(''),
    backoff: null,
    accessories: [],
  };
}

export function emptyWeek(weekNumber = 1) {
  return {
    week_number: weekNumber,
    block_type: 'volume',
    days: [emptyDay(1, 'squat')],
  };
}

export function defaultLiftName(lift) {
  return MAIN_LIFTS.find((item) => item.value === lift)?.label ?? lift;
}

export function formatPrescription(line) {
  if (!line?.exercise_name) {
    return '—';
  }
  const setsReps =
    line.sets != null && line.reps != null ? ` · ${line.sets}×${line.reps}` : '';
  const loadPart = line.load != null && line.load !== '' ? ` @ ${line.load} kg` : '';
  const pctPart =
    line.load_percent != null && line.load_percent !== ''
      ? ` @ ${line.load_percent}% 1RM`
      : '';
  const rpePart = line.rpe != null && line.rpe !== '' ? ` RPE ${line.rpe}` : '';
  return `${line.exercise_name}${setsReps}${loadPart || pctPart}${rpePart}`;
}

/** Ex. Bench - 3x3 @ 105kg */
export function formatLineRecap(line) {
  if (!line?.exercise_name?.trim()) {
    return null;
  }

  const label =
    line.exercise_name.trim() ||
    (line.lift ? defaultLiftName(line.lift) : 'Exercice');

  let volume = null;
  if (line.sets != null && line.reps != null) {
    volume = `${line.sets}x${line.reps}`;
  } else if (line.sets != null) {
    volume = `${line.sets} séries`;
  } else if (line.reps != null) {
    volume = `${line.reps} reps`;
  }

  let charge = '';
  if (line.load != null && line.load !== '') {
    charge = `@ ${line.load}kg`;
  } else if (line.load_percent != null && line.load_percent !== '') {
    charge = `@ ${line.load_percent}%`;
  } else if (line.rpe != null && line.rpe !== '') {
    charge = `RPE ${line.rpe}`;
  }

  const parts = [label];
  if (volume) {
    parts.push(volume);
  }
  if (charge) {
    parts.push(charge);
  }

  return parts.length > 1 ? `${parts[0]} - ${parts.slice(1).join(' ')}` : parts[0];
}

/** Ex. Bench - 3x3 @ 75% · 150 kg */
export function formatLineRecapWithKg(line, oneRm = {}, mainLift = 'squat') {
  const base = formatLineRecap(line);
  if (!base) {
    return null;
  }

  const loadKg = resolveLoadKg(line, oneRm, mainLift);
  if (loadKg == null) {
    return base;
  }

  const rounded = Math.round(loadKg);
  if (line?.load_percent != null && line.load_percent !== '') {
    return `${base} · ${rounded} kg`;
  }

  return base;
}

const EDITOR_SECTION_LABELS = {
  topset: 'Topset',
  backoff: 'Back-off',
  accessory: 'Accessoires',
};

/**
 * @param {object} line
 * @param {{ oneRm?: object, defaultLift?: string, section?: string|null }} [options]
 * @returns {{ section: string|null, main: string, e1rm: number|null }|null}
 */
export function formatEditorLineRecapParts(line, { oneRm = {}, defaultLift = 'squat', section = null } = {}) {
  const main = formatLineRecap(line);
  if (!main) {
    return null;
  }

  const sectionKey = section ?? line?.section ?? null;
  const sectionLabel = sectionKey ? (EDITOR_SECTION_LABELS[sectionKey] ?? null) : null;
  const lift = line?.lift ?? defaultLift;
  const loadKg = resolveLoadKg(line, oneRm, lift);
  const estimated = epleyE1rm(loadKg, line?.reps);

  return {
    section: sectionLabel,
    main,
    e1rm: estimated != null ? Math.round(estimated) : null,
  };
}

export function findCalendarCellByDate(weekCount, dateStart, targetDate) {
  const rows = buildCalendarRows(weekCount, dateStart, {});

  for (const row of rows) {
    for (const cell of row.cells) {
      if (cell.date === targetDate) {
        return cell;
      }
    }
  }

  return null;
}

let itemUid = 0;

export function nextItemUid() {
  itemUid += 1;
  return `item-${itemUid}`;
}

export const SESSION_SECTION_LABELS = {
  topset: 'Top set',
  backoff: 'Backoff',
  accessory: 'Accessoire',
};

export function createSessionItem(section, line = null) {
  return {
    id: nextItemUid(),
    section,
    line: line ? hydrateExerciseLine(line) : emptyExerciseLine(''),
  };
}

export function emptySessionDay() {
  return {
    lift: 'squat',
    session_label: '',
    notes: '',
    items: [],
    editingId: null,
  };
}

export function uppercaseSessionLabel(value) {
  return (value ?? '').trim().toUpperCase();
}

export function sessionItemHasContent(item) {
  return Boolean(item?.line?.exercise_name?.trim());
}

export function sessionHasContent(day) {
  return (day?.items ?? []).some(sessionItemHasContent);
}

export function findSessionItem(day, section) {
  return (day?.items ?? []).find((item) => item.section === section) ?? null;
}

export function accessoryOrdinal(items, itemId) {
  let n = 0;
  for (const item of items) {
    if (item.section !== 'accessory') {
      continue;
    }
    n += 1;
    if (item.id === itemId) {
      return n;
    }
  }
  return n;
}

export function itemSectionTitle(item, items) {
  if (item.section === 'accessory') {
    return `Accessoire ${accessoryOrdinal(items, item.id)}`;
  }
  return SESSION_SECTION_LABELS[item.section] ?? item.section;
}

export const WEEKDAY_LABELS = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

/** Jour ISO 1 = lundi … 7 = dimanche */
export function isoWeekdayFromDate(value) {
  const date =
    value instanceof Date
      ? value
      : typeof value === 'string' || typeof value === 'number'
        ? parseYmd(value)
        : null;
  if (!date || Number.isNaN(date.getTime())) {
    return 1;
  }
  const jsDay = date.getDay();
  return jsDay === 0 ? 7 : jsDay;
}

export function weekdayLabelFromDate(value) {
  return WEEKDAY_LABELS[isoWeekdayFromDate(value) - 1] ?? '';
}

export function weekdayShortLabel(weekday) {
  return WEEKDAY_LABELS[weekday - 1] ?? `J${weekday}`;
}

/** Ex. "Mer · 12 juin" */
export function columnHeading(dateStart, weekNumber, weekday) {
  const date = parseYmd(cellDate(dateStart, weekNumber, weekday));
  if (!date || Number.isNaN(date.getTime())) {
    return weekdayShortLabel(weekday);
  }

  const dayMonth = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
  return `${weekdayShortLabel(weekday)} · ${dayMonth}`;
}

export function cellKey(weekNumber, weekday) {
  return `${weekNumber}-${weekday}`;
}

/**
 * Numéro de séance dans la semaine (1 = première case programmée de la semaine, etc.).
 */
export const SECTION_LABELS = {
  topset: 'Top set',
  backoff: 'Backoff',
  accessory: 'Accessoire',
};

/** Jours de la semaine ayant au moins une séance ou un nom. */
export function weekDaysWithSessions(week) {
  return (week?.training_days ?? [])
    .filter((day) => (day.exercises ?? []).length > 0 || day.session_label?.trim())
    .sort((a, b) => a.day_number - b.day_number);
}

/** Titre carte : Jour 1 - Titre coach */
export function sessionCardTitle(day, daysInWeek) {
  const ordinal = daysInWeek.findIndex((d) => d.day_number === day.day_number) + 1;
  const label = day.session_label?.trim();
  if (label) {
    return `Jour ${ordinal} - ${label}`;
  }
  return `Jour ${ordinal}`;
}

export function sessionDayOrdinalInWeek(sessions, weekNumber, weekday) {
  const weekdays = new Set();

  for (const key of Object.keys(sessions ?? {})) {
    const [week, day] = key.split('-').map(Number);
    if (week === weekNumber) {
      weekdays.add(day);
    }
  }

  weekdays.add(weekday);

  const sorted = [...weekdays].sort((a, b) => a - b);

  return sorted.indexOf(weekday) + 1;
}

export function parseYmd(key) {
  if (key == null || key === '') {
    return null;
  }
  const [y, mo, d] = String(key).split('-').map(Number);
  if (!y || !mo || !d) {
    return null;
  }
  return new Date(y, mo - 1, d);
}

function formatYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/** Lundi de la semaine calendaire contenant dateStart */
export function startOfIsoWeek(dateStart) {
  const date = parseYmd(dateStart);
  if (!date) {
    return new Date();
  }
  const monday = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  monday.setDate(monday.getDate() - (isoWeekdayFromDate(date) - 1));
  return monday;
}

/**
 * Date d’une case : semaine N, colonne = jour ISO (1=lun … 7=dim).
 * Aligné sur les en-têtes Lun–Dim et day_number côté serveur.
 */
export function cellDate(dateStart, weekNumber, weekday) {
  const monday = startOfIsoWeek(dateStart);
  const date = new Date(monday.getFullYear(), monday.getMonth(), monday.getDate());
  date.setDate(monday.getDate() + (weekNumber - 1) * 7 + (weekday - 1));
  return formatYmd(date);
}

export function defaultMainLiftForWeekday(weekday) {
  const lifts = ['squat', 'bench', 'deadlift', 'squat', 'bench', 'deadlift', 'squat'];
  return lifts[(weekday - 1) % lifts.length];
}

let blockUid = 0;

export function nextBlockUid() {
  blockUid += 1;
  return `block-${blockUid}`;
}

export function emptyLiftBlock(lift = 'squat', collapsed = false) {
  return {
    uid: nextBlockUid(),
    lift,
    collapsed,
    topset: emptyExerciseLine(''),
    backoff: null,
    accessories: [],
  };
}

export function hydrateExerciseLine(line) {
  if (!line) {
    return null;
  }
  return {
    ...emptyExerciseLine(''),
    ...line,
    sets: line.sets ?? null,
    reps: line.reps ?? null,
    load_mode: inferLoadMode(line),
  };
}

function legacyBlockToItems(source) {
  const items = [];
  const lift = source.lift ?? source.topset?.lift ?? 'squat';

  if (source.topset) {
    items.push(createSessionItem('topset', { ...source.topset, lift: source.topset.lift ?? lift }));
  }
  if (source.backoff) {
    items.push(createSessionItem('backoff', { ...source.backoff, lift: source.backoff.lift ?? lift }));
  }
  for (const line of source.accessories ?? []) {
    items.push(createSessionItem('accessory', { ...line, lift: line.lift ?? lift }));
  }

  return items;
}

export function sessionToDay(session) {
  const day = emptySessionDay();

  if (!session) {
    return day;
  }

  if (session.items?.length) {
    day.items = session.items.map((row) =>
      createSessionItem(row.section, { ...row, lift: row.lift ?? session.main_lift }),
    );
  } else {
    let source = null;

    if (session.blocks?.length) {
      source = session.blocks[0];
    } else if (session.topset || session.backoff || session.accessories?.length) {
      source = {
        lift: session.main_lift,
        topset: session.topset,
        backoff: session.backoff,
        accessories: session.accessories,
      };
    }

    if (source) {
      day.items = legacyBlockToItems(source);
    }
  }

  day.lift =
    day.items.find((item) => item.line?.lift)?.line?.lift ??
    session.main_lift ??
    'squat';

  day.session_label = session.session_label ?? '';
  day.notes = session.notes ?? '';

  return day;
}

export function normalizeLineForSave(line) {
  if (!line?.exercise_name?.trim()) {
    return null;
  }
  const normalized = {
    exercise_variant_id: line.exercise_variant_id ? Number(line.exercise_variant_id) : null,
    exercise_name: line.exercise_name,
    lift: line.lift,
    sets: Number(line.sets ?? 1),
    reps: Number(line.reps ?? 1),
    load: null,
    load_percent: null,
    rpe: null,
  };

  const mode = line.load_mode ?? inferLoadMode(line);
  if (mode === 'rpe') {
    normalized.rpe = line.rpe;
  } else if (mode === 'percent') {
    normalized.load_percent = line.load_percent;
  } else if (mode === 'kg') {
    normalized.load =
      line.load != null && line.load !== '' ? Number(line.load) : null;
  }

  return normalized;
}

export function inferLoadMode(line) {
  if (line?.rpe != null && line.rpe !== '') {
    return 'rpe';
  }
  if (line?.load_percent != null && line.load_percent !== '') {
    return 'percent';
  }
  if (line?.load != null && line.load !== '') {
    return 'kg';
  }
  return null;
}

export function dayToSessionPayload(day) {
  const items = (day.items ?? [])
    .map((item) => {
      const line = normalizeLineForSave(item.line);
      if (!line) {
        return null;
      }
      return { section: item.section, ...line };
    })
    .filter(Boolean);

  const lift =
    items.find((item) => item.lift)?.lift ?? day.lift ?? 'squat';

  const block = {
    lift,
    topset: null,
    backoff: null,
    accessories: [],
  };

  for (const item of items) {
    const { section, ...line } = item;
    if (section === 'topset') {
      block.topset = line;
    } else if (section === 'backoff') {
      block.backoff = line;
    } else if (section === 'accessory') {
      block.accessories.push(line);
    }
  }

  const hasContent = items.length > 0;
  const label = day.session_label?.trim() ?? '';
  const notes = day.notes?.trim() ?? '';

  return {
    main_lift: lift,
    session_label: label !== '' ? label : null,
    notes: notes !== '' ? notes : null,
    items: hasContent ? items : [],
    blocks: hasContent ? [block] : [],
  };
}

export function buildCalendarRows(weekCount, dateStart, sessions = {}) {
  const rows = [];
  for (let week = 1; week <= weekCount; week++) {
    const cells = [];
    for (let weekday = 1; weekday <= 7; weekday++) {
      const key = cellKey(week, weekday);
      cells.push({
        weekNumber: week,
        weekday,
        key,
        date: cellDate(dateStart, week, weekday),
        hasSession: Boolean(sessions[key]),
      });
    }
    rows.push({ weekNumber: week, cells });
  }
  return rows;
}
