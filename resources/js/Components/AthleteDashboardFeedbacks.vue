<script setup>
import { Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { formatCalendarFr } from '../utils/formatDates';

const { t } = useI18n();

defineProps({
  recentFeedbacks: {
    type: Array,
    default: () => [],
  },
  feedbackSummary: {
    type: Object,
    default: () => ({ pending_reply: 0, due_today: false }),
  },
});

function statusLabel(status) {
  if (status === 'coach_replied') {
    return t('athleteUi.dashboard.coachReply');
  }
  return t('athleteUi.dashboard.pending');
}

function statusClass(status) {
  if (status === 'coach_replied') {
    return 'text-emerald-300';
  }
  return 'text-amber-300';
}
</script>

<template>
  <section
    v-if="recentFeedbacks.length || feedbackSummary.pending_reply > 0"
    class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold text-white">{{ t('athleteUi.dashboard.videoFeedbacks') }}</h2>
      <Link href="/feedbacks" class="text-xs font-medium text-blue-400 hover:text-blue-300">
        {{ t('athleteUi.dashboard.seeAll') }}
      </Link>
    </div>

    <p
      v-if="feedbackSummary.pending_reply > 0"
      class="mt-2 text-xs text-amber-300"
    >
      {{ t('athleteUi.dashboard.pendingReply', feedbackSummary.pending_reply) }}
    </p>

    <ul v-if="recentFeedbacks.length" class="mt-3 space-y-2">
      <li
        v-for="feedback in recentFeedbacks"
        :key="feedback.id"
      >
        <Link
          :href="`/feedbacks?feedback=${feedback.id}`"
          class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/40 px-3 py-2 transition hover:border-slate-600"
        >
          <div class="min-w-0">
            <p class="truncate text-sm font-medium text-white">
              {{ feedback.session_label }}
            </p>
            <p class="text-xs text-slate-500">
              {{ formatCalendarFr(feedback.session_date, 'medium') }}
              <span v-if="feedback.video_count" class="ml-1">· {{ t('athleteUi.dashboard.videos', feedback.video_count) }}</span>
            </p>
          </div>
          <span class="shrink-0 text-xs font-semibold" :class="statusClass(feedback.status)">
            {{ statusLabel(feedback.status) }}
          </span>
        </Link>
      </li>
    </ul>
  </section>
</template>
