<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { useI18n } from 'vue-i18n';
import { router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import CoachAddAthleteModal from '../Components/CoachAddAthleteModal.vue';
import CoachOnboardingTour from '../Components/CoachOnboardingTour.vue';
import CoachRosterAwardsModal from '../Components/CoachRosterAwardsModal.vue';
import CompetitionCalendarModal from '../Components/CompetitionCalendarModal.vue';
import DemoWelcomeModal from '../Components/DemoWelcomeModal.vue';
import FeedbackBreakdownModal from '../Components/FeedbackBreakdownModal.vue';
import OnboardingChecklist from '../Components/OnboardingChecklist.vue';
import UiIcon from '../Components/UiIcon.vue';
import AlertsPanel from '../Components/CoachDashboard/AlertsPanel.vue';
import CalendarWidget from '../Components/CoachDashboard/CalendarWidget.vue';
import ConversationPreview from '../Components/CoachDashboard/ConversationPreview.vue';
import DashboardHeader from '../Components/CoachDashboard/DashboardHeader.vue';
import PendingReviews from '../Components/CoachDashboard/PendingReviews.vue';
import PriorityPanel from '../Components/CoachDashboard/PriorityPanel.vue';
import ShortcutGrid from '../Components/CoachDashboard/ShortcutGrid.vue';
import { useDismissedAlerts } from '../composables/useDismissedAlerts';
import { formatCalendarFr } from '../utils/formatDates';
import { isCoachOnboardingDone } from '../utils/coachOnboarding';

const { t } = useI18n();

const props = defineProps({
  athleteCount: { type: Number, default: 0 },
  feedback: {
    type: Object,
    default: () => ({
      daily: {
        expected_today: 0,
        overdue: 0,
        due_today: 0,
        received_today: 0,
        replied_today: 0,
        processed_today: 0,
        pending_tasks: [],
      },
      weekly: {
        expected_week: 0,
        received_week: 0,
        processed_week: 0,
        replied_week: 0,
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
  upcomingCompetitions: { type: Array, default: () => [] },
  recentThreads: { type: Array, default: () => [] },
  stats: {
    type: Object,
    default: () => ({ active_programs: 0, program_templates: 0 }),
  },
  alerts: { type: Array, default: () => [] },
  calendarReminders: { type: Array, default: () => [] },
  calendarCompetitions: { type: Array, default: () => [] },
  calendarBlockEvents: { type: Array, default: () => [] },
  rosterAthletes: { type: Array, default: () => [] },
  coachReadinessForm: { type: Object, default: null },
  monthlyReadinessAwards: { type: Object, default: null },
  onboarding: {
    type: Object,
    default: () => ({ steps: [], completed_count: 0, total: 0 }),
  },
  activityFeed: { type: Array, default: () => [] },
  performance: { type: Object, default: () => ({}) },
});

const page = usePage();
const { filterActive } = useDismissedAlerts();
const activeAlerts = computed(() => filterActive(props.alerts));
const daily = computed(() => props.feedback.daily ?? {});
const weekly = computed(() => props.feedback.weekly ?? {});

function feedbackListRank(task) {
  if (task.feedback_status === 'coach_replied') return 2;
  if (task.has_submission) return 1;
  return 0;
}

function sortFeedbackList(tasks) {
  return [...tasks].sort((a, b) => {
    const rankDiff = feedbackListRank(a) - feedbackListRank(b);
    if (rankDiff !== 0) return rankDiff;
    return (a.id ?? 0) - (b.id ?? 0);
  });
}

const dailyFeedbackList = computed(() => sortFeedbackList(daily.value.pending_tasks ?? []));
const weeklyFeedbackList = computed(() => sortFeedbackList(weekly.value.pending_tasks ?? []));

const dailyPendingCount = computed(
  () =>
    (dailyFeedbackList.value ?? []).filter(
      (t) => t.has_submission && t.feedback_status !== 'coach_replied',
    ).length
    || (daily.value.due_today ?? 0) + (daily.value.overdue ?? 0),
);

const weeklyPendingCount = computed(
  () =>
    (weeklyFeedbackList.value ?? []).filter(
      (t) => t.has_submission && t.feedback_status !== 'coach_replied',
    ).length
    || Math.max(
      0,
      (weekly.value.expected_week ?? 0) - (weekly.value.replied_week ?? weekly.value.processed_week ?? 0),
    ),
);

const weekLabel = computed(() => {
  if (!props.feedback.week_start || !props.feedback.week_end) {
    return t('app.dashboard.thisWeek');
  }
  return t('app.feedbacks.weekFromTo', {
    start: formatCalendarFr(props.feedback.week_start),
    end: formatCalendarFr(props.feedback.week_end),
  });
});

const todayLabel = computed(() =>
  props.feedback.today ? formatCalendarFr(props.feedback.today) : t('app.dashboard.today'),
);

const upcomingComps = computed(() => props.upcomingCompetitions ?? []);
const threads = computed(() => props.recentThreads ?? []);
const unreadMessages = computed(() => page.props.messagingInbox?.total_unread ?? 0);
const criticalAlerts = computed(
  () => activeAlerts.value.filter((a) => a.severity === 'critical').length,
);
const competitionSummary = computed(() => props.competitionSummary ?? {});
const hasAthletes = computed(() => props.athleteCount > 0);

const showCompetitionModal = ref(false);
const showDailyFeedbackModal = ref(false);
const showWeeklyFeedbackModal = ref(false);
const dailyBreakdown = computed(() => daily.value.breakdown ?? { pending: [], submitted: [] });
const weeklyBreakdown = computed(() => weekly.value.breakdown ?? { pending: [], submitted: [] });
const showAddAthleteModal = ref(false);
const showOnboardingTour = ref(false);
const showRosterAwardsModal = ref(false);

function awardsStorageKey(awards) {
  return `tc-roster-awards-seen-${awards?.variant}-${awards?.period_end}`;
}

function hasSeenRosterAwards(awards) {
  if (typeof window === 'undefined' || !awards) return true;
  return window.localStorage.getItem(awardsStorageKey(awards)) === '1';
}

function markRosterAwardsSeen(awards) {
  if (typeof window === 'undefined' || !awards) return;
  window.localStorage.setItem(awardsStorageKey(awards), '1');
}

function openRosterAwards() {
  if (!props.monthlyReadinessAwards) return;
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
  // La popup reste ouverte avec le lien d’activation.
}

watch(showAddAthleteModal, (open) => {
  if (!open) {
    router.reload({
      preserveScroll: true,
      only: [
        'athleteCount',
        'feedback',
        'stats',
        'alerts',
        'recentThreads',
        'competitionSummary',
        'upcomingCompetitions',
        'monthlyReadinessAwards',
        'rosterAthletes',
      ],
    });
  }
});

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
      @invited="onAthleteInvited"
    />
    <DemoWelcomeModal />

    <template v-if="!hasAthletes">
      <div
        class="flex min-h-[calc(100vh-12rem)] flex-col items-center justify-center rounded-[1.25rem] border border-dashed border-blue-500/30 bg-gradient-to-b from-blue-600/10 to-slate-900/40 px-6 py-16 text-center shadow-xl"
      >
        <span
          class="flex h-16 w-16 items-center justify-center rounded-2xl border border-blue-500/30 bg-blue-600/15 text-blue-400"
        >
          <UiIcon name="users" class="h-8 w-8" />
        </span>
        <h1 class="mt-6 text-2xl font-bold text-white sm:text-3xl">{{ t('app.dashboard.welcomeTitle') }}</h1>
        <p class="mt-3 max-w-md text-sm text-slate-400 sm:text-base">
          {{ t('app.dashboard.welcomeBody') }}
        </p>
        <div class="mt-8 flex w-full max-w-sm flex-col items-stretch gap-3 sm:max-w-none sm:flex-row sm:flex-wrap sm:items-center sm:justify-center sm:gap-4">
          <button
            type="button"
            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500 sm:px-8 sm:py-4 sm:text-base"
            @click="openAddAthleteModal"
          >
            <UiIcon name="users" class="h-5 w-5" />
            {{ t('app.dashboard.addFirstAthlete') }}
          </button>
          <button
            v-if="!showOnboardingTour"
            type="button"
            class="rounded-2xl border border-slate-600 px-6 py-3.5 text-sm font-medium text-slate-300 hover:bg-slate-800/50 sm:py-4"
            @click="showOnboardingTour = true"
          >
            {{ t('app.dashboard.replayTour') }}
          </button>
        </div>
        <p class="mt-8 text-sm text-slate-500">
          {{ t('app.dashboard.welcomeHint') }}
        </p>
      </div>
    </template>

    <template v-else>
      <div class="space-y-6">
        <DashboardHeader
          :athlete-count="athleteCount"
          :alerts-count="activeAlerts.length"
          :active-programs="stats.active_programs ?? 0"
          :next-competition-date="competitionSummary.next_date"
          :next-competition-name="competitionSummary.next_name"
          @add-athlete="openAddAthleteModal"
        />

        <OnboardingChecklist :onboarding="onboarding" />

        <section
          v-if="monthlyReadinessAwards?.screens?.length"
          class="rounded-[1.25rem] border border-violet-500/30 bg-gradient-to-r from-violet-950/40 via-slate-900/50 to-slate-900/50 p-4 shadow-lg"
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <p class="text-[10px] font-semibold uppercase tracking-widest text-violet-300/90">
                Monthly Wrapped
              </p>
              <h2 class="mt-1 text-base font-semibold text-white">
                Roster Awards · {{ monthlyReadinessAwards.month_label }}
              </h2>
              <p class="mt-1 text-sm text-slate-400">
                Podiums humour du groupe — seulement si ces facteurs sont dans ton questionnaire.
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

        <PriorityPanel
          :daily-pending="dailyPendingCount"
          :weekly-pending="weeklyPendingCount"
          :alerts-count="activeAlerts.length"
          :critical-alerts="criticalAlerts"
          :unread-messages="unreadMessages"
        />

        <PendingReviews
          :daily-tasks="dailyFeedbackList"
          :weekly-tasks="weeklyFeedbackList"
          :today="feedback.today"
          :week-label="weekLabel"
        />

        <div class="grid min-w-0 gap-4 lg:grid-cols-2 lg:items-stretch">
          <ConversationPreview :threads="threads" />
          <AlertsPanel :alerts="alerts" />
        </div>

        <CalendarWidget
          :reminders="calendarReminders"
          :competitions="calendarCompetitions"
          :block-events="calendarBlockEvents"
          :roster-athletes="rosterAthletes"
        />

        <ShortcutGrid :on-add-athlete="openAddAthleteModal" />
      </div>

      <CompetitionCalendarModal
        :open="showCompetitionModal"
        :competitions="upcomingComps"
        @close="showCompetitionModal = false"
      />

      <FeedbackBreakdownModal
        :open="showDailyFeedbackModal"
        variant="daily"
        :title="t('app.dashboard.dailyFeedbackTitle', { date: todayLabel })"
        :subtitle="t('app.dashboard.dailyFeedbackSubtitle')"
        :breakdown="dailyBreakdown"
        @close="showDailyFeedbackModal = false"
      />

      <FeedbackBreakdownModal
        :open="showWeeklyFeedbackModal"
        variant="weekly"
        :title="t('app.dashboard.weeklyFeedbackTitle')"
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
