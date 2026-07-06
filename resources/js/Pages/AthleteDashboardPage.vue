<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AthleteDailyCheckInModal from '../Components/AthleteDailyCheckInModal.vue';
import AthleteDashboardHeader from '../Components/AthleteDashboardHeader.vue';
import AthleteReadinessCheckIn from '../Components/AthleteReadinessCheckIn.vue';
import TodaySessionCard from '../Components/TodaySessionCard.vue';

const props = defineProps({
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

const checkInModalOpen = ref(false);

const todaySessionTitle = computed(() => {
  if (props.todaySession?.status !== 'session') {
    return null;
  }

  const label = props.todaySession?.session?.session_label?.trim();
  return label || 'Séance du jour';
});

function skipStorageKey() {
  const today = new Date().toISOString().slice(0, 10);
  return `tc-daily-checkin-skipped-${props.athleteId}-${today}`;
}

function shouldPromptCheckIn() {
  if (typeof window === 'undefined') {
    return false;
  }
  if (window.localStorage.getItem(skipStorageKey())) {
    return false;
  }
  return !props.todayReadiness || !props.todayBodyWeight;
}

function openCheckInModal() {
  checkInModalOpen.value = true;
}

function closeCheckInModal() {
  checkInModalOpen.value = false;
}

function skipCheckInForLater() {
  if (typeof window !== 'undefined') {
    window.localStorage.setItem(skipStorageKey(), '1');
  }
  closeCheckInModal();
}

onMounted(() => {
  if (shouldPromptCheckIn()) {
    openCheckInModal();
  }
});
</script>

<template>
  <div class="space-y-3 lg:space-y-4">
    <AthleteDashboardHeader
      :athlete-name="athleteName"
      :athlete-id="athleteId"
      :next-competition="nextCompetition"
      :today-session-title="todaySessionTitle"
      :show-check-in-button="!todayReadiness || !todayBodyWeight"
      @open-check-in="openCheckInModal"
    />

    <TodaySessionCard
      class="min-w-0"
      :today-session="todaySession"
      :athlete-id="athleteId"
      :one-rm="oneRm"
      :today-logged-session="todayLoggedSession"
    />

    <Link
      v-if="feedbackDueToday"
      href="/feedbacks"
      class="flex items-center justify-between gap-3 rounded-xl border border-blue-500/40 bg-blue-600/15 px-4 py-2.5 text-sm transition hover:bg-blue-600/25"
    >
      <span class="font-medium text-blue-200">
        {{ feedbackFrequency === 'weekly'
          ? 'Retour hebdomadaire attendu'
          : 'Retour vidéo attendu pour ta séance du jour' }}
      </span>
      <span class="shrink-0 text-xs font-semibold text-blue-300">Envoyer →</span>
    </Link>

    <AthleteReadinessCheckIn
      class="hidden lg:block"
      :athlete-id="athleteId"
      :today-readiness="todayReadiness"
      :readiness-recent="readinessRecent"
      :today-body-weight="todayBodyWeight"
      :can-edit="true"
      compact
    />

    <AthleteDailyCheckInModal
      :open="checkInModalOpen"
      :athlete-id="athleteId"
      :today-readiness="todayReadiness"
      :today-body-weight="todayBodyWeight"
      @close="closeCheckInModal"
      @skipped="skipCheckInForLater"
    />
  </div>
</template>
