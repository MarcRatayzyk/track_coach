<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UiIcon from '../Components/UiIcon.vue';

const props = defineProps({
  athletes: {
    type: Array,
    default: () => [],
  },
});

const showModal = ref(false);
const modalStep = ref('form');
const invitationUrl = ref('');

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  feedback_frequency: 'weekly',
});

const athleteCount = computed(() => props.athletes.length);

const sortKey = ref(null);
const sortDirection = ref('desc');

function parseWeightClassKg(value) {
  if (!value) {
    return null;
  }
  const match = String(value).match(/(\d+(?:[.,]\d+)?)/);
  if (!match) {
    return null;
  }
  return Number.parseFloat(match[1].replace(',', '.'));
}

function sortValue(row, key) {
  switch (key) {
    case 'weight_class':
      return parseWeightClassKg(row.weight_class) ?? row.weight_class ?? '';
    case 'total_kg':
      return row.total_kg;
    case 'gl_points':
      return row.gl_points;
    case 'readiness_average':
      return row.readiness_average;
    case 'adherence_percentage':
      return row.adherence_percentage;
    case 'next_competition_days':
      return row.next_competition_days;
    default:
      return null;
  }
}

function isEmptySortValue(value) {
  return value === null || value === undefined || value === '';
}

function compareRows(a, b, key) {
  const aVal = sortValue(a, key);
  const bVal = sortValue(b, key);
  const aEmpty = isEmptySortValue(aVal);
  const bEmpty = isEmptySortValue(bVal);

  if (aEmpty && bEmpty) {
    return a.name.localeCompare(b.name, 'fr');
  }
  if (aEmpty) {
    return 1;
  }
  if (bEmpty) {
    return -1;
  }

  if (typeof aVal === 'string' && typeof bVal === 'string') {
    return aVal.localeCompare(bVal, 'fr', { numeric: true });
  }

  if (aVal < bVal) {
    return -1;
  }
  if (aVal > bVal) {
    return 1;
  }

  return a.name.localeCompare(b.name, 'fr');
}

const sortedAthletes = computed(() => {
  const list = [...props.athletes];

  if (!sortKey.value) {
    return list;
  }

  const direction = sortDirection.value === 'asc' ? 1 : -1;

  return list.sort((a, b) => compareRows(a, b, sortKey.value) * direction);
});

function toggleSort(key, direction = null) {
  if (direction) {
    if (sortKey.value === key && sortDirection.value === direction) {
      sortKey.value = null;
      return;
    }
    sortKey.value = key;
    sortDirection.value = direction;
    return;
  }

  if (sortKey.value === key) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    return;
  }

  sortKey.value = key;
  sortDirection.value = key === 'weight_class' ? 'asc' : key === 'next_competition_days' ? 'asc' : 'desc';
}

function sortArrowClass(key, direction) {
  const active = sortKey.value === key && sortDirection.value === direction;
  return active
    ? 'text-blue-400'
    : 'text-slate-600 group-hover:text-slate-400';
}

function formatTotal(value) {
  return value != null && value > 0 ? `${value} kg` : '—';
}

function formatGlPoints(value) {
  return value != null ? value.toLocaleString('fr-FR', { maximumFractionDigits: 1 }) : '—';
}

function formatReadiness(value, count) {
  if (value == null) {
    return count > 0 ? '—' : '—';
  }
  return `${value}/10`;
}

function formatAdherence(value) {
  return value != null ? `${value} %` : '—';
}

function formatNextCompetition(row) {
  if (row.next_competition_days == null) {
    return '—';
  }

  return `J-${row.next_competition_days}`;
}

function nextCompetitionTone(days) {
  if (days == null) {
    return 'text-slate-500';
  }
  if (days <= 7) {
    return 'text-red-400';
  }
  if (days <= 21) {
    return 'text-amber-400';
  }
  return 'text-slate-200';
}

