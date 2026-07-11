<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  BLOCK_TYPES,
  createSessionItem,
  dayToSessionPayload,
  hydrateExerciseLine,
  itemSectionTitle,
  sessionToDay,
} from '../utils/programBuilder';
import TodaySessionSetBlock from './TodaySessionSetBlock.vue';

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
  adherenceSharePayload: {
    type: Object,
    default: null,
  },
});
const emit = defineEmits(['share-adherence']);

const workItems = ref([]);
const expandedItemKey = ref(null);
const validatedItemKeys = ref(new Set());
const validatedSetCounts = ref({});

function itemKey(item) {
  return `${item.section}-${item.line?.exercise_name ?? ''}`;
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

const blockTypeLabel = computed(() => {
  const value = props.todaySession?.block_type;
  return BLOCK_TYPES.find((item) => item.value === value)?.label ?? value ?? '';
});

const sessionTitle = computed(() => {
  const label = session.value?.session_label?.trim();
  if (label) {
    return label;
  }
  return 'Séance du jour';
});

const sortedWorkItems = computed(() => {
  const order = { topset: 0, backoff: 1, accessory: 2 };
  return [...workItems.value].sort((a, b) => (order[a.section] ?? 9) - (order[b.section] ?? 9));
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
  return Math.max(1, Number(item?.line?.sets ?? 1));
}

function getValidatedSetCount(key) {
  return validatedSetCounts.value[key] ?? 0;
}

function isBlockFullyValidated(item) {
  const key = itemKey(item);
  return getValidatedSetCount(key) >= totalSetsFor(item);
}

const allSeriesValidated = computed(() => {
  if (!sortedWorkItems.value.length) {
    return false;
  }
  return sortedWorkItems.value.every((item) => isBlockFullyValidated(item));
});

function initializeWorkItems() {
  const plannedItems = session.value?.items ?? [];

  if (hasLoggedToday.value) {
    const day = sessionToDay(props.todayLoggedSession);
    workItems.value = day.items;
    if (Object.keys(validatedSetCounts.value).length === 0) {
      const counts = {};
      const validated = new Set();
      for (const item of day.items) {
        const key = itemKey(item);
        if (item.line?.rpe != null && item.line.rpe !== '') {
          counts[key] = totalSetsFor(item);
          validated.add(key);
        }
      }
      validatedSetCounts.value = counts;
      validatedItemKeys.value = validated;
    }
    return;
  }

  workItems.value = plannedItems.map((row) =>
    createSessionItem(row.section, hydrateExerciseLine({ ...row, lift: row.lift ?? mainLift.value })),
  );
  validatedItemKeys.value = new Set();
  validatedSetCounts.value = {};
  expandedItemKey.value = workItems.value[0] ? itemKey(workItems.value[0]) : null;
}

watch(
  () => [props.todaySession?.date, props.todaySession?.session, props.todayLoggedSession?.id],
  () => {
    if (status.value === 'session') {
      initializeWorkItems();
    }
  },
  { immediate: true, deep: true },
);

watch(
  () => props.todayLoggedSession,
  (logged) => {
    if (!logged?.id || status.value !== 'session') {
      return;
    }

    const preservedValidated = new Set(validatedItemKeys.value);
    const preservedCounts = { ...validatedSetCounts.value };
    const day = sessionToDay(logged);
    workItems.value = day.items;
    validatedItemKeys.value = preservedValidated;
    validatedSetCounts.value = preservedCounts;
  },
  { deep: true },
);

function toggleItem(key) {
  expandedItemKey.value = expandedItemKey.value === key ? null : key;
}

function buildPayload() {
  const day = {
    items: workItems.value,
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

  const total = totalSetsFor(item);
  const nextCount = Math.min(getValidatedSetCount(key) + 1, total);
  validatedSetCounts.value = { ...validatedSetCounts.value, [key]: nextCount };

  if (nextCount >= total) {
    validatedItemKeys.value = new Set([...validatedItemKeys.value, key]);
    expandedItemKey.value = null;

    const currentIndex = sortedWorkItems.value.findIndex((row) => itemKey(row) === key);
    const nextItem = sortedWorkItems.value[currentIndex + 1];
    if (nextItem && !isBlockFullyValidated(nextItem)) {
      expandedItemKey.value = itemKey(nextItem);
    }
  } else {
    item.line.rpe = null;
  }

  saveSession();
}

function shareAdherenceCard() {
  if (!props.adherenceSharePayload) {
    return;
  }

  emit('share-adherence', props.adherenceSharePayload);
}

</script>

<template>
  <section class="flex h-full flex-col rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex flex-wrap items-start justify-between gap-2">
      <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-400/80">
          Séance du jour
        </p>
        <p v-if="todaySession.program_name" class="mt-1 text-xs text-slate-400">
          <template v-if="status === 'rest'">Jour de repos</template>
          <template v-else-if="status === 'no_program'">Aucun programme actif</template>
          <template v-else>
            {{ todaySession.program_name }}
            <template v-if="todaySession.week_number">
              · S{{ todaySession.week_number }}
              <span v-if="blockTypeLabel">— {{ blockTypeLabel }}</span>
            </template>
          </template>
        </p>
      </div>
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
      <div v-if="sortedWorkItems.length" class="mt-3 space-y-2 border-t border-slate-800 pt-3">
        <TodaySessionSetBlock
          v-for="item in sortedWorkItems"
          :key="itemKey(item)"
          :item="item"
          :title="itemSectionTitle(item, workItems)"
          :one-rm="oneRm"
          :main-lift="mainLift"
          :expanded="expandedItemKey === itemKey(item)"
          :validated-sets-count="getValidatedSetCount(itemKey(item))"
          :saving="form.processing"
          @toggle="toggleItem(itemKey(item))"
          @validate="validateItem(itemKey(item))"
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
        <button
          v-if="allSeriesValidated && adherenceSharePayload"
          type="button"
          class="mt-2 flex w-full items-center justify-center rounded-lg border border-blue-500/40 bg-blue-600/20 px-3 py-2.5 text-sm font-semibold text-blue-100 hover:bg-blue-600/30"
          @click="shareAdherenceCard"
        >
          Partager ma séance
        </button>
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
</template>
