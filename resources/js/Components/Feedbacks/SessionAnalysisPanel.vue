<script setup>
import { useI18n } from 'vue-i18n';
import { computed, ref, watch } from 'vue';
import { motion } from 'motion-v';
import { formatCalendarFr } from '../../utils/formatDates';
import { messagingInitials } from '../../utils/messagingFormat';
import SessionComparison from './SessionComparison.vue';
import AthleteCommentCard from './AthleteCommentCard.vue';
import VideoFeedbackSlider from '../VideoFeedbackSlider.vue';
import EmptyState from './EmptyState.vue';
import FeedbackFrequencyPill from './FeedbackFrequencyPill.vue';
const { t } = useI18n();

const props = defineProps({
  feedback: { type: Object, default: null },
  mode: { type: String, default: 'coach' },
});

defineEmits(['back']);

const compareMode = ref(false);
const compareLeft = ref(0);
const compareRight = ref(1);

const videos = computed(() => props.feedback?.videos || []);
const canCompare = computed(() => videos.value.length >= 2);

const headerTitle = computed(() => {
  if (!props.feedback) return '';
  return props.mode === 'athlete'
    ? props.feedback.session_label || t('app.feedbacks.session')
    : props.feedback.athlete_name;
});

const headerAvatar = computed(() => headerTitle.value);

watch(
  () => props.feedback?.id,
  () => {
    compareMode.value = false;
    compareLeft.value = 0;
    compareRight.value = Math.min(1, Math.max(0, videos.value.length - 1));
  },
);
</script>

<template>
  <section
    class="flex min-h-0 min-w-0 flex-1 flex-col rounded-[18px] border border-slate-800/80 bg-slate-900/50 shadow-xl backdrop-blur-sm"
  >
    <motion.div
      v-if="feedback"
      :key="feedback.id"
      :initial="{ opacity: 0, y: 8 }"
      :animate="{ opacity: 1, y: 0 }"
      :transition="{ duration: 0.2 }"
      class="flex min-h-0 flex-1 flex-col"
    >
      <header class="border-b border-slate-800/80 p-4 sm:p-5">
        <button
          type="button"
          class="mb-3 text-xs font-medium text-blue-400 transition hover:text-blue-300 lg:hidden"
          @click="$emit('back')"
        >
          ← Retours
        </button>
        <div class="flex items-start gap-3">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700/80"
          >
            {{ messagingInitials(headerAvatar) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="truncate text-lg font-bold text-white">{{ headerTitle }}</h2>
              <FeedbackFrequencyPill :frequency="feedback.feedback_frequency" />
            </div>
            <p class="mt-0.5 text-sm text-slate-400">
              {{ formatCalendarFr(feedback.session_date) }}
              <template v-if="mode === 'coach' && feedback.session_label">
                · {{ feedback.session_label }}
              </template>
            </p>
            <p
              v-if="feedback.session_notes"
              class="mt-2 text-xs leading-relaxed text-slate-500"
            >
              <span class="font-medium text-slate-400">Objectif · </span>
              {{ feedback.session_notes }}
            </p>
          </div>
        </div>
      </header>

      <div class="min-h-0 flex-1 space-y-5 overflow-y-auto p-4 [scrollbar-width:thin] sm:p-5">
        <div>
          <h3 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
            Analyse de la séance
          </h3>
          <SessionComparison
            v-if="feedback.session_exercises?.length"
            :exercises="feedback.session_exercises"
          />
          <EmptyState
            v-else
            :title="t('app.feedbacks.noExercisesCompare')"
            :description="t('app.feedbacks.noExercisesCompareDesc')"
            class="!min-h-[8rem] !py-6"
          />
        </div>

        <AthleteCommentCard :feedback="feedback" :mode="mode" />

        <div>
          <div class="mb-3 flex items-center justify-between gap-3">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">
              Galerie vidéo
            </h3>
            <button
              v-if="canCompare"
              type="button"
              class="rounded-full border px-2.5 py-1 text-[11px] font-medium transition duration-200"
              :class="
                compareMode
                  ? 'border-blue-500/60 bg-blue-600/20 text-white'
                  : 'border-slate-700 text-slate-400 hover:border-slate-600 hover:text-slate-200'
              "
              @click="compareMode = !compareMode"
            >
              {{ compareMode ? t('app.feedbacks.simpleView') : t('app.feedbacks.compareVideos') }}
            </button>
          </div>

          <template v-if="videos.length">
            <div v-if="compareMode" class="grid gap-3 lg:grid-cols-2">
              <div
                v-for="(side, sideIndex) in [compareLeft, compareRight]"
                :key="sideIndex"
                class="rounded-[14px] border border-slate-800 bg-slate-950/40 p-2"
              >
                <select
                  class="mb-2 w-full rounded-lg border border-slate-700 bg-slate-950 px-2 py-1.5 text-xs text-slate-200"
                  :value="side"
                  @change="
                    sideIndex === 0
                      ? (compareLeft = Number($event.target.value))
                      : (compareRight = Number($event.target.value))
                  "
                >
                  <option
                    v-for="(video, index) in videos"
                    :key="video.id"
                    :value="index"
                  >
                    Vidéo {{ index + 1 }}
                    <template v-if="video.series?.exercise_name">
                      — {{ video.series.exercise_name }}
                    </template>
                  </option>
                </select>
                <VideoFeedbackSlider :videos="[videos[side]]" />
              </div>
            </div>
            <VideoFeedbackSlider v-else :videos="videos" />
          </template>
          <EmptyState
            v-else
            :title="t('app.feedbacks.noVideo')"
            :description="t('app.feedbacks.noVideoDesc')"
            class="!min-h-[8rem] !py-6"
          />
        </div>
      </div>
    </motion.div>

    <EmptyState
      v-else
      :title="t('app.feedbacks.selectFeedback')"
      :description="
        mode === 'athlete'
          ? t('app.feedbacks.selectFeedbackAthlete')
          : t('app.feedbacks.selectFeedbackCoach')
      "
      class="m-4 !min-h-[16rem] flex-1"
    />
  </section>
</template>
