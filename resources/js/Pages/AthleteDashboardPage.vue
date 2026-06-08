<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import AthleteDashboardActivity from '../Components/AthleteDashboardActivity.vue';
import AthleteDashboardCoachCard from '../Components/AthleteDashboardCoachCard.vue';
import AthleteDashboardFeedbacks from '../Components/AthleteDashboardFeedbacks.vue';
import AthleteDashboardHeader from '../Components/AthleteDashboardHeader.vue';
import AthleteReadinessCheckIn from '../Components/AthleteReadinessCheckIn.vue';
import TodaySessionCard from '../Components/TodaySessionCard.vue';

defineProps({
  athleteName: { type: String, required: true },
  athleteId: { type: Number, required: true },
  todaySession: { type: Object, required: true },
  todayLoggedSession: { type: Object, default: null },
  todayReadiness: { type: Object, default: null },
  readinessRecent: { type: Array, default: () => [] },
  nextCompetition: { type: Object, default: null },
  programBlock: { type: Object, default: null },
  blockProgress: { type: Object, default: null },
  trainingSessions: { type: Array, default: () => [] },
  oneRm: { type: Object, default: () => ({ squat: 0, bench: 0, deadlift: 0 }) },
  latestPr: { type: Object, default: null },
  personalRecords: { type: Array, default: () => [] },
  recentFeedbacks: { type: Array, default: () => [] },
  feedbackSummary: { type: Object, default: () => ({ pending_reply: 0, due_today: false }) },
  coachThread: { type: Object, default: null },
  feedbackDueToday: { type: Boolean, default: false },
});
</script>

<template>
  <div class="space-y-4">
    <AthleteDashboardHeader
      :athlete-name="athleteName"
      :athlete-id="athleteId"
      :next-competition="nextCompetition"
      :block-progress="blockProgress"
      :latest-pr="latestPr"
      :readiness-recent="readinessRecent"
      :feedback-due-today="feedbackDueToday"
    />

    <div class="grid items-stretch gap-4 lg:grid-cols-2">
      <TodaySessionCard
        :today-session="todaySession"
        :athlete-id="athleteId"
        :one-rm="oneRm"
        :today-logged-session="todayLoggedSession"
      />

      <AthleteReadinessCheckIn
        :athlete-id="athleteId"
        :today-readiness="todayReadiness"
        :readiness-recent="readinessRecent"
        :can-edit="true"
      />
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
      <AthleteDashboardActivity
        :training-sessions="trainingSessions"
        :program-block="programBlock"
        :one-rm="oneRm"
        :personal-records="personalRecords"
      />
      <div class="space-y-4">
        <AthleteDashboardCoachCard :coach-thread="coachThread" />
        <AthleteDashboardFeedbacks
          :recent-feedbacks="recentFeedbacks"
          :feedback-summary="feedbackSummary"
        />
      </div>
    </div>
  </div>
</template>
