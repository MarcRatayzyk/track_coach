import { localeTag, resolveLocale } from '../i18n';

function activeLocaleTag(locale) {
    if (locale) {
        return localeTag(locale);
    }
    const raw =
        typeof document !== 'undefined' ? document.documentElement.lang : 'fr';
    return localeTag(resolveLocale(raw));
}

/**
 * Interprète une valeur comme date civile (Y-m-d ou ISO commençant par Y-m-d)
 * sans décalage fuseau (évite « veille » pour minuit UTC).
 */
export function calendarDateFromValue(value) {
    if (value == null || value === '') {
        return null;
    }
    const s = String(value);
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (m) {
        const y = Number(m[1]);
        const mo = Number(m[2]);
        const d = Number(m[3]);
        return new Date(y, mo - 1, d);
    }
    const t = Date.parse(s);
    if (Number.isNaN(t)) {
        return null;
    }
    return new Date(t);
}

/**
 * @param {'long'|'medium'|'short'} style — long : jour de semaine + date ; medium : date lisible ; short : mois + année
 * @param {string} [locale] — 'fr' | 'en' (défaut: document lang)
 */
export function formatCalendarDate(value, style = 'long', locale) {
    const date = calendarDateFromValue(value);
    if (!date) {
        return '—';
    }
    const opts =
        style === 'short'
            ? { month: 'short', year: '2-digit' }
            : style === 'medium'
              ? { day: 'numeric', month: 'long', year: 'numeric' }
              : {
                    weekday: 'short',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                };
    return date.toLocaleDateString(activeLocaleTag(locale), opts);
}

/** @deprecated Use formatCalendarDate */
export function formatCalendarFr(value, style = 'long') {
    return formatCalendarDate(value, style);
}

/** Instant réel (échéances, horodatage messagerie). */
export function formatDateTime(value, locale) {
    if (value == null || value === '') {
        return '—';
    }
    const t = Date.parse(String(value));
    if (Number.isNaN(t)) {
        return '—';
    }
    return new Date(t).toLocaleString(activeLocaleTag(locale), {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** @deprecated Use formatDateTime */
export function formatDateTimeFr(value) {
    return formatDateTime(value);
}

export function formatShortDateTime(value, locale) {
    if (value == null || value === '') {
        return '';
    }
    const t = Date.parse(String(value));
    if (Number.isNaN(t)) {
        return '';
    }
    return new Date(t).toLocaleString(activeLocaleTag(locale), {
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** @deprecated Use formatShortDateTime */
export function formatShortDateTimeFr(value) {
    return formatShortDateTime(value);
}
