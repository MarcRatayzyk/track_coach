<script setup>
import { useI18n } from 'vue-i18n';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  applySchemeStepToLine,
  createSessionItem,
  dayToSessionPayload,
  expandValidatedSetsToItems,
  hydrateExerciseLine,
  itemSectionTitle,
  plannedSetsForLine,
  sessionToDay,
  SET_OPTIONS,
  snapshotValidatedSet,
  validatedSetsFromLoggedItems,
} from '../utils/programBuilder';
import TodaySessionSetBlock from './TodaySessionSetBlock.vue';
import ExerciseVariantStrip from './ExerciseVariantStrip.vue';
import OptionButtonGroup from './OptionButtonGroup.vue';
import ScrollPicker from './ScrollPicker.vue';
import SessionCelebrationModal from './SessionCelebrationModal.vue';
import { localizedExerciseName } from '../utils/exerciseNames';
import { buildSessionCelebrationPayload } from '../utils/sessionCelebration';
import { track } from '../utils/analytics';

const { t } = useI18n();
const page = usePage();

const props = defineProps({
  todaySession: {
    type: Object,
    required: true,
  },
  athleteId: {
    type: Number,
    required: true,
  },
  oneRm: {
    type: Object,
    default: () => ({ squat: 0, bench: 0, deadlift: 0 }),
  },
  todayLoggedSession: {
    type: Object,
    default: null,
  },
});

const workItems = ref([]);
const prescriptionItems = ref([]);
const editingPrescription = ref(false);
const prescriptionSaving = ref(false);
const prescriptionError = ref('');
const expandedItemKey = ref(null);
const celebrationOpen = ref(false);
const celebrationData = ref(null);
const timerBusy = ref(false);
const finishing = ref(false);
const pendingStartAt = ref(null);
const clock = ref(Date.now());
const MAX_TIMER_SEGMENT_SECONDS = 8 * 60 * 60;

let clockTimer = null;

function itemKey(item) {
  return `${item.section}-${item.line?.exercise_name ?? ''}`;
}

function withSetTracking(item, { plannedSets = null, validatedSets = [] } = {}) {
  const planned = Math.max(1, Number(plannedSets ?? plannedSetsForLine(item?.line) ?? 1));
  return {
    ...item,
    plannedSets: planned,
    validatedSets: [...validatedSets],
  };
}

const form = useForm({
  session_date: '',
  main_lift: 'squat',
  session_label: null,
  items: [],
  blocks: [],
  notes: null,
});

const isSelfCoached = computed(() => Boolean(page.props.auth?.user?.self_coached));
const status = computed(() => props.todaySession?.status ?? 'no_program');
const canEditPrescription = computed(() => (
  isSelfCoached.value
  && status.value === 'session'
  && props.todaySession?.assignment_id
));
const session = computed(() => props.todaySession?.session ?? null);
const mainLift = computed(() => session.value?.main_lift ?? 'squat');

const sessionTitle = computed(() => {
  const label = session.value?.session_label?.trim();
  if (label) {
    return label;
  }
  return t('app.todaySession.title');
});

const sortedWorkItems = computed(() => {
  const order = { topset: 0, backoff: 1, accessory: 2 };
  return [...workItems.value]
    .filter((item) => item.section !== 'warmup')
    .sort((a, b) => (order[a.section] ?? 9) - (order[b.section] ?? 9));
});

const resolvedWarmup = computed(() => {
  const sessionWarmup = props.todaySession?.session?.warmup;
  if (sessionWarmup && (sessionWarmup.notes || (sessionWarmup.items?.length ?? 0) > 0)) {
    return sessionWarmup;
  }
  return null;
});

const hasWarmup = computed(() => {
  const warmup = resolvedWarmup.value;
  if (!warmup) {
    return false;
  }
  return Boolean(String(warmup.notes ?? '').trim()) || (warmup.items?.length ?? 0) > 0;
});

