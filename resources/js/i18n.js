import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import fr from './locales/fr.json';
import { syncCurrencyWithLocale } from './utils/pricing';

export const LOCALE_STORAGE_KEY = 'pr_locale';

export const BCP47 = {
    fr: 'fr-FR',
    en: 'en-US',
};

export function resolveLocale(raw) {
    return raw === 'fr' ? 'fr' : 'en';
}

export function localeTag(locale = 'en') {
    return BCP47[resolveLocale(locale)] ?? BCP47.en;
}

const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { fr, en },
});

export function setAppLocale(locale) {
    const resolved = resolveLocale(locale);
    i18n.global.locale.value = resolved;
    if (typeof document !== 'undefined') {
        document.documentElement.lang = resolved;
    }
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(LOCALE_STORAGE_KEY, resolved);
    }
    syncCurrencyWithLocale(resolved);
    return resolved;
}

export default i18n;
