<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { motion } from 'motion-v';
import TodaySessionCard from './TodaySessionCard.vue';
import SectionHeader from './SectionHeader.vue';
import { cardShell } from './dashboardUi';

const { t } = useI18n();

const props = defineProps({
  sessions: { type: Array, default: () => [] },
});

const kindFilter = ref('all');

const filters = computed(() => [
  { key: 'all', label: t('app.coachDash.filterAll') },
  { key: 'not_started', label: t('app.coachDash.sessionNotStarted') },
  { key: 'in_progress', label: t('app.coachDash.sessionInProgress') },
  { key: 'done', label: t('app.coachDash.sessionDone') },
  { key: 'sent', label: t('app.coachDash.sent') },
  { key: 'not_sent', label: t('app.coachDash.notSent') },
]);

function matchesFilter(session, key) {
  if (key === 'all') return true;
  if (key === 'not_started' || key === 'in_progress' || key === 'done') {
    return session.progress_status === key;
  }
  if (key === 'sent') return Boolean(session.has_feedback);
  if (key === 'not_sent') return !session.has_feedback;
  return true;
}

const allItems = computed(() => props.sessions ?? []);

const counts = computed(() => {
  const list = allItems.value;
  return {
    all: list.length,
    not_started: list.filter((s) => s.progress_status === 'not_started').length,
    in_progress: list.filter((s) => s.progress_status === 'in_progress').length,
    done: list.filter((s) => s.progress_status === 'done').length,
    sent: list.filter((s) => s.has_feedback).length,
    not_sent: list.filter((s) => !s.has_feedback).length,
  };
});

const items = computed(() =>
  allItems.value.filter((s) => matchesFilter(s, kindFilter.value)),
);

const emptyLabels = computed(() => ({
  all: '',
  not_started: t('app.coachDash.sessionNotStartedStatus'),
  in_progress: t('app.coachDash.sessionInProgressStatus'),
  done: t('app.coachDash.sessionDoneStatus'),
  sent: t('app.coachDash.sentStatus'),
  not_sent: t('app.coachDash.notSentStatus'),
}));
</script>

<template>
  <section :class="[cardShell, 'min-w-0 overflow-hidden p-4 sm:p-5']">
    <SectionHeader
      :eyebrow="t('app.coachDash.todayEyebrow')"
      :title="t('app.coachDash.todaySessions')"
    >
      <template #actions>
        <Link
          href="/feedbacks"
          class="rounded-xl border border-blue-500/40 bg-blue-500/10 px-2.5 py-1.5 text-[11px] font-semibold text-blue-200 transition hover:bg-blue-500/20 sm:px-3 sm:text-xs"
        >
          <span class="sm:hidden">{{ t('app.coachDash.filterAll') }}</span>
          <span class="hidden sm:inline">{{ t('app.coachDash.seeAllFeedbacks') }}</span>
        </Link>
      </template>
    </SectionHeader>

    <div class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:thin]">
      <button
        v-for="f in filters"
        :key="f.key"
        type="button"
        class="shrink-0 rounded-full border px-3 py-1.5 text-xs font-medium transition duration-200"
        :class="
          kindFilter === f.key
            ? 'border-blue-500/50 bg-blue-600/20 text-blue-100 shadow-[0_0_16px_rgba(59,130,246,0.18)]'
            : 'border-slate-700 bg-slate-950/40 text-slate-400 hover:border-slate-600 hover:text-slate-200'
        "
        @click="kindFilter = f.key"
      >
        {{ f.label }}
        <span
          class="ml-1 tabular-nums"
          :class="kindFilter === f.key ? 'text-blue-200/80' : 'text-slate-500'"
        >
          {{ counts[f.key] }}
        </span>
      </button>
    </div>

    <p
      v-if="!items.length"
      class="mt-6 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
    >
      <template v-if="kindFilter === 'all' || !allItems.length">
        {{ t('app.coachDash.noTodaySessionsAll') }}
      </template>
      <template v-else>
        {{ t('app.coachDash.noTodaySessions', { filter: emptyLabels[kindFilter] }) }}
      </template>
    </p>

    <div
      v-else
      class="tc-scrollbar mt-5 grid max-h-[28rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3"
    >
      <motion.div
        v-for="(session, index) in items"
        :key="`${session.athlete_id}-${session.session_date}`"
        :initial="{ opacity: 0, y: 10 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: Math.min(index, 8) * 0.03, duration: 0.3 }"
      >
        <TodaySessionCard :session="session" />
      </motion.div>
    </div>
  </section>
</template>
