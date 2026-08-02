<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { formatShortDateTimeFr } from '../../utils/formatDates';
import FeedbackFrequencyPill from './FeedbackFrequencyPill.vue';
const { t } = useI18n();

const props = defineProps({
  feedback: { type: Object, default: null },
});

const hasReply = computed(() => Boolean(props.feedback?.reply));
const waiting = computed(
  () => props.feedback && props.feedback.status === 'submitted' && !props.feedback.reply,
);
</script>

<template>
  <aside
    class="flex h-full min-h-0 w-full flex-col rounded-[18px] border border-slate-800/80 bg-slate-900/50 shadow-xl backdrop-blur-sm lg:w-[18rem] xl:w-[20rem]"
  >
    <div class="border-b border-slate-800/80 p-4">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        {{ t('app.feedbacks.coachReply') }}
      </h2>
      <p class="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-white">
        <span class="truncate">{{ feedback?.session_label || t('app.feedbacks.selectFeedback') }}</span>
        <FeedbackFrequencyPill
          v-if="feedback?.feedback_frequency"
          :frequency="feedback.feedback_frequency"
          compact
        />
      </p>
    </div>

    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 [scrollbar-width:thin]">
      <template v-if="feedback">
        <div
          v-if="hasReply"
          class="rounded-[14px] border border-emerald-500/25 bg-emerald-950/20 p-3"
        >
          <p class="text-xs font-semibold text-emerald-400">{{ t('app.feedbacks.replyReceived') }}</p>
          <p
            v-if="feedback.reply?.body"
            class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
          >
            {{ feedback.reply.body }}
          </p>
          <div v-if="feedback.reply?.audio_files?.length" class="mt-3 space-y-2">
            <audio
              v-for="audio in feedback.reply.audio_files"
              :key="audio.id"
              :src="audio.url"
              controls
              class="w-full"
            />
          </div>
          <p v-if="feedback.reply?.created_at" class="mt-3 text-[11px] text-slate-500">
            {{ formatShortDateTimeFr(feedback.reply.created_at) }}
          </p>
        </div>

        <div
          v-else-if="waiting"
          class="rounded-[14px] border border-amber-500/25 bg-amber-950/15 p-4 text-center"
        >
          <p class="text-sm font-medium text-amber-200">{{ t('app.feedbacks.waitingForCoach') }}</p>
          <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
            {{ t('app.feedbacks.waitingForCoachHint') }}
          </p>
        </div>

        <div
          v-else
          class="rounded-[14px] border border-slate-800 bg-slate-950/40 p-3 text-sm text-slate-500"
        >
          {{ t('app.feedbacks.noReplyForFeedback') }}
        </div>
      </template>

      <div
        v-else
        class="rounded-[14px] border border-dashed border-slate-700 bg-slate-950/30 px-4 py-10 text-center text-sm text-slate-500"
      >
        {{ t('app.feedbacks.selectToSeeReply') }}
      </div>
    </div>
  </aside>
</template>