function adherenceTone(value) {
  if (value == null) {
    return 'text-slate-500';
  }
  if (value >= 80) {
    return 'text-emerald-400';
  }
  if (value >= 55) {
    return 'text-amber-400';
  }
  return 'text-red-400';
}

function readinessTone(value) {
  if (value == null) {
    return 'text-slate-500';
  }
  if (value >= 8) {
    return 'text-emerald-400';
  }
  if (value >= 6) {
    return 'text-amber-400';
  }
  return 'text-red-400';
}

function messageHref(row) {
  if (row.message_thread_id) {
    return `/messaging?thread=${row.message_thread_id}`;
  }
  return `/messaging?athlete=${row.id}`;
}

function programHref(row) {
  if (row.active_program_assignment_id) {
    return `/program-builder?assignment=${row.active_program_assignment_id}`;
  }
  return `/program-builder`;
}

function openModal() {
  showModal.value = true;
  modalStep.value = 'form';
  invitationUrl.value = '';
  form.clearErrors();
}

function closeModal() {
  showModal.value = false;
  modalStep.value = 'form';
  invitationUrl.value = '';
}

function submitNewAthlete() {
  form.post('/coach/athletes', {
    preserveScroll: true,
    onSuccess: (p) => {
      invitationUrl.value = p.props.flash?.first_login_url ?? '';
      modalStep.value = 'invite';
      showModal.value = true;
      form.reset();
      form.clearErrors();
    },
  });
}

async function copyInvitation() {
  if (!invitationUrl.value) {
    return;
  }
  try {
    await navigator.clipboard.writeText(invitationUrl.value);
  } catch {
    window.prompt('Copie ce lien :', invitationUrl.value);
  }
}

function confirmRemove(row) {
  const ok = window.confirm(
    `Retirer « ${row.name} » de ton groupe ? Son compte restera actif ; tu pourras le rattacher plus tard si besoin.`,
  );
  if (!ok) {
    return;
  }
  router.delete(`/coach/athletes/${row.id}`, { preserveScroll: true });
}
</script>