const programCalendarHref = computed(() => {
  const date = props.todaySession?.date;
  if (!date) {
    return '/athlete/program?tab=calendar';
  }
  return `/athlete/program?tab=calendar&date=${date}`;
});

const hasLoggedToday = computed(() => Boolean(props.todayLoggedSession?.id));

function totalSetsFor(item) {
  return Math.max(1, Number(item?.plannedSets ?? plannedSetsForLine(item?.line) ?? 1));
}

function getValidatedSetCount(item) {
  return item?.validatedSets?.length ?? 0;
}

function isBlockFullyValidated(item) {
  return getValidatedSetCount(item) >= totalSetsFor(item);
}

const allSeriesValidated = computed(() => {
  if (!sortedWorkItems.value.length) {
    return false;
  }
  return sortedWorkItems.value.every((item) => isBlockFullyValidated(item));
});

const sessionFinished = computed(() => Boolean(props.todayLoggedSession?.finished_at));

const timerStartedAt = computed(() => {
  if (sessionFinished.value) {
    return null;
  }
  return props.todayLoggedSession?.timer_started_at || pendingStartAt.value;
});

const timerRunning = computed(() => Boolean(timerStartedAt.value));

const elapsedSeconds = computed(() => {
  const stored = Math.max(0, Number(props.todayLoggedSession?.timer_elapsed_seconds ?? 0));
  const startedAt = timerStartedAt.value;
  if (!startedAt) {
    return stored;
  }
  const startMs = Date.parse(startedAt);
  if (!Number.isFinite(startMs)) {
    return stored;
  }
  const open = Math.max(0, Math.floor((clock.value - startMs) / 1000));
  return stored + Math.min(open, MAX_TIMER_SEGMENT_SECONDS);
});

const formattedElapsed = computed(() => {
  const total = Math.max(0, elapsedSeconds.value);
  const hours = Math.floor(total / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const seconds = total % 60;
  const pad = (value) => String(value).padStart(2, '0');
  if (hours > 0) {
    return `${hours}:${pad(minutes)}:${pad(seconds)}`;
  }
  return `${minutes}:${pad(seconds)}`;
});

const timerButtonLabel = computed(() => {
  if (timerRunning.value) {
    return formattedElapsed.value;
  }
  if (elapsedSeconds.value > 0) {
    return `${formattedElapsed.value} · ${t('app.todaySession.startTimer')}`;
  }
  return t('app.todaySession.startTimer');
});

function syncClock() {
  clock.value = Date.now();
}

onMounted(() => {
  clockTimer = window.setInterval(syncClock, 1000);
  document.addEventListener('visibilitychange', syncClock);

  const local = readLocalTimer();
  if (
    status.value === 'session'
    && !sessionFinished.value
    && !props.todayLoggedSession?.timer_started_at
    && local?.startedAt
  ) {
    pendingStartAt.value = local.startedAt;
    syncClock();
    timerBusy.value = true;
    router.post(
      `/athletes/${props.athleteId}/training-sessions/timer/start`,
      timerPayload(local.startedAt),
      {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
          timerBusy.value = false;
          syncClock();
        },
      },
    );
  }
});

onUnmounted(() => {
  if (clockTimer !== null) {
    window.clearInterval(clockTimer);
  }
  document.removeEventListener('visibilitychange', syncClock);
});

watch(
  () => props.todayLoggedSession?.timer_started_at,
  (startedAt) => {
    if (startedAt) {
      pendingStartAt.value = null;
      clearLocalTimer();
    }
  },
  { immediate: true },
);

function applySnapshotToLine(line, snapshot) {
  if (!line || !snapshot) {
    return;
  }
  line.reps = snapshot.reps;
  line.load = snapshot.load;
  line.load_percent = snapshot.load_percent;
  line.rpe = snapshot.rpe;
  line.load_mode = snapshot.load_mode;
}

