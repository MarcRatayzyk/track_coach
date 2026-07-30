<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CompetitionAttemptsCell from '../Components/CompetitionAttemptsCell.vue';
import { CATEGORY_LABELS } from '../config/ipfWeightCategories';
import { defaultStructuredPlan, formatWeight } from '../utils/matchPlan';

const props = defineProps({
  upcoming: {
    type: Array,
    default: () => [],
  },
  past: {
    type: Array,
    default: () => [],
  },
  athletes: {
    type: Array,
    default: () => [],
  },
});

const openWeightKey = ref(null);
const showAddModal = ref(false);
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  athlete_id: '',
  name: '',
  competition_date: today,
  goal: '',
  location: '',
  match_plan_data: defaultStructuredPlan(),
});

function openAddModal() {
  form.reset();
  form.competition_date = today;
  form.match_plan_data = defaultStructuredPlan();
  form.athlete_id = props.athletes[0]?.id ? String(props.athletes[0].id) : '';
  showAddModal.value = true;
}

function closeAddModal() {
  showAddModal.value = false;
}

function submitCompetition() {
  if (!form.athlete_id) {
    return;
  }

  form
    .transform((data) => ({
      name: data.name,
      competition_date: data.competition_date,
      goal: data.goal || null,
      location: data.location || null,
      match_plan_data: data.match_plan_data,
    }))
    .post(`/coach/athletes/${form.athlete_id}/competitions`, {
      preserveScroll: true,
      onSuccess: () => {
        showAddModal.value = false;
        form.reset();
        form.competition_date = today;
        form.match_plan_data = defaultStructuredPlan();
      },
    });
}

function categoryLabel(code) {
  if (!code) {
    return '—';
  }
  return CATEGORY_LABELS[code] ?? code;
}

function formatDate(value) {
  if (!value) {
    return '—';
  }
  const date = new Date(`${value}T12:00:00`);
  if (Number.isNaN(date.getTime())) {
    return value;
  }
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  });
}

function weightKey(section, rowId) {
  return `${section}-${rowId}`;
}

function toggleWeightDate(section, rowId) {
  const key = weightKey(section, rowId);
  openWeightKey.value = openWeightKey.value === key ? null : key;
}

function formatTotal(value) {
  if (value == null || value === '') {
    return '—';
  }
  return `${formatWeight(value)} kg`;
}
</script>

