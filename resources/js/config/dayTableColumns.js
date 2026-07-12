export const EXERCISE_MODE_NAME = 'name';
export const EXERCISE_MODE_SPLIT_LIFT = 'split_lift';

export const LOAD_MODE_KG = 'kg';
export const LOAD_MODE_PERCENT = 'percent';
export const LOAD_MODE_RPE = 'rpe';

export const OPTIONAL_COLUMN_IDS = ['section', 'sets', 'reps', 'load', 'rest', 'muscles'];

export const PRESCRIPTION_COLUMN_IDS = ['sets', 'reps', 'load'];

/** Champs numériques du tableau séance : plus lisibles sans alourdir la ligne. */
export const PRESCRIPTION_VALUE_INPUT_CLASS =
  'w-full min-w-0 border-0 bg-transparent px-0.5 py-0 text-center text-sm font-semibold tabular-nums leading-none tracking-tight text-white outline-none sm:text-base';

export const PRESCRIPTION_LOAD_INPUT_CLASS =
  'w-full min-w-0 border-0 bg-transparent px-0.5 py-0 text-center text-sm font-bold tabular-nums leading-none tracking-tight text-white outline-none sm:text-base';

export const DAY_TABLE_COLUMNS = {
  exercise: {
    id: 'exercise',
    label: 'Exercice',
    widthClass: 'min-w-[8rem]',
    align: 'left',
  },
  main_lift: {
    id: 'main_lift',
    label: 'Main lift',
    widthClass: 'w-24',
    align: 'center',
  },
  variant: {
    id: 'variant',
    label: 'Variante',
    widthClass: 'min-w-[7rem]',
    align: 'left',
  },
  section: {
    id: 'section',
    label: 'Type',
    widthClass: 'w-[3.25rem]',
    align: 'center',
  },
  sets: {
    id: 'sets',
    label: 'Séries',
    widthClass: 'w-14',
    align: 'center',
  },
  reps: {
    id: 'reps',
    label: 'Reps',
    widthClass: 'w-14',
    align: 'center',
  },
  load: {
    id: 'load',
    label: 'Charge',
    widthClass: 'w-28',
    align: 'center',
  },
  rest: {
    id: 'rest',
    label: 'Repos',
    widthClass: 'w-16',
    align: 'center',
  },
  muscles: {
    id: 'muscles',
    label: 'Muscles',
    widthClass: 'w-24',
    align: 'left',
  },
};

export const OPTIONAL_COLUMN_OPTIONS = OPTIONAL_COLUMN_IDS.map((id) => ({
  id,
  label: DAY_TABLE_COLUMNS[id].label,
}));

export const LOAD_MODE_OPTIONS = [
  { value: LOAD_MODE_KG, label: 'kg' },
  { value: LOAD_MODE_PERCENT, label: '% 1RM' },
  { value: LOAD_MODE_RPE, label: 'RPE' },
];

export const EXERCISE_MODE_OPTIONS = [
  { value: EXERCISE_MODE_NAME, label: 'Nom complet' },
  { value: EXERCISE_MODE_SPLIT_LIFT, label: 'Main lift + variante' },
];

export function classicTableLayout() {
  return {
    columns: ['section', 'sets', 'reps', 'load'],
    exercise_mode: EXERCISE_MODE_NAME,
    load_mode: LOAD_MODE_KG,
  };
}

export function normalizeTableLayout(layout) {
  const source = layout && typeof layout === 'object' ? layout : {};
  const columns = Array.isArray(source.columns)
    ? source.columns.filter((column) => OPTIONAL_COLUMN_IDS.includes(column))
    : classicTableLayout().columns;

  const exerciseMode = EXERCISE_MODE_OPTIONS.some((option) => option.value === source.exercise_mode)
    ? source.exercise_mode
    : EXERCISE_MODE_NAME;

  const loadMode = LOAD_MODE_OPTIONS.some((option) => option.value === source.load_mode)
    ? source.load_mode
    : LOAD_MODE_KG;

  return {
    columns,
    exercise_mode: exerciseMode,
    load_mode: loadMode,
  };
}

