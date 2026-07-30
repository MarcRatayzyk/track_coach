import { ref } from 'vue';

const STORAGE_KEY = 'tc-dismissed-alerts';

/** @type {import('vue').Ref<Set<string>>|null} */
let sharedKeys = null;

function readStoredKeys() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed.filter((k) => typeof k === 'string') : [];
  } catch {
    return [];
  }
}

function writeStoredKeys(keys) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify([...keys]));
  } catch {
    // ignore quota / private mode
  }
}

/**
 * Persistance locale des alertes écartées par le coach (bouton OK).
 */
export function useDismissedAlerts() {
  if (!sharedKeys) {
    sharedKeys = ref(new Set(readStoredKeys()));
  }

  const dismissedKeys = sharedKeys;

  function isDismissed(key) {
    if (!key) return false;
    return dismissedKeys.value.has(String(key));
  }

  function dismiss(key) {
    if (!key) return;
    const next = new Set(dismissedKeys.value);
    next.add(String(key));
    dismissedKeys.value = next;
    writeStoredKeys(next);
  }

  function filterActive(alerts) {
    return (alerts ?? []).filter((alert) => !isDismissed(alert?.key));
  }

  return {
    dismissedKeys,
    isDismissed,
    dismiss,
    filterActive,
  };
}
