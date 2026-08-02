import i18n from '../i18n';

function tt(key) {
  return i18n.global.t(key);
}

export const PROGRAM_TABLE_SECTIONS = [
  {
    value: 'warmup',
    get label() { return tt('config.programSections.warmup'); },
    get shortLabel() { return tt('config.programSections.warmupShort'); },
    compactLabel: 'Éc',
    rowClass: 'bg-sky-500/12',
    buttonActiveClass: 'bg-sky-400 text-slate-950 font-semibold shadow-sm shadow-sky-900/30',
    buttonInactiveClass: 'text-sky-300/75 hover:bg-sky-500/15 hover:text-sky-200',
  },
  {
    value: 'topset',
    get label() { return tt('config.programSections.topset'); },
    get shortLabel() { return tt('config.programSections.topsetShort'); },
    compactLabel: 'To',
    rowClass: 'bg-amber-500/12',
    buttonActiveClass: 'bg-amber-400 text-slate-950 font-semibold shadow-sm shadow-amber-900/30',
    buttonInactiveClass: 'text-amber-300/75 hover:bg-amber-500/15 hover:text-amber-200',
  },
  {
    value: 'backoff',
    get label() { return tt('config.programSections.backoff'); },
    get shortLabel() { return tt('config.programSections.backoffShort'); },
    compactLabel: 'Ba',
    rowClass: 'bg-blue-500/12',
    buttonActiveClass: 'bg-blue-500 text-white font-semibold shadow-sm shadow-blue-900/30',
    buttonInactiveClass: 'text-blue-300/75 hover:bg-blue-500/15 hover:text-blue-200',
  },
  {
    value: 'accessory',
    get label() { return tt('config.programSections.accessory'); },
    get shortLabel() { return tt('config.programSections.accessoryShort'); },
    compactLabel: 'Ac',
    rowClass: 'bg-emerald-500/10',
    buttonActiveClass: 'bg-emerald-500 text-slate-950 font-semibold shadow-sm shadow-emerald-900/30',
    buttonInactiveClass: 'text-emerald-300/75 hover:bg-emerald-500/15 hover:text-emerald-200',
  },
];

export function sectionRowClass(_section) {
  return '';
}

export function sectionOption(section) {
  return (
    PROGRAM_TABLE_SECTIONS.find((option) => option.value === section) ??
    PROGRAM_TABLE_SECTIONS.find((option) => option.value === 'accessory')
  );
}

export function sectionBadgeClass(section) {
  const option = sectionOption(section);
  return {
    warmup: 'border-sky-500/40 bg-sky-500/15 text-sky-200',
    topset: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
    backoff: 'border-blue-500/40 bg-blue-500/15 text-blue-200',
    accessory: 'border-emerald-500/35 bg-emerald-500/10 text-emerald-200',
  }[option.value] ?? 'border-slate-600 bg-slate-800 text-slate-300';
}

export function schemeShortLabel(setScheme) {
  if (setScheme === 'ramp') {
    return tt('config.programSections.rampUp');
  }
  if (setScheme === 'cluster') {
    return tt('config.programSections.cluster');
  }
  return '';
}

/** Ex. "Topset - Ramp-up" pour les séries spéciales. */
export function sectionWithSchemeLabel(section, setScheme) {
  const base = sectionOption(section).label;
  const scheme = schemeShortLabel(setScheme);
  if (!scheme) {
    return base;
  }
  return tt('config.programSections.withScheme', { section: base, scheme });
}

