<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  createSessionItem,
  dayToSessionPayload,
  expandValidatedSetsToItems,
  hydrateExerciseLine,
  itemSectionTitle,
  sessionToDay,
  snapshotValidatedSet,
  validatedSetsFromLoggedItems,
} from '../utils/programBuilder';
import TodaySessionSetBlock from './TodaySessionSetBlock.vue';
import SessionCelebrationModal from './SessionCelebrationModal.vue';
import { buildSessionCelebrationPayload } from '../utils/sessionCelebration';
import { track } from '../utils/analytics';

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
const expandedItemKey = ref(null);
const celebrationOpen = ref(false);
const celebrationData = ref(null);

function itemKey(item) {
  return `${item.section}-${item.line?.exercise_name ?? ''}`;
}

function withSetTracking(item, { plannedSets = null, validatedSets = [] } = {}) {
  const planned = Math.max(1, Number(plannedSets ?? item?.line?.sets ?? 1));
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

const status = computed(() => props.todaySession?.status ?? 'no_program');
const session = computed(() => props.todaySession?.session ?? null);
const mainLift = computed(() => session.value?.main_lift ?? 'squat');

const sessionTitle = computed(() => {
  const label = session.value?.session_label?.trim();
  if (label) {
    return label;
  }
  return 'Séance du jour';
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
  return Math.max(1, Number(item?.plannedSets ?? item?.line?.sets ?? 1));
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
      plannedSets: item.line.sets,
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
      }
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

  workItems.value = plannedItems.map((row) =>
    withSetTracking(
      createSessionItem(row.section, hydrateExerciseLine({ ...row, lift: row.lift ?? mainLift.value })),
      { plannedSets: row.sets },
    ),
  );
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

function validateItem(key) {
  const item = workItems.value.find((row) => itemKey(row) === key);
  if (!item) {
    return;
  }

  const wasAllValidated = allSeriesValidated.value;
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
  }

  saveSession({
    onSuccess: () => {
      if (!wasAllValidated && allSeriesValidated.value) {
        celebrationData.value = buildSessionCelebrationPayload({
          sessionTitle: sessionTitle.value,
          workItems: expandValidatedSetsToItems(workItems.value),
          plannedItems: (session.value?.items ?? []).filter((row) => row.section !== 'warmup'),
          oneRm: props.oneRm,
          mainLift: mainLift.value,
        });
        celebrationOpen.value = true;
      }
    },
  });
}

function saveItemNote() {
  saveSession();
}

function closeCelebration() {
  celebrationOpen.value = false;
}
</script>

<template>
  <section class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-400/80">
        Séance du jour
      </p>
      <span
        v-if="allSeriesValidated"
        class="rounded-lg border border-emerald-500/40 bg-emerald-950/30 px-2 py-1 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
      >
        Séance validée
      </span>
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
          @save-note="saveItemNote"
        />
      </div>
      <p v-else class="mt-3 border-t border-slate-800 pt-3 text-xs text-slate-500">
        Séance prévue, exercices non renseignés.
      </p>

      <div class="mt-3 border-t border-slate-800 pt-3">
        <Link
          href="/feedbacks"
          class="flex w-full items-center justify-center rounded-lg border border-slate-700 px-3 py-2.5 text-sm font-semibold text-slate-200 hover:border-slate-500"
        >
          Envoyer un retour
        </Link>
      </div>
    </template>

    <template v-else-if="status === 'rest'">
      <p class="mt-3 text-xs text-slate-400">
        Pas de séance aujourd’hui.
        <template v-if="todaySession.next_session_date">
          Prochaine :
          <span class="font-medium text-slate-200">
            {{ formatCalendarFr(todaySession.next_session_date, 'medium') }}
          </span>
        </template>
        <template v-else>
          Aucune séance prévue sous 2 semaines.
        </template>
      </p>
      <div class="mt-3 flex flex-wrap gap-2 border-t border-slate-800 pt-3">
        <Link
          :href="programCalendarHref"
          class="rounded-lg border border-slate-700 px-3 py-2 text-xs font-semibold text-slate-300 hover:border-slate-500"
        >
          Voir dans le programme
        </Link>
      </div>
    </template>

    <template v-else>
      <p class="mt-3 text-xs text-slate-400">
        Aucun programme actif. Contacte ton coach.
      </p>
    </template>
  </section>

  <SessionCelebrationModal
    :open="celebrationOpen"
    :celebration="celebrationData"
    @close="closeCelebration"
  />
</template>
