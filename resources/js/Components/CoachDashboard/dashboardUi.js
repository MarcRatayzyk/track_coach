import { localeTag } from '../../i18n';
export function athleteInitials(name) {
  const parts = String(name || '')
    .trim()
    .split(/\s+/)
    .filter(Boolean);
  if (!parts.length) {
    return '?';
  }
  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }
  return `${parts[0][0]}${parts[1][0]}`.toUpperCase();
}

export function daysUntil(dateStr) {
  if (!dateStr) {
    return null;
  }
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const target = new Date(`${String(dateStr).slice(0, 10)}T00:00:00`);
  if (Number.isNaN(target.getTime())) {
    return null;
  }
  return Math.round((target.getTime() - today.getTime()) / 86400000);
}

export function relativeTimeFr(iso) {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  const diffMs = date.getTime() - Date.now();
  const abs = Math.abs(diffMs);
  const rtf = new Intl.RelativeTimeFormat('fr', { numeric: 'auto' });
  const minutes = Math.round(diffMs / 60000);
  if (abs < 3600000) {
    return rtf.format(minutes, 'minute');
  }
  const hours = Math.round(diffMs / 3600000);
  if (abs < 86400000) {
    return rtf.format(hours, 'hour');
  }
  const days = Math.round(diffMs / 86400000);
  return rtf.format(days, 'day');
}

export function timeOfDayFr(iso) {
  if (!iso) {
    return '';
  }
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) {
    return '';
  }
  return date.toLocaleTimeString(localeTag(), { hour: '2-digit', minute: '2-digit' });
}

export const cardShell =
  'rounded-[1.25rem] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg shadow-blue-950/20 backdrop-blur-sm transition duration-200';

export const cardHover =
  'hover:-translate-y-0.5 hover:border-blue-500/35 hover:shadow-xl hover:shadow-blue-500/10';
