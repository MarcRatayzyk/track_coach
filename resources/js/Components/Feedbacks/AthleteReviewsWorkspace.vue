<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AthleteKPIBar from './AthleteKPIBar.vue';
import ReviewSidebar from './ReviewSidebar.vue';
import SessionAnalysisPanel from './SessionAnalysisPanel.vue';
import AthleteReplyPanel from './AthleteReplyPanel.vue';

const props = defineProps({
  feedbacks: { type: Array, default: () => [] },
  activeFeedback: { type: Object, default: null },
  canSubmit: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Nouveau retour' },
  showSubmitForm: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle-submit']);

const listFilter = ref('all');
const search = ref('');
const mobilePane = ref(props.activeFeedback ? 'analysis' : 'list');

watch(
  () => props.activeFeedback?.id,
  (id) => {
    if (id) mobilePane.value = 'analysis';
  },
);

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
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-white">Retours de séance</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-400">
          Suivez vos retours, la séance réalisée et la réponse de votre coach.
        </p>
      </div>
      <button
        v-if="canSubmit"
        type="button"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-500"
        @click="emit('toggle-submit')"
      >
        {{ showSubmitForm ? 'Annuler' : submitLabel }}
      </button>
    </div>

    <slot name="submit-form" />

    <AthleteKPIBar :feedbacks="feedbacks" />

    <div
      class="flex min-h-[calc(100dvh-14rem)] flex-col gap-3 lg:h-[calc(100dvh-13rem)] lg:min-h-[36rem] lg:flex-row lg:gap-4"
    >
      <div
        class="min-h-0"
        :class="mobilePane === 'list' ? 'flex flex-1 lg:flex-none' : 'hidden lg:flex'"
      >
        <ReviewSidebar
          mode="athlete"
          :feedbacks="feedbacks"
          :active-id="activeFeedback?.id ?? null"
          :filter="listFilter"
          :search="search"
          @update:filter="listFilter = $event"
          @update:search="search = $event"
          @select="selectFeedback"
        />
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
      v-if="activeFeedback"
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
      v-if="mobilePane === 'reply'"
      class="fixed inset-0 z-40 flex bg-slate-950/80 p-3 backdrop-blur-sm xl:hidden"
    >
      <div class="relative flex min-h-0 w-full flex-1">
        <button
          type="button"
          class="absolute left-3 top-3 z-10 rounded-lg border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-300"
          @click="mobilePane = 'analysis'"
        >
          Fermer
        </button>
        <AthleteReplyPanel class="!w-full" :feedback="activeFeedback" />
      </div>
    </div>
  </div>
</template>
