<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';

const props = defineProps({
  athleteName: {
    type: String,
    required: true,
  },
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
  showCheckInButton: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['open-check-in']);

const todayLabel = formatCalendarFr(new Date().toISOString().slice(0, 10), 'long');

const athleteFirstName = computed(() => {
  const trimmed = props.athleteName.trim();
  if (!trimmed) {
    return '';
  }

  return trimmed.split(/\s+/)[0];
});

const competitionCountdown = computed(() => {
  if (!props.nextCompetition) {
    return null;
  }

  const days = props.nextCompetition.days_until;
  return days === 0 ? 'J-0' : `J-${days}`;
});
</script>

<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex min-w-0 flex-wrap items-center gap-2">
        <p class="text-xs text-slate-500">{{ todayLabel }}</p>
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

    <h1 class="text-lg font-bold text-white sm:text-2xl">
      Bonjour, {{ athleteFirstName }}
    </h1>

    <h2
      v-if="todaySessionTitle"
      class="text-2xl font-bold tracking-tight text-white sm:text-3xl"
    >
      Aujourd'hui {{ todaySessionTitle }}
    </h2>
  </div>
</template>
