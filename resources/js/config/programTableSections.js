export const PROGRAM_TABLE_SECTIONS = [
  {
    value: 'topset',
    label: 'Topset',
    shortLabel: 'Top',
    rowClass: 'bg-amber-500/12',
    buttonActiveClass: 'bg-amber-400 text-slate-950 font-semibold shadow-sm shadow-amber-900/30',
    buttonInactiveClass: 'text-amber-300/75 hover:bg-amber-500/15 hover:text-amber-200',
  },
  {
    value: 'backoff',
    label: 'Back off',
    shortLabel: 'Back',
    rowClass: 'bg-blue-500/12',
    buttonActiveClass: 'bg-blue-500 text-white font-semibold shadow-sm shadow-blue-900/30',
    buttonInactiveClass: 'text-blue-300/75 hover:bg-blue-500/15 hover:text-blue-200',
  },
  {
    value: 'accessory',
    label: 'Accessoires',
    shortLabel: 'Acc.',
    rowClass: 'bg-emerald-500/10',
    buttonActiveClass: 'bg-emerald-500 text-slate-950 font-semibold shadow-sm shadow-emerald-900/30',
    buttonInactiveClass: 'text-emerald-300/75 hover:bg-emerald-500/15 hover:text-emerald-200',
  },
];

export function sectionRowClass(section) {
  return (
    PROGRAM_TABLE_SECTIONS.find((option) => option.value === section)?.rowClass ?? 'bg-white/5'
  );
}

export function sectionOption(section) {
  return (
    PROGRAM_TABLE_SECTIONS.find((option) => option.value === section) ??
    PROGRAM_TABLE_SECTIONS[2]
  );
}
