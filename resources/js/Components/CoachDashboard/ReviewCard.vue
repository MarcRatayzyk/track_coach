<script setup>
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiIcon from '../UiIcon.vue';
import {
  athleteInitials,
  cardHover,
} from './dashboardUi';
import { formatCalendarFr } from '../../utils/formatDates';
import { isFeedbackOverdue } from '../../utils/feedbackUrgency';

const props = defineProps({
  task: { type: Object, required: true },
  kind: { type: String, default: 'daily' },
  weekLabel: { type: String, default: '' },
  today: { type: String, default: null },
});

function feedbackStatusBadge(task) {
  if (task.feedback_status === 'coach_replied') {
    return { label: 'Répondu', class: 'bg-emerald-950/60 text-emerald-300 border-emerald-500/30' };
  }
  if (task.has_submission) {
    return { label: 'Reçu', class: 'bg-blue-950/60 text-blue-300 border-blue-500/30' };
  }
  return {
    label: "N'a pas encore envoyé",
    class: 'bg-amber-950/60 text-amber-300 border-amber-500/40',
  };
}

const urgency = computed(() => {
  if (props.task.feedback_status === 'coach_replied') {
    return { label: 'Traité', tone: 'emerald', level: 0 };
  }
  if (isFeedbackOverdue({ ...props.task, _kind: props.kind }, props.today)) {
    return { label: 'En retard', tone: 'rose', level: 3 };
  }
  if (props.task.has_submission) {
    return { label: 'À répondre', tone: 'amber', level: 2 };
  }
  return { label: 'En attente', tone: 'slate', level: 1 };
});

const badge = computed(() => feedbackStatusBadge(props.task));

const remaining = computed(() => {
  // Sans envoi athlète : pas de libellé « en retard » (alerte dédiée à la place).
  if (!props.task.has_submission) return null;
  if (!props.task.due_at) return null;
  const due = new Date(props.task.due_at).getTime();
  if (Number.isNaN(due)) return null;
  const diff = due - Date.now();
  const absH = Math.abs(Math.round(diff / 3600000));
  if (diff < 0) {
    return `En retard de ${absH < 24 ? `${absH} h` : `${Math.round(absH / 24)} j`}`;
  }
  return absH < 24 ? `${absH} h restantes` : `${Math.round(absH / 24)} j restants`;
});

const borderClass = computed(() => {
  if (urgency.value.level >= 3) return 'border-rose-500/45 bg-rose-950/20 shadow-rose-950/20';
  if (urgency.value.level === 2) return 'border-amber-500/35 bg-amber-950/15';
  return 'border-slate-800/80 bg-slate-950/40';
});

const toneBadge = {
  rose: 'border-rose-500/40 bg-rose-500/15 text-rose-200',
  amber: 'border-amber-500/40 bg-amber-500/15 text-amber-200',
  emerald: 'border-emerald-500/40 bg-emerald-500/15 text-emerald-200',
  slate: 'border-slate-600/50 bg-slate-800/60 text-slate-300',
};

function openTask() {
  if (!props.task.session_feedback_id) return;
  const filter = props.task.feedback_status === 'coach_replied' ? 'all' : 'pending';
  router.visit(`/feedbacks?feedback=${props.task.session_feedback_id}&filter=${filter}`);
}
</script>

<template>
  <article
    class="flex min-h-[9.5rem] flex-col justify-between rounded-[1.15rem] border p-4 transition duration-200"
    :class="[
      borderClass,
      task.has_submission ? `${cardHover} cursor-pointer` : 'opacity-95',
    ]"
    @click="openTask"
  >
    <div>
      <div class="flex min-w-0 items-start justify-between gap-2 sm:gap-3">
        <div class="flex min-w-0 flex-1 items-center gap-3 overflow-hidden">
          <span
            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-blue-500/15 text-sm font-semibold text-blue-100"
          >
            {{ athleteInitials(task.athlete?.name) }}
          </span>
          <div class="min-w-0 flex-1 overflow-hidden">
            <Link
              v-if="task.athlete"
              :href="`/athletes/${task.athlete_id}`"
              class="block truncate text-sm font-semibold text-white hover:text-blue-300"
              @click.stop
            >
              {{ task.athlete.name }}
            </Link>
            <p class="mt-0.5 truncate text-xs text-slate-500">
              <template v-if="kind === 'weekly'">
                Hebdo · {{ weekLabel }}
              </template>
              <template v-else>
                Journalier
                <span v-if="task.session_date">
                  · {{ formatCalendarFr(task.session_date) }}
                </span>
              </template>
            </p>
          </div>
        </div>
        <span
          class="shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
          :class="toneBadge[urgency.tone]"
        >
          {{ urgency.label }}
        </span>
      </div>

      <div class="mt-3 flex flex-wrap items-center gap-2">
        <span
          class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium"
          :class="badge.class"
        >
          <UiIcon :name="kind === 'weekly' ? 'calendar' : 'list'" class="h-3 w-3" />
          {{ badge.label }}
        </span>
        <span v-if="remaining" class="text-[11px] text-slate-500">{{ remaining }}</span>
      </div>
    </div>

    <p
      class="mt-3 text-center text-xs font-semibold"
      :class="task.has_submission ? 'text-blue-300' : 'text-slate-500'"
    >
      {{ task.has_submission ? 'Voir le retour →' : 'En attente d’envoi' }}
    </p>
  </article>
</template>
