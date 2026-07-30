<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import { formatCalendarFr, formatDateTimeFr } from '../../utils/formatDates';
import { messagingInitials } from '../../utils/messagingFormat';

const props = defineProps({
  task: {
    type: Object,
    required: true,
  },
  type: {
    type: String,
    default: 'daily',
  },
});

const urgency = computed(() => {
  if (props.task.feedback_status === 'coach_replied') {
    return { key: 'done', label: 'Traité', class: 'border-emerald-500/35 bg-emerald-500/10 text-emerald-300' };
  }
  if (props.task.has_submission) {
    return { key: 'high', label: 'À répondre', class: 'border-amber-500/40 bg-amber-500/15 text-amber-200' };
  }
  // Sans envoi athlète : jamais « en retard » (alerte dashboard dédiée).
  return { key: 'wait', label: 'En attente', class: 'border-slate-600 bg-slate-800/50 text-slate-400' };
});

const timeLeft = computed(() => {
  if (!props.task.due_at) {
    return props.type === 'weekly' ? 'Cette semaine' : 'Aujourd’hui';
  }
  const due = Date.parse(props.task.due_at);
  if (Number.isNaN(due)) {
    return formatDateTimeFr(props.task.due_at);
  }
  const diff = due - Date.now();
  if (diff < 0) {
    const hours = Math.ceil(Math.abs(diff) / 3600000);
    if (hours < 24) {
      return `Retard ${hours}h`;
    }
    return `Retard ${Math.ceil(hours / 24)}j`;
  }
  const hours = Math.ceil(diff / 3600000);
  if (hours < 24) {
    return `${hours}h restantes`;
  }
  return `${Math.ceil(hours / 24)}j restants`;
});

const typeLabel = computed(() =>
  props.type === 'weekly' ? 'Hebdomadaire' : 'Journalier',
);

const dateLabel = computed(() => {
  if (props.task.session_date) {
    return `Séance du ${formatCalendarFr(props.task.session_date, 'medium')}`;
  }
  if (props.task.period_week_start) {
    return `Semaine du ${formatCalendarFr(props.task.period_week_start, 'medium')}`;
  }
  return '';
});

const canOpen = computed(() => Boolean(props.task.session_feedback_id));

function open() {
  if (!canOpen.value) {
    return;
  }
  const filter = props.task.feedback_status === 'coach_replied' ? 'all' : 'pending';
  router.visit(`/feedbacks?feedback=${props.task.session_feedback_id}&filter=${filter}`);
}

const cardTone = computed(() => {
  if (urgency.value.key === 'critical') {
    return 'border-rose-500/35 bg-rose-950/20 shadow-[0_0_24px_rgba(244,63,94,0.08)]';
  }
  if (urgency.value.key === 'high') {
    return 'border-amber-500/30 bg-amber-950/15';
  }
  return 'border-slate-800/80 bg-slate-950/40';
});
</script>

<template>
  <motion.article
    :initial="{ opacity: 0, y: 10 }"
    :whileInView="{ opacity: 1, y: 0 }"
    :viewport="{ once: true, amount: 0.2 }"
    :transition="{ duration: 0.3 }"
    :whileHover="{ y: -2, scale: 1.01 }"
    class="flex min-w-[17rem] flex-col rounded-[18px] border p-4 shadow-lg backdrop-blur-sm transition duration-200 hover:shadow-[0_0_24px_rgba(59,130,246,0.12)] sm:min-w-0"
    :class="[cardTone, canOpen ? 'cursor-pointer' : '']"
    @click="open"
  >
    <div class="flex items-start gap-3">
      <div
        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700/80"
      >
        {{ messagingInitials(task.athlete?.name) }}
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <Link
            v-if="task.athlete"
            :href="`/athletes/${task.athlete_id}`"
            class="truncate text-sm font-semibold text-white hover:text-blue-300"
            @click.stop
          >
            {{ task.athlete.name }}
          </Link>
          <span
            class="rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
            :class="urgency.class"
          >
            {{ urgency.label }}
          </span>
        </div>
        <p class="mt-1 text-xs text-slate-500">
          <span class="text-slate-400">{{ typeLabel }}</span>
          <span v-if="dateLabel"> · {{ dateLabel }}</span>
        </p>
      </div>
    </div>

    <div class="mt-4 flex items-center justify-between gap-2">
      <span class="text-xs font-medium text-slate-500">{{ timeLeft }}</span>
      <span
        class="text-xs font-semibold"
        :class="canOpen ? 'text-blue-400' : 'text-slate-600'"
      >
        {{ canOpen ? 'Voir le retour →' : 'En attente d’envoi' }}
      </span>
    </div>
  </motion.article>
</template>
