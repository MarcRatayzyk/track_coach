<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CoachAddAthleteModal from '../Components/CoachAddAthleteModal.vue';
import CoachDashboardCalendar from '../Components/CoachDashboardCalendar.vue';
import CoachOnboardingTour from '../Components/CoachOnboardingTour.vue';
import CoachRosterAwardsModal from '../Components/CoachRosterAwardsModal.vue';
import CompetitionCalendarModal from '../Components/CompetitionCalendarModal.vue';
import DashboardAlertsPanel from '../Components/DashboardAlertsPanel.vue';
import FeedbackBreakdownModal from '../Components/FeedbackBreakdownModal.vue';
import MessageThreadUnreadBadge from '../Components/MessageThreadUnreadBadge.vue';
import SemiCircleGauge from '../Components/SemiCircleGauge.vue';
import UiIcon from '../Components/UiIcon.vue';
import {
  formatCalendarFr,
  formatDateTimeFr,
} from '../utils/formatDates';
import { isCoachOnboardingDone } from '../utils/coachOnboarding';

const props = defineProps({
  athleteCount: {
    type: Number,
    default: 0,
  },
  feedback: {
    type: Object,
    default: () => ({
      daily: {
        expected_today: 0,
        overdue: 0,
        due_today: 0,
        received_today: 0,
        processed_today: 0,
        pending_tasks: [],
      },
      weekly: {
        expected_week: 0,
        received_week: 0,
        processed_week: 0,
        pending_tasks: [],
      },
      week_start: null,
      week_end: null,
      today: null,
    }),
  },
  competitionSummary: {
    type: Object,
    default: () => ({
      count: 0,
      next_date: null,
      next_name: null,
      next_athlete_name: null,
    }),
  },
  upcomingCompetitions: {
    type: Array,
    default: () => [],
  },
  recentThreads: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({
      active_programs: 0,
      program_templates: 0,
    }),
  },
  alerts: {
    type: Array,
    default: () => [],
  },
  calendarReminders: {
    type: Array,
    default: () => [],
  },
  calendarCompetitions: {
    type: Array,
    default: () => [],
  },
  calendarBlockEvents: {
    type: Array,
    default: () => [],
  },
  rosterAthletes: {
    type: Array,
    default: () => [],
  },
  coachReadinessForm: {
    type: Object,
    default: null,
  },
  monthlyReadinessAwards: {
    type: Object,
    default: null,
  },
});

const daily = computed(() => props.feedback.daily ?? {});
const weekly = computed(() => props.feedback.weekly ?? {});

const dailyPending = computed(() => daily.value.pending_tasks ?? []);
const weeklyPending = computed(() => weekly.value.pending_tasks ?? []);

const dailyReceivedPending = computed(() =>
  dailyPending.value.filter((task) => task.has_submission),
);
const weeklyReceivedPending = computed(() =>
  weeklyPending.value.filter((task) => task.has_submission),
);

const weekLabel = computed(() => {
  if (!props.feedback.week_start || !props.feedback.week_end) {
    return 'Cette semaine';
  }
  return `Semaine du ${formatCalendarFr(props.feedback.week_start)} au ${formatCalendarFr(props.feedback.week_end)}`;
});

const todayLabel = computed(() =>
  props.feedback.today ? formatCalendarFr(props.feedback.today) : "Aujourd'hui",
);

function feedbackTaskUrl(task) {
  if (!task.session_feedback_id) {
    return null;
  }
  return `/feedbacks?feedback=${task.session_feedback_id}&filter=pending`;
}

function openFeedbackTask(task) {
  const url = feedbackTaskUrl(task);
  if (url) {
    router.visit(url);
  }
}

const upcomingComps = computed(() => props.upcomingCompetitions ?? []);
const threads = computed(() => props.recentThreads ?? []);
const showCompetitionModal = ref(false);
const showDailyFeedbackModal = ref(false);
const showWeeklyFeedbackModal = ref(false);

