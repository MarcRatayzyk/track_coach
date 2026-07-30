/**
 * Urgence des retours coach — règles métier :
 * - Sans envoi athlète : jamais « en retard » (c’est une alerte coach, pas un retard de traitement).
 * - Journalier (envoyé) : en retard dès que session_date < aujourd’hui et pas encore répondu.
 * - Hebdomadaire (envoyé) : en retard seulement après la fin de la semaine d’envoi (dimanche).
 */

export function dateKey(value) {
  if (!value) return '';
  const m = String(value).match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : '';
}

export function todayKey(reference = null) {
  if (reference) {
    return dateKey(reference);
  }
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

/** Dimanche de la semaine ISO (lundi → dimanche) contenant `ymd`. */
export function weekEndSunday(ymd) {
  const key = dateKey(ymd);
  if (!key) return '';
  const d = new Date(`${key}T12:00:00`);
  const mondayOffset = (d.getDay() + 6) % 7;
  d.setDate(d.getDate() - mondayOffset + 6);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export function weekStartMonday(ymd) {
  const key = dateKey(ymd);
  if (!key) return '';
  const d = new Date(`${key}T12:00:00`);
  const mondayOffset = (d.getDay() + 6) % 7;
  d.setDate(d.getDate() - mondayOffset);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export function isWeeklyFrequency(frequency) {
  return frequency !== 'daily';
}

/**
 * @param {{ status?: string, feedback_status?: string, session_date?: string, period_week_start?: string, due_at?: string, feedback_frequency?: string, _kind?: string }} item
 * @param {string|null} today
 */
export function isFeedbackOverdue(item, today = null) {
  const status = item.feedback_status ?? item.status;
  if (status === 'coach_replied') {
    return false;
  }

  // Pas d’envoi athlète → pas un retard coach (alerte dédiée côté dashboard).
  const hasSubmission =
    item.has_submission === true ||
    item.session_feedback_id != null ||
    status === 'submitted';
  if (!hasSubmission) {
    return false;
  }

  const todayStr = todayKey(today);
  const isWeekly =
    item._kind === 'weekly' ||
    Boolean(item.period_week_start) ||
    isWeeklyFrequency(item.feedback_frequency);

  if (isWeekly) {
    const anchor =
      dateKey(item.period_week_start) ||
      dateKey(item.session_date) ||
      dateKey(item.due_at);
    if (!anchor) {
      if (item.due_at) {
        const due = Date.parse(item.due_at);
        return !Number.isNaN(due) && due < Date.now();
      }
      return false;
    }
    return todayStr > weekEndSunday(anchor);
  }

  // Journalier
  const session = dateKey(item.session_date);
  if (session) {
    return session < todayStr;
  }
  if (item.due_at) {
    const due = Date.parse(item.due_at);
    return !Number.isNaN(due) && due < Date.now();
  }
  return false;
}

/**
 * @returns {'done'|'overdue'|'today'|'normal'}
 */
export function feedbackUrgency(item, today = null) {
  const status = item.feedback_status ?? item.status;
  if (status === 'coach_replied') {
    return 'done';
  }

  const todayStr = todayKey(today);
  if (isFeedbackOverdue(item, todayStr)) {
    return 'overdue';
  }

  const session = dateKey(item.session_date);
  if (session && session === todayStr) {
    return 'today';
  }

  return 'normal';
}
