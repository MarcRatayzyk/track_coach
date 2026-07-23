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
        capture_pageview: false,
        capture_pageleave: true,
        person_profiles: 'identified_only',
        session_recording: {
            maskAllInputs: true,
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

export function trackPageview(path) {
    if (!enabled) {
        return;
    }

    posthog.capture('$pageview', {
        platform: getAnalyticsPlatform(),
        path: path || window.location.pathname,
        $current_url: window.location.href,
    });
}

export function isAnalyticsEnabled() {
    return enabled;
}
