<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CustomExercisesModal from '../Components/CustomExercisesModal.vue';
import DayTableLayoutModal from '../Components/DayTableLayoutModal.vue';
import ReadinessFormBuilderModal from '../Components/ReadinessFormBuilderModal.vue';
import UiIcon from '../Components/UiIcon.vue';
import { COACH_SPECIALTY_OPTIONS } from '../config/ipfWeightCategories';
import { DAY_TABLE_COLUMNS } from '../config/dayTableColumns';

const props = defineProps({
  coach: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  editableProfile: {
    type: Object,
    default: null,
  },
  messagingThreadId: {
    type: Number,
    default: null,
  },
  coachReadinessForm: {
    type: Object,
    default: null,
  },
  dayTableLayouts: {
    type: Array,
    default: () => [],
  },
  defaultDayTableLayoutId: {
    type: Number,
    default: null,
  },
});

const page = usePage();
const showEditForm = ref(false);
const showReadinessBuilder = ref(false);
const showLayoutModal = ref(false);
const showExercisesModal = ref(false);

const profileForm = useForm({
  bio: props.editableProfile?.bio ?? '',
  specialties: props.editableProfile?.specialties ?? [],
  years_experience: props.editableProfile?.years_experience ?? null,
  certifications: props.editableProfile?.certifications ?? '',
  club_gym: props.editableProfile?.club_gym ?? '',
});

const exerciseLibrary = computed(() => page.props.exerciseLibrary ?? []);
const exerciseCounts = computed(() => {
  const all = exerciseLibrary.value;
  const custom = all.filter((exercise) => exercise.is_custom).length;
  return {
    total: all.length,
    custom,
    builtin: all.length - custom,
  };
});

const readinessFieldCount = computed(
  () => props.coachReadinessForm?.fields?.length ?? 0,
);

function columnLabel(columnId) {
  return DAY_TABLE_COLUMNS[columnId]?.label ?? columnId;
}

function layoutColumnsLabel(layout) {
  const columns = layout?.columns ?? [];
  if (!columns.length) {
    return 'Aucune colonne';
  }
  return columns.map(columnLabel).join(' · ');
}

function toggleSpecialty(value) {
  const current = new Set(profileForm.specialties ?? []);
  if (current.has(value)) {
    current.delete(value);
  } else {
    current.add(value);
  }
  profileForm.specialties = [...current];
}

function cancelEdit() {
  profileForm.defaults({
    bio: props.editableProfile?.bio ?? '',
    specialties: props.editableProfile?.specialties ?? [],
    years_experience: props.editableProfile?.years_experience ?? null,
    certifications: props.editableProfile?.certifications ?? '',
    club_gym: props.editableProfile?.club_gym ?? '',
  });
  profileForm.reset();
  showEditForm.value = false;
}

