<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  variant: {
    type: String,
    default: 'daily',
    validator: (v) => ['daily', 'weekly'].includes(v),
  },
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  breakdown: {
    type: Object,
    default: () => ({ pending: [], submitted: [] }),
  },
});

const emit = defineEmits(['close']);

const pending = computed(() => props.breakdown?.pending ?? []);
const submitted = computed(() => props.breakdown?.submitted ?? []);

function close() {
  emit('close');
}

function statusLabel(item) {
  if (item.feedback_status === 'coach_replied') {
    return 'Répondu par le coach';
  }
  if (item.session_feedback_id) {
    return 'Envoyé · en attente de réponse';
  }
  if (item.is_overdue) {
    return 'En retard';
  }
  return "À faire aujourd'hui";
}

function dateLabel(item) {
  if (item.session_date) {
    return `Séance du ${formatCalendarFr(item.session_date)}`;
  }
  if (item.period_week_start) {
    return `Semaine du ${formatCalendarFr(item.period_week_start)}`;
  }
  return '';
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <div
        class="tc-scrollbar max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-base font-semibold text-white">{{ title }}</h2>
            <p v-if="subtitle" class="mt-1 text-sm text-slate-400">{{ subtitle }}</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            ✕
          </button>
        </div>

        <section class="mt-6">
          <h3 class="text-sm font-semibold text-amber-300">
            Doit encore envoyer un retour
            <span class="font-normal text-slate-500">({{ pending.length }})</span>
          </h3>
          <p
            v-if="pending.length === 0"
            class="mt-3 rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-6 text-center text-sm text-slate-500"
          >
            Tous les athlètes attendus ont envoyé leur retour.
          </p>
          <ul v-else class="mt-3 space-y-2">
            <li
              v-for="item in pending"
              :key="`pending-${item.athlete_id}-${item.session_date ?? item.period_week_start}`"
              class="rounded-xl border border-amber-500/25 bg-amber-950/15 px-4 py-3"
            >
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <Link
                    v-if="item.athlete_id"
                    :href="`/athletes/${item.athlete_id}`"
                    class="text-sm font-semibold text-white hover:text-blue-300"
                  >
                    {{ item.athlete_name ?? 'Athlète' }}
                  </Link>
                  <p v-if="dateLabel(item)" class="mt-0.5 text-xs text-slate-400">
                    {{ dateLabel(item) }}
                  </p>
                </div>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="
                    item.is_overdue
                      ? 'bg-red-950/60 text-red-300'
                      : 'bg-amber-950/60 text-amber-300'
                  "
                >
                  {{ statusLabel(item) }}
                </span>
              </div>
            </li>
          </ul>
        </section>

        <section class="mt-8 border-t border-slate-800 pt-6">
          <h3 class="text-sm font-semibold text-emerald-300">
            Retour déjà envoyé
            <span class="font-normal text-slate-500">({{ submitted.length }})</span>
          </h3>
          <p
            v-if="submitted.length === 0"
            class="mt-3 rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-6 text-center text-sm text-slate-500"
          >
            Aucun retour reçu pour cette période.
          </p>
          <ul v-else class="mt-3 space-y-2">
            <li
              v-for="item in submitted"
              :key="`submitted-${item.athlete_id}-${item.session_feedback_id}`"
              class="rounded-xl border border-emerald-500/25 bg-emerald-950/15 px-4 py-3"
            >
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div>
                  <Link
                    v-if="item.athlete_id"
                    :href="`/athletes/${item.athlete_id}`"
                    class="text-sm font-semibold text-white hover:text-blue-300"
                  >
                    {{ item.athlete_name ?? 'Athlète' }}
                  </Link>
                  <p v-if="dateLabel(item)" class="mt-0.5 text-xs text-slate-400">
                    {{ dateLabel(item) }}
                  </p>
                </div>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="
                    item.feedback_status === 'coach_replied'
                      ? 'bg-emerald-950/60 text-emerald-300'
                      : 'bg-blue-950/60 text-blue-300'
                  "
                >
                  {{ statusLabel(item) }}
                </span>
              </div>
              <Link
                v-if="item.session_feedback_id"
                :href="`/feedbacks?feedback=${item.session_feedback_id}`"
                class="mt-2 inline-block text-xs font-medium text-blue-400 hover:text-blue-300"
              >
                Voir le retour →
              </Link>
            </li>
          </ul>
        </section>

        <div class="mt-6 flex justify-end">
          <Link
            href="/feedbacks"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
            @click="close"
          >
            Ouvrir tous les retours
          </Link>
        </div>
      </div>
    </div>
  </Teleport>
</template>
