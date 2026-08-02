import i18n, { localeTag } from '../i18n';

function activeTag() {
  return localeTag(i18n.global.locale.value);
}

function tr(key) {
  return i18n.global.t(key);
}

export function messagingInitials(name) {
  if (!name || typeof name !== 'string') {
    return '?';
  }

  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) {
    return '?';
  }

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase();
  }

  return `${parts[0][0] ?? ''}${parts[1][0] ?? ''}`.toUpperCase();
}

export function messagingRelativeTime(iso) {
  if (!iso) {
    return '';
  }

  try {
    const date = new Date(iso);
    const now = new Date();
    const sameDay =
      date.getFullYear() === now.getFullYear() &&
      date.getMonth() === now.getMonth() &&
      date.getDate() === now.getDate();

    if (sameDay) {
      return date.toLocaleTimeString(activeTag(), { hour: '2-digit', minute: '2-digit' });
    }

    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday =
      date.getFullYear() === yesterday.getFullYear() &&
      date.getMonth() === yesterday.getMonth() &&
      date.getDate() === yesterday.getDate();

    if (isYesterday) {
      return tr('app.messaging.yesterday');
    }

    return date.toLocaleDateString(activeTag(), { day: 'numeric', month: 'short' });
  } catch {
    return '';
  }
}

export function messagingClock(iso) {
  if (!iso) {
    return '';
  }

  try {
    return new Date(iso).toLocaleTimeString(activeTag(), {
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return '';
  }
}

export function messagingDateKey(iso) {
  if (!iso) {
    return '';
  }

  try {
    const date = new Date(iso);
    return `${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
  } catch {
    return '';
  }
}

export function messagingDateLabel(iso) {
  if (!iso) {
    return '';
  }

  try {
    const date = new Date(iso);
    const now = new Date();
    const sameDay =
      date.getFullYear() === now.getFullYear() &&
      date.getMonth() === now.getMonth() &&
      date.getDate() === now.getDate();

    if (sameDay) {
      return tr('app.messaging.today');
    }

    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday =
      date.getFullYear() === yesterday.getFullYear() &&
      date.getMonth() === yesterday.getMonth() &&
      date.getDate() === yesterday.getDate();

    if (isYesterday) {
      return tr('app.messaging.yesterday');
    }

    return date.toLocaleDateString(activeTag(), {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });
  } catch {
    return '';
  }
}
