<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { formatCalendarFr, formatShortDateTimeFr } from '../../utils/formatDates';
import { messagingInitials } from '../../utils/messagingFormat';
const { t } = useI18n();

const props = defineProps({
  feedback: { type: Object, required: true },
  mode: { type: String, default: 'coach' },
});

const hasNotes = computed(() => Boolean((props.feedback.athlete_notes || '').trim()));
const loggedNotes = computed(() => props.feedback.session_logged_notes || []);

const title = computed(() =>
  props.mode === 'athlete' ? t('app.feedbacks.yourComment') : t('app.feedbacks.athleteComment'),
);
</script>

<template>
  <article class="rounded-[18px] border border-slate-800/80 bg-slate-950/40 p-4 shadow-lg backdrop-blur-sm">
    <div class="flex items-start gap-3">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700/80"
      >
        {{ messagingInitials(feedback.athlete_name || feedback.session_label) }}
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
          <span class="text-[11px] text-slate-400">
            {{ formatShortDateTimeFr(feedback.submitted_at) || formatCalendarFr(feedback.session_date, 'medium') }}
          </span>
        </div>
        <p v-if="mode === 'coach'" class="mt-0.5 text-xs text-slate-500">{{ feedback.athlete_name }}</p>
      </div>
    </div>

    <p
      v-if="hasNotes"
      class="mt-3 whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
    >
      {{ feedback.athlete_notes }}
    </p>
    <p v-else class="mt-3 text-sm text-slate-500">
      {{ mode === 'athlete' ? t('app.feedbacks.noFreeMessageYou') : t('app.feedbacks.noFreeMessageAthlete') }}
    </p>

    <div
      v-if="loggedNotes.length"
      class="mt-4 rounded-[14px] border border-slate-800 bg-slate-900/50 p-3"
    >
      <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">
        Notes de séance
      </p>
      <ul class="mt-2 space-y-1.5">
        <li
          v-for="(entry, index) in loggedNotes"
          :key="`${entry.exercise_name}-${index}`"
          class="text-sm text-slate-300"
        >
          <span class="font-medium text-slate-100">{{ entry.exercise_name }}</span>
          <span class="text-slate-600"> — </span>
          <span class="whitespace-pre-wrap">{{ entry.note }}</span>
        </li>
      </ul>
    </div>
  </article>
</template>