function submitProfile() {
  profileForm.patch('/coach/profile', {
    preserveScroll: true,
    onSuccess: () => {
      showEditForm.value = false;
    },
  });
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-4">
    <!-- Identité + infos profil (lecture) -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-blue-400">Profil coach</p>
          <h1 class="mt-1 text-2xl font-bold text-white">{{ coach.name }}</h1>
          <p v-if="coach.club_gym" class="mt-1 text-sm text-slate-400">{{ coach.club_gym }}</p>
        </div>
        <Link
          v-if="messagingThreadId"
          :href="`/messaging?thread=${messagingThreadId}`"
          class="rounded-xl border border-blue-500/40 bg-blue-950/30 px-4 py-2 text-sm font-semibold text-blue-200 hover:bg-blue-950/50"
        >
          Messagerie →
        </Link>
      </div>

      <div v-if="coach.specialty_labels?.length" class="mt-4 flex flex-wrap gap-2">
        <span
          v-for="label in coach.specialty_labels"
          :key="label"
          class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-xs font-medium text-slate-200"
        >
          {{ label }}
        </span>
      </div>

      <dl class="mt-4 space-y-3 text-sm">
        <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
          <dt class="text-slate-500">Expérience</dt>
          <dd class="font-semibold text-white">
            <template v-if="coach.years_experience != null">
              {{ coach.years_experience }} an{{ coach.years_experience > 1 ? 's' : '' }}
            </template>
            <template v-else>—</template>
          </dd>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
          <dt class="text-slate-500">Certifications</dt>
          <dd class="mt-1 whitespace-pre-wrap font-semibold text-white">
            {{ coach.certifications || '—' }}
          </dd>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
          <dt class="text-slate-500">Club / salle</dt>
          <dd class="mt-1 font-semibold text-white">{{ coach.club_gym || '—' }}</dd>
        </div>
        <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
          <dt class="text-slate-500">Bio</dt>
          <dd class="mt-1 whitespace-pre-wrap text-slate-300">{{ coach.bio || '—' }}</dd>
        </div>
      </dl>

      <button
        v-if="canEdit && editableProfile && !showEditForm"
        type="button"
        class="mt-4 w-full rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-2.5 text-sm font-semibold text-blue-300 transition hover:bg-blue-500/20"
        @click="showEditForm = true"
      >
        Modifier le profil
      </button>

      <form
        v-if="canEdit && editableProfile && showEditForm"
        class="mt-4 space-y-3 rounded-lg border border-slate-800 bg-slate-950/50 p-3"
        @submit.prevent="submitProfile"
      >
        <p class="text-xs font-semibold text-white">Modifier le profil</p>
        <label class="block text-xs text-slate-400">
          Bio
          <textarea
            v-model="profileForm.bio"
            rows="4"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <div>
          <p class="text-xs text-slate-400">Spécialités</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="option in COACH_SPECIALTY_OPTIONS"
              :key="option.value"
              type="button"
              class="rounded-full border px-3 py-1 text-xs font-medium transition"
              :class="
                profileForm.specialties.includes(option.value)
                  ? 'border-blue-500/60 bg-blue-600/20 text-blue-200'
                  : 'border-slate-700 text-slate-400 hover:border-slate-600'
              "
              @click="toggleSpecialty(option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
        <label class="block text-xs text-slate-400">
          Années d'expérience
          <input
            v-model.number="profileForm.years_experience"
            type="number"
            min="0"
            max="60"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <label class="block text-xs text-slate-400">
          Certifications
          <textarea
            v-model="profileForm.certifications"
            rows="2"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <label class="block text-xs text-slate-400">
          Club / salle
          <input
            v-model="profileForm.club_gym"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <div class="flex gap-2">
          <button
            type="button"
            class="flex-1 rounded-lg border border-slate-600 px-3 py-2 text-sm font-semibold text-slate-300 hover:bg-slate-800"
            @click="cancelEdit"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="flex-1 rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
            :disabled="profileForm.processing"
          >
            Enregistrer
          </button>
        </div>
      </form>
    </div>

    <!-- Configuration (uniquement pour le coach propriétaire) -->
    <template v-if="canEdit">
      <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg">
        <h2 class="text-sm font-semibold text-white">Configuration</h2>
        <p class="mt-1 text-xs text-slate-500">
          Paramètres par défaut appliqués à ta roster et à tes programmes.
        </p>

        <div class="mt-4 space-y-3">
          <!-- Questionnaires quotidiens -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-white">Questionnaires quotidiens</p>
              <p class="mt-0.5 text-xs text-slate-400">
                Formulaire facteurs externes par défaut
                <span class="text-slate-500">({{ readinessFieldCount }} champs)</span>
              </p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/20"
              @click="showReadinessBuilder = true"
            >
              Configurer
            </button>
          </div>

          <!-- Table infos programme -->
          <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-medium text-white">Table programme</p>
                <p class="mt-0.5 text-xs text-slate-400">
                  Colonnes et modes d’affichage des séances
                </p>
              </div>
              <button
                type="button"
                class="shrink-0 rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/20"
                @click="showLayoutModal = true"
              >
                Configurer
              </button>
            </div>

            <ul v-if="dayTableLayouts.length" class="mt-3 space-y-2">
              <li
                v-for="layout in dayTableLayouts"
                :key="layout.id"
                class="rounded-lg border border-slate-800/80 bg-slate-900/40 px-3 py-2"
              >
                <div class="flex flex-wrap items-center gap-2">
                  <span class="text-sm font-medium text-slate-200">{{ layout.name }}</span>
                  <span
                    v-if="layout.is_default || layout.id === defaultDayTableLayoutId"
                    class="rounded-full border border-emerald-500/40 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-300"
                  >
                    Défaut
                  </span>
                </div>
                <p class="mt-1 text-xs text-slate-500">{{ layoutColumnsLabel(layout) }}</p>
              </li>
            </ul>
            <p v-else class="mt-3 text-xs text-slate-500">
              Aucun modèle de table pour l’instant.
            </p>
          </div>

          <!-- Banque d'exercices -->
          <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium text-white">Banque d’exercices</p>
              <p class="mt-0.5 text-xs text-slate-400">
                {{ exerciseCounts.total }} exercices
                <span class="text-slate-500">
                  ({{ exerciseCounts.builtin }} catalogue · {{ exerciseCounts.custom }} custom)
                </span>
              </p>
            </div>
            <button
              type="button"
              class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/20"
              @click="showExercisesModal = true"
            >
              <UiIcon name="clipboard" class="h-3.5 w-3.5" />
              Ouvrir
            </button>
          </div>
        </div>
      </section>
    </template>

    <ReadinessFormBuilderModal
      v-if="canEdit"
      :open="showReadinessBuilder"
      mode="template"
      title="Formulaire facteurs externes par défaut"
      :initial-fields="coachReadinessForm?.fields ?? []"
      @close="showReadinessBuilder = false"
    />

    <DayTableLayoutModal
      v-if="canEdit"
      :open="showLayoutModal"
      :layouts="dayTableLayouts"
      :default-layout-id="defaultDayTableLayoutId"
      @close="showLayoutModal = false"
    />

    <CustomExercisesModal v-if="canEdit" v-model:open="showExercisesModal" />
  </div>
</template>
