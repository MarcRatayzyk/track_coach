import posthog from 'posthog-js';
import { isNativeApp } from '../composables/useNativeApp';

let enabled = false;

function resolveConfig() {
    const runtime =
        typeof window !== 'undefined' && window.__POSTHOG__ && typeof window.__POSTHOG__ === 'object'
            ? window.__POSTHOG__
            : {};

    return {
        key: String(runtime.key || import.meta.env.VITE_POSTHOG_KEY || '').trim(),
        host: String(
            runtime.host || import.meta.env.VITE_POSTHOG_HOST || 'https://eu.i.posthog.com',
        ).trim(),
        uiHost: String(runtime.ui_host || 'https://eu.posthog.com').trim(),
    };
}

export function getAnalyticsPlatform() {
    return isNativeApp ? 'android' : 'web';
}

export function initAnalytics() {
    if (enabled) {
        return;
    }

    const { key, host, uiHost } = resolveConfig();
    if (!key) {
        return;
    }

    posthog.init(key, {
        api_host: host,
        ui_host: uiHost,
        defaults: '2025-05-24',
        // SPA Inertia : pageviews au chargement + à chaque pushState.
        capture_pageview: 'history_change',
        capture_pageleave: true,
        // Beta : profils même avant login (sinon Web Analytics / Persons restent vides).
        person_profiles: 'always',
        autocapture: true,
        session_recording: {
            maskAllInputs: true,
        },
        loaded: (ph) => {
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
