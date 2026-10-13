<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';
import { formatCalendarFr } from '../utils/formatDates';
import { localizedExerciseName } from '../utils/exerciseNames';
import {
  formatLineRecap,
  sessionDayOrdinalInWeek,
} from '../utils/programBuilder';
import { buildComparisonRows, editableExercises } from '../utils/sessionComparison';
import LoggedSessionSetEditor from './LoggedSessionSetEditor.vue';

const { t } = useI18n();

const props = defineProps({
  programBlock: {
    type: Object,
    required: true,
  },
  selectedCell: {
    type: Object,
    default: null,
  },
  session: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const headerTitle = computed(() => {
  const week = props.selectedCell.weekNumber;
  const dayNum = sessionDayOrdinalInWeek(
    props.programBlock.sessions,
    week,
    props.selectedCell.weekday,
  );
  const label = props.session?.session_label?.trim();

  if (label) {
    return t('athleteUi.programSession.weekDayLabelNamed', { week, day: dayNum, label });
  }

  return t('athleteUi.programSession.weekDayLabel', { week, day: dayNum });
});

const resolvedWarmup = computed(() => props.session?.warmup ?? null);

const hasWarmup = computed(() => {
  const warmup = resolvedWarmup.value;
  if (!warmup) {
    return false;
  }
  return Boolean(String(warmup.notes ?? '').trim()) || (warmup.items?.length ?? 0) > 0;
});

const editingLog = ref(false);
const savingLog = ref(false);
const logError = ref('');
const draftExercises = ref([]);

function localToday() {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  const day = String(now.getDate()).padStart(2, '0');
  return `${now.getFullYear()}-${month}-${day}`;
}

const selectedDate = computed(() => props.selectedCell?.date ?? null);

const canEditLog = computed(() => {
  const date = selectedDate.value;
  return Boolean(date && props.programBlock?.athlete_id && date <= localToday());
});

const plannedItems = computed(() => {
  const order = { topset: 0, backoff: 1, accessory: 2 };
  return [...(props.session?.items ?? [])]
    .filter((item) => (item.section ?? item.line?.section) !== 'warmup')
    .sort((a, b) => (order[a.section ?? a.line?.section] ?? 9) - (order[b.section ?? b.line?.section] ?? 9));
});

const loggedSession = computed(() => {
  const date = selectedDate.value;
  return (props.programBlock?.training_sessions ?? []).find(
    (entry) => String(entry.session_date ?? '').slice(0, 10) === date,
  ) ?? null;
});

const loggedItems = computed(() => loggedSession.value?.items ?? []);

const comparisonRows = computed(() => buildComparisonRows(
  plannedItems.value,
  loggedItems.value,
  localizedExerciseName,
));

watch(selectedDate, () => {
  editingLog.value = false;
  logError.value = '';
});

function startEdit() {
  const seeded = editableExercises(plannedItems.value, loggedItems.value);
  draftExercises.value = seeded.length
    ? seeded
    : [{
      section: 'accessory',
      exercise_variant_id: null,
      exercise_name: '',
      lift: 'squat',
      sets: [{ reps: 5, load: null, load_percent: null, load_mode: 'kg' }],
    }];
  logError.value = '';
  editingLog.value = true;
}

function sessionPayload(items) {
  const lift = items.find((item) => ['squat', 'bench', 'deadlift'].includes(item.lift))?.lift
    ?? loggedSession.value?.main_lift
    ?? props.session?.main_lift
    ?? 'squat';

  return {
    session_date: selectedDate.value,
    main_lift: lift,
    session_label: loggedSession.value?.session_label ?? props.session?.session_label ?? null,
    notes: loggedSession.value?.notes ?? null,
    items,
    blocks: [],
  };
}

function saveLog() {
  const named = draftExercises.value.every((exercise) => String(exercise.exercise_name ?? '').trim() !== '');
  if (!named) {
    logError.value = t('athleteUi.programSession.nameRequired');
    return;
  }
  const withSets = draftExercises.value.every((exercise) => (exercise.sets ?? []).length > 0);
  if (!withSets) {
    logError.value = t('athleteUi.programSession.setRequired');
    return;
  }

  const items = draftExercises.value.flatMap((exercise) => (exercise.sets ?? []).map((set) => {
    const mode = set.load_mode === 'percent' ? 'percent' : 'kg';
    return {
      section: exercise.section || 'accessory',
      exercise_variant_id: exercise.exercise_variant_id ?? null,
      exercise_name: String(exercise.exercise_name).trim(),
      lift: exercise.lift || 'squat',
      set_scheme: 'standard',
      sets: 1,
      reps: Number(set.reps),
      load: mode === 'kg' && set.load != null && set.load !== '' ? Number(set.load) : null,
      load_percent: mode === 'percent' && set.load_percent != null && set.load_percent !== ''
        ? Number(set.load_percent)
        : null,
    };
  }));

  const athleteId = props.programBlock.athlete_id;
  const existing = loggedSession.value;
  savingLog.value = true;
  logError.value = '';
  const visit = {
    preserveScroll: true,
    onSuccess: () => {
      editingLog.value = false;
    },
    onError: (errors) => {
      logError.value = Object.values(errors).flat().filter(Boolean).join(' ');
    },
    onFinish: () => {
      savingLog.value = false;
    },
  };

  if (existing?.id) {
    router.put(`/athletes/${athleteId}/training-sessions/${existing.id}`, sessionPayload(items), visit);
    return;
  }
  router.post(`/athletes/${athleteId}/training-sessions`, sessionPayload(items), visit);
}

function deleteLog() {
  const existing = loggedSession.value;
  if (!existing?.id) {
    editingLog.value = false;
    return;
  }
  savingLog.value = true;
  logError.value = '';
  router.delete(`/athletes/${props.programBlock.athlete_id}/training-sessions/${existing.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      editingLog.value = false;
    },
    onError: (errors) => {
      logError.value = Object.values(errors).flat().filter(Boolean).join(' ');
    },
    onFinish: () => {
      savingLog.value = false;
    },
  });
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-white">{{ headerTitle }}</h3>
        <p v-if="selectedCell?.date" class="mt-1 text-xs text-slate-500">
          {{ formatCalendarFr(selectedCell.date, 'medium') }}
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-3">
        <button
          v-if="canEditLog && !editingLog"
          type="button"
          class="rounded-lg border border-blue-500/40 px-2 py-1 text-xs font-semibold text-blue-200 hover:bg-blue-500/10"
          @click="startEdit"
        >
          {{ t('athleteUi.programSession.edit') }}
        </button>
        <button
          v-if="selectedCell"
          type="button"
          class="text-xs text-slate-500 hover:text-slate-300"
          @click="emit('close')"
        >
          {{ t('common.close') }}
        </button>
      </div>
    </div>

    <div
      v-if="hasWarmup"
      class="mt-4 space-y-2 rounded-xl border border-sky-500/25 bg-sky-950/20 px-3 py-3"
    >
      <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
        {{ t('athleteUi.programSession.warmup') }}
      </p>
      <p
        v-if="resolvedWarmup.notes?.trim()"
        class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
      >
        {{ resolvedWarmup.notes }}
      </p>
      <ul v-if="resolvedWarmup.items?.length" class="space-y-1">
        <li
          v-for="(item, index) in resolvedWarmup.items"
          :key="`${item.exercise_name}-${index}`"
          class="text-sm text-slate-300"
        >
          {{ formatLineRecap(item) || item.exercise_name }}
        </li>
      </ul>
    </div>

    <LoggedSessionSetEditor
      v-if="editingLog"
      v-model:exercises="draftExercises"
      :saving="savingLog"
      :error="logError"
      :can-delete-session="Boolean(loggedSession?.id)"
      @save="saveLog"
      @cancel="editingLog = false"
      @delete-session="deleteLog"
    />

    <div v-else-if="comparisonRows.length" class="mt-4 overflow-x-auto border-t border-slate-800 pt-4">
      <table class="w-full min-w-[18rem] border-collapse text-left text-xs">
        <thead>
          <tr class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.exercise') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.set') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.planned') }}</th>
            <th class="px-2 py-1.5 font-semibold">{{ t('athleteUi.programSession.done') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in comparisonRows"
            :key="row.key"
            class="border-t border-slate-800/80"
            :class="row.match ? 'bg-emerald-500/15 text-emerald-100' : 'bg-red-500/15 text-red-100'"
          >
            <td class="px-2 py-2 font-medium text-white">{{ row.exercise }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.setNumber }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.plannedLabel }}</td>
            <td class="px-2 py-2 tabular-nums">{{ row.actualLabel }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <p
      v-else-if="!hasWarmup"
      class="mt-4 border-t border-slate-800 pt-4 text-sm text-slate-500"
    >
      {{ t('athleteUi.programSession.noSession') }}
    </p>
  </section>
</template>
