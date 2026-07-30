<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { messagingInitials } from '../../utils/messagingFormat';
import { formatCalendarFr } from '../../utils/formatDates';

const props = defineProps({
  context: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const initials = computed(() => messagingInitials(props.context?.name));

function formatDate(value) {
  if (!value) {
    return '—';
  }
  try {
    return formatCalendarFr(value);
  } catch {
    return value;
  }
}
</script>

<template>
  <aside
    class="flex h-full min-h-0 w-full flex-col overflow-hidden rounded-[18px] border border-slate-800 bg-slate-900/50 shadow-xl backdrop-blur-md xl:w-[300px] xl:shrink-0"
  >
    <div class="flex items-center justify-between border-b border-slate-800/80 px-4 py-3">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Contexte</h2>
      <button
        type="button"
        class="rounded-full p-1.5 text-slate-500 transition hover:bg-slate-800 hover:text-white xl:hidden"
        aria-label="Fermer"
        @click="emit('close')"
      >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <div v-if="context" class="tc-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
      <div class="rounded-[18px] border border-slate-800 bg-gradient-to-b from-slate-900/80 to-slate-950/60 p-4 text-center">
        <div
          class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-lg font-semibold text-white ring-1 ring-slate-700 shadow-[0_0_0_4px_rgba(59,130,246,0.12)]"
        >
          {{ initials }}
        </div>
        <h3 class="mt-3 text-base font-semibold text-white">{{ context.name }}</h3>
        <p class="mt-1 text-xs text-slate-500">
          {{ context.category_label || context.category || 'Catégorie non renseignée' }}
          <template v-if="context.level_label"> · {{ context.level_label }}</template>
        </p>
        <Link
          :href="context.profile_url"
          class="mt-3 inline-flex rounded-[12px] border border-blue-500/30 bg-blue-600/10 px-3 py-1.5 text-xs font-semibold text-blue-300 transition hover:bg-blue-600/20"
        >
          Voir le profil
        </Link>
      </div>

      <section class="grid grid-cols-2 gap-2">
        <div class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
          <p class="text-[10px] uppercase tracking-wide text-slate-500">Poids</p>
          <p class="mt-1 text-sm font-semibold text-white">
            {{ context.weight_kg != null ? `${context.weight_kg} kg` : '—' }}
          </p>
        </div>
        <div class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
          <p class="text-[10px] uppercase tracking-wide text-slate-500">Objectif</p>
          <p class="mt-1 truncate text-sm font-semibold text-white">
            {{ context.goal || '—' }}
          </p>
        </div>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Compétition</p>
        <template v-if="context.competition">
          <p class="mt-1 text-sm font-semibold text-white">{{ context.competition.name }}</p>
          <p class="mt-0.5 text-xs text-slate-400">
            {{ formatDate(context.competition.date) }}
            <template v-if="context.competition.location"> · {{ context.competition.location }}</template>
          </p>
        </template>
        <p v-else class="mt-1 text-sm text-slate-500">Aucune à venir</p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Dernière séance</p>
        <template v-if="context.last_session">
          <p class="mt-1 text-sm font-semibold text-white">
            {{ context.last_session.label || 'Séance' }}
          </p>
          <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(context.last_session.date) }}</p>
        </template>
        <p v-else class="mt-1 text-sm text-slate-500">Pas encore de séance</p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Volume semaine</p>
        <div class="mt-2 flex items-end justify-between gap-2">
          <p class="text-2xl font-bold text-white">
            {{ context.week_volume?.sessions_count ?? 0 }}
            <span class="text-sm font-medium text-slate-500">séances</span>
          </p>
          <p v-if="context.week_volume?.adherence_percentage != null" class="text-xs text-blue-300">
            {{ context.week_volume.adherence_percentage }}% adhérence
          </p>
        </div>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">PR récents</p>
        <template v-if="context.latest_pr">
          <div class="mt-2 grid grid-cols-3 gap-2 text-center">
            <div>
              <p class="text-[10px] text-slate-500">Squat</p>
              <p class="text-sm font-semibold text-white">{{ context.latest_pr.squat || '—' }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">Bench</p>
              <p class="text-sm font-semibold text-white">{{ context.latest_pr.bench || '—' }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-500">DL</p>
              <p class="text-sm font-semibold text-white">{{ context.latest_pr.deadlift || '—' }}</p>
            </div>
          </div>
          <p class="mt-2 text-center text-xs text-slate-500">
            Total {{ context.latest_pr.total }} kg
          </p>
        </template>
        <p v-else class="mt-1 text-sm text-slate-500">Aucun PR enregistré</p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Planning</p>
        <template v-if="context.program">
          <p class="mt-1 text-sm font-semibold text-white">{{ context.program.name || 'Programme actif' }}</p>
          <p class="mt-0.5 text-xs text-slate-400">
            {{ formatDate(context.program.date_start) }}
            <template v-if="context.program.date_end"> → {{ formatDate(context.program.date_end) }}</template>
          </p>
        </template>
        <p v-else class="mt-1 text-sm text-slate-500">Aucun programme actif</p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Objectifs</p>
        <p class="mt-1 text-sm leading-relaxed text-slate-300">
          {{ context.goal || context.competition?.goal || 'Aucun objectif renseigné' }}
        </p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Notes coach</p>
        <p class="mt-1 whitespace-pre-wrap text-sm leading-relaxed text-slate-300">
          {{ context.coach_notes || context.bio || 'Aucune note pour le moment.' }}
        </p>
      </section>

      <section class="rounded-[14px] border border-slate-800 bg-slate-950/50 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Vidéos récentes</p>
        <ul v-if="context.recent_videos?.length" class="mt-2 space-y-2">
          <li
            v-for="video in context.recent_videos"
            :key="video.id"
          >
            <Link
              :href="`/feedbacks?feedback=${video.id}`"
              class="flex items-center justify-between gap-2 rounded-[12px] border border-slate-800 bg-slate-900/60 px-3 py-2 text-left transition hover:border-blue-500/30 hover:bg-slate-900"
            >
              <span class="min-w-0">
                <span class="block truncate text-sm text-white">
                  {{ video.session_label || 'Retour vidéo' }}
                </span>
                <span class="text-[11px] text-slate-500">{{ formatDate(video.session_date) }}</span>
              </span>
              <span class="shrink-0 text-[11px] text-blue-400">
                {{ video.videos_count }} vid.
              </span>
            </Link>
          </li>
        </ul>
        <p v-else class="mt-1 text-sm text-slate-500">Aucun retour vidéo récent</p>
      </section>

      <section class="rounded-[14px] border border-dashed border-slate-800 bg-slate-950/30 p-3">
        <p class="text-[10px] uppercase tracking-wide text-slate-500">Fichiers partagés</p>
        <p class="mt-1 text-sm text-slate-500">
          Les pièces jointes de la conversation apparaîtront ici.
        </p>
      </section>
    </div>

    <div v-else class="flex flex-1 items-center justify-center p-6 text-center text-sm text-slate-500">
      Sélectionne une conversation pour afficher le contexte athlète.
    </div>
  </aside>
</template>
