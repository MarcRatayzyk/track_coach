import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import fr from './locales/fr.json';

export const LOCALE_STORAGE_KEY = 'pr_locale';

export const BCP47 = {
    fr: 'fr-FR',
    en: 'en-US',
};

export function resolveLocale(raw) {
    return raw === 'en' ? 'en' : 'fr';
}

export function localeTag(locale = 'fr') {
    return BCP47[resolveLocale(locale)] ?? BCP47.fr;
}

const i18n = createI18n({
    legacy: false,
    locale: 'fr',
    fallbackLocale: 'fr',
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
    return resolved;
}

export default i18n;