<template>
  <div>
    <div class="flex flex-wrap items-end justify-between gap-6">
      <div>
        <h1 class="text-2xl font-bold text-white">Athlètes</h1>
        <p class="mt-2 text-slate-400">
          Tous les athlètes ({{ athleteCount }}) · actifs
        </p>
      </div>
      <button
        type="button"
        class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg hover:bg-blue-500"
        @click="openModal"
      >
        Nouvel athlète
      </button>
    </div>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 shadow-xl">
      <div v-if="athletes.length === 0" class="px-6 py-16 text-center text-slate-500">
        Aucun athlète pour l’instant. Utilise « Nouvel athlète » pour inviter quelqu’un.
      </div>

      <div v-else class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
            <tr class="border-b border-slate-800 bg-slate-950/60 text-xs uppercase tracking-wide text-slate-500">
              <th class="px-4 py-3 font-semibold lg:px-6">Athlète</th>
              <th class="px-3 py-3 font-semibold">Actions</th>
              <th class="px-3 py-3 font-semibold">
                <button
                  type="button"
                  class="group inline-flex items-center gap-1.5 hover:text-slate-300"
                  @click="toggleSort('weight_class')"
                >
                  <span>Caté. poids</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('weight_class', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('weight_class', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('weight_class', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('weight_class', 'asc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold text-right">
                <button
                  type="button"
                  class="group ml-auto inline-flex items-center justify-end gap-1.5 hover:text-slate-300"
                  @click="toggleSort('total_kg')"
                >
                  <span>Total</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('total_kg', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('total_kg', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('total_kg', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('total_kg', 'asc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold text-right">
                <button
                  type="button"
                  class="group ml-auto inline-flex items-center justify-end gap-1.5 hover:text-slate-300"
                  @click="toggleSort('gl_points')"
                >
                  <span>GL Points</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('gl_points', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('gl_points', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('gl_points', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('gl_points', 'asc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold text-right">
                <button
                  type="button"
                  class="group ml-auto inline-flex items-center justify-end gap-1.5 hover:text-slate-300"
                  @click="toggleSort('readiness_average')"
                >
                  <span>Readiness (7 j)</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('readiness_average', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('readiness_average', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('readiness_average', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('readiness_average', 'asc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold text-right">
                <button
                  type="button"
                  class="group ml-auto inline-flex items-center justify-end gap-1.5 hover:text-slate-300"
                  @click="toggleSort('next_competition_days')"
                >
                  <span>Prochaine compétition</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('next_competition_days', 'asc')"
                      title="Compétition la plus proche en premier"
                      @click.stop="toggleSort('next_competition_days', 'asc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('next_competition_days', 'desc')"
                      title="Compétition la plus lointaine en premier"
                      @click.stop="toggleSort('next_competition_days', 'desc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold text-right">
                <button
                  type="button"
                  class="group ml-auto inline-flex items-center justify-end gap-1.5 hover:text-slate-300"
                  @click="toggleSort('adherence_percentage')"
                >
                  <span>Adhérence</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('adherence_percentage', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('adherence_percentage', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('adherence_percentage', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('adherence_percentage', 'asc')"
                    >▼</span>
                  </span>
                </button>
              </th>
              <th class="px-3 py-3 font-semibold lg:px-6" />
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/80">
            <tr
              v-for="row in sortedAthletes"
              :key="row.id"
              class="transition hover:bg-slate-800/30"
            >
              <td class="px-4 py-4 lg:px-6">
                <div class="flex items-center gap-3">
                  <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-600/20 text-sm font-bold text-blue-300"
                  >
                    {{ row.name.charAt(0).toUpperCase() }}
                  </span>
                  <div class="min-w-0">
                    <Link
                      :href="`/athletes/${row.id}`"
                      class="font-semibold text-blue-400 hover:text-blue-300"
                    >
                      {{ row.name }}
                    </Link>
                    <p class="truncate text-xs text-slate-500">{{ row.email }}</p>
                  </div>
                </div>
              </td>

              <td class="px-3 py-4">
                <div class="flex items-center gap-1">
                  <Link
                    :href="programHref(row)"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                    title="Programme"
                  >
                    <UiIcon name="calendar" class="h-5 w-5" />
                  </Link>
                  <Link
                    :href="messageHref(row)"
                    class="relative rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                    title="Messages"
                  >
                    <UiIcon name="chat" class="h-5 w-5" />
                    <span
                      v-if="row.unread_messages_count > 0"
                      class="absolute right-0.5 top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white"
                    >
                      {{ row.unread_messages_count > 9 ? '9+' : row.unread_messages_count }}
                    </span>
                  </Link>
                  <Link
                    :href="`/athletes/${row.id}`"
                    class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                    title="Fiche athlète"
                  >
                    <UiIcon name="user-circle" class="h-5 w-5" />
                  </Link>
                </div>
              </td>

              <td class="px-3 py-4 text-slate-300">
                {{ row.weight_class || '—' }}
              </td>

              <td class="px-3 py-4 text-right font-medium text-white">
                {{ formatTotal(row.total_kg) }}
              </td>

              <td class="px-3 py-4 text-right font-medium text-slate-200">
                {{ formatGlPoints(row.gl_points) }}
              </td>

              <td class="px-3 py-4 text-right">
                <span :class="['font-semibold', readinessTone(row.readiness_average)]">
                  {{ formatReadiness(row.readiness_average, row.readiness_entries_count) }}
                </span>
                <p
                  v-if="row.readiness_entries_count > 0 && row.readiness_entries_count < 7"
                  class="text-[10px] text-slate-500"
                >
                  {{ row.readiness_entries_count }} j. saisis
                </p>
              </td>

              <td class="px-3 py-4 text-right">
                <span :class="['font-semibold tabular-nums', nextCompetitionTone(row.next_competition_days)]">
                  {{ formatNextCompetition(row) }}
                </span>
                <p
                  v-if="row.next_competition_name"
                  class="truncate text-[10px] text-slate-500"
                  :title="row.next_competition_name"
                >
                  {{ row.next_competition_name }}
                </p>
              </td>

              <td class="px-3 py-4 text-right">
                <span :class="['font-semibold', adherenceTone(row.adherence_percentage)]">
                  {{ formatAdherence(row.adherence_percentage) }}
                </span>
              </td>

              <td class="px-3 py-4 text-right lg:px-6">
                <button
                  type="button"
                  class="text-xs font-medium text-red-400/80 hover:text-red-300"
                  @click="confirmRemove(row)"
                >
                  Retirer
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <Teleport to="body">
      <div
        v-if="showModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="closeModal"
      >
        <div
          class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl lg:p-8"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <h2 class="text-base font-semibold text-white">
              {{ modalStep === 'invite' ? 'Lien d’activation' : 'Nouvel athlète' }}
            </h2>
            <button
              type="button"
              class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="closeModal"
            >
              ✕
            </button>
          </div>

          <template v-if="modalStep === 'form'">
            <p class="mt-3 text-slate-400">
              Renseigne le prénom, le nom et l’e-mail. L’athlète recevra un lien (à lui transmettre) pour
              choisir son mot de passe et compléter son profil lors de sa première visite.
            </p>
            <form class="mt-4 space-y-4" @submit.prevent="submitNewAthlete">
              <label class="block text-sm font-medium text-slate-400">
                Prénom
                <input
                  v-model="form.first_name"
                  type="text"
                  required
                  autocomplete="given-name"
                  class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
                />
                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-400">
                  {{ form.errors.first_name }}
                </p>
              </label>
              <label class="block text-sm font-medium text-slate-400">
                Nom
                <input
                  v-model="form.last_name"
                  type="text"
                  required
                  autocomplete="family-name"
                  class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
                />
                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-400">
                  {{ form.errors.last_name }}
                </p>
              </label>
              <label class="block text-sm font-medium text-slate-400">
                E-mail (identifiant de connexion)
                <input
                  v-model="form.email"
                  type="email"
                  required
                  autocomplete="email"
                  class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
                />
                <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">
                  {{ form.errors.email }}
                </p>
              </label>
              <label class="block text-sm font-medium text-slate-400">
                Suivi des retours
                <select
                  v-model="form.feedback_frequency"
                  class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
                >
                  <option value="daily">Journalier (retour à chaque séance)</option>
                  <option value="weekly">Hebdomadaire (1 retour par semaine)</option>
                </select>
                <p v-if="form.errors.feedback_frequency" class="mt-1 text-sm text-red-400">
                  {{ form.errors.feedback_frequency }}
                </p>
              </label>
              <div class="flex flex-wrap gap-3 pt-2">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                  Créer et inviter
                </button>
                <button
                  type="button"
                  class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
                  @click="closeModal"
                >
                  Annuler
                </button>
              </div>
            </form>
          </template>

          <template v-else>
            <p class="mt-3 text-slate-400">
              Copie ce lien et envoie-le à ton athlète (message, e-mail, etc.). Il est valable 14 jours.
              L’athlète pourra alors définir son mot de passe et compléter son profil.
            </p>
            <div class="mt-4 rounded-xl border border-slate-700 bg-slate-950 p-3">
              <p class="break-all font-mono text-xs text-slate-300">{{ invitationUrl }}</p>
            </div>
            <div class="mt-4 flex flex-wrap gap-3">
              <button
                type="button"
                class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-500"
                @click="copyInvitation"
              >
                Copier le lien
              </button>
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
                @click="closeModal"
              >
                Fermer
              </button>
            </div>
          </template>
        </div>
      </div>
    </Teleport>
  </div>
</template>
