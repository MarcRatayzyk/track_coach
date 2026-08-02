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
  if (!field || field.type !== 'select') {
    return String(value);
  }
  const option = (field.options ?? []).find((opt) => String(opt.value) === String(value));
  return option?.label ?? String(value);
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