<template>
  <div class="mx-auto w-full max-w-[100rem] px-4 py-6 sm:px-6 lg:px-8">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
      <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">Compétitions</h1>
      <button
        type="button"
        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/30 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
        :disabled="athletes.length === 0"
        @click="openAddModal"
      >
        + Ajouter une compétition
      </button>
    </div>

    <Teleport to="body">
      <div
        v-if="showAddModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="closeAddModal"
      >
        <div
          class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <h2 class="text-lg font-semibold text-white">Nouvelle compétition</h2>
            <button
              type="button"
              class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="closeAddModal"
            >
              ✕
            </button>
          </div>

          <form class="mt-5 space-y-4" @submit.prevent="submitCompetition">
            <label class="block text-sm text-slate-400">
              Athlète
              <select
                v-model="form.athlete_id"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
                <option disabled value="">Choisir un athlète</option>
                <option
                  v-for="athlete in athletes"
                  :key="athlete.id"
                  :value="String(athlete.id)"
                >
                  {{ athlete.name }}
                </option>
              </select>
            </label>
            <label class="block text-sm text-slate-400">
              Nom
              <input
                v-model="form.name"
                type="text"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Date
              <input
                v-model="form.competition_date"
                type="date"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Lieu
              <input
                v-model="form.location"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Objectif
              <input
                v-model="form.goal"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <p v-if="Object.keys(form.errors).length" class="text-sm text-red-400">
              {{ Object.values(form.errors).flat().join(' ') }}
            </p>
            <div class="flex flex-wrap gap-3 pt-1">
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
                @click="closeAddModal"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="form.processing || !form.athlete_id"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                Enregistrer
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <section class="mb-10">
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">
        À venir
      </h2>

      <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 shadow-xl">
        <div v-if="upcoming.length === 0" class="px-6 py-12 text-center text-slate-500">
          Aucune compétition à venir.
        </div>

        <template v-else>
          <!-- Mobile cards -->
          <ul class="divide-y divide-slate-800/80 md:hidden">
            <li
              v-for="row in upcoming"
              :key="`m-up-${row.id}`"
              class="px-4 py-4"
            >
              <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0 flex-1 overflow-hidden">
                  <Link
                    v-if="row.athlete?.id"
                    :href="`/athletes/${row.athlete.id}?competition=${row.id}`"
                    class="block truncate font-semibold text-blue-400 hover:text-blue-300"
                  >
                    {{ row.athlete.name }}
                  </Link>
                  <span v-else class="font-semibold text-slate-300">—</span>
                  <p v-if="row.name" class="mt-0.5 truncate text-xs text-slate-500">
                    {{ row.name }}
                  </p>
                  <p class="mt-1 truncate text-xs text-slate-400">
                    {{ formatDate(row.competition_date) }}
                    <template v-if="row.location"> · {{ row.location }}</template>
                  </p>
                </div>
                <div class="shrink-0 text-right">
                  <p class="text-sm text-slate-300">
                    {{ categoryLabel(row.athlete?.weight_category) }}
                  </p>
                  <p
                    v-if="row.primary_scenario?.total != null"
                    class="mt-1 font-mono text-sm font-semibold text-rose-200"
                  >
                    {{ formatTotal(row.primary_scenario.total) }}
                  </p>
                </div>
              </div>
            </li>
          </ul>

          <!-- Desktop table -->
          <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[72rem] text-left text-sm">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                  <th class="px-5 py-3.5 font-semibold lg:px-6">Athlète</th>
                  <th class="px-4 py-3.5 font-semibold">Catégorie</th>
                  <th class="px-4 py-3.5 font-semibold">Poids</th>
                  <th class="min-w-[22rem] px-4 py-3.5 font-semibold">Scénario</th>
                  <th class="px-4 py-3.5 font-semibold text-right">Total estimé</th>
                  <th class="px-4 py-3.5 font-semibold">Date</th>
                  <th class="px-5 py-3.5 font-semibold lg:px-6">Lieu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/80">
                <tr
                  v-for="row in upcoming"
                  :key="row.id"
                  class="transition hover:bg-slate-800/30"
                >
                  <td class="px-5 py-5 lg:px-6">
                    <div class="min-w-0">
                      <Link
                        v-if="row.athlete?.id"
                        :href="`/athletes/${row.athlete.id}`"
                        class="font-semibold text-blue-400 hover:text-blue-300"
                      >
                        {{ row.athlete.name }}
                      </Link>
                      <span v-else class="font-semibold text-slate-300">—</span>
                      <p v-if="row.name" class="mt-0.5 truncate text-xs text-slate-500">
                        {{ row.name }}
                      </p>
                    </div>
                  </td>
                  <td class="px-4 py-5 text-slate-300">
                    {{ categoryLabel(row.athlete?.weight_category) }}
                  </td>
                  <td class="px-4 py-5">
                    <template v-if="row.athlete?.last_body_weight">
                      <button
                        type="button"
                        class="rounded-lg px-1.5 py-0.5 font-mono text-slate-200 transition hover:bg-slate-800 hover:text-white"
                        :title="`Pesée du ${formatDate(row.athlete.last_body_weight.entry_date)}`"
                        @click="toggleWeightDate('upcoming', row.id)"
                      >
                        {{ formatWeight(row.athlete.last_body_weight.weight_kg) }} kg
                      </button>
                      <p
                        v-if="openWeightKey === weightKey('upcoming', row.id)"
                        class="mt-1 text-xs text-slate-400"
                      >
                        {{ formatDate(row.athlete.last_body_weight.entry_date) }}
                      </p>
                    </template>
                    <span v-else class="text-slate-600">—</span>
                  </td>
                  <td class="px-4 py-5">
                    <CompetitionAttemptsCell
                      mode="planned"
                      :scenario="row.primary_scenario"
                      :href="row.athlete?.id ? `/athletes/${row.athlete.id}?competition=${row.id}` : null"
                    />
                  </td>
                  <td class="px-4 py-5 text-right font-mono text-base font-semibold text-rose-200">
                    {{ formatTotal(row.primary_scenario?.total) }}
                  </td>
                  <td class="px-4 py-5 whitespace-nowrap text-slate-300">
                    {{ formatDate(row.competition_date) }}
                  </td>
                  <td class="px-5 py-5 text-slate-400 lg:px-6">
                    {{ row.location || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </section>

    <section>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-400">
        Passées
      </h2>

      <div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 shadow-xl">
        <div v-if="past.length === 0" class="px-6 py-12 text-center text-slate-500">
          Aucune compétition passée.
        </div>

        <template v-else>
          <ul class="divide-y divide-slate-800/80 md:hidden">
            <li
              v-for="row in past"
              :key="`m-past-${row.id}`"
              class="px-4 py-4"
            >
              <div class="flex min-w-0 items-start justify-between gap-3">
                <div class="min-w-0 flex-1 overflow-hidden">
                  <Link
                    v-if="row.athlete?.id"
                    :href="`/athletes/${row.athlete.id}?competition=${row.id}`"
                    class="block truncate font-semibold text-blue-400 hover:text-blue-300"
                  >
                    {{ row.athlete.name }}
                  </Link>
                  <span v-else class="font-semibold text-slate-300">—</span>
                  <p v-if="row.name" class="mt-0.5 truncate text-xs text-slate-500">
                    {{ row.name }}
                  </p>
                  <p class="mt-1 truncate text-xs text-slate-400">
                    {{ formatDate(row.competition_date) }}
                    <template v-if="row.location"> · {{ row.location }}</template>
                  </p>
                </div>
                <div class="shrink-0 text-right">
                  <p class="text-sm text-slate-300">
                    {{ categoryLabel(row.athlete?.weight_category) }}
                  </p>
                  <p
                    v-if="row.live_result"
                    class="mt-1 font-mono text-sm font-semibold text-emerald-200"
                  >
                    {{ formatTotal(row.live_result.total_gl) }}
                  </p>
                </div>
              </div>
            </li>
          </ul>

          <div class="hidden overflow-x-auto md:block">
            <table class="w-full min-w-[72rem] text-left text-sm">
              <thead>
                <tr class="border-b border-slate-800 bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
                  <th class="px-5 py-3.5 font-semibold lg:px-6">Athlète</th>
                  <th class="px-4 py-3.5 font-semibold">Catégorie</th>
                  <th class="px-4 py-3.5 font-semibold">Poids</th>
                  <th class="min-w-[22rem] px-4 py-3.5 font-semibold">Résultats</th>
                  <th class="px-4 py-3.5 font-semibold text-right">Total</th>
                  <th class="px-4 py-3.5 font-semibold">Date</th>
                  <th class="px-5 py-3.5 font-semibold lg:px-6">Lieu</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/80">
                <tr
                  v-for="row in past"
                  :key="row.id"
                  class="transition hover:bg-slate-800/30"
                >
                  <td class="px-5 py-5 lg:px-6">
                    <div class="min-w-0">
                      <Link
                        v-if="row.athlete?.id"
                        :href="`/athletes/${row.athlete.id}`"
                        class="font-semibold text-blue-400 hover:text-blue-300"
                      >
                        {{ row.athlete.name }}
                      </Link>
                      <span v-else class="font-semibold text-slate-300">—</span>
                      <p v-if="row.name" class="mt-0.5 truncate text-xs text-slate-500">
                        {{ row.name }}
                      </p>
                    </div>
                  </td>
                  <td class="px-4 py-5 text-slate-300">
                    {{ categoryLabel(row.athlete?.weight_category) }}
                  </td>
                  <td class="px-4 py-5">
                    <template v-if="row.athlete?.last_body_weight">
                      <button
                        type="button"
                        class="rounded-lg px-1.5 py-0.5 font-mono text-slate-200 transition hover:bg-slate-800 hover:text-white"
                        :title="`Pesée du ${formatDate(row.athlete.last_body_weight.entry_date)}`"
                        @click="toggleWeightDate('past', row.id)"
                      >
                        {{ formatWeight(row.athlete.last_body_weight.weight_kg) }} kg
                      </button>
                      <p
                        v-if="openWeightKey === weightKey('past', row.id)"
                        class="mt-1 text-xs text-slate-400"
                      >
                        {{ formatDate(row.athlete.last_body_weight.entry_date) }}
                      </p>
                    </template>
                    <span v-else class="text-slate-600">—</span>
                  </td>
                  <td class="px-4 py-5">
                    <CompetitionAttemptsCell
                      mode="live"
                      :live-result="row.live_result"
                      :href="row.athlete?.id ? `/athletes/${row.athlete.id}?competition=${row.id}` : null"
                    />
                  </td>
                  <td class="px-4 py-5 text-right font-mono text-base font-semibold text-emerald-200">
                    <template v-if="row.live_result">
                      {{ formatTotal(row.live_result.total_gl) }}
                    </template>
                    <span v-else class="font-normal text-slate-600">—</span>
                  </td>
                  <td class="px-4 py-5 whitespace-nowrap text-slate-300">
                    {{ formatDate(row.competition_date) }}
                  </td>
                  <td class="px-5 py-5 text-slate-400 lg:px-6">
                    {{ row.location || '—' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </section>
  </div>
</template>