function mergeLoggedOntoPlanned(plannedRows, loggedItems) {
  const loggedByKey = new Map();
  for (const item of loggedItems) {
    const key = itemKey(item);
    if (!loggedByKey.has(key)) {
      loggedByKey.set(key, []);
    }
    loggedByKey.get(key).push(item);
  }

  const usedKeys = new Set();
  const merged = plannedRows.map((row) => {
    const item = createSessionItem(
      row.section,
      hydrateExerciseLine({ ...row, lift: row.lift ?? mainLift.value }),
    );
    const key = itemKey(item);
    const loggedGroup = loggedByKey.get(key) ?? [];
    usedKeys.add(key);
    const validatedSets = validatedSetsFromLoggedItems(loggedGroup);
    const tracked = withSetTracking(item, {
      plannedSets: plannedSetsForLine(item.line),
      validatedSets,
    });
    const noteFromLogged = [...loggedGroup]
      .reverse()
      .map((row) => row.line?.athlete_note)
      .find((note) => String(note ?? '').trim());
    if (noteFromLogged) {
      tracked.line.athlete_note = noteFromLogged;
    }
    if (validatedSets.length) {
      applySnapshotToLine(tracked.line, validatedSets[validatedSets.length - 1]);
      tracked.line.sets = tracked.plannedSets;
      if (validatedSets.length < tracked.plannedSets) {
        tracked.line.rpe = null;
        applySchemeStepToLine(tracked.line, validatedSets.length);
      }
    } else {
      applySchemeStepToLine(tracked.line, 0);
    }
    return tracked;
  });

  for (const [key, loggedGroup] of loggedByKey.entries()) {
    if (usedKeys.has(key) || !loggedGroup.length) {
      continue;
    }
    const first = loggedGroup[0];
    const validatedSets = validatedSetsFromLoggedItems(loggedGroup);
    const tracked = withSetTracking(
      createSessionItem(first.section, hydrateExerciseLine(first.line)),
      {
        plannedSets: validatedSets.length || first.line?.sets,
        validatedSets,
      },
    );
    const noteFromLogged = [...loggedGroup]
      .reverse()
      .map((row) => row.line?.athlete_note)
      .find((note) => String(note ?? '').trim());
    if (noteFromLogged) {
      tracked.line.athlete_note = noteFromLogged;
    }
    if (validatedSets.length) {
      applySnapshotToLine(tracked.line, validatedSets[validatedSets.length - 1]);
      tracked.line.sets = tracked.plannedSets;
      if (validatedSets.length < tracked.plannedSets) {
        tracked.line.rpe = null;
      }
    }
    merged.push(tracked);
  }

  return merged;
}

function initializeWorkItems() {
  const plannedItems = (session.value?.items ?? []).filter((row) => row.section !== 'warmup');

  if (hasLoggedToday.value) {
    const day = sessionToDay(props.todayLoggedSession);
    const loggedItems = day.items.filter((item) => item.section !== 'warmup');
    workItems.value = mergeLoggedOntoPlanned(plannedItems, loggedItems);
    return;
  }

  workItems.value = plannedItems.map((row) => {
    const line = hydrateExerciseLine({ ...row, lift: row.lift ?? mainLift.value });
    applySchemeStepToLine(line, 0);
    return withSetTracking(createSessionItem(row.section, line), {
      plannedSets: plannedSetsForLine(line),
    });
  });
  expandedItemKey.value = workItems.value[0] ? itemKey(workItems.value[0]) : null;
}

watch(
  () => [props.todaySession?.date, status.value],
  () => {
    if (status.value === 'session') {
      initializeWorkItems();
    }
  },
  { immediate: true },
);

function toggleItem(key) {
  expandedItemKey.value = expandedItemKey.value === key ? null : key;
}

function buildPayload() {
  const day = {
    items: expandValidatedSetsToItems(workItems.value),
    lift: mainLift.value,
    session_label: sessionTitle.value,
  };

  return {
    session_date: props.todaySession.date,
    notes: props.todayLoggedSession?.notes ?? null,
    ...dayToSessionPayload(day),
  };
}

