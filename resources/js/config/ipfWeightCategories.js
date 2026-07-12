export const SEX_OPTIONS = [
  { value: 'male', label: 'Homme' },
  { value: 'female', label: 'Femme' },
];

export const LEVEL_OPTIONS = [
  { value: 'beginner', label: 'Débutant' },
  { value: 'intermediate', label: 'Intermédiaire' },
  { value: 'advanced', label: 'Avancé' },
  { value: 'elite', label: 'Élite' },
];

export const MALE_CATEGORIES = [
  { value: 'm59', label: '59 kg' },
  { value: 'm66', label: '66 kg' },
  { value: 'm74', label: '74 kg' },
  { value: 'm83', label: '83 kg' },
  { value: 'm93', label: '93 kg' },
  { value: 'm105', label: '105 kg' },
  { value: 'm120', label: '120 kg' },
  { value: 'm120plus', label: '120+ kg' },
];

export const FEMALE_CATEGORIES = [
  { value: 'f47', label: '47 kg' },
  { value: 'f52', label: '52 kg' },
  { value: 'f57', label: '57 kg' },
  { value: 'f63', label: '63 kg' },
  { value: 'f69', label: '69 kg' },
  { value: 'f76', label: '76 kg' },
  { value: 'f84', label: '84 kg' },
  { value: 'f84plus', label: '84+ kg' },
];

export const CATEGORY_LABELS = Object.fromEntries(
  [...MALE_CATEGORIES, ...FEMALE_CATEGORIES].map((item) => [item.value, item.label]),
);

export const LEVEL_LABELS = Object.fromEntries(
  LEVEL_OPTIONS.map((item) => [item.value, item.label]),
);

export function weightCategoriesForSex(sex) {
  if (sex === 'male') {
    return MALE_CATEGORIES;
  }
  if (sex === 'female') {
    return FEMALE_CATEGORIES;
  }
  return [...MALE_CATEGORIES, ...FEMALE_CATEGORIES];
}

export function weightCategoryLabel(value) {
  return CATEGORY_LABELS[value] ?? value ?? '—';
}

export function levelLabel(value) {
  return LEVEL_LABELS[value] ?? value ?? '—';
}

export const COACH_SPECIALTY_OPTIONS = [
  { value: 'powerlifting', label: 'Powerlifting' },
  { value: 'force_athletique', label: 'Force athlétique' },
  { value: 'hypertrophie', label: 'Hypertrophie' },
  { value: 'preparation_physique', label: 'Préparation physique' },
  { value: 'rehabilitation', label: 'Réhabilitation / retour' },
];

export const COACH_SPECIALTY_LABELS = Object.fromEntries(
  COACH_SPECIALTY_OPTIONS.map((item) => [item.value, item.label]),
);
