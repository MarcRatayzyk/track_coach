<script setup>
import { ref, watch, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { useForm } from '@inertiajs/vue3';
import SessionDayEditorPanel from './SessionDayEditorPanel.vue';
import {
  dayToSessionPayload,
  sessionDayOrdinalInWeek,
  sessionToDay,
} from '../utils/programBuilder';

const { t } = useI18n();

const props = defineProps({
  activeBlock: {
    type: Object,
    required: true,
  },
  selectedCell: {
    type: Object,
    required: true,
  },
  session: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['saved', 'cleared', 'close']);

const day = ref(sessionToDay(props.session));
const sessionLabel = ref(props.session?.session_label ?? '');
const sessionNotes = ref(props.session?.notes ?? '');

const form = useForm({
  week_number: 1,
  weekday: 1,
  main_lift: 'squat',
  session_label: null,
  notes: null,
  warmup_override: false,
  warmup_notes: null,
  items: [],
  blocks: [],
});

watch(
  () => [props.selectedCell?.key, props.session],
  () => {
    day.value = sessionToDay(props.session);
    sessionLabel.value = props.session?.session_label ?? '';
    sessionNotes.value = props.session?.notes ?? '';
    day.value.session_label = sessionLabel.value;
    day.value.notes = sessionNotes.value;
  },
  { immediate: true },
);

watch(sessionLabel, (value) => {
  day.value.session_label = value;
});

watch(sessionNotes, (value) => {
  day.value.notes = value;
});

const headerTitle = computed(() => {
  const week = props.selectedCell.weekNumber;
  const dayNum = sessionDayOrdinalInWeek(
    props.activeBlock.sessions,
    week,
    props.selectedCell.weekday,
  );

  return t('programBuilder.sessionEditor.headerTitle', { week, day: dayNum });
});

const defaultWarmup = computed(() => ({
  notes: props.activeBlock?.default_warmup_notes ?? null,
  items: props.activeBlock?.default_warmup_items ?? [],
}));

function save() {
  day.value.session_label = sessionLabel.value;
  day.value.notes = sessionNotes.value;

  const payload = {
    week_number: props.selectedCell.weekNumber,
    weekday: props.selectedCell.weekday,
    ...dayToSessionPayload(day.value),
  };

  form.week_number = payload.week_number;
  form.weekday = payload.weekday;
  form.main_lift = payload.main_lift;
  form.session_label = payload.session_label;
  form.notes = payload.notes;
  form.warmup_override = payload.warmup_override;
  form.warmup_notes = payload.warmup_notes;
  form.items = payload.items;
  form.blocks = payload.blocks;

  form.put(`/coach/program-blocks/${props.activeBlock.id}/sessions`, {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  });
}

const hasSavedSession = computed(() => Boolean(props.session?.id));

const confirmingDelete = ref(false);

function requestDelete() {
  if (!hasSavedSession.value) {
    emit('close');
    return;
  }

  confirmingDelete.value = true;
}

function deleteSession() {
  confirmingDelete.value = false;
  form.week_number = props.selectedCell.weekNumber;
  form.weekday = props.selectedCell.weekday;

  form.delete(`/coach/program-blocks/${props.activeBlock.id}/sessions`, {
    preserveScroll: true,
    onSuccess: () => emit('cleared'),
  });
}
</script>

<template>
  <div class="flex flex-col rounded-xl border border-slate-800 bg-slate-950/50 p-4">
    <div v-if="confirmingDelete" class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-red-500/40 bg-red-950/30 px-3 py-2">
      <p class="text-sm text-red-100">{{ t('programBuilder.sessionEditor.deleteConfirm') }}</p>
      <button
        type="button"
        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-500"
        :disabled="form.processing"
        @click="deleteSession"
      >
        {{ t('common.delete') }}
      </button>
      <button
        type="button"
        class="rounded-lg border border-slate-600 px-3 py-1.5 text-xs text-slate-200"
        @click="confirmingDelete = false"
      >
        {{ t('common.cancel') }}
      </button>
    </div>
    <SessionDayEditorPanel
      v-model:day="day"
      v-model:session-label="sessionLabel"
      v-model:notes="sessionNotes"
      :title="headerTitle"
      :processing="form.processing"
      :errors="form.errors"
      :show-delete="hasSavedSession && !confirmingDelete"
      show-notes
      show-warmup
      :default-warmup="defaultWarmup"
      @save="save"
      @delete="requestDelete"
      @close="emit('close')"
    />
  </div>
</template>
