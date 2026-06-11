<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SessionDayEditorPanel from './SessionDayEditorPanel.vue';
import {
  dayToSessionPayload,
  sessionDayOrdinalInWeek,
  sessionToDay,
} from '../utils/programBuilder';

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

  return `Semaine ${week} - Jour ${dayNum}`;
});

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
  form.items = payload.items;
  form.blocks = payload.blocks;

  form.put(`/coach/program-blocks/${props.activeBlock.id}/sessions`, {
    preserveScroll: true,
    onSuccess: () => emit('saved'),
  });
}

const hasSavedSession = computed(() => Boolean(props.session?.id));

function deleteSession() {
  if (!hasSavedSession.value) {
    emit('close');
    return;
  }

  if (
    !window.confirm(
      'Supprimer cette séance ? Tous les exercices programmés pour ce jour seront effacés.',
    )
  ) {
    return;
  }

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
    <SessionDayEditorPanel
      v-model:day="day"
      v-model:session-label="sessionLabel"
      v-model:notes="sessionNotes"
      :title="headerTitle"
      :processing="form.processing"
      :errors="form.errors"
      :show-delete="hasSavedSession"
      show-notes
      @save="save"
      @delete="deleteSession"
      @close="emit('close')"
    />
  </div>
</template>
