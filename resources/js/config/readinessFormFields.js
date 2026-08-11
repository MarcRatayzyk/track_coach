import i18n from '../i18n';

function tt(key, params) {
  return i18n.global.t(key, params);
}

export const READINESS_FIELD_TYPES = [
  { value: 'number', get label() { return tt('config.readiness.types.number'); } },
  { value: 'text', get label() { return tt('config.readiness.types.text'); } },
  { value: 'select', get label() { return tt('config.readiness.types.select'); } },
];

export const READINESS_OPTION_COLORS = [
  '#991b1b',
  '#ea580c',
  '#ca8a04',
  '#4ade80',
  '#7dd3fc',
  '#64748b',
];

/** Catalogue presets (miroir de ReadinessFormSupport::presetCatalog). */
export const READINESS_PRESET_CATALOG = [
  { key: 'steps', get label() { return tt('config.readiness.presets.steps'); }, type: 'number' },
  { key: 'kcal', get label() { return tt('config.readiness.presets.kcal'); }, type: 'text' },
  {
    key: 'sommeil',
    get label() { return tt('config.readiness.presets.sommeil'); },
    type: 'select',
    options: [
      { value: 'lt_5h', get label() { return tt('config.readiness.options.sleepLt5'); }, color: '#991b1b' },
      { value: '5_6h', get label() { return tt('config.readiness.options.sleep5_6'); }, color: '#ea580c' },
      { value: '6_7h', get label() { return tt('config.readiness.options.sleep6_7'); }, color: '#ca8a04' },
      { value: '7_8h', get label() { return tt('config.readiness.options.sleep7_8'); }, color: '#4ade80' },
      { value: '8_9h', get label() { return tt('config.readiness.options.sleep8_9'); }, color: '#7dd3fc' },
    ],
  },
  {
    key: 'alimentation',
    get label() { return tt('config.readiness.presets.alimentation'); },
    type: 'select',
    options: [
      { value: 'mauvaise', get label() { return tt('config.readiness.options.bad'); }, color: '#991b1b' },
      { value: 'moyenne', get label() { return tt('config.readiness.options.average'); }, color: '#ca8a04' },
      { value: 'bonne', get label() { return tt('config.readiness.options.good'); }, color: '#4ade80' },
    ],
  },
  {
    key: 'hydratation',
    get label() { return tt('config.readiness.presets.hydratation'); },
    type: 'select',
    options: [
      { value: 'faible', get label() { return tt('config.readiness.options.hydrationLow'); }, color: '#991b1b' },
      { value: 'moyenne', get label() { return tt('config.readiness.options.hydrationMid'); }, color: '#ca8a04' },
      { value: 'bonne', get label() { return tt('config.readiness.options.hydrationGood'); }, color: '#4ade80' },
      { value: 'excellente', get label() { return tt('config.readiness.options.hydrationExcellent'); }, color: '#7dd3fc' },
    ],
  },
  {
    key: 'stress_global',
    get label() { return tt('config.readiness.presets.stressGlobal'); },
    type: 'select',
    options: [
      { value: 'eleve', get label() { return tt('config.readiness.options.stressHigh'); }, color: '#991b1b' },
      { value: 'moyen', get label() { return tt('config.readiness.options.stressMid'); }, color: '#4ade80' },
      { value: 'bas', get label() { return tt('config.readiness.options.stressLow'); }, color: '#7dd3fc' },
    ],
  },
  {
    key: 'motivation',
    get label() { return tt('config.readiness.presets.motivation'); },
    type: 'select',
    options: [
      { value: 'faible', get label() { return tt('config.readiness.options.motivationLow'); }, color: '#991b1b' },
      { value: 'moyenne', get label() { return tt('config.readiness.options.motivationMid'); }, color: '#ca8a04' },
      { value: 'bonne', get label() { return tt('config.readiness.options.motivationGood'); }, color: '#4ade80' },
      { value: 'excellente', get label() { return tt('config.readiness.options.motivationExcellent'); }, color: '#7dd3fc' },
    ],
  },
  {
    key: 'forme_physique',
    get label() { return tt('config.readiness.presets.formePhysique'); },
    type: 'select',
    options: [
      { value: '1', label: '1', color: '#991b1b' },
      { value: '2', label: '2', color: '#ea580c' },
      { value: '3', label: '3', color: '#ca8a04' },
      { value: '4', label: '4', color: '#4ade80' },
      { value: '5', label: '5', color: '#7dd3fc' },
    ],
  },
  {
    key: 'forme_mentale',
    get label() { return tt('config.readiness.presets.formeMentale'); },
    type: 'select',
    options: [
      { value: '1', label: '1', color: '#991b1b' },
      { value: '2', label: '2', color: '#ea580c' },
      { value: '3', label: '3', color: '#ca8a04' },
      { value: '4', label: '4', color: '#4ade80' },
      { value: '5', label: '5', color: '#7dd3fc' },
    ],
  },
];

