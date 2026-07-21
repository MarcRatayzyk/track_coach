export const READINESS_FIELD_TYPES = [
  { value: 'number', label: 'Numérique' },
  { value: 'text', label: 'Texte' },
  { value: 'select', label: 'Options' },
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
  { key: 'steps', label: 'STEPS', type: 'number' },
  { key: 'kcal', label: 'KCAL', type: 'text' },
  {
    key: 'sommeil',
    label: 'SOMMEIL',
    type: 'select',
    options: [
      { value: 'lt_5h', label: '- 5H', color: '#991b1b' },
      { value: '5_6h', label: '5-6H', color: '#ea580c' },
      { value: '6_7h', label: '6-7H', color: '#ca8a04' },
      { value: '7_8h', label: '7-8H', color: '#4ade80' },
      { value: '8_9h', label: '8-9H', color: '#7dd3fc' },
    ],
  },
  {
    key: 'alimentation',
    label: 'ALIMENTATION',
    type: 'select',
    options: [
      { value: 'mauvaise', label: 'MAUVAISE', color: '#991b1b' },
      { value: 'moyenne', label: 'MOYENNE', color: '#ca8a04' },
      { value: 'bonne', label: 'BONNE', color: '#4ade80' },
    ],
  },
  {
    key: 'hydratation',
    label: 'HYDRATATION',
    type: 'select',
    options: [
      { value: 'faible', label: 'FAIBLE <1.5L', color: '#991b1b' },
      { value: 'moyenne', label: 'MOYENNE ~1.5-2L', color: '#ca8a04' },
      { value: 'bonne', label: 'BON ~2L', color: '#4ade80' },
      { value: 'excellente', label: 'EXCELLENTE +2.5L', color: '#7dd3fc' },
    ],
  },
  {
    key: 'stress_global',
    label: 'STRESS GLOBAL',
    type: 'select',
    options: [
      { value: 'eleve', label: 'ÉLEVÉ', color: '#991b1b' },
      { value: 'moyen', label: 'MOYEN', color: '#4ade80' },
      { value: 'bas', label: 'BAS', color: '#7dd3fc' },
    ],
  },
  {
    key: 'motivation',
    label: 'MOTIVATION',
    type: 'select',
    options: [
      { value: 'faible', label: 'FAIBLE', color: '#991b1b' },
      { value: 'moyenne', label: 'MOYENNE', color: '#ca8a04' },
      { value: 'bonne', label: 'BONNE', color: '#4ade80' },
      { value: 'excellente', label: 'EXCELLENTE', color: '#7dd3fc' },
    ],
  },
  {
    key: 'forme_physique',
    label: 'FORME PHYSIQUE',
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
    label: 'FORME MENTALE',
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
    label: field.label ?? 'Champ',
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
    label: 'Nouveau champ',
    type: 'text',
    required: true,
    sort_order: sortOrder,
    options: [],
  };
}

export function emptySelectOption() {
  return {
    value: '',
    label: 'Option',
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
    errors.push('Ajoute au moins un champ.');
    return errors;
  }
  for (const field of fields) {
    if (!String(field.label ?? '').trim()) {
      errors.push('Chaque champ doit avoir un libellé.');
      break;
    }
    if (field.type === 'select' && (!field.options || field.options.length === 0)) {
      errors.push(`« ${field.label} » : ajoute au moins une option.`);
      break;
    }
  }
  return errors;
}
