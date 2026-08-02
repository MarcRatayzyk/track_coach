import i18n from '../i18n';

/** Slug approximatif aligné sur Laravel Str::slug (ASCII). */
export function exerciseNameSlug(name) {
  return String(name ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');
}

/**
 * Traduit un nom d'exercice du catalogue (snapshot FR en base) selon la locale.
 * Les exercices custom hors dictionnaire restent inchangés.
 */
export function localizedExerciseName(name) {
  const raw = String(name ?? '').trim();
  if (!raw) {
    return raw;
  }

  const slug = exerciseNameSlug(raw);
  if (!slug) {
    return raw;
  }

  const key = `exercises.names.${slug}`;
  if (i18n.global.te(key)) {
    return i18n.global.t(key);
  }

  return raw;
}
