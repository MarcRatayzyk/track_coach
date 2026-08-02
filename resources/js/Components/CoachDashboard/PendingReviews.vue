<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { motion } from 'motion-v';
import ReviewCard from './ReviewCard.vue';
import SectionHeader from './SectionHeader.vue';
import { cardShell } from './dashboardUi';
import { isFeedbackOverdue, todayKey as resolveToday, dateKey, weekStartMonday } from '../../utils/feedbackUrgency';
const { t } = useI18n();

const props = defineProps({
  dailyTasks: { type: Array, default: () => [] },
  weeklyTasks: { type: Array, default: () => [] },
  today: { type: String, default: null },
  weekLabel: { type: String, default: '' },
});

const kindFilter = ref('all');

const filters = [
  { key: 'all', label: 'Tous' },
  { key: 'today', label: "Aujourd'hui" },
  { key: 'daily', label: 'Journaliers' },
  { key: 'weekly', label: 'Hebdomadaires' },
  { key: 'overdue', label: 'En retard' },
  { key: 'not_sent', label: t('app.coachDash.notSent') },
  { key: 'sent', label: t('app.coachDash.sent') },
];

function todayStr() {
  return resolveToday(props.today);
}

function isToday(task) {
  const today = todayStr();
  if (task._kind === 'daily') {
    return dateKey(task.session_date) === today;
  }
  // Hebdo de la semaine en cours
  const weekStart = weekStartMonday(today);
  return dateKey(task.period_week_start) === weekStart;
}

function isNotSent(task) {
  return !task.has_submission;
}

function isSent(task) {
  return Boolean(task.has_submission) && task.feedback_status !== 'coach_replied';
}

function matchesFilter(task, key) {
  if (key === 'all') return true;
  if (key === 'today') return isToday(task);
  if (key === 'daily') return task._kind === 'daily';
  if (key === 'weekly') return task._kind === 'weekly';
  if (key === 'overdue') return isFeedbackOverdue(task, todayStr());
  if (key === 'not_sent') return isNotSent(task);
  if (key === 'sent') return isSent(task);
  return true;
}

function rank(task) {
  if (task.feedback_status === 'coach_replied') return 3;
  if (isFeedbackOverdue(task, todayStr())) return 0;
  if (task.has_submission) return 1;
  return 2;
}

const allItems = computed(() => {
  const daily = (props.dailyTasks ?? []).map((t) => ({
    ...t,
    _kind: 'daily',
    feedback_frequency: t.feedback_frequency ?? 'daily',
  }));
  const weekly = (props.weeklyTasks ?? []).map((t) => ({
    ...t,
    _kind: 'weekly',
    feedback_frequency: t.feedback_frequency ?? 'weekly',
  }));

  return [...daily, ...weekly]
    .filter((t) => t.feedback_status !== 'coach_replied')
    .sort((a, b) => {
      const rankDiff = rank(a) - rank(b);
      if (rankDiff !== 0) return rankDiff;
      return String(a.session_date || a.period_week_start || '').localeCompare(
        String(b.session_date || b.period_week_start || ''),
      );
    });
});

const counts = computed(() => {
  const list = allItems.value;
  return {
    all: list.length,
    today: list.filter((t) => isToday(t)).length,
    daily: list.filter((t) => t._kind === 'daily').length,
    weekly: list.filter((t) => t._kind === 'weekly').length,
    overdue: list.filter((t) => isFeedbackOverdue(t, todayStr())).length,
    not_sent: list.filter((t) => isNotSent(t)).length,
    sent: list.filter((t) => isSent(t)).length,
  };
});

const items = computed(() =>
  allItems.value.filter((t) => matchesFilter(t, kindFilter.value)),
);

const emptyLabels = {
  all: '',
  today: "d'aujourd'hui",
  daily: 'journalier',
  weekly: 'hebdomadaire',
  overdue: 'en retard',
  not_sent: t('app.coachDash.notSentStatus'),
  sent: t('app.coachDash.sentStatus'),
};
</script>

<template>
  <section :class="[cardShell, 'min-w-0 overflow-hidden p-4 sm:p-5']">
    <SectionHeader
      eyebrow="À traiter"
      :title="t('app.coachDash.pendingReviews')"
    >
      <template #actions>
        <Link
          href="/feedbacks?filter=overdue"
          class="rounded-xl border border-blue-500/40 bg-blue-500/10 px-2.5 py-1.5 text-[11px] font-semibold text-blue-200 transition hover:bg-blue-500/20 sm:px-3 sm:text-xs"
        >
          <span class="sm:hidden">Tous</span>
          <span class="hidden sm:inline">Voir tous les retours</span>
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
      Aucun retour {{ emptyLabels[kindFilter] }} à traiter.
    </p>

    <div
      v-else
      class="tc-scrollbar mt-5 grid max-h-[28rem] gap-3 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3"
    >
      <motion.div
        v-for="(task, index) in items"
        :key="`${task._kind}-${task.id}`"
        :initial="{ opacity: 0, y: 10 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: Math.min(index, 8) * 0.03, duration: 0.3 }"
      >
        <ReviewCard
          :task="task"
          :kind="task._kind"
          :week-label="weekLabel"
          :today="today"
        />
      </motion.div>
    </div>
  </section>
</template>