const dailyBreakdown = computed(() => daily.value.breakdown ?? { pending: [], submitted: [] });
const weeklyBreakdown = computed(() => weekly.value.breakdown ?? { pending: [], submitted: [] });

const competitionSummary = computed(() => props.competitionSummary ?? {});

const hasAthletes = computed(() => props.athleteCount > 0);
const showAddAthleteModal = ref(false);
const showOnboardingTour = ref(false);
const showRosterAwardsModal = ref(false);

function awardsStorageKey(awards) {
  return `tc-roster-awards-seen-${awards?.variant}-${awards?.period_end}`;
}

function hasSeenRosterAwards(awards) {
  if (typeof window === 'undefined' || !awards) {
    return true;
  }
  return window.localStorage.getItem(awardsStorageKey(awards)) === '1';
}

function markRosterAwardsSeen(awards) {
  if (typeof window === 'undefined' || !awards) {
    return;
  }
  window.localStorage.setItem(awardsStorageKey(awards), '1');
}

function openRosterAwards() {
  if (!props.monthlyReadinessAwards) {
    return;
  }
  showRosterAwardsModal.value = true;
}

function closeRosterAwards() {
  if (props.monthlyReadinessAwards) {
    markRosterAwardsSeen(props.monthlyReadinessAwards);
  }
  showRosterAwardsModal.value = false;
}

function openAddAthleteModal() {
  showAddAthleteModal.value = true;
}

function onAthleteInvited() {
  router.reload({
    only: [
      'athleteCount',
      'feedback',
      'stats',
      'alerts',
      'recentThreads',
      'competitionSummary',
      'upcomingCompetitions',
      'monthlyReadinessAwards',
    ],
  });
}

onMounted(() => {
  if (!hasAthletes.value && !isCoachOnboardingDone()) {
    showOnboardingTour.value = true;
  }
  if (
    props.monthlyReadinessAwards?.screens?.length
    && !hasSeenRosterAwards(props.monthlyReadinessAwards)
  ) {
    showRosterAwardsModal.value = true;
  }
});
</script>

