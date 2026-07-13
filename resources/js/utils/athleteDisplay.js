import { weightCategoryLabel } from '../config/ipfWeightCategories';

function capitalizeWord(word) {
  if (!word) {
    return '';
  }
  return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
}

export function formatAthleteDisplayName(name) {
  const parts = String(name ?? '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);

  if (!parts.length) {
    return '—';
  }

  if (parts.length === 1) {
    return capitalizeWord(parts[0]);
  }

  const lastName = parts[parts.length - 1];
  const firstName = parts.slice(0, -1).join(' ');

  return `${lastName.toUpperCase()} ${firstName
    .split(/(\s|-)/)
    .map((segment) => (/^\s+$/.test(segment) || segment === '-' ? segment : capitalizeWord(segment)))
    .join('')}`;
}

export function athleteAgeFromBirthDate(birthDate) {
  if (!birthDate) {
    return null;
  }

  const birth = new Date(String(birthDate).slice(0, 10));
  if (!Number.isFinite(birth.getTime())) {
    return null;
  }

  const now = new Date();
  let age = now.getFullYear() - birth.getFullYear();
  const monthDiff = now.getMonth() - birth.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
    age -= 1;
  }

  return age > 0 ? age : null;
}

export function ipfAgeDivision(age) {
  if (age == null || age < 0) {
    return null;
  }

  if (age < 18) {
    return 'subjunior';
  }

  if (age < 23) {
    return 'junior';
  }

  return 'open';
}

export function formatWeightClassCompact(value) {
  if (!value) {
    return null;
  }

  const label = weightCategoryLabel(value);
  const match = String(label).match(/(\d+(?:\+)?)/);

  if (!match) {
    return label;
  }

  const numeric = match[1];

  if (String(value).startsWith('m')) {
    return `-${numeric}`;
  }

  return numeric;
}

export function formatAthleteCategoryLine(weightCategory, birthDate) {
  const weightLabel = formatWeightClassCompact(weightCategory);
  const ageDivision = ipfAgeDivision(athleteAgeFromBirthDate(birthDate));

  if (!weightLabel && !ageDivision) {
    return '—';
  }

  if (weightLabel && ageDivision) {
    return `${weightLabel} ${ageDivision}`;
  }

  return weightLabel ?? ageDivision;
}
