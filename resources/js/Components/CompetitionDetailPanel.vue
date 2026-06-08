<script setup>
import { Link } from '@inertiajs/vue3';
import MatchPlanDisplay from './MatchPlanDisplay.vue';
import { formatCalendarFr } from '../utils/formatDates';

defineProps({
  competition: {
    type: Object,
    required: true,
  },
  showBack: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['back']);
</script>

<template>
  <div>
    <button
      v-if="showBack"
      type="button"
      class="mb-4 text-sm font-medium text-blue-400 hover:text-blue-300"
      @click="$emit('back')"
    >
      ← Retour au calendrier
    </button>

    <h3 class="text-lg font-semibold text-white">{{ competition.name }}</h3>
    <p class="mt-1 text-sm text-slate-400">
      {{ formatCalendarFr(competition.competition_date, 'medium') }}
    </p>

    <dl class="mt-6 space-y-4 text-sm">
      <div>
        <dt class="text-slate-500">Qui</dt>
        <dd class="mt-1 font-medium text-slate-200">
          <Link
            v-if="competition.athlete_id"
            :href="`/athletes/${competition.athlete_id}`"
            class="text-blue-400 hover:text-blue-300"
          >
            {{ competition.athlete?.name ?? 'Athlète' }}
          </Link>
          <span v-else>—</span>
        </dd>
      </div>
      <div>
        <dt class="text-slate-500">Où</dt>
        <dd class="mt-1 text-slate-200">{{ competition.location?.trim() || '—' }}</dd>
      </div>
      <div>
        <dt class="text-slate-500">Objectif</dt>
        <dd class="mt-1 text-slate-200">{{ competition.goal?.trim() || '—' }}</dd>
      </div>
      <div>
        <dt class="text-slate-500">Plan de match</dt>
        <dd class="mt-2">
          <MatchPlanDisplay :competition="competition" />
        </dd>
      </div>
    </dl>
  </div>
</template>