function saveSession({ onSuccess } = {}) {
  const payload = buildPayload();

  form.session_date = payload.session_date;
  form.main_lift = payload.main_lift;
  form.session_label = payload.session_label;
  form.items = payload.items;
  form.blocks = payload.blocks;
  form.notes = payload.notes;

  const options = {
    preserveScroll: true,
    onSuccess: () => {
      track('session_logged', {
        source: 'today_session',
        is_update: hasLoggedToday.value,
      });
      onSuccess?.();
    },
  };

  if (hasLoggedToday.value) {
    form.put(`/athletes/${props.athleteId}/training-sessions/${props.todayLoggedSession.id}`, options);
  } else {
    form.post(`/athletes/${props.athleteId}/training-sessions`, options);
  }
}

function undoLastSet(key) {
  const item = workItems.value.find((row) => itemKey(row) === key);
  if (!item?.validatedSets?.length) {
    return;
  }

  item.validatedSets = item.validatedSets.slice(0, -1);
  const nextCount = item.validatedSets.length;

  if (nextCount === 0) {
    const planned = (session.value?.items ?? []).find(
      (row) => `${row.section}-${row.exercise_name ?? ''}` === key,
    );
    if (planned) {
      const line = hydrateExerciseLine({ ...planned, lift: planned.lift ?? mainLift.value });
      applySchemeStepToLine(line, 0);
      item.line = {
        ...line,
        athlete_note: item.line?.athlete_note ?? null,
      };
      item.plannedSets = plannedSetsForLine(line);
    } else {
      item.line.rpe = null;
    }
  } else {
    applySnapshotToLine(item.line, item.validatedSets[nextCount - 1]);
    item.line.sets = item.plannedSets;
    if (nextCount < totalSetsFor(item)) {
      item.line.rpe = null;
      applySchemeStepToLine(item.line, nextCount);
    }
  }

  expandedItemKey.value = key;
  saveSession();
}

function validateItem(key) {
  const item = workItems.value.find((row) => itemKey(row) === key);
  if (!item) {
    return;
  }

  const total = totalSetsFor(item);
  const snapshot = snapshotValidatedSet(item.line);
  if (!snapshot) {
    return;
  }

  if (!Array.isArray(item.validatedSets)) {
    item.validatedSets = [];
  }
  if (item.validatedSets.length >= total) {
    return;
  }

  item.validatedSets = [...item.validatedSets, snapshot];
  const nextCount = item.validatedSets.length;

  if (nextCount >= total) {
    // Keep expanded so the athlete can leave an exercise note.
    expandedItemKey.value = key;
  } else {
    item.line.rpe = null;
    applySchemeStepToLine(item.line, nextCount);
  }

  saveSession();
}

function saveItemNote() {
  saveSession();
}

function closeCelebration() {
  celebrationOpen.value = false;
}

function timerStorageKey() {
  return `tc-session-timer-${props.athleteId}-${props.todaySession?.date ?? ''}`;
}