<template>
  <div>
    <CoachOnboardingTour
      v-model="showOnboardingTour"
      @add-athlete="openAddAthleteModal"
    />
    <CoachAddAthleteModal
      v-model="showAddAthleteModal"
      :coach-readiness-form="coachReadinessForm"
      @invited="onAthleteInvited"
    />

    <template v-if="!hasAthletes">
      <div
        class="flex min-h-[calc(100vh-12rem)] flex-col items-center justify-center rounded-2xl border border-dashed border-blue-500/30 bg-gradient-to-b from-blue-600/10 to-slate-900/40 px-6 py-16 text-center shadow-xl"
      >
        <span
          class="flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-500/30 bg-blue-600/15 text-blue-400"
        >
          <UiIcon name="users" class="h-8 w-8" />
        </span>
        <h1 class="mt-6 text-3xl font-bold text-white">Bienvenue sur ton dashboard</h1>
        <p class="mt-3 max-w-md text-slate-400">
          Tu n’as pas encore d’athlète. Commence par en inviter un et partage le lien d’activation pour
          qu’il configure son compte.
        </p>
        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl bg-blue-600 px-8 py-4 text-base font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500"
            @click="openAddAthleteModal"
          >
            <UiIcon name="users" class="h-5 w-5" />
            Ajouter mon premier athlète
          </button>
          <button
            v-if="!showOnboardingTour"
            type="button"
            class="rounded-2xl border border-slate-600 px-6 py-4 text-sm font-medium text-slate-300 hover:bg-slate-800/50"
            @click="showOnboardingTour = true"
          >
            Revoir la visite guidée
          </button>
        </div>
        <p class="mt-8 text-sm text-slate-500">
          Ensuite : programmes, retours vidéo, messagerie et alertes apparaîtront ici.
        </p>
      </div>
    </template>

    <template v-else>
    <h1 class="text-2xl font-bold tracking-tight text-white">Dashboard</h1>

    <section
      v-if="monthlyReadinessAwards?.screens?.length"
      class="mt-4 rounded-xl border border-violet-500/30 bg-gradient-to-r from-violet-950/40 via-slate-900/60 to-slate-900/50 p-4 shadow-lg"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-violet-300/90">Monthly Wrapped</p>
          <h2 class="mt-1 text-base font-semibold text-white">Roster Awards · {{ monthlyReadinessAwards.month_label }}</h2>
          <p class="mt-1 text-sm text-slate-400">
            Podiums humour du groupe (pas, kcal, sommeil) — seulement si ces facteurs sont dans ton questionnaire.
          </p>
        </div>
        <button
          type="button"
          class="rounded-xl bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500"
          @click="openRosterAwards"
        >
          Voir les awards
        </button>
      </div>
    </section>

    <!-- Ligne 1 : Retours journaliers + hebdomadaires -->
    <div class="mt-4 grid gap-4 lg:grid-cols-2 lg:items-stretch">
      <section
        class="flex min-h-0 flex-col rounded-xl border border-amber-500/25 bg-slate-900/50 p-4 shadow-lg"
      >
        <div class="flex shrink-0 flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-white">Retours journaliers à faire</h2>
          </div>
          <Link
            href="/feedbacks?filter=pending"
            class="shrink-0 rounded-lg border border-amber-500/50 bg-amber-500/15 px-3 py-1.5 text-xs font-semibold text-amber-200 hover:bg-amber-500/25"
          >
            Voir les retours
          </Link>
          <span
            v-if="(daily.processed_today ?? 0) > 0"
            class="rounded-full bg-emerald-950/50 px-2.5 py-1 text-xs text-emerald-300"
          >
            {{ daily.processed_today }} traité{{ daily.processed_today > 1 ? 's' : '' }} aujourd'hui
          </span>
        </div>

        <p
          v-if="dailyReceivedPending.length === 0"
          class="mt-4 flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500"
        >
          Aucun retour journalier reçu pour le moment.
        </p>

        <div
          v-else
          class="tc-scrollbar tc-scrollbar-alerts mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1.5 lg:max-h-[17.5rem]"
        >
          <div
            v-for="task in dailyReceivedPending"
            :key="task.id"
            class="flex min-h-[7rem] cursor-pointer flex-col justify-between rounded-lg border border-slate-800 bg-slate-950/40 p-4 transition hover:border-blue-500/40 hover:bg-slate-900/70"
            @click="openFeedbackTask(task)"
          >
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-white">Retour de séance</p>
                <span
                  class="rounded-full bg-blue-950/60 px-2 py-0.5 text-xs font-medium text-blue-300"
                >
                  Reçu
                </span>
              </div>
              <Link
                v-if="task.athlete"
                :href="`/athletes/${task.athlete_id}`"
                class="mt-2 inline-block text-sm font-medium text-blue-400 hover:text-blue-300"
                @click.stop
              >
                {{ task.athlete.name }}
              </Link>
              <p v-if="task.session_date" class="mt-1 text-xs text-slate-500">
                Séance du {{ formatCalendarFr(task.session_date) }}
                <span v-if="task.due_at"> · échéance {{ formatDateTimeFr(task.due_at) }}</span>
              </p>
            </div>
            <p class="mt-3 text-center text-xs font-medium text-blue-400">Voir le retour →</p>
          </div>
        </div>
      </section>

      <section
        class="flex min-h-0 flex-col rounded-xl border border-indigo-500/25 bg-slate-900/50 p-4 shadow-lg"
      >
        <div class="flex shrink-0 flex-wrap items-start justify-between gap-3">
          <div>
            <h2 class="text-base font-semibold text-white">Retours hebdomadaires à faire</h2>
          </div>
          <Link
            href="/feedbacks?filter=pending"
            class="shrink-0 rounded-lg border border-indigo-500/50 bg-indigo-500/15 px-3 py-1.5 text-xs font-semibold text-indigo-200 hover:bg-indigo-500/25"
          >
            Voir les retours
          </Link>
        </div>

        <p
          v-if="weeklyReceivedPending.length === 0"
          class="mt-4 flex flex-1 items-center justify-center rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500"
        >
          Aucun retour hebdomadaire reçu pour le moment.
        </p>

        <div
          v-else
          class="tc-scrollbar mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1.5 lg:max-h-[17.5rem]"
        >
          <div
            v-for="task in weeklyReceivedPending"
            :key="task.id"
            class="flex min-h-[7rem] cursor-pointer flex-col justify-between rounded-lg border border-slate-800 bg-slate-950/40 p-4 transition hover:border-blue-500/40 hover:bg-slate-900/70"
            @click="openFeedbackTask(task)"
          >
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <p class="text-sm font-semibold text-white">Retour hebdomadaire</p>
                <span
                  class="rounded-full bg-blue-950/60 px-2 py-0.5 text-xs font-medium text-blue-300"
                >
                  Reçu
                </span>
              </div>
              <Link
                v-if="task.athlete"
                :href="`/athletes/${task.athlete_id}`"
                class="mt-2 inline-block text-sm font-medium text-blue-400 hover:text-blue-300"
                @click.stop
              >
                {{ task.athlete.name }}
              </Link>
              <p class="mt-1 text-xs text-slate-500">
                {{ weekLabel }}
                <span v-if="task.due_at"> · échéance {{ formatDateTimeFr(task.due_at) }}</span>
              </p>
            </div>
            <p class="mt-3 text-center text-xs font-medium text-blue-400">Voir le retour →</p>
          </div>
        </div>
      </section>
    </div>

    <!-- Ligne 2 : 4 cartes KPI -->
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
      <button
        type="button"
        class="flex min-h-[8.5rem] flex-col items-center justify-center gap-2.5 rounded-xl border border-amber-500/30 bg-amber-950/20 p-4 text-center shadow-lg transition hover:border-amber-500/50 hover:bg-amber-950/30"
        @click="showDailyFeedbackModal = true"
      >
        <span
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-500/15 text-amber-400"
        >
          <UiIcon name="list" class="h-5 w-5" />
        </span>
        <div class="min-w-0 w-full px-1">
          <p class="text-xs font-semibold leading-snug text-amber-200/90">
            Journaliers · {{ todayLabel }}
          </p>
          <div class="mt-1 flex justify-center">
            <SemiCircleGauge
              :value="daily.received_today ?? 0"
              :total="daily.expected_today ?? 0"
              color="#f59e0b"
              track-color="rgba(120, 53, 15, 0.45)"
            />
          </div>
        </div>
      </button>

      <button
        type="button"
        class="flex min-h-[8.5rem] flex-col items-center justify-center gap-2.5 rounded-xl border border-indigo-500/30 bg-indigo-950/20 p-4 text-center shadow-lg transition hover:border-indigo-500/50 hover:bg-indigo-950/30"
        @click="showWeeklyFeedbackModal = true"
      >
        <span
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-400"
        >
          <UiIcon name="calendar" class="h-5 w-5" />
        </span>
        <div class="min-w-0 w-full px-1">
          <p class="text-xs font-semibold text-indigo-200/90">Hebdomadaires</p>
          <div class="mt-1 flex justify-center">
            <SemiCircleGauge
              :value="weekly.received_week ?? 0"
              :total="weekly.expected_week ?? 0"
              color="#818cf8"
              track-color="rgba(49, 46, 129, 0.45)"
            />
          </div>
        </div>
      </button>

      <button
        type="button"
        class="flex min-h-[8.5rem] flex-col items-center justify-center gap-2.5 rounded-xl border border-rose-500/30 bg-rose-950/20 p-4 text-center shadow-lg transition hover:border-rose-500/50 hover:bg-rose-950/30"
        @click="showCompetitionModal = true"
      >
        <span
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-rose-500/15 text-rose-400"
        >
          <UiIcon name="calendar" class="h-5 w-5" />
        </span>
        <div class="min-w-0 w-full px-1">
          <p class="text-xs font-semibold text-rose-200/90">Compétitions</p>
          <p class="mt-1.5 text-3xl font-bold tabular-nums text-white">
            {{ competitionSummary.count ?? 0 }}
          </p>
        </div>
      </button>

      <div
        class="flex min-h-[8.5rem] flex-col items-center justify-center gap-2.5 rounded-xl border border-slate-800 bg-slate-900/70 p-4 text-center shadow-lg"
      >
        <span
          class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-500/15 text-violet-400"
        >
          <UiIcon name="bolt" class="h-5 w-5" />
        </span>
        <div class="min-w-0 w-full px-1">
          <p class="text-xs font-semibold text-slate-400">Programmes actifs</p>
          <p class="mt-1.5 text-3xl font-bold tabular-nums text-white">
            {{ stats.active_programs ?? 0 }}
          </p>
        </div>
      </div>
    </div>

    <!-- Ligne 3 : Alertes + Conversations -->
    <div class="mt-4 grid gap-4 lg:grid-cols-2 lg:items-stretch">
      <DashboardAlertsPanel :alerts="alerts" />

      <section class="flex min-h-0 flex-col rounded-xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
        <div class="flex shrink-0 items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <span class="text-blue-400">
              <UiIcon name="chat" class="h-5 w-5" />
            </span>
            <h2 class="text-base font-semibold text-white">Conversations</h2>
          </div>
          <Link href="/messaging" class="text-sm font-medium text-blue-400 hover:text-blue-300">
            Ouvrir
          </Link>
        </div>
        <p v-if="!threads.length" class="mt-4 flex flex-1 items-center justify-center text-center text-sm text-slate-500">
          Aucune conversation.
        </p>
        <ul
          v-else
          class="tc-scrollbar mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1.5 lg:max-h-[17.5rem]"
        >
          <li v-for="t in threads" :key="t.id">
            <Link
              :href="`/messaging?thread=${t.id}`"
              class="relative block rounded-lg border border-slate-800/80 bg-slate-950/40 px-3 py-2.5 pr-8 transition hover:border-blue-500/40 hover:bg-slate-800/50"
              :class="(t.unread_messages_count ?? 0) > 0 ? 'border-blue-500/30 bg-blue-950/20' : ''"
            >
              <MessageThreadUnreadBadge :count="t.unread_messages_count ?? 0" />
              <span class="block text-sm font-semibold text-white">{{
                t.athlete?.name ?? 'Athlète'
              }}</span>
              <span class="mt-0.5 block truncate text-xs text-slate-500">
                <template v-if="t.last_message">
                  {{ t.last_message.is_mine ? 'Toi : ' : '' }}{{ t.last_message.content }}
                </template>
                <template v-else>Aucun message</template>
              </span>
            </Link>
          </li>
        </ul>
      </section>
    </div>

    <CoachDashboardCalendar
      class="mt-4"
      :reminders="calendarReminders"
      :competitions="calendarCompetitions"
      :block-events="calendarBlockEvents"
      :roster-athletes="rosterAthletes"
    />

    <CompetitionCalendarModal
      :open="showCompetitionModal"
      :competitions="upcomingComps"
      @close="showCompetitionModal = false"
    />

    <FeedbackBreakdownModal
      :open="showDailyFeedbackModal"
      variant="daily"
      :title="`Retours journaliers · ${todayLabel}`"
      subtitle="Athlètes avec une séance programme prévue aujourd'hui"
      :breakdown="dailyBreakdown"
      @close="showDailyFeedbackModal = false"
    />

    <FeedbackBreakdownModal
      :open="showWeeklyFeedbackModal"
      variant="weekly"
      title="Retours hebdomadaires"
      :subtitle="weekLabel"
      :breakdown="weeklyBreakdown"
      @close="showWeeklyFeedbackModal = false"
    />

    <CoachRosterAwardsModal
      :open="showRosterAwardsModal"
      :awards="monthlyReadinessAwards"
      @close="closeRosterAwards"
    />
    </template>
  </div>
</template>
