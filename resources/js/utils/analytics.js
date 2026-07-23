import posthog from 'posthog-js';
import { isNativeApp } from '../composables/useNativeApp';

const key = import.meta.env.VITE_POSTHOG_KEY;
const host = import.meta.env.VITE_POSTHOG_HOST || 'https://eu.i.posthog.com';

let enabled = false;

export function getAnalyticsPlatform() {
    return isNativeApp ? 'android' : 'web';
}

export function initAnalytics() {
    if (!key || enabled) {
        return;
    }

    posthog.init(key, {
        api_host: host,
        ui_host: 'https://eu.posthog.com',
        // SPA Inertia : laisse PostHog émettre un $pageview à chaque changement d'URL (pushState).
        capture_pageview: 'history_change',
        capture_pageleave: true,
        // Beta : on crée un profil pour tous les visiteurs (sinon Web Analytics reste vide avant login).
        person_profiles: 'always',
        autocapture: true,
        session_recording: {
            maskAllInputs: true,
        },
        loaded: (ph) => {
            // Ajoute la plateforme (web/android) sur TOUS les events, y compris pageviews et autocapture.
            ph.register({ platform: getAnalyticsPlatform() });
            if (import.meta.env.DEV) {
                ph.debug();
            }
        },
    });

    enabled = true;
}

export function identifyUser(user) {
    if (!enabled || !user?.id) {
        return;
    }

    posthog.identify(String(user.id), {
        email: user.email,
        name: user.name,
        role: user.role,
    });
}

export function resetAnalytics() {
    if (!enabled) {
        return;
    }

    posthog.reset();
}

export function track(event, props = {}) {
    if (!enabled || !event) {
        return;
    }

    posthog.capture(event, {
        platform: getAnalyticsPlatform(),
        ...props,
    });
}

export function isAnalyticsEnabled() {
    return enabled;
}