function readLocalTimer() {
  if (typeof window === 'undefined') {
    return null;
  }
  try {
    const raw = window.localStorage.getItem(timerStorageKey());
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function writeLocalTimer(startedAt) {
  if (typeof window === 'undefined') {
    return;
  }
  window.localStorage.setItem(timerStorageKey(), JSON.stringify({ startedAt }));
}

function clearLocalTimer() {
  if (typeof window === 'undefined') {
    return;
  }
  window.localStorage.removeItem(timerStorageKey());
}

function timerPayload(startedAt = null) {
  return {
    session_date: props.todaySession.date,
    main_lift: mainLift.value,
    session_label: session.value?.session_label ?? null,
    ...(startedAt ? { started_at: startedAt } : {}),
  };
}

function openCelebration() {
  celebrationData.value = buildSessionCelebrationPayload({
    sessionTitle: sessionTitle.value,
    workItems: expandValidatedSetsToItems(workItems.value),
    plannedItems: (session.value?.items ?? []).filter((row) => row.section !== 'warmup'),
    oneRm: props.oneRm,
    mainLift: mainLift.value,
  });
  celebrationOpen.value = true;
}

function toggleTimer() {
  if (sessionFinished.value || timerBusy.value || status.value !== 'session') {
    return;
  }

  const running = timerRunning.value;
  const startedAt = running ? null : (pendingStartAt.value || new Date().toISOString());
  if (running) {
    pendingStartAt.value = null;
    clearLocalTimer();
  } else {
    pendingStartAt.value = startedAt;
    writeLocalTimer(startedAt);
    syncClock();
  }

  timerBusy.value = true;
  router.post(
    `/athletes/${props.athleteId}/training-sessions/timer/${running ? 'stop' : 'start'}`,
    timerPayload(startedAt),
    {
      preserveScroll: true,
      preserveState: true,
      onError: () => {
        if (!running) {
          return;
        }
        pendingStartAt.value = null;
      },
      onFinish: () => {
        timerBusy.value = false;
        syncClock();
      },
    },
  );
}

function finishSession() {
  if (sessionFinished.value || finishing.value || status.value !== 'session') {
    return;
  }

  const startedAt = timerStartedAt.value;
  pendingStartAt.value = null;
  clearLocalTimer();
  finishing.value = true;
  router.post(
    `/athletes/${props.athleteId}/training-sessions/finish`,
    timerPayload(startedAt),
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        openCelebration();
      },
      onFinish: () => {
        finishing.value = false;
        syncClock();
      },
    },
  );
}

const prescriptionRepOptions = Array.from({ length: 20 }, (_, index) => index + 1);
const prescriptionKgOptions = (() => {
  const values = [];
  for (let kg = 0; kg <= 300; kg += 2.5) {
    values.push(Number(kg.toFixed(1)));
  }
  return values;
})();
const prescriptionPercentOptions = (() => {
  const values = [];
  for (let percent = 20; percent <= 110; percent += 2.5) {
    values.push(Number(percent.toFixed(1)));
  }
  return values;
})();

function prescriptionChargeMode(row) {
  if (row?.load_mode === 'percent' || (row?.load_percent != null && row.load_percent !== '')) {
    return 'percent';
  }
  return 'kg';
}

function clonePrescriptionItems() {
  return (session.value?.items ?? [])
    .filter((row) => row.section !== 'warmup')
    .map((row) => ({
      ...row,
      exercise_variant_id: row.exercise_variant_id ?? null,
      exercise_name: row.exercise_name ?? '',
      lift: row.lift ?? mainLift.value,
      set_scheme: row.set_scheme === 'straight' ? 'standard' : (row.set_scheme ?? 'standard'),
      sets: Number(row.sets) || 1,
      reps: Number(row.reps) || 1,
      load: row.load ?? null,
      load_percent: row.load_percent ?? null,
      load_mode: prescriptionChargeMode(row),
    }));
}

function startPrescriptionEdit() {
  prescriptionError.value = '';
  prescriptionItems.value = clonePrescriptionItems();
  editingPrescription.value = true;
}

function addPrescriptionItem() {
  prescriptionItems.value.push({
    section: 'accessory',
    exercise_variant_id: null,
    exercise_name: '',
    lift: mainLift.value,
    set_scheme: 'standard',
    sets: 3,
    reps: 8,
    load: null,
    load_percent: null,
    load_mode: 'kg',
  });
}

function selectPrescriptionExercise(index, selected) {
  const row = prescriptionItems.value[index];
  if (!row || !selected) {
    return;
  }
  row.exercise_variant_id = selected.exercise_variant_id ?? null;
  row.exercise_name = selected.exercise_name ?? '';
  if (selected.lift) {
    row.lift = selected.lift;
  }
}

