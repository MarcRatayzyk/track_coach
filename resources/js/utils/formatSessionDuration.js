const MAX_OPEN_SEGMENT_SECONDS = 8 * 60 * 60;

export function formatSessionDuration(totalSeconds) {
  const total = Math.max(0, Math.floor(Number(totalSeconds) || 0));
  if (total <= 0) {
    return '';
  }

  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;
  const pad = (value) => String(value).padStart(2, '0');

  if (hours > 0) {
    return `${hours}:${pad(minutes)}:${pad(seconds)}`;
  }

  return `${minutes}:${pad(seconds)}`;
}

/** Secondes figées + segment encore ouvert, comme côté serveur. */
export function sessionRecordedSeconds(session, now = Date.now()) {
  const stored = Math.max(0, Number(session?.timer_elapsed_seconds ?? 0));
  if (!session || session.finished_at || !session.timer_started_at) {
    return stored;
  }

  const startMs = Date.parse(session.timer_started_at);
  if (!Number.isFinite(startMs)) {
    return stored;
  }

  const open = Math.max(0, Math.floor((now - startMs) / 1000));
  return stored + Math.min(open, MAX_OPEN_SEGMENT_SECONDS);
}
