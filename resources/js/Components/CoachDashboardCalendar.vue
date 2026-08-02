<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AthleteMonthCalendar from './AthleteMonthCalendar.vue';
import SectionHeader from './Dashboard/SectionHeader.vue';
import { formatCalendarFr } from '../utils/formatDates';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../i18n';

const { t, locale } = useI18n();


const props = defineProps({
  reminders: {
    type: Array,
    default: () => [],
  },
  competitions: {
    type: Array,
    default: () => [],
  },
  blockEvents: {
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
const view = ref('month');
const rangeFilter = ref('month');
const hoveredEvent = ref(null);

const form = useForm({
  title: '',
  event_date: new Date().toISOString().slice(0, 10),
  notes: '',
  athlete_id: '',
});

onMounted(() => {
  if (typeof window !== 'undefined' && window.matchMedia('(max-width: 767px)').matches) {
    view.value = 'agenda';
  }
});

const views = computed(() => [
  { key: 'agenda', label: t('athleteUi.calendar.agenda') },
  { key: 'month', label: t('athleteUi.calendar.month') },
  { key: 'planning', label: t('athleteUi.calendar.planning') },
]);

const ranges = computed(() => [
  { key: 'today', label: t('athleteUi.calendar.today') },
  { key: 'week', label: t('athleteUi.calendar.thisWeek') },
  { key: 'month', label: t('athleteUi.calendar.thisMonth') },
]);

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
  if (!window.confirm(t('athleteUi.calendar.confirmDeleteReminder'))) {
    return;
  }

  router.delete(`/coach/calendar-reminders/${reminder.id}`, {
    preserveScroll: true,
  });
}

function dateKey(value) {
  return String(value ?? '').slice(0, 10);
}

const today = new Date().toISOString().slice(0, 10);

function startOfWeek(d) {
  const date = new Date(d);
  const day = (date.getDay() + 6) % 7;
  date.setDate(date.getDate() - day);
  date.setHours(0, 0, 0, 0);
  return date;
}

function inRange(dateStr) {
  const key = dateKey(dateStr);
  if (!key) {
    return false;
  }
  if (rangeFilter.value === 'today') {
    return key === today;
  }
  if (rangeFilter.value === 'week') {
    const start = startOfWeek(new Date());
    const end = new Date(start);
    end.setDate(start.getDate() + 6);
    const d = new Date(`${key}T12:00:00`);
    return d >= start && d <= end;
  }
  const now = new Date();
  const d = new Date(`${key}T12:00:00`);
  return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
}

const typeMeta = computed(() => ({
  competition: {
    label: t('athleteUi.calendar.typeCompetition'),
    class: 'border-rose-500/35 bg-rose-950/25 text-rose-200',
    dot: 'bg-rose-400',
  },
  reminder: {
    label: t('athleteUi.calendar.typeReminder'),
    class: 'border-blue-500/35 bg-blue-950/25 text-blue-200',
    dot: 'bg-blue-400',
  },
  block_start: {
    label: t('athleteUi.calendar.typeBlockStart'),
    class: 'border-emerald-500/35 bg-emerald-950/20 text-emerald-200',
    dot: 'bg-emerald-400',
  },
  block_end: {
    label: t('athleteUi.calendar.typeBlockEnd'),
    class: 'border-amber-500/35 bg-amber-950/20 text-amber-200',
    dot: 'bg-amber-400',
  },
  feedback_due: {
    label: t('athleteUi.calendar.typeFeedbackDue'),
    class: 'border-indigo-500/35 bg-indigo-950/20 text-indigo-200',
    dot: 'bg-indigo-400',
  },
  session: {
    label: t('athleteUi.calendar.typeImportantSession'),
    class: 'border-violet-500/35 bg-violet-950/20 text-violet-200',
    dot: 'bg-violet-400',
  },
}));

const unifiedEvents = computed(() => {
  const events = [];

  for (const c of props.competitions ?? []) {
    events.push({
      id: `comp-${c.id ?? c.name}-${c.competition_date ?? c.date}`,
      date: dateKey(c.competition_date ?? c.date),
      title: c.name ?? t('athleteUi.calendar.competition'),
      subtitle: c.athlete?.name ?? c.athlete_name ?? '',
      type: 'competition',
      raw: c,
    });
  }

  for (const r of props.reminders ?? []) {
    events.push({
      id: `rem-${r.id}`,
      date: dateKey(r.event_date),
      title: r.title,
      subtitle: r.athlete_name ?? '',
      type: 'reminder',
      raw: r,
      editable: true,
    });
  }

  for (const b of props.blockEvents ?? []) {
    const kind = String(b.type ?? b.kind ?? '').toLowerCase();
    const isEnd = kind.includes('end') || kind.includes('fin');
    events.push({
      id: `block-${b.id ?? b.date}-${b.title ?? b.label}`,
      date: dateKey(b.date ?? b.event_date ?? b.start_date),
      title: b.title ?? b.label ?? (isEnd ? t('athleteUi.calendar.typeBlockEnd') : t('athleteUi.calendar.typeBlockStart')),
      subtitle: b.athlete_name ?? b.athlete?.name ?? '',
      type: isEnd ? 'block_end' : 'block_start',
      raw: b,
    });
  }

  return events
    .filter((e) => e.date)
    .sort((a, b) => a.date.localeCompare(b.date) || a.title.localeCompare(b.title));
});

const filteredEvents = computed(() => unifiedEvents.value.filter((e) => inRange(e.date)));

const planningDays = computed(() => {
  const start = startOfWeek(new Date());
  return Array.from({ length: 7 }, (_, i) => {
    const d = new Date(start);
    d.setDate(start.getDate() + i);
    const key = d.toISOString().slice(0, 10);
    return {
      key,
      label: d.toLocaleDateString(localeTag(locale.value), { weekday: 'short', day: 'numeric' }),
      isToday: key === today,
      events: unifiedEvents.value.filter((e) => e.date === key),
    };
  });
});

watch(view, (v) => {
  if (v === 'planning') {
    rangeFilter.value = 'week';
  }
});
</script>

<template>
  <section class="rounded-[20px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg backdrop-blur-sm sm:p-5">
    <SectionHeader
      :eyebrow="t('athleteUi.calendar.planningEyebrow')"
      :title="t('athleteUi.calendar.calendarTitle')"
    >
      <template #actions>
        <button
          type="button"
          class="rounded-[12px] border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 transition hover:bg-blue-950/50"
          @click="openCreateForm"
        >
          {{ t('athleteUi.calendar.addReminder') }}
        </button>
      </template>
    </SectionHeader>

    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <div class="flex flex-wrap gap-1.5 rounded-[14px] border border-slate-800 bg-slate-950/50 p-1">
        <button
          v-for="v in views"
          :key="v.key"
          type="button"
          class="rounded-[10px] px-3 py-1.5 text-xs font-semibold transition duration-200"
          :class="
            view === v.key
              ? 'bg-blue-600 text-white shadow-md shadow-blue-900/40'
              : 'text-slate-400 hover:text-white'
          "
          @click="view = v.key"
        >
          {{ v.label }}
        </button>
      </div>
      <div class="flex flex-wrap gap-1.5">
        <button
          v-for="r in ranges"
          :key="r.key"
          type="button"
          class="rounded-full border px-3 py-1 text-[11px] font-semibold transition duration-200"
          :class="
            rangeFilter === r.key
              ? 'border-blue-500/45 bg-blue-600/20 text-blue-100'
              : 'border-slate-700 text-slate-500 hover:border-slate-600 hover:text-slate-300'
          "
          @click="rangeFilter = r.key"
        >
          {{ r.label }}
        </button>
      </div>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
      <span
        v-for="(meta, key) in typeMeta"
        :key="key"
        class="inline-flex items-center gap-1.5 rounded-full border border-slate-800 px-2 py-0.5 text-[10px] text-slate-400"
      >
        <span class="h-1.5 w-1.5 rounded-full" :class="meta.dot" />
        {{ meta.label }}
      </span>
    </div>

    <form
      v-if="showForm"
      class="mt-4 space-y-3 rounded-[16px] border border-slate-800 bg-slate-950/50 p-3"
      @submit.prevent="submitReminder"
    >
      <p class="text-xs font-semibold text-white">
        {{ editingReminder ? t('athleteUi.calendar.editReminder') : t('athleteUi.calendar.newReminder') }}
      </p>
      <label class="block text-xs text-slate-400">
        {{ t('athleteUi.calendar.title') }}
        <input
          v-model="form.title"
          type="text"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <label class="block text-xs text-slate-400">
        {{ t('common.date') }}
        <input
          v-model="form.event_date"
          type="date"
          required
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <label class="block text-xs text-slate-400">
        {{ t('athleteUi.calendar.athleteOptional') }}
        <select
          v-model="form.athlete_id"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        >
          <option value="">{{ t('athleteUi.calendar.none') }}</option>
          <option v-for="athlete in rosterAthletes" :key="athlete.id" :value="athlete.id">
            {{ athlete.name }}
          </option>
        </select>
      </label>
      <label class="block text-xs text-slate-400">
        {{ t('athleteUi.calendar.notes') }}
        <textarea
          v-model="form.notes"
          rows="2"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
      </label>
      <div class="flex flex-wrap gap-2">
        <button
          type="submit"
          class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500"
          :disabled="form.processing"
        >
          {{ t('common.save') }}
        </button>
        <button
          v-if="editingReminder"
          type="button"
          class="rounded-lg border border-red-500/30 px-3 py-2 text-xs text-red-300 hover:bg-red-500/10"
          @click="deleteReminder(editingReminder)"
        >
          {{ t('common.delete') }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-700 px-3 py-2 text-xs text-slate-300 hover:bg-slate-800"
          @click="showForm = false"
        >
          {{ t('common.cancel') }}
        </button>
      </div>
    </form>

    <!-- Agenda -->
    <div v-if="view === 'agenda'" class="mt-4 space-y-2">
      <p
        v-if="!filteredEvents.length"
        class="rounded-[16px] border border-dashed border-slate-700 px-4 py-8 text-center text-sm text-slate-500"
      >
        {{ t('athleteUi.calendar.noEvents') }}
      </p>
      <button
        v-for="event in filteredEvents"
        :key="event.id"
        type="button"
        class="relative flex w-full items-start gap-3 rounded-[16px] border px-3 py-3 text-left transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_0_18px_rgba(59,130,246,0.12)]"
        :class="typeMeta[event.type]?.class ?? 'border-slate-800 bg-slate-950/40'"
        @mouseenter="hoveredEvent = event.id"
        @mouseleave="hoveredEvent = null"
        @click="event.editable && openEditForm(event.raw)"
      >
        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full" :class="typeMeta[event.type]?.dot" />
        <div class="min-w-0 flex-1">
          <div class="flex items-center justify-between gap-2">
            <p class="truncate text-sm font-semibold text-white">{{ event.title }}</p>
            <span class="shrink-0 text-[11px] text-slate-400">{{ formatCalendarFr(event.date, 'medium') }}</span>
          </div>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ typeMeta[event.type]?.label }}
            <span v-if="event.subtitle"> · {{ event.subtitle }}</span>
          </p>
        </div>
        <div
          v-if="hoveredEvent === event.id"
          class="pointer-events-none absolute bottom-full left-4 z-10 mb-2 max-w-xs rounded-[12px] border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-slate-300 shadow-xl"
        >
          {{ event.title }}
          <span v-if="event.subtitle"> — {{ event.subtitle }}</span>
        </div>
      </button>
    </div>

    <!-- Planning semaine -->
    <div v-else-if="view === 'planning'" class="mt-4 -mx-1 overflow-x-auto px-1">
      <div class="grid min-w-[44rem] grid-cols-7 gap-2">
        <div
          v-for="day in planningDays"
          :key="day.key"
          class="min-h-[10rem] rounded-[16px] border p-2 transition duration-200"
          :class="
            day.isToday
              ? 'border-blue-500/40 bg-blue-950/20 shadow-[0_0_20px_rgba(59,130,246,0.12)]'
              : 'border-slate-800 bg-slate-950/40'
          "
        >
          <p
            class="text-[11px] font-semibold uppercase tracking-wide"
            :class="day.isToday ? 'text-blue-300' : 'text-slate-500'"
          >
            {{ day.label }}
          </p>
          <div class="mt-2 space-y-1.5">
            <div
              v-for="event in day.events"
              :key="event.id"
              class="rounded-[10px] border px-2 py-1.5 text-[10px] font-medium leading-snug transition hover:brightness-110"
              :class="typeMeta[event.type]?.class"
              :title="`${event.title}${event.subtitle ? ' — ' + event.subtitle : ''}`"
            >
              {{ event.title }}
            </div>
            <p v-if="!day.events.length" class="pt-4 text-center text-[10px] text-slate-600">—</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Mois -->
    <div v-else class="mt-4">
      <AthleteMonthCalendar
        mode="overview"
        :block-events="blockEvents"
        :competitions="competitions"
        :reminders="reminders"
      />
      <div v-if="filteredEvents.length" class="mt-4 space-y-2">
        <p class="text-xs font-semibold text-slate-400">{{ t('athleteUi.calendar.filteredEvents') }}</p>
        <div class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1">
          <div
            v-for="event in filteredEvents.slice(0, 12)"
            :key="event.id"
            class="min-w-[12rem] rounded-[14px] border px-3 py-2 transition duration-200 hover:-translate-y-0.5"
            :class="typeMeta[event.type]?.class"
            :title="event.title"
          >
            <p class="truncate text-xs font-semibold text-white">{{ event.title }}</p>
            <p class="mt-0.5 text-[10px] opacity-80">{{ formatCalendarFr(event.date, 'medium') }}</p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
