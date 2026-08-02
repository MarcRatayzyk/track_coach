import i18n from '../i18n';

function tt(key) {
  return i18n.global.t(key);
}

export const SEX_OPTIONS = [
  { value: 'male', get label() { return tt('config.sex.male'); } },
  { value: 'female', get label() { return tt('config.sex.female'); } },
];

export const LEVEL_OPTIONS = [
  { value: 'beginner', get label() { return tt('config.level.beginner'); } },
  { value: 'intermediate', get label() { return tt('config.level.intermediate'); } },
  { value: 'advanced', get label() { return tt('config.level.advanced'); } },
  { value: 'elite', get label() { return tt('config.level.elite'); } },
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

const LEVEL_KEYS = {
  beginner: 'config.level.beginner',
  intermediate: 'config.level.intermediate',
  advanced: 'config.level.advanced',
  elite: 'config.level.elite',
};

export function weightCategoriesForSex(sex) {
  if (sex === 'male') {
    return MALE_CATEGORIES;
  }
  if (sex === 'female') {
    return FEMALE_CATEGORIES;
  }

  return [
    ...FEMALE_CATEGORIES.map((item) => ({ ...item, label: `F · ${item.label}` })),
    ...MALE_CATEGORIES.map((item) => ({ ...item, label: `H · ${item.label}` })),
  ];
}

export function weightCategoryLabel(value) {
  return CATEGORY_LABELS[value] ?? value ?? '—';
}

export function levelLabel(value) {
  const key = LEVEL_KEYS[value];
  return key ? tt(key) : (value ?? '—');
}

export const COACH_SPECIALTY_OPTIONS = [
  { value: 'powerlifting', get label() { return tt('config.coachSpecialty.powerlifting'); } },
  { value: 'force_athletique', get label() { return tt('config.coachSpecialty.forceAthletique'); } },
  { value: 'hypertrophie', get label() { return tt('config.coachSpecialty.hypertrophie'); } },
  { value: 'preparation_physique', get label() { return tt('config.coachSpecialty.preparationPhysique'); } },
  { value: 'rehabilitation', get label() { return tt('config.coachSpecialty.rehabilitation'); } },
];

export const COACH_SPECIALTY_LABELS = {
  get powerlifting() { return tt('config.coachSpecialty.powerlifting'); },
  get force_athletique() { return tt('config.coachSpecialty.forceAthletique'); },
  get hypertrophie() { return tt('config.coachSpecialty.hypertrophie'); },
  get preparation_physique() { return tt('config.coachSpecialty.preparationPhysique'); },
  get rehabilitation() { return tt('config.coachSpecialty.rehabilitation'); },
};