function setPrescriptionChargeMode(row, mode) {
  row.load_mode = mode;
  if (mode === 'kg') {
    row.load_percent = null;
    return;
  }
  row.load = null;
}

function removePrescriptionItem(index) {
  prescriptionItems.value.splice(index, 1);
}

function savePrescription() {
  if (!canEditPrescription.value || !session.value) {
    return;
  }

  const named = prescriptionItems.value.every((row) => String(row.exercise_name ?? '').trim() !== '');
  if (!named) {
    prescriptionError.value = t('app.todaySession.setNameRequired');
    return;
  }

  const warmupItems = (session.value.items ?? []).filter((row) => row.section === 'warmup');
  const items = [
    ...warmupItems,
    ...prescriptionItems.value.map((row) => {
      const mode = prescriptionChargeMode(row);
      return {
        ...row,
        exercise_name: String(row.exercise_name).trim(),
        exercise_variant_id: row.exercise_variant_id ?? null,
        set_scheme: row.set_scheme === 'straight' ? 'standard' : (row.set_scheme ?? 'standard'),
        sets: Number(row.sets),
        reps: Number(row.reps),
        load_mode: mode,
        load: mode === 'kg' && row.load != null && row.load !== '' ? Number(row.load) : null,
        load_percent: mode === 'percent' && row.load_percent != null && row.load_percent !== ''
          ? Number(row.load_percent)
          : null,
      };
    }),
  ];

  prescriptionSaving.value = true;
  prescriptionError.value = '';
  router.put(`/coach/program-blocks/${props.todaySession.assignment_id}/sessions`, {
    week_number: session.value.week_number,
    weekday: session.value.weekday,
    main_lift: session.value.main_lift,
    session_label: session.value.session_label,
    notes: session.value.notes,
    warmup_override: Boolean(session.value.warmup_override),
    warmup_notes: session.value.warmup_notes,
    items,
    blocks: [],
    return_to: 'today',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      editingPrescription.value = false;
    },
    onError: (errors) => {
      prescriptionError.value = Object.values(errors).flat().filter(Boolean).join(' ');
    },
    onFinish: () => {
      prescriptionSaving.value = false;
    },
  });
}
</script>

