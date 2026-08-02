<script setup>
import { useI18n } from 'vue-i18n';
import { computed, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import { track } from '../../utils/analytics';
import FeedbackFrequencyPill from './FeedbackFrequencyPill.vue';
const { t } = useI18n();

const props = defineProps({
  feedback: { type: Object, default: null },
  nextPendingId: { type: Number, default: null },
});

const emit = defineEmits(['sent', 'sent-next']);

const replyForm = useForm({ content: '' });
const editorRef = ref(null);

const canReply = computed(
  () => props.feedback && props.feedback.status === 'submitted',
);

const alreadyReplied = computed(() => Boolean(props.feedback?.reply));

watch(
  () => props.feedback?.id,
  async () => {
    replyForm.reset();
    replyForm.clearErrors();
    await nextTick();
    editorRef.value?.focus?.();
  },
);

function sendReply() {
  if (!props.feedback?.id) return;

  const content = replyForm.content?.trim() ?? '';
  if (!content) {
    replyForm.setError('content', 'Écrivez votre retour avant de l’envoyer.');
    return;
  }

  replyForm
    .transform(() => ({ content }))
    .post(`/feedbacks/${props.feedback.id}/reply`, {
      preserveScroll: true,
      onSuccess: () => {
        track('feedback_replied', { feedback_id: props.feedback.id });
        replyForm.reset();
        if (props.nextPendingId) {
          emit('sent-next', props.nextPendingId);
        } else {
          emit('sent');
        }
      },
    });
}
</script>

<template>
  <aside
    class="flex h-full min-h-0 w-full flex-col rounded-[18px] border border-slate-800/80 bg-slate-900/50 shadow-xl backdrop-blur-sm lg:w-[18rem] xl:w-[20rem]"
  >
    <div class="border-b border-slate-800/80 p-4">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
        Retour du coach
      </h2>
      <p class="mt-1 flex flex-wrap items-center gap-2 text-sm font-semibold text-white">
        <span>{{ feedback?.athlete_name || t('app.feedbacks.selectFeedback') }}</span>
        <FeedbackFrequencyPill
          v-if="feedback?.feedback_frequency"
          :frequency="feedback.feedback_frequency"
          compact
        />
      </p>
    </div>

    <div v-if="feedback" class="min-h-0 flex-1 space-y-4 overflow-y-auto p-4 [scrollbar-width:thin]">
      <div v-if="alreadyReplied" class="rounded-[14px] border border-emerald-500/25 bg-emerald-950/20 p-3">
        <p class="text-xs font-semibold text-emerald-400">Réponse envoyée</p>
        <p
          v-if="feedback.reply?.body"
          class="mt-2 whitespace-pre-wrap text-sm text-slate-200"
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
      </div>

      <template v-else-if="canReply">
        <div>
          <label class="text-xs font-medium text-slate-400">Votre retour</label>
          <textarea
            ref="editorRef"
            v-model="replyForm.content"
            rows="10"
            :placeholder="t('app.feedbacks.writeReply')"
            class="mt-2 w-full rounded-[14px] border border-slate-700 bg-slate-950/80 px-3.5 py-3 text-sm text-white placeholder:text-slate-600 transition duration-200 focus:border-blue-500/50 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
          />
          <p v-if="replyForm.errors.content" class="mt-1 text-sm text-red-400">
            {{ replyForm.errors.content }}
          </p>
        </div>
      </template>

      <div
        v-else
        class="rounded-[14px] border border-slate-800 bg-slate-950/40 p-3 text-sm text-slate-500"
      >
        Ce retour ne nécessite plus d’action.
      </div>
    </div>

    <div
      v-if="canReply"
      class="border-t border-slate-800/80 p-4"
    >
      <motion.button
        type="button"
        :disabled="replyForm.processing"
        :whileHover="{ scale: 1.01 }"
        :whileTap="{ scale: 0.99 }"
        class="flex w-full items-center justify-center rounded-[14px] bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-[0_0_24px_rgba(59,130,246,0.25)] transition duration-200 hover:from-blue-500 hover:to-blue-400 disabled:opacity-50"
        @click="sendReply"
      >
        {{ replyForm.processing ? 'Envoi…' : 'Envoyer' }}
      </motion.button>
    </div>
  </aside>
</template>
