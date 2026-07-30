<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AthleteMonthCalendar from '../AthleteMonthCalendar.vue';
import SectionHeader from './SectionHeader.vue';
import { cardShell } from './dashboardUi';

defineProps({
  reminders: { type: Array, default: () => [] },
  competitions: { type: Array, default: () => [] },
  blockEvents: { type: Array, default: () => [] },
  rosterAthletes: { type: Array, default: () => [] },
});

const showForm = ref(false);
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  title: '',
  event_date: today,
  notes: '',
  athlete_id: '',
});

function openCreateForm() {
  form.reset();
  form.event_date = today;
  showForm.value = true;
}

function submitReminder() {
  form.transform(() => ({
    title: form.title,
    event_date: form.event_date,
    notes: form.notes || null,
    athlete_id: form.athlete_id || null,
  })).post('/coach/calendar-reminders', {
    preserveScroll: true,
    onSuccess: () => {
      showForm.value = false;
      form.reset();
    },
  });
}
</script>

<template>
  <section :class="[cardShell, 'p-5']">
    <SectionHeader
      eyebrow="Planning"
      title="Calendrier"
    >
      <template #actions>
        <button
          type="button"
          class="rounded-xl border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 transition hover:bg-blue-950/50"
          @click="openCreateForm"
        >
          + Rappel
        </button>
      </template>
    </SectionHeader>

    <form
      v-if="showForm"
      class="mt-4 space-y-3 rounded-[1.15rem] border border-slate-800 bg-slate-950/50 p-4"
      @submit.prevent="submitReminder"
    >
      <p class="text-xs font-semibold text-white">Nouveau rappel</p>
      <div class="grid gap-3 sm:grid-cols-2">
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
        <label class="block text-xs text-slate-400 sm:col-span-2">
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
        <label class="block text-xs text-slate-400 sm:col-span-2">
          Notes
          <textarea
            v-model="form.notes"
            rows="2"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
      </div>
      <div class="flex flex-wrap gap-2">
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

    <div class="mt-5">
      <AthleteMonthCalendar
        mode="overview"
        :block-events="blockEvents"
        :competitions="competitions"
        :reminders="reminders"
      />
    </div>
  </section>
</template>
