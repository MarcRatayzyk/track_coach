<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { isNativeApp } from '../composables/useNativeApp';
import {
    CookieConsent,
    readCookieConsent,
    writeCookieConsent,
} from '../composables/useCookieConsent';
import { initAnalytics } from '../utils/analytics';

const { t } = useI18n();
const visible = ref(false);

function accept() {
    writeCookieConsent(CookieConsent.Accepted);
    visible.value = false;
    initAnalytics();
}

function reject() {
    writeCookieConsent(CookieConsent.Rejected);
    visible.value = false;
}

onMounted(() => {
    if (isNativeApp) {
        initAnalytics();
        return;
    }

    const consent = readCookieConsent();
    if (consent === CookieConsent.Accepted) {
        initAnalytics();
        return;
    }

    if (consent === CookieConsent.Rejected) {
        return;
    }

    visible.value = true;
});
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="visible"
            class="fixed inset-x-0 bottom-0 z-[100] border-t border-slate-700/80 bg-slate-900/95 p-4 shadow-2xl backdrop-blur-md sm:p-5"
            role="dialog"
            aria-live="polite"
            :aria-label="t('cookieConsent.aria')"
        >
            <div class="mx-auto flex max-w-4xl flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-white">{{ t('cookieConsent.title') }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-400">
                        {{ t('cookieConsent.body') }}
                        <Link href="/confidentialite" class="text-blue-400 hover:underline">
                            {{ t('cookieConsent.privacyLink') }}
                        </Link>.
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-600 px-4 py-2 text-sm font-medium text-slate-300 transition hover:border-slate-500 hover:text-white"
                        @click="reject"
                    >
                        {{ t('cookieConsent.reject') }}
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500"
                        @click="accept"
                    >
                        {{ t('cookieConsent.accept') }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
