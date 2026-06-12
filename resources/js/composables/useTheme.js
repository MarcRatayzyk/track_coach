import { computed, ref, watch } from 'vue';

export const THEME_STORAGE_KEY = 'tc-theme';

/** @type {import('vue').Ref<'dark' | 'light'>} */
export const theme = ref('dark');

export function applyTheme(value) {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.dataset.theme = value;
    document.documentElement.style.colorScheme = value;
}

export function initTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    const stored = window.localStorage.getItem(THEME_STORAGE_KEY);
    theme.value = stored === 'light' ? 'light' : 'dark';
    applyTheme(theme.value);
}

watch(theme, (value) => {
    if (typeof window === 'undefined') {
        return;
    }

    applyTheme(value);
    window.localStorage.setItem(THEME_STORAGE_KEY, value);
});

export function useTheme() {
    const isLight = computed(() => theme.value === 'light');

    function toggleTheme() {
        theme.value = theme.value === 'light' ? 'dark' : 'light';
    }

    function setTheme(value) {
        theme.value = value === 'light' ? 'light' : 'dark';
    }

    return {
        theme,
        isLight,
        toggleTheme,
        setTheme,
    };
}
