<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
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
  todayBodyWeight: { type: Object, default: null },
  nextCompetition: { type: Object, default: null },
  blockProgress: { type: Object, default: null },
  oneRm: { type: Object, default: () => ({ squat: 0, bench: 0, deadlift: 0 }) },
  latestPr: { type: Object, default: null },
  feedbackDueToday: { type: Boolean, default: false },
  feedbackFrequency: { type: String, default: 'weekly' },
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
      :feedback-frequency="feedbackFrequency"
    />

    <div class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(17rem,20rem)]">
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
        :today-body-weight="todayBodyWeight"
        :can-edit="true"
        compact
      />
    </div>
  </div>
</template>
