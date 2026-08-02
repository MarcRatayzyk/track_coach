<script setup>
import { useI18n } from 'vue-i18n';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiIcon from '../UiIcon.vue';
import { athleteInitials, cardHover } from './dashboardUi';

const { t } = useI18n();

const props = defineProps({
  session: { type: Object, required: true },
});

const progressBadge = computed(() => {
  const status = props.session.progress_status;
  if (status === 'done') {
    return {
      label: t('app.coachDash.sessionDone'),
      class: 'border-emerald-500/40 bg-emerald-500/15 text-emerald-200',
    };
  }
  if (status === 'in_progress') {
    return {
      label: t('app.coachDash.sessionInProgress'),
      class: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
    };
  }
  return {
    label: t('app.coachDash.sessionNotStarted'),
    class: 'border-slate-600/50 bg-slate-800/60 text-slate-300',
  };
});

const feedbackBadge = computed(() => {
  if (props.session.has_feedback) {
    return {
      label: t('app.coachDash.feedbackSent'),
      class: 'bg-blue-950/60 text-blue-300 border-blue-500/30',
    };
  }
  return {
    label: t('app.coachDash.feedbackNotSent'),
    class: 'bg-amber-950/60 text-amber-300 border-amber-500/40',
  };
});

const borderClass = computed(() => {
  if (props.session.progress_status === 'done') {
    return 'border-emerald-500/30 bg-emerald-950/10';
  }
  if (props.session.progress_status === 'in_progress') {
    return 'border-amber-500/35 bg-amber-950/15';
  }
  return 'border-slate-800/80 bg-slate-950/40';
});

const subtitle = computed(() => {
  const parts = [];
  if (props.session.session_label) {
    parts.push(props.session.session_label);
  }
  if (props.session.program_name) {
    parts.push(props.session.program_name);
  }
  return parts.join(' · ') || t('app.coachDash.todaySessionFallback');
});

function openCard() {
  if (props.session.session_feedback_id) {
    router.visit(`/feedbacks?feedback=${props.session.session_feedback_id}&filter=pending`);
    return;
  }
  router.visit(`/athletes/${props.session.athlete_id}`);
}
</script>

<template>
  <article
    class="flex min-h-[9.5rem] flex-col justify-between rounded-[1.15rem] border p-4 transition duration-200"
    :class="[borderClass, cardHover, 'cursor-pointer']"
    @click="openCard"
  >
    <div>
      <div class="flex min-w-0 items-start justify-between gap-2 sm:gap-3">
        <div class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
          <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-500/15 text-sm font-semibold text-blue-100"
          >
            {{ athleteInitials(session.athlete?.name) }}
          </span>
          <div class="min-w-0 flex-1 overflow-hidden">
            <Link
              v-if="session.athlete"
              :href="`/athletes/${session.athlete_id}`"
              class="block truncate text-sm font-semibold text-white hover:text-blue-300"
              @click.stop
            >
              {{ session.athlete.name }}
            </Link>
            <p class="mt-0.5 truncate text-xs text-slate-500">
              {{ subtitle }}
            </p>
          </div>
        </div>
        <span
          class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
          :class="progressBadge.class"
        >
          {{ progressBadge.label }}
        </span>
      </div>

      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span
          class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium"
          :class="feedbackBadge.class"
        >
          <UiIcon name="list" class="h-3 w-3" />
          {{ feedbackBadge.label }}
        </span>
      </div>
    </div>

    <p
      class="mt-3 text-center text-xs font-semibold"
      :class="session.has_feedback ? 'text-blue-300' : 'text-slate-500'"
    >
      {{ session.has_feedback ? t('app.coachDash.seeFeedback') : t('app.coachDash.waitingSend') }}
    </p>
  </article>
</template>
