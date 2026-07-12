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
import AthleteFunStatsPanel from '../Components/AthleteFunStatsPanel.vue';
import AthleteMonthCalendar from '../Components/AthleteMonthCalendar.vue';
import AthleteReadinessCheckIn from '../Components/AthleteReadinessCheckIn.vue';
import TodaySessionCard from '../Components/TodaySessionCard.vue';
import WrappedStoryModal from '../Components/WrappedStoryModal.vue';

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
  shareHighlights: {
    type: Object,
    default: () => ({ pr_card: null, adherence_card: null, templates: [] }),
  },
  wrapped: {
    type: Object,
    default: () => ({ weekly: null, monthly: null }),
  },
  programBlock: {
    type: Object,
    default: null,
  },
  competitions: {
    type: Array,
    default: () => [],
  },
  funStats: {
    type: Object,
    default: null,
  },
});

const checkInModalOpen = ref(false);
const shareMessage = ref('');
const activeWrapped = ref(null);
const wrappedModalOpen = ref(false);

const wrappedCards = computed(() => [
  props.wrapped?.weekly ?? null,
  props.wrapped?.monthly ?? null,
].filter(Boolean));

function wrappedStorageKey(wrapped) {
  return `tc-wrapped-seen-${props.athleteId}-${wrapped.variant}-${wrapped.period_end}`;
}

function markWrappedSeen(wrapped) {
  if (typeof window === 'undefined' || !wrapped) {
    return;
  }
  window.localStorage.setItem(wrappedStorageKey(wrapped), '1');
}

function hasSeenWrapped(wrapped) {
  if (typeof window === 'undefined' || !wrapped) {
    return true;
  }
  return window.localStorage.getItem(wrappedStorageKey(wrapped)) === '1';
}

function openWrappedStory(wrapped) {
  activeWrapped.value = wrapped;
  wrappedModalOpen.value = true;
}

function closeWrappedStory() {
  if (activeWrapped.value) {
    markWrappedSeen(activeWrapped.value);
  }
  wrappedModalOpen.value = false;
}

function maybeAutoOpenWrapped() {
  const weekly = props.wrapped?.weekly;
  const monthly = props.wrapped?.monthly;

  if (weekly && !hasSeenWrapped(weekly)) {
    openWrappedStory(weekly);
    return;
  }

  if (monthly && !hasSeenWrapped(monthly)) {
    openWrappedStory(monthly);
  }
}

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

async function sharePayload(payload) {
  if (!payload || typeof window === 'undefined') {
    return;
  }

  const text = `${payload.social_text}\n${window.location.origin}${payload.share_url}`;
  const shareData = {
    title: payload.headline ?? 'Track Coach',
    text,
    url: `${window.location.origin}${payload.share_url}`,
  };

  try {
    if (navigator.share) {
      await navigator.share(shareData);
      shareMessage.value = 'Partage envoyé.';
      return;
    }

    await navigator.clipboard.writeText(text);
    shareMessage.value = 'Texte copié dans le presse-papiers.';
  } catch (_error) {
    shareMessage.value = 'Partage non disponible sur cet appareil.';
  }
}

onMounted(() => {
  if (shouldPromptCheckIn()) {
    openCheckInModal();
    return;
  }

  maybeAutoOpenWrapped();
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
      :adherence-share-payload="shareHighlights.adherence_card"
      @share-adherence="sharePayload"
    />

    <AthleteFunStatsPanel
      v-if="funStats"
      compact
      :stats="funStats"
    />

    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold text-white">Calendrier</h2>
        <Link
          :href="`/athletes/${athleteId}`"
          class="text-xs font-medium text-blue-400 hover:text-blue-300"
        >
          Voir mon profil →
        </Link>
      </div>
      <AthleteMonthCalendar
        class="mt-3"
        mode="overview"
        :program-block="programBlock"
        :competitions="competitions"
        :training-sessions="[]"
      />
    </section>

    <section
      v-if="shareHighlights.pr_card || (shareHighlights.templates?.length ?? 0) > 0"
      class="rounded-2xl border border-blue-500/30 bg-blue-950/15 p-4 shadow-lg"
    >
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-300/90">Partage</p>
          <p class="mt-1 text-sm font-semibold text-white">Mets en avant ta progression</p>
        </div>
        <button
          v-if="shareHighlights.pr_card"
          type="button"
          class="rounded-xl bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-blue-500"
          @click="sharePayload(shareHighlights.pr_card)"
        >
          Partager mon PR
        </button>
      </div>
      <p
        v-if="shareHighlights.pr_card"
        class="mt-3 rounded-xl border border-slate-700 bg-slate-900/70 px-3 py-2 text-xs text-slate-300"
      >
        {{ shareHighlights.pr_card.headline }} · {{ shareHighlights.pr_card.subline }}
      </p>
      <div v-if="shareHighlights.templates?.length" class="mt-3 flex flex-wrap gap-2">
        <span
          v-for="template in shareHighlights.templates"
          :key="template.id"
          class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[10px] font-medium text-slate-300"
        >
          {{ template.label }}
        </span>
      </div>
      <p v-if="shareMessage" class="mt-2 text-xs text-emerald-300">{{ shareMessage }}</p>
    </section>

    <section
      v-if="wrappedCards.length"
      class="rounded-2xl border border-violet-500/30 bg-violet-950/15 p-4 shadow-lg"
    >
      <div class="flex items-center justify-between gap-3">
        <div>
          <p class="text-[10px] font-semibold uppercase tracking-widest text-violet-300/90">Wrapped</p>
          <p class="mt-1 text-sm font-semibold text-white">Ton récap hebdo & mensuel</p>
        </div>
      </div>

      <div class="mt-3 grid gap-3 md:grid-cols-2">
        <article
          v-for="card in wrappedCards"
          :key="card.label"
          class="rounded-xl border border-slate-700 bg-slate-900/70 p-3"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <p class="text-xs font-semibold text-white">{{ card.label }}</p>
              <p class="mt-0.5 text-[11px] text-slate-400">
                {{ card.period_start }} → {{ card.period_end }}
              </p>
            </div>
          </div>

          <p class="mt-3 text-xs text-slate-300">
            Ton récap est prêt : volume, adhérence et stats Squat / Bench / Terre.
          </p>

          <div class="mt-3 flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg bg-violet-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-violet-500"
              @click="openWrappedStory(card)"
            >
              Voir mon recap
            </button>
            <button
              type="button"
              class="rounded-lg border border-violet-500/40 bg-violet-600/20 px-2.5 py-1.5 text-[11px] font-semibold text-violet-100 hover:bg-violet-600/30"
              @click="sharePayload(card.share_payload)"
            >
              Partager
            </button>
          </div>
        </article>
      </div>
    </section>

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

    <WrappedStoryModal
      :open="wrappedModalOpen"
      :wrapped="activeWrapped"
      @close="closeWrappedStory"
      @share="sharePayload"
    />
  </div>
</template>