const SPACED_COLUMN_WEIGHTS = {
  exercise: 3.4,
  main_lift: 1.1,
  variant: 2.3,
  section: 0.9,
  sets: 1.2,
  reps: 1.2,
  load: 2.8,
  rest: 1.0,
  muscles: 1.3,
};

export function resolveVisibleColumns(layout) {
  const normalized = normalizeTableLayout(layout);
  const exerciseColumns =
    normalized.exercise_mode === EXERCISE_MODE_SPLIT_LIFT
      ? [DAY_TABLE_COLUMNS.main_lift, DAY_TABLE_COLUMNS.variant]
      : [DAY_TABLE_COLUMNS.exercise];

  const optionalColumns = normalized.columns
    .map((columnId) => DAY_TABLE_COLUMNS[columnId])
    .filter(Boolean);

  return [...exerciseColumns, ...optionalColumns];
}

export function spacedColumnPercent(columnId, visibleColumns) {
  const columns = Array.isArray(visibleColumns) ? visibleColumns : [];
  const totalWeight = columns.reduce(
    (sum, column) => sum + (SPACED_COLUMN_WEIGHTS[column.id] ?? 1),
    0,
  );
  const weight = SPACED_COLUMN_WEIGHTS[columnId] ?? 1;

  return `${((weight / totalWeight) * 100).toFixed(2)}%`;
}

const ATHLETE_COLUMN_WEIGHTS = {
  exercise: 2.4,
  main_lift: 0.95,
  variant: 1.7,
  section: 1.2,
  sets: 1.15,
  reps: 0.95,
  load: 2.1,
  rest: 0.85,
  muscles: 0.9,
};

const ATHLETE_COLUMN_HEADER_SHORT = {
  exercise: 'Exo.',
  main_lift: 'Lift',
  variant: 'Var.',
  section: 'Typ.',
  sets: 'Sér.',
  reps: 'R.',
  load: 'Ch.',
  rest: 'Repos',
  muscles: 'Mus.',
};

export function athleteSpacedColumnPercent(columnId, visibleColumns) {
  const columns = Array.isArray(visibleColumns) ? visibleColumns : [];
  const totalWeight = columns.reduce(
    (sum, column) => sum + (ATHLETE_COLUMN_WEIGHTS[column.id] ?? 1),
    0,
  );
  const weight = ATHLETE_COLUMN_WEIGHTS[columnId] ?? 1;

  return `${((weight / totalWeight) * 100).toFixed(2)}%`;
}

export function athleteColumnHeaderLabel(columnId, fallbackLabel = '') {
  return ATHLETE_COLUMN_HEADER_SHORT[columnId] ?? fallbackLabel;
}

export function layoutHasPrescriptionColumn(layout) {
  const normalized = normalizeTableLayout(layout);

  return normalized.columns.some((columnId) => PRESCRIPTION_COLUMN_IDS.includes(columnId));
}

export function validateTableLayoutDraft(draft) {
  const normalized = normalizeTableLayout(draft);
  const errors = [];

  if (!String(draft?.name ?? '').trim()) {
    errors.push('Donne un nom à ton tableau jour.');
  }

  if (!layoutHasPrescriptionColumn(normalized)) {
    errors.push('Active au moins une colonne de prescription (séries, reps ou charge).');
  }

  return errors;
}

export function emptyPreviewRow(loadMode = LOAD_MODE_KG) {
  return {
    section: 'accessory',
    exercise_variant_id: null,
    exercise_name: '',
    lift: 'squat',
    sets: '',
    reps: '',
    load: '',
    load_percent: '',
    rpe: '',
    rest_seconds: '',
    movement_pattern: '',
    load_mode: loadMode,
  };
}
