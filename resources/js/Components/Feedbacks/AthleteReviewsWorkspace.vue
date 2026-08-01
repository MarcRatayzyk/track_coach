<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ReviewCard from './ReviewCard.vue';
import SessionAnalysisPanel from './SessionAnalysisPanel.vue';
import AthleteReplyPanel from './AthleteReplyPanel.vue';
import EmptyState from './EmptyState.vue';

const props = defineProps({
  feedbacks: { type: Array, default: () => [] },
  activeFeedback: { type: Object, default: null },
  canSubmit: { type: Boolean, default: false },
  tab: { type: String, default: 'submit' },
  /** Empêche de quitter l’onglet d’envoi pendant compression / upload. */
  busy: { type: Boolean, default: false },
});

const emit = defineEmits(['update:tab']);

const mobilePane = ref(props.activeFeedback ? 'analysis' : 'list');

const activeTab = computed({
  get: () => (props.tab === 'history' ? 'history' : 'submit'),
  set: (value) => emit('update:tab', value),
});

const sortedFeedbacks = computed(() =>
  [...props.feedbacks].sort((a, b) =>
    String(b.submitted_at || '').localeCompare(String(a.submitted_at || '')),
  ),
);

watch(
  () => props.activeFeedback?.id,
  (id) => {
    if (id) {
      mobilePane.value = 'analysis';
      if (!props.busy && activeTab.value !== 'history') {
        activeTab.value = 'history';
      }
    }
  },
);

function setTab(tab) {
  if (props.busy && tab !== 'submit') {
    return;
  }
  activeTab.value = tab;
  if (tab === 'submit') {
    mobilePane.value = 'list';
  }
}

function selectFeedback(id) {
  router.get(`/feedbacks?feedback=${id}`, {}, { preserveState: true, preserveScroll: true });
  mobilePane.value = 'analysis';
}

function backToList() {
  mobilePane.value = 'list';
}

function openReplyPane() {
  mobilePane.value = 'reply';
}

function urgencyFor(item) {
  return item.status === 'coach_replied' ? 'done' : 'normal';
}
</script>

<template>
  <div class="space-y-3">
    <div>
      <h1 class="text-xl font-bold text-white sm:text-2xl">Retours de séance</h1>
      <p class="mt-0.5 max-w-2xl text-sm text-slate-400">
        Suivez vos retours, la séance réalisée et la réponse de votre coach.
      </p>
    </div>

    <div
      class="flex rounded-[14px] border border-slate-800 bg-slate-950/50 p-1"
      role="tablist"
      aria-label="Navigation retours"
    >
      <button
        type="button"
        role="tab"
        :aria-selected="activeTab === 'submit'"
        class="flex-1 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition duration-200"
        :class="
          activeTab === 'submit'
            ? 'bg-blue-600 text-white shadow-[0_0_16px_rgba(59,130,246,0.25)]'
            : 'text-slate-400 hover:bg-slate-900/80 hover:text-slate-200'
        "
        @click="setTab('submit')"
      >
        Faire un retour
      </button>
      <button
        type="button"
        role="tab"
        :aria-selected="activeTab === 'history'"
        :disabled="busy"
        class="flex-1 rounded-[10px] px-3 py-2.5 text-sm font-semibold transition duration-200"
        :class="
          activeTab === 'history'
            ? 'bg-blue-600 text-white shadow-[0_0_16px_rgba(59,130,246,0.25)]'
            : busy
              ? 'cursor-not-allowed text-slate-600'
              : 'text-slate-400 hover:bg-slate-900/80 hover:text-slate-200'
        "
        @click="setTab('history')"
      >
        Retours passés
        <span
          v-if="feedbacks.length"
          class="ml-1.5 tabular-nums opacity-80"
        >{{ feedbacks.length }}</span>
      </button>
    </div>

    <div v-show="activeTab === 'submit'" class="space-y-3">
      <slot v-if="canSubmit" name="submit-form" />
      <EmptyState
        v-else
        title="Aucune séance à renvoyer"
        description="Dès qu’une séance est éligible, vous pourrez envoyer un retour ici."
        tone="empty"
      />
    </div>

    <div
      v-show="activeTab === 'history'"
      class="flex min-h-[calc(100dvh-18rem)] flex-col gap-3 lg:h-[calc(100dvh-16rem)] lg:min-h-[36rem] lg:flex-row lg:gap-4"
    >
      <div
        class="min-h-0"
        :class="mobilePane === 'list' ? 'flex flex-1 lg:flex-none' : 'hidden lg:flex'"
      >
        <aside class="flex h-full min-h-0 w-full flex-col lg:w-[14rem] xl:w-[15rem]">
          <div class="tc-scrollbar min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
            <EmptyState
              v-if="!sortedFeedbacks.length"
              title="Aucun retour passé"
              description="Vos retours envoyés apparaîtront ici avec la réponse du coach."
              tone="empty"
            />
            <ReviewCard
              v-for="item in sortedFeedbacks"
              :key="item.id"
              :item="item"
              :selected="item.id === activeFeedback?.id"
              :urgency="urgencyFor(item)"
              mode="athlete"
              @select="selectFeedback"
            />
          </div>
        </aside>
      </div>

      <div
        class="min-h-0 min-w-0 flex-1"
        :class="mobilePane === 'analysis' ? 'flex' : 'hidden lg:flex'"
      >
        <SessionAnalysisPanel
          mode="athlete"
          :feedback="activeFeedback"
          @back="backToList"
        />
      </div>

      <div
        class="min-h-0"
        :class="mobilePane === 'reply' ? 'flex flex-1' : 'hidden xl:flex'"
      >
        <AthleteReplyPanel :feedback="activeFeedback" />
      </div>
    </div>

    <div
      v-if="activeTab === 'history' && activeFeedback"
      class="fixed bottom-20 right-4 z-30 xl:hidden"
    >
      <button
        type="button"
        class="rounded-full bg-gradient-to-r from-blue-600 to-blue-500 px-4 py-3 text-sm font-semibold text-white shadow-[0_0_24px_rgba(59,130,246,0.35)]"
        @click="openReplyPane"
      >
        Voir la réponse
      </button>
    </div>

    <div
      v-if="activeTab === 'history' && mobilePane === 'reply'"
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
        <AthleteReplyPanel class="!w-full" :feedback="activeFeedback" />
      </div>
    </div>
  </div>
</template>
