<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { formatCalendarFr } from '../utils/formatDates';

const props = defineProps({
  reminders: {
    type: Array,
    default: () => [],
  },
  upcomingCompetitions: {
    type: Array,
    default: () => [],
  },
  rosterAthletes: {
    type: Array,
    default: () => [],
  },
});

const showForm = ref(false);
const editingReminder = ref(null);

const form = useForm({
  title: '',
  event_date: new Date().toISOString().slice(0, 10),
  notes: '',
  athlete_id: '',
});

const upcomingItems = computed(() => {
  const reminderItems = (props.reminders ?? []).map((reminder) => ({
    id: `reminder-${reminder.id}`,
    kind: 'reminder',
    date: reminder.event_date,
    title: reminder.title,
    subtitle: reminder.athlete_name ?? reminder.notes ?? 'Rappel personnel',
    raw: reminder,
  }));

  const competitionItems = (props.upcomingCompetitions ?? []).map((competition) => ({
    id: `competition-${competition.id}`,
    kind: 'competition',
    date: competition.competition_date,
    title: competition.name,
    subtitle: competition.athlete?.name ?? 'Compétition roster',
    raw: competition,
  }));

  return [...reminderItems, ...competitionItems]
    .sort((a, b) => String(a.date).localeCompare(String(b.date)))
    .slice(0, 12);
});

function openCreateForm() {
  editingReminder.value = null;
  form.reset();
  form.event_date = new Date().toISOString().slice(0, 10);
  showForm.value = true;
}

function openEditForm(reminder) {
  editingReminder.value = reminder;
  form.title = reminder.title;
  form.event_date = String(reminder.event_date).slice(0, 10);
  form.notes = reminder.notes ?? '';
  form.athlete_id = reminder.athlete_id ?? '';
  showForm.value = true;
}

function submitReminder() {
  const payload = {
    title: form.title,
    event_date: form.event_date,
    notes: form.notes || null,
    athlete_id: form.athlete_id || null,
  };

  if (editingReminder.value) {
    form.transform(() => payload).patch(`/coach/calendar-reminders/${editingReminder.value.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        showForm.value = false;
        editingReminder.value = null;
      },
    });
    return;
  }

  form.transform(() => payload).post('/coach/calendar-reminders', {
    preserveScroll: true,
    onSuccess: () => {
      showForm.value = false;
      form.reset();
    },
  });
}

function deleteReminder(reminder) {
  if (!window.confirm('Supprimer ce rappel ?')) {
    return;
  }

  router.delete(`/coach/calendar-reminders/${reminder.id}`, {
    preserveScroll: true,
  });
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-sm font-semibold text-white">Calendrier coach</h2>
        <p class="mt-0.5 text-xs text-slate-500">Rappels perso + compétitions roster</p>
      </div>
      <button
        type="button"
        class="rounded-xl border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-950/50"
        @click="openCreateForm"
      >
        + Rappel
      </button>
    </div>

    <form
      v-if="showForm"
      class="mt-4 space-y-3 rounded-xl border border-slate-800 bg-slate-950/50 p-3"
      @submit.prevent="submitReminder"
    >
      <p class="text-xs font-semibold text-white">
        {{ editingReminder ? 'Modifier le rappel' : 'Nouveau rappel' }}
      </p>
      <label class="block text-xs text-slate-400">
        Titre
        <input
          v-model="form.title"
          type="text"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <label class="block text-xs text-slate-400">
        Date
        <input
          v-model="form.event_date"
          type="date"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <label class="block text-xs text-slate-400">
        Athlète (optionnel)
        <select
          v-model="form.athlete_id"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        >
          <option value="">Aucun</option>
          <option v-for="athlete in rosterAthletes" :key="athlete.id" :value="athlete.id">
            {{ athlete.name }}
          </option>
        </select>
      </label>
      <label class="block text-xs text-slate-400">
        Notes
        <textarea
          v-model="form.notes"
          rows="2"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <div class="flex gap-2">
        <button
          type="submit"
          class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500"
          :disabled="form.processing"
        >
          Enregistrer
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:bg-slate-800"
          @click="showForm = false"
        >
          Annuler
        </button>
      </div>
    </form>

    <ul class="mt-4 space-y-2">
      <li
        v-for="item in upcomingItems"
        :key="item.id"
        class="flex items-start justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/40 px-3 py-2.5"
      >
        <div class="min-w-0">
          <p class="text-[10px] uppercase tracking-wide text-slate-500">
            {{ formatCalendarFr(item.date, 'medium') }}
            · {{ item.kind === 'competition' ? 'Compétition' : 'Rappel' }}
          </p>
          <p class="mt-0.5 truncate text-sm font-semibold text-white">{{ item.title }}</p>
          <p class="truncate text-xs text-slate-400">{{ item.subtitle }}</p>
        </div>
        <div v-if="item.kind === 'reminder'" class="flex shrink-0 gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2 py-1 text-[10px] text-slate-300 hover:bg-slate-800"
            @click="openEditForm(item.raw)"
          >
            Éditer
          </button>
          <button
            type="button"
            class="rounded-lg border border-red-500/30 px-2 py-1 text-[10px] text-red-300 hover:bg-red-500/10"
            @click="deleteReminder(item.raw)"
          >
            Suppr.
          </button>
        </div>
      </li>
      <li v-if="!upcomingItems.length" class="text-sm text-slate-500">
        Aucun rappel ni compétition à venir.
      </li>
    </ul>
  </section>
</template>