export function createFieldId() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  return `field-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export function fieldFromPreset(preset, sortOrder = 0) {
  return {
    id: `preset-${preset.key}`,
    preset_key: preset.key,
    label: preset.label,
    type: preset.type,
    required: true,
    sort_order: sortOrder,
    options: preset.type === 'select' ? (preset.options ?? []).map((opt) => ({ ...opt })) : [],
  };
}

export function defaultReadinessFields() {
  return READINESS_PRESET_CATALOG.map((preset, index) => fieldFromPreset(preset, index));
}

export function cloneFields(fields) {
  return (fields ?? []).map((field, index) => ({
    id: field.id || createFieldId(),
    preset_key: field.preset_key ?? null,
    label: field.label ?? tt('config.readiness.field'),
    type: field.type ?? 'text',
    required: field.required !== false,
    sort_order: field.sort_order ?? index,
    options: Array.isArray(field.options)
      ? field.options.map((opt) => ({
          value: opt.value ?? '',
          label: opt.label ?? '',
          color: opt.color ?? '#64748b',
        }))
      : [],
  }));
}

export function emptyCustomField(sortOrder = 0) {
  return {
    id: createFieldId(),
    preset_key: null,
    label: tt('config.readiness.newField'),
    type: 'text',
    required: true,
    sort_order: sortOrder,
    options: [],
  };
}

export function emptySelectOption() {
  return {
    value: '',
    label: tt('config.readiness.option'),
    color: '#64748b',
  };
}

export function emptyValuesForFields(fields) {
  const values = {};
  for (const field of fields ?? []) {
    values[field.id] = field.type === 'number' ? '' : '';
  }
  return values;
}

export function resolveOptionColor(field, value) {
  if (!field || field.type !== 'select' || value == null || value === '') {
    return null;
  }
  const option = (field.options ?? []).find((opt) => String(opt.value) === String(value));
  return option?.color ?? null;
}

export function resolveOptionLabel(field, value) {
  if (value == null || value === '') {
    return '—';
  }
  const localized = localizeReadinessField(field);
  if (!localized || localized.type !== 'select') {
    return String(value);
  }
  const option = (localized.options ?? []).find((opt) => String(opt.value) === String(value));
  return option?.label ?? String(value);
}

/** Applique les libellés i18n des presets connus (labels stockés en FR en base). */
export function localizeReadinessField(field) {
  if (!field || typeof field !== 'object') {
    return field;
  }

  const presetKey =
    field.preset_key
    || (typeof field.id === 'string' && field.id.startsWith('preset-')
      ? field.id.slice('preset-'.length)
      : null);

  if (!presetKey) {
    return field;
  }

  const preset = READINESS_PRESET_CATALOG.find((item) => item.key === presetKey);
  if (!preset) {
    return field;
  }

  return {
    ...field,
    label: preset.label,
    options: Array.isArray(field.options)
      ? field.options.map((opt) => {
          const presetOpt = (preset.options ?? []).find(
            (candidate) => String(candidate.value) === String(opt.value),
          );
          return {
            ...opt,
            label: presetOpt?.label ?? opt.label,
          };
        })
      : [],
  };
}

export function localizeReadinessFields(fields) {
  return (fields ?? []).map((field) => localizeReadinessField(field));
}

/** ISO dates for the last N calendar days ending today (inclusive), oldest → newest. */
export function rollingLastDaysIso(days = 7) {
  const today = new Date();
  today.setHours(12, 0, 0, 0);
  const out = [];
  for (let i = days - 1; i >= 0; i -= 1) {
    const date = new Date(today);
    date.setDate(today.getDate() - i);
    out.push(date.toISOString().slice(0, 10));
  }
  return out;
}

/**
 * Average of select-type external factors on a 1–5 scale (options ordered worst → best).
 * Defaults to the last 7 rolling days.
 */
export function averageExternalFactorScore(fields = [], entries = [], { days = 7 } = {}) {
  const window = new Set(rollingLastDaysIso(days));
  const selectFields = (fields ?? []).filter(
    (field) => field?.type === 'select' && Array.isArray(field.options) && field.options.length > 0,
  );
  if (!selectFields.length) {
    return null;
  }

  let sum = 0;
  let count = 0;
  for (const entry of entries ?? []) {
    if (!window.has(entry?.entry_date)) {
      continue;
    }
    const values = entry?.values ?? {};
    for (const field of selectFields) {
      const raw = values[field.id];
      if (raw == null || raw === '') {
        continue;
      }
      const idx = field.options.findIndex((opt) => String(opt.value) === String(raw));
      if (idx < 0) {
        continue;
      }
      const maxIdx = field.options.length - 1;
      const score = maxIdx === 0 ? 5 : 1 + (idx / maxIdx) * 4;
      sum += score;
      count += 1;
    }
  }

  if (!count) {
    return null;
  }

  return Math.round((sum / count) * 10) / 10;
}

export function externalFactorScoreTone(score) {
  if (score == null) {
    return 'text-slate-500';
  }
  if (score >= 4) {
    return 'text-emerald-400';
  }
  if (score >= 3) {
    return 'text-amber-400';
  }
  return 'text-red-400';
}

export function validateReadinessFieldsDraft(fields) {
  const errors = [];
  if (!Array.isArray(fields) || fields.length === 0) {
    errors.push(tt('config.readiness.addAtLeastOne'));
    return errors;
  }
  for (const field of fields) {
    if (!String(field.label ?? '').trim()) {
      errors.push(tt('config.readiness.labelRequired'));
      break;
    }
    if (field.type === 'select' && (!field.options || field.options.length === 0)) {
      errors.push(tt('config.readiness.optionRequired', { label: field.label }));
      break;
    }
  }
  return errors;
}
