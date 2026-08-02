<script setup>
import { useI18n } from 'vue-i18n';
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import KPIBar from './KPIBar.vue';
import ReviewSidebar from './ReviewSidebar.vue';
import SessionAnalysisPanel from './SessionAnalysisPanel.vue';
import CoachFeedbackPanel from './CoachFeedbackPanel.vue';
import EmptyState from './EmptyState.vue';
const { t } = useI18n();

const props = defineProps({
  feedbacks: { type: Array, default: () => [] },
  activeFeedback: { type: Object, default: null },
  metrics: { type: Object, default: null },
});

const listFilter = ref('all');
const search = ref('');
const mobilePane = ref(props.activeFeedback ? 'analysis' : 'list');

watch(
  () => props.activeFeedback?.id,
  (id) => {
    if (id) mobilePane.value = 'analysis';
  },
);

const allDone = computed(
  () =>
    props.feedbacks.length > 0 &&
    props.feedbacks.every((item) => item.status === 'coach_replied'),
);

const nextPendingId = computed(() => {
  const activeId = props.activeFeedback?.id;
  const pending = props.feedbacks.filter((item) => item.status === 'submitted');
  if (!pending.length) return null;
  const idx = pending.findIndex((item) => item.id === activeId);
  if (idx >= 0 && idx < pending.length - 1) return pending[idx + 1].id;
  const other = pending.find((item) => item.id !== activeId);
  return other?.id ?? null;
});

function selectFeedback(id) {
  router.get(`/feedbacks?feedback=${id}`, {}, { preserveState: true, preserveScroll: true });
  mobilePane.value = 'analysis';
}

function openReplyPane() {
  mobilePane.value = 'reply';
}

function backToList() {
  mobilePane.value = 'list';
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-white">Retours de séance</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-400">
          Analysez la séance, visionnez les vidéos et répondez — sans changer d’écran.
        </p>
      </div>
    </div>

    <KPIBar :metrics="metrics" />

    <EmptyState
      v-if="allDone && !activeFeedback"
      :title="t('app.feedbacks.allDone')"
      :description="t('app.feedbacks.allDoneDesc')"
      tone="success"
    />

    <div
      class="flex min-h-[calc(100dvh-14rem)] flex-col gap-3 lg:h-[calc(100dvh-13rem)] lg:min-h-[36rem] lg:flex-row lg:gap-4"
    >
      <!-- Liste -->
      <div
        class="min-h-0"
        :class="mobilePane === 'list' ? 'flex flex-1 lg:flex-none' : 'hidden lg:flex'"
      >
        <ReviewSidebar
          :feedbacks="feedbacks"
          :active-id="activeFeedback?.id ?? null"
          :filter="listFilter"
          :search="search"
          @update:filter="listFilter = $event"
          @update:search="search = $event"
          @select="selectFeedback"
        />
      </div>

      <!-- Analyse -->
      <div
        class="min-h-0 min-w-0 flex-1"
        :class="mobilePane === 'analysis' ? 'flex' : 'hidden lg:flex'"
      >
        <SessionAnalysisPanel :feedback="activeFeedback" @back="backToList" />
      </div>

      <!-- Réponse -->
      <div
        class="min-h-0"
        :class="
          mobilePane === 'reply'
            ? 'flex flex-1'
            : activeFeedback
              ? 'hidden xl:flex'
              : 'hidden xl:flex'
        "
      >
        <CoachFeedbackPanel
          :feedback="activeFeedback"
          :next-pending-id="nextPendingId"
          @sent-next="selectFeedback"
        />
      </div>
    </div>

    <!-- Mobile / tablet : accès panneau réponse -->
    <div
      v-if="activeFeedback && activeFeedback.status === 'submitted'"
      class="fixed bottom-20 right-4 z-30 xl:hidden"
    >
      <button
        type="button"
        class="rounded-full bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-[0_0_24px_rgba(59,130,246,0.35)]"
        @click="openReplyPane"
      >
        Répondre
      </button>
    </div>

    <div
      v-if="mobilePane === 'reply'"
      class="fixed inset-0 z-40 flex flex-col bg-slate-950/80 p-3 backdrop-blur-sm xl:hidden"
    >
      <button
        type="button"
        class="mb-2 self-start rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs font-medium text-slate-300"
        @click="mobilePane = 'analysis'"
      >
        Fermer
      </button>
      <div class="flex min-h-0 w-full flex-1">
        <CoachFeedbackPanel
          class="!w-full"
          :feedback="activeFeedback"
          :next-pending-id="nextPendingId"
          @sent-next="
            (id) => {
              selectFeedback(id);
              mobilePane = 'analysis';
            }
          "
          @sent="mobilePane = 'analysis'"
        />
      </div>
    </div>
  </div>
</template>