<template>
  <section class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <div class="flex min-w-0 items-center gap-2">
        <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-400/80">{{ t('app.todaySession.title') }}</p>
        <button
          v-if="status === 'session' && !sessionFinished"
          type="button"
          class="inline-flex items-center gap-1 rounded-md px-1 py-0.5 text-[11px] font-medium text-slate-500 transition hover:text-slate-300 disabled:opacity-60"
          :disabled="timerBusy"
          :aria-pressed="timerRunning"
          :aria-label="timerRunning ? t('app.todaySession.stopTimer') : t('app.todaySession.startTimer')"
          @click="toggleTimer"
        >
          <span
            class="h-1.5 w-1.5 rounded-full"
            :class="timerRunning ? 'bg-emerald-400/80' : 'bg-slate-600'"
          />
          <span class="tabular-nums">{{ timerButtonLabel }}</span>
        </button>
        <span
          v-else-if="status === 'session' && elapsedSeconds > 0"
          class="tabular-nums text-[11px] font-medium text-slate-500"
        >
          {{ formattedElapsed }}
        </span>
      </div>
      <span
        v-if="sessionFinished"
        class="rounded-lg border border-emerald-500/40 bg-emerald-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
      >{{ t('app.todaySession.finished') }}</span>
      <span
        v-else-if="allSeriesValidated"
        class="rounded-lg border border-emerald-500/40 bg-emerald-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
      >{{ t('app.todaySession.validated') }}</span>
      <span
        v-else-if="hasLoggedToday"
        class="rounded-lg border border-amber-500/40 bg-amber-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-amber-300"
      >
        En cours
      </span>
    </div>

    <template v-if="status === 'session'">
      <div
        v-if="hasWarmup"
        class="mt-3 space-y-2 rounded-xl border border-sky-500/25 bg-sky-950/20 px-3 py-3"
      >
        <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
          Échauffement
        </p>
        <p
          v-if="resolvedWarmup.notes?.trim()"
          class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
        >
          {{ resolvedWarmup.notes }}
        </p>
        <ul v-if="resolvedWarmup.items?.length" class="space-y-1.5">
          <li
            v-for="(row, index) in resolvedWarmup.items"
            :key="`${row.exercise_name}-${index}`"
            class="text-sm text-slate-300"
          >
            <span class="font-medium text-slate-100">{{ row.exercise_name }}</span>
            <span v-if="row.sets || row.reps" class="text-slate-400">
              — {{ row.sets ?? '?' }}×{{ row.reps ?? '?' }}
              <template v-if="row.load"> @ {{ row.load }} kg</template>
              <template v-else-if="row.load_percent"> @ {{ row.load_percent }}%</template>
              <template v-else-if="row.rpe"> @ RPE {{ row.rpe }}</template>
            </span>
          </li>
        </ul>
      </div>

      <div v-if="canEditPrescription" class="mt-3 space-y-2 border-t border-slate-800 pt-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-slate-500"
            @click="editingPrescription ? (editingPrescription = false) : startPrescriptionEdit()"
          >
            {{ editingPrescription ? t('app.todaySession.doneEditing') : t('app.todaySession.editSets') }}
          </button>
        </div>

        <div v-if="editingPrescription" class="space-y-3">
          <article
            v-for="(row, index) in prescriptionItems"
            :key="`prescription-${index}`"
            class="rounded-xl border border-slate-800 bg-slate-950/50 p-3"
          >
            <div class="flex items-start justify-between gap-2">
              <p class="min-w-0 text-sm font-semibold text-white">
                {{ row.exercise_name ? localizedExerciseName(row.exercise_name) : t('app.todaySession.chooseExercise') }}
              </p>
              <button
                type="button"
                class="shrink-0 rounded-md border border-red-500/40 px-2 py-1 text-xs font-semibold text-red-300 hover:bg-red-950/40"
                @click="removePrescriptionItem(index)"
              >
                {{ t('common.remove') }}
              </button>
            </div>

            <ExerciseVariantStrip
              class="mt-2"
              accessory-panel
              :default-lift="row.lift || mainLift"
              :exercise-variant-id="row.exercise_variant_id"
              :exercise-name="row.exercise_name"
              @select="selectPrescriptionExercise(index, $event)"
            />

            <OptionButtonGroup
              class="mt-3"
              :model-value="row.sets"
              :options="SET_OPTIONS"
              :columns="5"
              dense
              :label="t('app.todaySession.sets')"
              @update:model-value="row.sets = $event"
            />

            <div class="mt-3 grid grid-cols-2 gap-2">
              <div class="min-w-0">
                <p class="mb-1 text-center text-[10px] font-medium text-slate-400">{{ t('app.todaySession.reps') }}</p>
                <ScrollPicker
                  v-model="row.reps"
                  :options="prescriptionRepOptions"
                  :item-height="32"
                  :visible-count="3"
                  :aria-label="t('app.todaySession.reps')"
                />
              </div>
              <div class="min-w-0">
                <div class="mb-1 flex items-center justify-center gap-1">
                  <button
                    type="button"
                    class="rounded px-2 py-0.5 text-[10px] font-semibold"
                    :class="prescriptionChargeMode(row) === 'kg' ? 'bg-blue-600 text-white' : 'border border-slate-700 text-slate-400'"
                    @click="setPrescriptionChargeMode(row, 'kg')"
                  >
                    kg
                  </button>
                  <button
                    type="button"
                    class="rounded px-2 py-0.5 text-[10px] font-semibold"
                    :class="prescriptionChargeMode(row) === 'percent' ? 'bg-blue-600 text-white' : 'border border-slate-700 text-slate-400'"
                    @click="setPrescriptionChargeMode(row, 'percent')"
                  >
                    %
                  </button>
                </div>
                <ScrollPicker
                  v-if="prescriptionChargeMode(row) === 'kg'"
                  v-model="row.load"
                  allow-empty
                  :options="prescriptionKgOptions"
                  :item-height="32"
                  :visible-count="3"
                  :aria-label="t('app.todaySession.load')"
                />
                <ScrollPicker
                  v-else
                  v-model="row.load_percent"
                  allow-empty
                  :options="prescriptionPercentOptions"
                  :format="(value) => `${value}%`"
                  :item-height="32"
                  :visible-count="3"
                  :aria-label="t('app.todaySession.load')"
                />
              </div>
            </div>
          </article>

          <p v-if="prescriptionError" class="text-xs text-red-400">{{ prescriptionError }}</p>

          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-slate-500"
              @click="addPrescriptionItem"
            >
              {{ t('app.todaySession.addSet') }}
            </button>
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-60"
              :disabled="prescriptionSaving"
              @click="savePrescription"
            >
              {{ t('app.todaySession.saveSets') }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="sortedWorkItems.length" class="mt-3 space-y-2 border-t border-slate-800 pt-3">
        <TodaySessionSetBlock
          v-for="item in sortedWorkItems"
          :key="itemKey(item)"
          :item="item"
          :title="itemSectionTitle(item, workItems)"
          :one-rm="oneRm"
          :main-lift="mainLift"
          :expanded="expandedItemKey === itemKey(item)"
          :validated-sets-count="getValidatedSetCount(item)"
          :validated-sets="item.validatedSets ?? []"
          :planned-sets="totalSetsFor(item)"
          :saving="form.processing"
          @toggle="toggleItem(itemKey(item))"
          @validate="validateItem(itemKey(item))"
          @undo="undoLastSet(itemKey(item))"
          @save-note="saveItemNote"
        />
      </div>
      <p v-else class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-500">
        {{ t('app.todaySession.noExercises') }}
      </p>

      <div class="mt-3 space-y-2 border-t border-slate-800 pt-3">
        <button
          v-if="!sessionFinished"
          type="button"
          class="flex w-full items-center justify-center rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-semibold text-slate-200 hover:border-slate-500 disabled:opacity-60"
          :disabled="finishing || form.processing"
          @click="finishSession"
        >
          {{ t('app.todaySession.finish') }}
        </button>
        <Link
          href="/feedbacks"
          class="flex w-full items-center justify-center rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-semibold text-slate-200 hover:border-slate-500"
        >
          {{ t('app.feedbacks.send') }}
        </Link>
      </div>
    </template>

    <template v-else-if="status === 'rest'">
      <p class="mt-3 text-xs text-slate-400">
        {{ t('app.todaySession.restToday') }}
        <template v-if="todaySession.next_session_date">
          {{ t('app.todaySession.next') }}
          <span class="font-medium text-slate-200">
            {{ formatCalendarFr(todaySession.next_session_date, 'medium') }}
          </span>
        </template>
        <template v-else>
          {{ t('app.todaySession.noneUpcoming') }}
        </template>
      </p>
      <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-800 pt-3">
        <Link
          :href="programCalendarHref"
          class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 hover:border-slate-500"
        >
          {{ t('app.todaySession.viewInProgram') }}
        </Link>
      </div>
    </template>

    <template v-else>
      <p class="mt-3 text-xs text-slate-400">
        <template v-if="isSelfCoached">
            {{ t('app.todaySession.noProgramSelf') }}
          <Link href="/program-builder" class="font-semibold text-blue-300 hover:text-blue-200">
            {{ t('nav.build') }}
          </Link>
        </template>
        <template v-else>
          {{ t('app.todaySession.noProgramCoach') }}
        </template>
      </p>
    </template>
  </section>

  <SessionCelebrationModal
    :open="celebrationOpen"
    :celebration="celebrationData"
    @close="closeCelebration"
  />
</template>
