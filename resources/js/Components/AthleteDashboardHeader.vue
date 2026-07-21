<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import CelebrationBarbell from './CelebrationBarbell.vue';

const props = defineProps({
  athleteId: {
    type: Number,
    required: true,
  },
  nextCompetition: {
    type: Object,
    default: null,
  },
  todaySessionTitle: {
    type: String,
    default: null,
  },
  topsetBarbell: {
    type: Object,
    default: null,
  },
  showCheckInButton: {
    type: Boolean,
    default: false,
  },
  /** Première visite du jour : anime titre + barre depuis le bas */
  introAnimate: {
    type: Boolean,
    default: false,
  },
  introVisible: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['open-check-in']);

const competitionCountdown = computed(() => {
  if (!props.nextCompetition) {
    return null;
  }

  const days = props.nextCompetition.days_until;
  return days === 0 ? 'J-0' : `J-${days}`;
});

const heroMotionClass = computed(() => {
  if (!props.introAnimate) {
    return '';
  }
  return [
    'transition-all duration-700 ease-out',
    props.introVisible ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0',
  ];
});
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="competitionCountdown || showCheckInButton"
      class="flex flex-wrap items-center justify-between gap-3"
    >
      <div class="flex min-w-0 flex-wrap items-center gap-2">
        <Link
          v-if="competitionCountdown"
          :href="`/athletes/${athleteId}?competition=${nextCompetition.id}`"
          class="inline-flex shrink-0 items-center rounded-lg border border-amber-500/40 bg-amber-500/10 px-2 py-0.5 text-xs font-semibold tabular-nums text-amber-200 transition hover:bg-amber-500/20"
        >
          {{ competitionCountdown }}
        </Link>
      </div>
      <button
        v-if="showCheckInButton"
        type="button"
        class="shrink-0 rounded-lg border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 lg:hidden"
        @click="emit('open-check-in')"
      >
        Check-in
      </button>
    </div>

    <div :class="heroMotionClass">
      <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
        Aujourd'hui
        <template v-if="todaySessionTitle"> {{ todaySessionTitle }}</template>
      </h1>

      <div v-if="topsetBarbell" class="mt-3">
        <CelebrationBarbell :barbell="topsetBarbell" variant="peek" />
      </div>
    </div>
  </div>
</template>
