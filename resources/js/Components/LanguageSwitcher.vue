<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { LOCALE_STORAGE_KEY, setAppLocale } from '../i18n';

const props = defineProps({
    variant: {
        type: String,
        default: 'default', // default | landing | compact
    },
});

const page = usePage();
const { t } = useI18n();

const current = computed(() => (page.props.locale === 'en' ? 'en' : 'fr'));

function switchTo(locale) {
    if (locale === current.value) {
        return;
    }
    setAppLocale(locale);
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(LOCALE_STORAGE_KEY, locale);
    }
    router.post(
        '/locale',
        { locale },
        { preserveScroll: true, preserveState: false },
    );
}

const wrapClass = computed(() => {
    if (props.variant === 'landing') {
        return 'inline-flex items-center rounded-lg border border-white/10 bg-white/[0.04] p-0.5';
    }
    if (props.variant === 'compact') {
        return 'inline-flex items-center rounded-md border border-slate-200 bg-slate-50 p-0.5 dark:border-slate-700 dark:bg-slate-800';
    }
    return 'inline-flex items-center rounded-lg border border-slate-200 bg-white p-0.5 dark:border-slate-700 dark:bg-slate-900';
});

function btnClass(locale) {
    const active = current.value === locale;
    if (props.variant === 'landing') {
        return [
            'rounded-md px-2 py-1 text-[11px] font-semibold uppercase tracking-wide transition',
            active ? 'bg-white/15 text-white' : 'text-slate-400 hover:text-white',
        ];
    }
    return [
        'rounded-md px-2 py-1 text-[11px] font-semibold uppercase tracking-wide transition',
        active
            ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
            : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white',
    ];
}
</script>

<template>
    <div
        :class="wrapClass"
        role="group"
        :aria-label="t('common.language')"
    >
        <button
            type="button"
            :class="btnClass('fr')"
            :aria-pressed="current === 'fr'"
            @click="switchTo('fr')"
        >
            FR
        </button>
        <button
            type="button"
            :class="btnClass('en')"
            :aria-pressed="current === 'en'"
            @click="switchTo('en')"
        >
            EN
        </button>
    </div>
</template>
