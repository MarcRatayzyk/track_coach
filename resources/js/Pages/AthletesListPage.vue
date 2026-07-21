<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CoachAddAthleteModal from '../Components/CoachAddAthleteModal.vue';
import ReadinessFormBuilderModal from '../Components/ReadinessFormBuilderModal.vue';
import UiIcon from '../Components/UiIcon.vue';

const props = defineProps({
  athletes: {
    type: Array,
    default: () => [],
  },
  coachReadinessForm: {
    type: Object,
    default: null,
  },
});

const showModal = ref(false);
const showDefaultReadinessBuilder = ref(false);

const page = usePage();
const activationLink = computed(() => page.props.flash?.first_login_url ?? '');

const resendForm = useForm({});

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
    case 'weight_category':
      return parseWeightClassKg(row.weight_category_label) ?? row.weight_category_label ?? '';
    case 'total_kg':
      return row.total_kg;
    case 'gl_points':
      return row.gl_points;
    case 'readiness_checkins_7d':
      return row.readiness_checkins_7d ?? row.readiness_entries_count ?? 0;
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
  sortDirection.value = key === 'weight_category' ? 'asc' : key === 'next_competition_days' ? 'asc' : 'desc';
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

function formatReadinessCheckins(count) {
  return `${count ?? 0}/7`;
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

function readinessTone(count) {
  if (count == null || count === 0) {
    return 'text-slate-500';
  }
  if (count >= 5) {
    return 'text-emerald-400';
  }
  if (count >= 3) {
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
}

function resendInvitation(row) {
  resendForm.post(`/coach/athletes/${row.id}/resend-invitation`, {
    preserveScroll: true,
  });
}

async function copyActivationLink() {
  if (!activationLink.value) {
    return;
  }
  try {
    await navigator.clipboard.writeText(activationLink.value);
  } catch {
    window.prompt('Copie ce lien :', activationLink.value);
  }
}

onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  if (params.get('add') === '1') {
    showModal.value = true;
  }
});

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
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-600 px-4 py-3 text-sm font-semibold text-slate-200 hover:bg-slate-800"
          @click="showDefaultReadinessBuilder = true"
        >
          Formulaire readiness
        </button>
        <button
          type="button"
          class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white shadow-lg hover:bg-blue-500"
          @click="openModal"
        >
          Nouvel athlète
        </button>
      </div>
    </div>

    <div
      v-if="activationLink"
      class="mt-6 rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4 shadow-lg"
    >
      <p class="text-sm font-semibold text-emerald-200">Lien d’activation athlète</p>
      <p class="mt-1 text-xs text-slate-400">
        Copie ce lien et transmets-le à l’athlète (WhatsApp, SMS…). Valable 14 jours.
      </p>
      <p class="mt-3 break-all rounded-xl border border-slate-700 bg-slate-950 p-3 font-mono text-xs text-slate-300">
        {{ activationLink }}
      </p>
      <button
        type="button"
        class="mt-3 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500"
        @click="copyActivationLink"
      >
        Copier le lien
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
                  @click="toggleSort('weight_category')"
                >
                  <span>Caté. poids</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('weight_category', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('weight_category', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('weight_category', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('weight_category', 'asc')"
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
                  @click="toggleSort('readiness_checkins_7d')"
                >
                  <span>Check-in (7 j)</span>
                  <span class="inline-flex flex-col leading-none" aria-hidden="true">
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('readiness_checkins_7d', 'desc')"
                      title="Tri décroissant"
                      @click.stop="toggleSort('readiness_checkins_7d', 'desc')"
                    >▲</span>
                    <span
                      class="cursor-pointer text-[10px] leading-3"
                      :class="sortArrowClass('readiness_checkins_7d', 'asc')"
                      title="Tri croissant"
                      @click.stop="toggleSort('readiness_checkins_7d', 'asc')"
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
                    <span
                      v-if="row.is_pending_activation"
                      class="ml-2 inline-flex rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-400"
                    >
                      En attente
                    </span>
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
                    v-if="!row.is_pending_activation"
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
                  <button
                    v-else
                    type="button"
                    class="rounded-lg px-2 py-1 text-xs font-medium text-amber-400 hover:bg-slate-800"
                    title="Afficher le lien d’activation"
                    :disabled="resendForm.processing"
                    @click="resendInvitation(row)"
                  >
                    Lien
                  </button>
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
                {{ row.weight_category_label || '—' }}
              </td>

              <td class="px-3 py-4 text-right font-medium text-white">
                {{ formatTotal(row.total_kg) }}
              </td>

              <td class="px-3 py-4 text-right font-medium text-slate-200">
                {{ formatGlPoints(row.gl_points) }}
              </td>

              <td class="px-3 py-4 text-right">
                <span :class="['font-semibold', readinessTone(row.readiness_checkins_7d ?? row.readiness_entries_count)]">
                  {{ formatReadinessCheckins(row.readiness_checkins_7d ?? row.readiness_entries_count) }}
                </span>
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

    <CoachAddAthleteModal
      v-model="showModal"
      :coach-readiness-form="coachReadinessForm"
      @invited="() => router.reload({ preserveScroll: true })"
    />

    <ReadinessFormBuilderModal
      :open="showDefaultReadinessBuilder"
      mode="template"
      title="Formulaire readiness par défaut"
      :initial-fields="coachReadinessForm?.fields ?? []"
      @close="showDefaultReadinessBuilder = false"
    />
  </div>
</template>
