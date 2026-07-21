<script setup>
import { computed, ref, watch } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import { formatLineRecap, SESSION_SECTION_LABELS } from '../utils/programBuilder';

const props = defineProps({
  sessions: {
    type: Array,
    default: () => [],
  },
  selectedDay: {
    type: String,
    default: null,
  },
  referenceLifts: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:selectedDay', 'edit-session']);

const weekdayLabels = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

const LIFTS = [
  { key: 'squat', label: 'Squat', short: 'S', bar: 'bg-violet-500', text: 'text-violet-300', ring: 'ring-violet-500/30' },
  { key: 'bench', label: 'Bench', short: 'B', bar: 'bg-blue-500', text: 'text-blue-300', ring: 'ring-blue-500/30' },
  { key: 'deadlift', label: 'Terre', short: 'T', bar: 'bg-amber-500', text: 'text-amber-300', ring: 'ring-amber-500/30' },
];

function sessionDateKey(value) {
  const s = String(value ?? '');
  const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : null;
}

function parseYmd(key) {
  const [y, mo, d] = key.split('-').map(Number);
  return new Date(y, mo - 1, d);
}

const sessionsByDay = computed(() => {
  const map = {};
  for (const session of props.sessions) {
    const key = sessionDateKey(session.session_date);
    if (!key) {
      continue;
    }
    if (!map[key]) {
      map[key] = [];
    }
    map[key].push(session);
  }
  for (const key of Object.keys(map)) {
    map[key].sort((a, b) => Number(a.id) - Number(b.id));
  }
  return map;
});

const sessionsChronological = computed(() =>
  [...props.sessions].sort((a, b) => {
    const dateCmp = String(a.session_date ?? '').localeCompare(String(b.session_date ?? ''));
    if (dateCmp !== 0) {
      return dateCmp;
    }
    return Number(a.id) - Number(b.id);
  }),
);

function initialMonth() {
  const latest = [...props.sessions].sort((a, b) =>
    String(b.session_date ?? '').localeCompare(String(a.session_date ?? '')),
  )[0];

  if (latest?.session_date) {
    const key = sessionDateKey(latest.session_date);
    if (key) {
      const d = parseYmd(key);
      return { year: d.getFullYear(), month: d.getMonth() };
    }
  }

  const now = new Date();
  return { year: now.getFullYear(), month: now.getMonth() };
}

const calendarMonth = ref(initialMonth().month);
const calendarYear = ref(initialMonth().year);

const monthLabel = computed(() => {
  const d = new Date(calendarYear.value, calendarMonth.value, 1);
  return d.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
});

const calendarCells = computed(() => {
  const year = calendarYear.value;
  const month = calendarMonth.value;
  const first = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0).getDate();
  const startOffset = (first.getDay() + 6) % 7;
  const cells = [];

  for (let i = 0; i < startOffset; i++) {
    cells.push({ key: `pad-${i}`, empty: true });
  }

  for (let day = 1; day <= lastDay; day++) {
    const key = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    const items = sessionsByDay.value[key] ?? [];
    cells.push({
      key,
      empty: false,
      day,
      dateKey: key,
      hasSession: items.length > 0,
      count: items.length,
    });
  }

  return cells;
});

const selectedDayLocal = computed({
  get: () => props.selectedDay,
  set: (value) => emit('update:selectedDay', value),
});

const daySessions = computed(() => {
  if (!selectedDayLocal.value) {
    return [];
  }
  return sessionsByDay.value[selectedDayLocal.value] ?? [];
});

const selectedDayWeekday = computed(() => {
  if (!selectedDayLocal.value) {
    return '';
  }
  const d = parseYmd(selectedDayLocal.value);
  return d.toLocaleDateString('fr-FR', { weekday: 'long' });
});

const sessionsInVisibleMonth = computed(() => {
  const prefix = `${calendarYear.value}-${String(calendarMonth.value + 1).padStart(2, '0')}`;
  return Object.keys(sessionsByDay.value).filter((key) => key.startsWith(prefix)).length;
});

const bestSessionTotals = computed(() => {
  let best = 0;
  for (const session of props.sessions) {
    best = Math.max(best, sessionTotal(session));
  }
  return best;
});

function prevMonth() {
  if (calendarMonth.value === 0) {
    calendarMonth.value = 11;
    calendarYear.value -= 1;
  } else {
    calendarMonth.value -= 1;
  }
}

function nextMonth() {
  if (calendarMonth.value === 11) {
    calendarMonth.value = 0;
    calendarYear.value += 1;
  } else {
    calendarMonth.value += 1;
  }
}

function selectDay(cell) {
  if (cell.empty || !cell.hasSession) {
    return;
  }
  selectedDayLocal.value = cell.dateKey;
}

function sessionTotal(session) {
  return Number(session.squat ?? 0) + Number(session.bench ?? 0) + Number(session.deadlift ?? 0);
}

function previousSession(session) {
  const list = sessionsChronological.value;
  const index = list.findIndex((s) => s.id === session.id);
  if (index <= 0) {
    return null;
  }
  return list[index - 1];
}

function liftValue(session, key) {
  return Number(session[key] ?? 0);
}

function referenceFor(key) {
  return Math.max(Number(props.referenceLifts[key] ?? 0), 0);
}

function maxLiftInHistory(key) {
  return props.sessions.reduce((max, s) => Math.max(max, liftValue(s, key)), 0);
}

function isLiftRecordSession(session, key) {
  const value = liftValue(session, key);
  const max = maxLiftInHistory(key);
  if (!value || value < max) {
    return false;
  }
  const tied = props.sessions.filter((s) => liftValue(s, key) === max);
  const latest = [...tied].sort(
    (a, b) =>
      String(b.session_date ?? '').localeCompare(String(a.session_date ?? '')) ||
      Number(b.id) - Number(a.id),
  )[0];
  return latest?.id === session.id;
}

function percentOfReference(value, key) {
  const ref = referenceFor(key);
  if (!ref || !value) {
    return null;
  }
  return Math.min(100, Math.round((value / ref) * 100));
}

function formatDelta(current, previous) {
  const diff = current - previous;
  if (diff === 0) {
    return { text: '=', tone: 'neutral' };
  }
  if (diff > 0) {
    return { text: `+${diff} kg`, tone: 'up' };
  }
  return { text: `${diff} kg`, tone: 'down' };
}

function sessionLiftRows(session) {
  const prev = previousSession(session);
  return LIFTS.map((lift) => {
    const value = liftValue(session, lift.key);
    const prevValue = prev ? liftValue(prev, lift.key) : null;
    const ref = referenceFor(lift.key);
    const isRecord = isLiftRecordSession(session, lift.key);
    return {
      ...lift,
      value,
      percent: percentOfReference(value, lift.key),
      delta: prevValue !== null ? formatDelta(value, prevValue) : null,
      isRecord,
      ref,
    };
  });
}

function sessionHasItemDetails(session) {
  return (session.items ?? []).some((item) => String(item.exercise_name ?? '').trim());
}

function sessionExerciseRecaps(session) {
  return (session.items ?? [])
    .filter((item) => String(item.exercise_name ?? '').trim())
    .filter((item) => item.section !== 'warmup')
    .map((item, index) => ({
      key: `${item.section}-${item.exercise_name}-${index}`,
      section: SESSION_SECTION_LABELS[item.section] ?? item.section,
      recap: formatLineRecap(item),
      athleteNote: String(item.athlete_note ?? '').trim() || null,
    }));
}

function sessionInsights(session) {
  const total = sessionTotal(session);
  const prev = previousSession(session);
  const prevTotal = prev ? sessionTotal(prev) : null;
  const totalDelta = prevTotal !== null ? formatDelta(total, prevTotal) : null;
  const isBestTotal = total > 0 && total >= bestSessionTotals.value;
  const recordLifts = sessionLiftRows(session).filter((row) => row.isRecord && row.value > 0);

  return { total, totalDelta, isBestTotal, recordLifts };
}

function ensureDefaultSelection() {
  if (selectedDayLocal.value && sessionsByDay.value[selectedDayLocal.value]) {
    return;
  }

  const latest = [...props.sessions].sort((a, b) =>
    String(b.session_date ?? '').localeCompare(String(a.session_date ?? '')),
  )[0];

  if (latest?.session_date) {
    const key = sessionDateKey(latest.session_date);
    if (key) {
      selectedDayLocal.value = key;
    }
  }
}

watch(
  () => props.sessions.length,
  () => {
    if (selectedDayLocal.value && !sessionsByDay.value[selectedDayLocal.value]) {
      selectedDayLocal.value = null;
      ensureDefaultSelection();
    } else {
      ensureDefaultSelection();
    }
  },
  { immediate: true },
);
</script>

<template>
  <div v-if="!sessions.length" class="rounded-xl border border-dashed border-slate-700 px-4 py-8 text-center text-sm text-slate-500">
    Aucune séance enregistrée.
  </div>

  <div v-else class="grid gap-4 lg:grid-cols-2 lg:items-start">
    <div class="w-full">
      <div class="flex items-center justify-between gap-2">
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800"
          @click="prevMonth"
        >
          ←
        </button>
        <div class="min-w-0 flex-1 text-center">
          <p class="truncate text-sm font-medium capitalize text-white">{{ monthLabel }}</p>
          <p class="text-xs text-slate-500">
            {{ sessionsInVisibleMonth }} séance{{ sessionsInVisibleMonth > 1 ? 's' : '' }} ce mois
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-2.5 py-1 text-xs text-slate-300 hover:bg-slate-800"
          @click="nextMonth"
        >
          →
        </button>
      </div>

      <div class="mt-3 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500">
        <span v-for="(label, i) in weekdayLabels" :key="i">{{ label }}</span>
      </div>

      <div class="mt-1.5 grid grid-cols-7 gap-1">
        <template v-for="cell in calendarCells" :key="cell.key">
          <div v-if="cell.empty" class="aspect-square max-h-10" />
          <button
            v-else
            type="button"
            :disabled="!cell.hasSession"
            class="relative flex aspect-square max-h-10 w-full items-center justify-center rounded-lg text-sm transition"
            :class="
              cell.hasSession
                ? selectedDayLocal === cell.dateKey
                  ? 'bg-emerald-600 font-semibold text-white shadow-md shadow-emerald-900/30'
                  : 'bg-emerald-500/25 font-medium text-emerald-100 ring-1 ring-emerald-500/40 hover:bg-emerald-500/40'
                : 'text-slate-500'
            "
            @click="selectDay(cell)"
          >
            {{ cell.day }}
            <span
              v-if="cell.hasSession"
              class="absolute bottom-1 h-1.5 w-1.5 rounded-full"
              :class="selectedDayLocal === cell.dateKey ? 'bg-white' : 'bg-emerald-400'"
            />
          </button>
        </template>
      </div>
    </div>

    <div
      class="tc-session-panel flex min-h-[14rem] flex-col rounded-xl border border-slate-800 bg-slate-950/50 p-4 lg:min-h-[18rem]"
    >
      <template v-if="selectedDayLocal && daySessions.length">
        <div class="border-b border-slate-800/80 pb-3">
          <p class="text-xs font-medium uppercase tracking-wide text-emerald-400/90">
            {{ formatCalendarFr(selectedDayLocal, 'medium') }}
          </p>
          <p class="mt-0.5 capitalize text-sm text-slate-400">{{ selectedDayWeekday }}</p>
          <p v-if="daySessions.length > 1" class="mt-1 text-xs text-slate-500">
            {{ daySessions.length }} séances ce jour
          </p>
        </div>

        <div class="mt-3 flex-1 space-y-4 overflow-y-auto pr-0.5">
          <article
            v-for="(session, sessionIndex) in daySessions"
            :key="session.id"
            class="tc-session-card rounded-xl border border-slate-800 bg-slate-900/60 p-3"
          >
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <p
                  v-if="daySessions.length > 1"
                  class="text-xs font-medium text-slate-500"
                >
                  Séance {{ sessionIndex + 1 }}
                </p>
                <p
                  v-if="session.session_label?.trim()"
                  class="text-sm font-semibold text-white"
                  :class="daySessions.length > 1 ? 'mt-0.5' : ''"
                >
                  {{ session.session_label }}
                </p>
              </div>
              <button
                v-if="canEdit"
                type="button"
                class="shrink-0 rounded-lg border border-blue-500/40 px-2.5 py-1 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
                @click="emit('edit-session', session)"
              >
                Modifier
              </button>
            </div>

            <ul
              v-if="sessionHasItemDetails(session)"
              class="mt-3 space-y-2"
            >
              <li
                v-for="line in sessionExerciseRecaps(session)"
                :key="line.key"
                class="rounded-lg border border-slate-800/80 bg-slate-950/40 px-3 py-2"
              >
                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">
                  {{ line.section }}
                </p>
                <p class="mt-0.5 text-sm font-semibold text-white">
                  {{ line.recap }}
                </p>
                <p
                  v-if="line.athleteNote"
                  class="mt-1.5 whitespace-pre-wrap text-xs leading-relaxed text-slate-400"
                >
                  Note : {{ line.athleteNote }}
                </p>
              </li>
            </ul>

            <div
              v-if="sessionHasItemDetails(session) && sessionTotal(session) > 0"
              class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-800/80 pt-3"
            >
              <p class="text-xs text-slate-500">Synthèse SBD</p>
              <p class="font-mono text-sm text-slate-300">
                S {{ session.squat }} · B {{ session.bench }} · T {{ session.deadlift }} kg
              </p>
              <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-300">
                {{ sessionTotal(session) }} kg
              </span>
            </div>

            <template v-else>
            <div class="mt-3 flex flex-wrap items-start justify-between gap-3">
              <div>
                <p class="text-xs text-slate-500">Total séance</p>
                <p class="text-2xl font-bold tabular-nums text-white">
                  {{ sessionInsights(session).total }}
                  <span class="text-base font-semibold text-slate-400">kg</span>
                </p>
              </div>
              <div class="flex flex-wrap items-center justify-end gap-1.5">
                <span
                  v-if="sessionInsights(session).totalDelta"
                  class="rounded-full px-2.5 py-1 text-xs font-semibold"
                  :class="
                    sessionInsights(session).totalDelta.tone === 'up'
                      ? 'bg-emerald-500/15 text-emerald-300'
                      : sessionInsights(session).totalDelta.tone === 'down'
                        ? 'bg-red-500/15 text-red-300'
                        : 'bg-slate-700/50 text-slate-400'
                  "
                >
                  {{ sessionInsights(session).totalDelta.text }}
                  <span class="font-normal text-slate-500"> vs préc.</span>
                </span>
                <span
                  v-if="sessionInsights(session).isBestTotal"
                  class="rounded-full bg-emerald-500/20 px-2.5 py-1 text-xs font-semibold text-emerald-300"
                >
                  Meilleur total
                </span>
              </div>
            </div>

            <ul class="mt-3 space-y-2">
              <li
                v-for="row in sessionLiftRows(session)"
                :key="row.key"
                class="rounded-lg border border-slate-800/80 bg-slate-950/40 px-3 py-2.5"
              >
                <div class="flex items-center justify-between gap-2">
                  <div class="min-w-0">
                    <p class="text-xs font-medium text-slate-500">{{ row.label }}</p>
                    <p class="mt-0.5 text-lg font-bold tabular-nums text-white">
                      {{ row.value }}
                      <span class="text-sm font-semibold text-slate-400">kg</span>
                    </p>
                  </div>
                  <div class="flex shrink-0 flex-col items-end gap-1">
                    <span
                      v-if="row.delta"
                      class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                      :class="
                        row.delta.tone === 'up'
                          ? 'bg-emerald-500/15 text-emerald-300'
                          : row.delta.tone === 'down'
                            ? 'bg-red-500/15 text-red-300'
                            : 'bg-slate-700/50 text-slate-400'
                      "
                    >
                      {{ row.delta.text }}
                    </span>
                    <span
                      v-if="row.isRecord && row.value > 0"
                      class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-semibold text-amber-300"
                    >
                      Record
                    </span>
                  </div>
                </div>
                <div
                  v-if="row.percent !== null && row.ref > 0"
                  class="mt-2"
                >
                  <div class="flex items-center justify-between text-[11px] text-slate-500">
                    <span>{{ row.percent }}% du meilleur</span>
                    <span class="tabular-nums">{{ row.ref }} kg</span>
                  </div>
                  <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-800">
                    <div
                      class="h-full rounded-full transition-all"
                      :class="row.bar"
                      :style="{ width: `${row.percent}%` }"
                    />
                  </div>
                </div>
              </li>
            </ul>
            </template>

            <div
              v-if="session.notes?.trim()"
              class="mt-3 rounded-lg border border-slate-700/60 bg-slate-950/50 px-3 py-2.5"
            >
              <p class="text-[11px] font-medium uppercase tracking-wide text-slate-500">Notes</p>
              <p class="mt-1 text-sm leading-relaxed text-slate-300 whitespace-pre-wrap">
                {{ session.notes }}
              </p>
            </div>
          </article>
        </div>
      </template>

      <div
        v-else
        class="flex flex-1 flex-col items-center justify-center text-center text-sm text-slate-500"
      >
        <p>Sélectionne un jour de séance</p>
        <p class="mt-1 text-xs text-slate-600">Les jours en vert ont une séance enregistrée.</p>
      </div>
    </div>
  </div>
</template>
