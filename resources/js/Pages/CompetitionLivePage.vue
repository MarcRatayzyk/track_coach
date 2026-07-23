<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import { track } from '../utils/analytics';

const props = defineProps({
  athlete: { type: Object, required: true },
  competition: { type: Object, required: true },
  liftLabels: { type: Object, default: () => ({}) },
});

const lifts = ['squat', 'bench', 'deadlift'];
const warmupSeconds = ref(300);
const warmupRunning = ref(false);
let warmupTimer = null;

onMounted(() => {
  track('competition_live_opened', {
    competition_id: props.competition.id,
    athlete_id: props.athlete.id,
  });
});

const form = useForm({
  live_state: structuredClone(props.competition.live_state),
});

const totalGl = computed(() => {
  let sum = 0;
  lifts.forEach((lift) => {
    let best = 0;
    (form.live_state.attempts[lift] ?? []).forEach((attempt) => {
      if (attempt.success === true && attempt.weight) {
        best = Math.max(best, Number(attempt.weight));
      }
    });
    sum += best;
  });
  return sum;
});

const warmupLabel = computed(() => {
  const minutes = Math.floor(warmupSeconds.value / 60);
  const seconds = warmupSeconds.value % 60;
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
});

function saveState() {
  form.patch(`/athletes/${props.athlete.id}/competitions/${props.competition.id}/live`, {
    preserveScroll: true,
  });
}

function setAction(action) {
  useForm({ action }).patch(`/athletes/${props.athlete.id}/competitions/${props.competition.id}/live`, {
    preserveScroll: true,
    onSuccess: () => window.location.reload(),
  });
}

function markAttempt(lift, index, success) {
  const attempt = form.live_state.attempts[lift][index];
  attempt.success = success;
  attempt.timestamp = new Date().toISOString();
  saveState();
}

function toggleWarmup() {
  if (warmupRunning.value) {
    clearInterval(warmupTimer);
    warmupRunning.value = false;
    return;
  }
  warmupRunning.value = true;
  warmupTimer = setInterval(() => {
    if (warmupSeconds.value <= 0) {
      clearInterval(warmupTimer);
      warmupRunning.value = false;
      return;
    }
    warmupSeconds.value -= 1;
  }, 1000);
}

onUnmounted(() => {
  if (warmupTimer) {
    clearInterval(warmupTimer);
  }
});
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <p class="text-sm text-slate-500">
          <Link :href="`/athletes/${athlete.id}`" class="text-blue-400 hover:text-blue-300">← Fiche athlète</Link>
        </p>
        <h1 class="mt-2 text-2xl font-bold text-white">Meet live — {{ competition.name }}</h1>
        <p class="mt-1 text-slate-400">
          {{ athlete.name }} · {{ formatCalendarFr(competition.competition_date) }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-right">
        <p class="text-xs uppercase tracking-wide text-slate-500">Total GL</p>
        <p class="text-2xl font-bold tabular-nums text-emerald-300">{{ totalGl }} kg</p>
      </div>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        v-if="competition.live_state.status !== 'live'"
        type="button"
        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
        @click="setAction('start')"
      >
        Démarrer le meet
      </button>
      <button
        v-if="competition.live_state.status === 'live'"
        type="button"
        class="rounded-lg bg-red-600/90 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
        @click="setAction('end')"
      >
        Terminer le meet
      </button>
      <span
        class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
        :class="{
          'bg-amber-500/20 text-amber-300': competition.live_state.status === 'warming',
          'bg-emerald-500/20 text-emerald-300': competition.live_state.status === 'live',
          'bg-slate-700 text-slate-300': competition.live_state.status === 'done',
        }"
      >
        {{ competition.live_state.status }}
      </span>
    </div>

    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
      <div class="flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-slate-300">Timer échauffement</h2>
        <div class="flex items-center gap-2">
          <span class="font-mono text-lg text-white">{{ warmupLabel }}</span>
          <button type="button" class="rounded-lg border border-slate-600 px-3 py-1 text-sm" @click="toggleWarmup">
            {{ warmupRunning ? 'Pause' : 'Start' }}
          </button>
        </div>
      </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
      <section
        v-for="lift in lifts"
        :key="lift"
        class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4"
      >
        <h3 class="text-base font-semibold text-white">{{ liftLabels[lift] ?? lift }}</h3>
        <ul class="mt-3 space-y-2">
          <li
            v-for="(attempt, index) in form.live_state.attempts[lift]"
            :key="`${lift}-${attempt.n}`"
            class="rounded-lg border border-slate-800 bg-slate-950/50 p-3"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="text-sm text-slate-300">T{{ attempt.n }}</span>
              <input
                v-model.number="attempt.weight"
                type="number"
                step="0.5"
                class="w-24 rounded border border-slate-700 bg-slate-900 px-2 py-1 text-right text-sm text-white"
                @change="saveState"
              />
            </div>
            <div class="mt-2 flex gap-2">
              <button
                type="button"
                class="flex-1 rounded-lg px-2 py-1 text-xs font-semibold"
                :class="attempt.success === true ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300'"
                @click="markAttempt(lift, index, true)"
              >
                Good
              </button>
              <button
                type="button"
                class="flex-1 rounded-lg px-2 py-1 text-xs font-semibold"
                :class="attempt.success === false ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-300'"
                @click="markAttempt(lift, index, false)"
              >
                Miss
              </button>
            </div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
