<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SessionDayEditorPanel from './SessionDayEditorPanel.vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  dayToSessionPayload,
  sessionToDay,
} from '../utils/programBuilder';
import { track } from '../utils/analytics';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  athleteId: {
    type: Number,
    required: true,
  },
  session: {
    type: Object,
    default: null,
  },
  defaultDate: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close', 'saved']);

const day = ref(sessionToDay(props.session));
const sessionLabel = ref(props.session?.session_label ?? '');
const sessionDate = ref(props.session?.session_date?.slice?.(0, 10) ?? props.defaultDate ?? '');
const notes = ref(props.session?.notes ?? '');

const form = useForm({
  session_date: '',
  main_lift: 'squat',
  session_label: null,
  items: [],
  blocks: [],
  notes: null,
});

const isEditing = computed(() => Boolean(props.session?.id));

const headerTitle = computed(() => {
  if (isEditing.value && sessionLabel.value?.trim()) {
    return sessionLabel.value.trim();
  }
  if (sessionDate.value) {
    return formatCalendarFr(sessionDate.value, 'medium');
  }
  return 'Séance isolée';
});

function resetFromProps() {
  day.value = sessionToDay(props.session);
  sessionLabel.value = props.session?.session_label ?? '';
  sessionDate.value =
    props.session?.session_date?.toString?.().slice(0, 10) ?? props.defaultDate ?? '';
  notes.value = props.session?.notes ?? '';
  day.value.session_label = sessionLabel.value;
}

watch(
  () => [props.open, props.session, props.defaultDate],
  () => {
    if (props.open) {
      resetFromProps();
    }
  },
);

watch(sessionLabel, (value) => {
  day.value.session_label = value;
});

function close() {
  emit('close');
}

function save() {
  day.value.session_label = sessionLabel.value;

  const payload = {
    session_date: sessionDate.value,
    notes: notes.value?.trim() || null,
    ...dayToSessionPayload(day.value),
  };

  form.session_date = payload.session_date;
  form.main_lift = payload.main_lift;
  form.session_label = payload.session_label;
  form.items = payload.items;
  form.blocks = payload.blocks;
  form.notes = payload.notes;

  if (isEditing.value) {
    form.put(`/athletes/${props.athleteId}/training-sessions/${props.session.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        track('session_logged', { source: 'editor', is_update: true });
        emit('saved', { sessionDate: sessionDate.value });
        close();
      },
    });
  } else {
    form.post(`/athletes/${props.athleteId}/training-sessions`, {
      preserveScroll: true,
      onSuccess: () => {
        track('session_logged', { source: 'editor', is_update: false });
        emit('saved', { sessionDate: sessionDate.value });
        close();
      },
    });
  }
}

function deleteSession() {
  if (!isEditing.value) {
    close();
    return;
  }

  if (!window.confirm('Supprimer cette séance ?')) {
    return;
  }

  form.delete(`/athletes/${props.athleteId}/training-sessions/${props.session.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      emit('saved', { sessionDate: sessionDate.value });
      close();
    },
  });
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="training-session-editor-title"
    >
      <button
        type="button"
        class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        aria-label="Fermer"
        @click="close"
      />

      <div
        class="relative z-10 flex w-full max-w-2xl flex-col rounded-t-2xl border border-slate-800 bg-slate-900 p-4 shadow-2xl sm:rounded-2xl sm:p-5"
      >
        <SessionDayEditorPanel
          id="training-session-editor-title"
          v-model:day="day"
          v-model:session-label="sessionLabel"
          v-model:session-date="sessionDate"
          v-model:notes="notes"
          :title="headerTitle"
          :processing="form.processing"
          :errors="form.errors"
          :show-delete="isEditing"
          show-date-field
          show-notes
          @save="save"
          @delete="deleteSession"
          @close="close"
        />
      </div>
    </div>
  </Teleport>
</template>
