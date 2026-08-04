const STORAGE_KEY = 'pr_cookie_consent';

export const CookieConsent = {
    Accepted: 'accepted',
    Rejected: 'rejected',
};

export function readCookieConsent() {
    if (typeof window === 'undefined') {
        return null;
    }

    const value = window.localStorage.getItem(STORAGE_KEY);
    if (value === CookieConsent.Accepted || value === CookieConsent.Rejected) {
        return value;
    }

    return null;
}

export function writeCookieConsent(value) {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, value);
}

export function hasAnalyticsConsent() {
    return readCookieConsent() === CookieConsent.Accepted;
}

export function clearCookieConsent() {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(STORAGE_KEY);
}
