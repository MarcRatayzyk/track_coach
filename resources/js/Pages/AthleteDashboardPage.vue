<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import AthleteDailyCheckInModal from '../Components/AthleteDailyCheckInModal.vue';
import AthleteDashboardHeader from '../Components/AthleteDashboardHeader.vue';
import AthleteReadinessCheckIn from '../Components/AthleteReadinessCheckIn.vue';
import TodaySessionCard from '../Components/TodaySessionCard.vue';
import WrappedStoryModal from '../Components/WrappedStoryModal.vue';
import { WEEKDAY_LABELS } from '../utils/programBuilder';
import { buildPrimaryTopsetBarbell } from '../utils/sessionCelebration';

const props = defineProps({
  athleteName: { type: String, required: true },
  athleteId: { type: Number, required: true },
  todaySession: { type: Object, required: true },
  todayLoggedSession: { type: Object, default: null },
  todayReadiness: { type: Object, default: null },
  readinessRecent: { type: Array, default: () => [] },
  readinessForm: { type: Object, default: null },
  todayBodyWeight: { type: Object, default: null },
  nextCompetition: { type: Object, default: null },
  blockProgress: { type: Object, default: null },
  oneRm: { type: Object, default: () => ({ squat: 0, bench: 0, deadlift: 0 }) },
  latestPr: { type: Object, default: null },
  feedbackDueToday: { type: Boolean, default: false },
  feedbackFrequency: { type: String, default: 'weekly' },
  wrapped: {
    type: Object,
    default: () => ({ weekly: null, monthly: null }),
  },
});

const checkInModalOpen = ref(false);
const activeWrapped = ref(null);
const wrappedModalOpen = ref(false);
const playHomeIntro = ref(false);
const homeIntroVisible = ref(true);

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

function isDefaultSessionLabel(label) {
  const normalized = String(label ?? '').trim().toLowerCase();
  if (!normalized) {
    return true;
  }
  return WEEKDAY_LABELS.some((weekday) => weekday.toLowerCase() === normalized);
}

const todaySessionTitle = computed(() => {
  if (props.todaySession?.status !== 'session') {
    return null;
  }

  const label = props.todaySession?.session?.session_label?.trim();
  if (!label || isDefaultSessionLabel(label)) {
    return null;
  }

  return label;
});

const topsetBarbell = computed(() => {
  if (props.todaySession?.status !== 'session') {
    return null;
  }
  return buildPrimaryTopsetBarbell(props.todaySession.session, props.oneRm);
});

const sessionCardMotionClass = computed(() => {
  if (!playHomeIntro.value) {
    return '';
  }
  return [
    'transition-opacity duration-700 ease-out',
    homeIntroVisible.value ? 'opacity-100' : 'opacity-0',
  ];
});

function homeIntroStorageKey() {
  const today = new Date().toISOString().slice(0, 10);
  return `tc-home-intro-${props.athleteId}-${today}`;
}

function initHomeIntro() {
  if (typeof window === 'undefined') {
    return;
  }

  if (window.localStorage.getItem(homeIntroStorageKey())) {
    playHomeIntro.value = false;
    homeIntroVisible.value = true;
    return;
  }

  playHomeIntro.value = true;
  homeIntroVisible.value = false;
  window.localStorage.setItem(homeIntroStorageKey(), '1');

  nextTick(() => {
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        homeIntroVisible.value = true;
      });
    });
  });
}

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
      return;
    }

    await navigator.clipboard.writeText(text);
  } catch (_error) {
    // Partage annulé ou indisponible.
  }
}

onMounted(() => {
  initHomeIntro();

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
      :athlete-id="athleteId"
      :next-competition="nextCompetition"
      :today-session-title="todaySessionTitle"
      :topset-barbell="topsetBarbell"
      :show-check-in-button="!todayReadiness || !todayBodyWeight"
      :intro-animate="playHomeIntro"
      :intro-visible="homeIntroVisible"
      @open-check-in="openCheckInModal"
    />

    <TodaySessionCard
      class="min-w-0"
      :class="sessionCardMotionClass"
      :today-session="todaySession"
      :athlete-id="athleteId"
      :one-rm="oneRm"
      :today-logged-session="todayLoggedSession"
    />

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

    <AthleteReadinessCheckIn
      class="hidden lg:block"
      :athlete-id="athleteId"
      :readiness-form="readinessForm"
      :today-readiness="todayReadiness"
      :readiness-recent="readinessRecent"
      :today-body-weight="todayBodyWeight"
      :can-edit="true"
      compact
    />

    <AthleteDailyCheckInModal
      :open="checkInModalOpen"
      :athlete-id="athleteId"
      :readiness-form="readinessForm"
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
