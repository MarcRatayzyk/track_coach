<script setup>
import { computed, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import PrEvolutionMiniCard from './charts/PrEvolutionMiniCard.vue';
import { formatCalendarFr } from '../utils/formatDates';
import { buildPrEvolutionSeries, currentValueFromSeries } from '../utils/prEvolution';
import { LIFT_LABELS } from '../utils/chartTheme';
import {
  LEVEL_OPTIONS,
  SEX_OPTIONS,
  levelLabel as formatLevelLabel,
  weightCategoriesForSex,
  weightCategoryLabel,
} from '../config/ipfWeightCategories';
import { formatAthleteCategoryLine, formatAthleteDisplayName } from '../utils/athleteDisplay';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  name: {
    type: String,
    default: '',
  },
  email: {
    type: String,
    default: '',
  },
  weightClass: {
    type: String,
    default: '—',
  },
  weightCategory: {
    type: String,
    default: '',
  },
  birthDate: {
    type: String,
    default: '',
  },
  heightLabel: {
    type: String,
    default: '—',
  },
  levelLabel: {
    type: String,
    default: '—',
  },
  injuriesNotes: {
    type: String,
    default: '',
  },
  practiceDurationLabel: {
    type: String,
    default: '—',
  },
  profession: {
    type: String,
    default: '—',
  },
  ageLabel: {
    type: String,
    default: '—',
  },
  bio: {
    type: String,
    default: '',
  },
  canEditProfile: {
    type: Boolean,
    default: false,
  },
  canEditPrs: {
    type: Boolean,
    default: false,
  },
  editableProfile: {
    type: Object,
    default: null,
  },
  latestCompetitionDateLabel: {
    type: String,
    default: '—',
  },
  latestCompetitionBars: {
    type: Object,
    default: () => ({ squat: '—', bench: '—', deadlift: '—' }),
  },
  personalRecords: {
    type: Array,
    default: () => [],
  },
  nextCompetition: {
    type: Object,
    default: null,
  },
  isCoach: {
    type: Boolean,
    default: false,
  },
  canManageCompetitions: {
    type: Boolean,
    default: false,
  },
  feedbackFrequencyLabel: {
    type: String,
    default: 'Hebdomadaire',
  },
  nextFeedbackButtonLabel: {
    type: String,
    default: '',
  },
  programUrl: {
    type: String,
    default: null,
  },
  programExportUrl: {
    type: String,
    default: null,
  },
  athleteId: {
    type: [Number, String],
    default: null,
  },
});

const emit = defineEmits([
  'open-competition',
  'add-competition',
  'toggle-feedback-frequency',
  'save-profile',
]);

const showProfileModal = ref(false);
const showEditForm = ref(false);
const showPrForm = ref(false);
const today = new Date().toISOString().slice(0, 10);

const prForm = useForm({
  squat: '',
  bench: '',
  deadlift: '',
  reference_date: today,
});

watch(showProfileModal, (open) => {
  if (!open) {
    showEditForm.value = false;
    showPrForm.value = false;
  }
});

const prSubmitUrl = computed(() => {
  if (!props.athleteId) {
    return null;
  }

  return props.isCoach
    ? `/coach/athletes/${props.athleteId}/prs`
    : `/athletes/${props.athleteId}/prs`;
});

function openPrForm() {
  const values = Object.fromEntries(
    modalPrValues.value.map((card) => [card.key, Number(card.value ?? 0)]),
  );

  prForm.clearErrors();
  prForm.squat = values.squat > 0 ? values.squat : 0;
  prForm.bench = values.bench > 0 ? values.bench : 0;
  prForm.deadlift = values.deadlift > 0 ? values.deadlift : 0;
  prForm.reference_date = today;
  showPrForm.value = true;
}

function submitPr() {
  if (!prSubmitUrl.value) {
    return;
  }

  prForm
    .transform((data) => ({
      squat: Number(data.squat) || 0,
      bench: Number(data.bench) || 0,
      deadlift: Number(data.deadlift) || 0,
      reference_date: data.reference_date,
    }))
    .post(prSubmitUrl.value, {
      preserveScroll: true,
      onSuccess: () => {
        showPrForm.value = false;
      },
    });
}

const categoryOptions = computed(() => {
  const sex = props.editableProfile?.sex ?? null;
  return weightCategoriesForSex(sex);
});

watch(
  () => props.editableProfile?.sex,
  (sex, previousSex) => {
    if (!props.editableProfile || sex === previousSex) {
      return;
    }
    const options = weightCategoriesForSex(sex).map((item) => item.value);
    if (props.editableProfile.weight_category && !options.includes(props.editableProfile.weight_category)) {
      props.editableProfile.weight_category = '';
    }
  },
);

const lifts = [
  { key: 'squat', label: 'Squat' },
  { key: 'bench', label: 'Bench' },
  { key: 'deadlift', label: 'Terre' },
];

const prEvolution = computed(() =>
  buildPrEvolutionSeries({
    personalRecords: props.personalRecords,
  }),
);

const prCards = computed(() => [
  { key: 'squat', label: LIFT_LABELS.squat, series: prEvolution.value.squat, colorKey: 'squat' },
  { key: 'bench', label: LIFT_LABELS.bench, series: prEvolution.value.bench, colorKey: 'squat' },
  { key: 'deadlift', label: LIFT_LABELS.deadlift, series: prEvolution.value.deadlift, colorKey: 'squat' },
  { key: 'total', label: LIFT_LABELS.total, series: prEvolution.value.total, usePrGlow: true },
]);

const displayName = computed(() => formatAthleteDisplayName(props.name));

const categoryLine = computed(() =>
  formatAthleteCategoryLine(props.weightCategory, props.birthDate),
);

const modalPrValues = computed(() =>
  prCards.value.map((card) => ({
    ...card,
    value: currentValueFromSeries(card.series),
  })),
);

const competitionCountdown = computed(() => {
  if (!props.nextCompetition) {
    return null;
  }
  const days = props.nextCompetition.days_until;
  if (days === 0) {
    return 'J-0';
  }
  return `J-${days}`;
});

const nextCompetitionDateLabel = computed(() =>
  props.nextCompetition?.competition_date
    ? formatCalendarFr(props.nextCompetition.competition_date, 'medium')
    : '—',
);

function formatKg(value) {
  const numeric = Number(value ?? 0);
  return numeric > 0 ? `${numeric} kg` : '—';
}

function openNextCompetition() {
  if (props.nextCompetition) {
    emit('open-competition', props.nextCompetition);
    showProfileModal.value = false;
  }
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/50 p-3 shadow-lg">
    <div class="mb-3">
      <div class="flex items-start justify-between gap-3">
        <h2 class="truncate text-xl font-bold text-white sm:text-2xl">{{ displayName }}</h2>
        <div class="flex shrink-0 items-center gap-2">
          <a
            v-if="isCoach && programExportUrl"
            :href="programExportUrl"
            class="rounded-lg border border-slate-600 px-2.5 py-1.5 text-xs font-semibold text-slate-200 hover:bg-slate-800/60"
          >
            PDF
          </a>
          <Link
            v-if="isCoach && athleteId"
            :href="`/messaging?athlete=${athleteId}`"
            class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-2 text-slate-400 transition hover:border-blue-500/40 hover:bg-slate-800 hover:text-blue-300"
            aria-label="Ouvrir la conversation"
            title="Messagerie"
          >
            <UiIcon name="chat" class="h-5 w-5" />
          </Link>
          <button
            type="button"
            class="rounded-lg border border-slate-700/80 bg-slate-950/60 p-2 text-slate-400 transition hover:border-slate-600 hover:bg-slate-800 hover:text-white"
            aria-label="Voir le profil"
            @click="showProfileModal = true"
          >
            <UiIcon name="user-circle" class="h-5 w-5" />
          </button>
        </div>
      </div>

      <div class="mt-2 flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <p class="text-base font-medium text-slate-300 sm:text-lg">{{ categoryLine }}</p>
        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <button
            v-if="nextCompetition"
            type="button"
            class="rounded-lg border border-amber-500/50 bg-amber-500/10 px-3.5 py-1.5 text-sm font-semibold tabular-nums text-amber-200 transition hover:border-amber-400/70 hover:bg-amber-500/15"
            @click="openNextCompetition"
          >
            {{ competitionCountdown }}
          </button>
          <Link
            v-if="isCoach && programUrl"
            :href="programUrl"
            class="rounded-lg border border-emerald-500/50 bg-emerald-950/30 px-3.5 py-1.5 text-sm font-semibold text-emerald-200 transition hover:border-emerald-400/70 hover:bg-emerald-950/50"
          >
            Aller au programme
          </Link>
        </div>
      </div>
    </div>

    <div
      class="grid gap-1.5 sm:grid-cols-4"
      :class="isCoach ? 'grid-cols-2' : 'grid-cols-2 lg:grid-cols-4'"
    >
      <PrEvolutionMiniCard
        v-for="card in prCards"
        :key="card.key"
        :lift-key="card.key"
        :color-key="card.colorKey"
        :label="card.label"
        :series="card.series"
        :use-pr-glow="Boolean(card.usePrGlow)"
      />
    </div>

    <Teleport to="body">
      <div
        v-if="showProfileModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="athlete-profile-title"
        @click.self="showProfileModal = false"
      >
        <div
          class="tc-scrollbar max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <h2 id="athlete-profile-title" class="text-lg font-bold text-white">{{ displayName }}</h2>
              <p v-if="email" class="mt-0.5 truncate text-sm text-slate-400">{{ email }}</p>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="showProfileModal = false"
            >
              ✕
            </button>
          </div>

          <dl class="mt-4 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Temps de pratique</dt>
              <dd class="font-semibold text-white">{{ practiceDurationLabel || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Âge</dt>
              <dd class="font-semibold text-white">{{ ageLabel || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Taille</dt>
              <dd class="font-semibold text-white">{{ heightLabel || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Profession</dt>
              <dd class="font-semibold text-white">{{ profession || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Catégorie</dt>
              <dd class="font-semibold text-white">{{ weightClass || '—' }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Niveau</dt>
              <dd class="font-semibold text-white">{{ levelLabel || '—' }}</dd>
            </div>
            <div
              v-if="injuriesNotes && !canEditProfile"
              class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
            >
              <dt class="text-slate-500">Blessures / gênes</dt>
              <dd class="mt-1 whitespace-pre-wrap text-white">{{ injuriesNotes }}</dd>
            </div>
            <div class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2">
              <dt class="text-slate-500">Retours vidéo</dt>
              <dd class="mt-1 flex items-center justify-between gap-3">
                <span class="font-semibold text-white">{{ feedbackFrequencyLabel }}</span>
                <button
                  v-if="isCoach && nextFeedbackButtonLabel"
                  type="button"
                  class="shrink-0 rounded-lg border border-blue-500/40 px-2.5 py-1 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
                  @click="emit('toggle-feedback-frequency')"
                >
                  {{ nextFeedbackButtonLabel }}
                </button>
              </dd>
            </div>
          </dl>

          <div
            v-if="bio && !canEditProfile"
            class="mt-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-300"
          >
            <p class="text-xs font-semibold text-slate-500">Objectifs</p>
            <p class="mt-1 whitespace-pre-wrap">{{ bio }}</p>
          </div>

          <button
            v-if="canEditProfile && editableProfile && !showEditForm"
            type="button"
            class="mt-4 w-full rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-2.5 text-sm font-semibold text-blue-300 transition hover:bg-blue-500/20"
            @click="showEditForm = true"
          >
            Modifier le profil
          </button>

          <form
            v-if="canEditProfile && editableProfile && showEditForm"
            class="mt-4 space-y-3 rounded-lg border border-slate-800 bg-slate-950/50 p-3"
            @submit.prevent="emit('save-profile')"
          >
            <p class="text-xs font-semibold text-white">Modifier le profil</p>
            <label class="block text-xs text-slate-400">
              Date de naissance
              <input
                v-model="editableProfile.birth_date"
                type="date"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              />
            </label>
            <label class="block text-xs text-slate-400">
              Taille (cm)
              <input
                v-model.number="editableProfile.height_cm"
                type="number"
                min="100"
                max="250"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              />
            </label>
            <label class="block text-xs text-slate-400">
              Sexe (catégorie IPF)
              <select
                v-model="editableProfile.sex"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              >
                <option value="">—</option>
                <option v-for="option in SEX_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>
            <label class="block text-xs text-slate-400">
              Profession
              <input
                v-model="editableProfile.profession"
                type="text"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              />
            </label>
            <label class="block text-xs text-slate-400">
              Catégorie de poids IPF
              <select
                v-model="editableProfile.weight_category"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              >
                <option value="">—</option>
                <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>
            <label class="block text-xs text-slate-400">
              Niveau
              <select
                v-model="editableProfile.level"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              >
                <option value="">—</option>
                <option v-for="option in LEVEL_OPTIONS" :key="option.value" :value="option.value">
                  {{ option.label }}
                </option>
              </select>
            </label>
            <label class="block text-xs text-slate-400">
              Blessures / gênes récentes
              <textarea
                v-model="editableProfile.injuries_notes"
                rows="2"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
                placeholder="Douleur épaule droite, genou…"
              />
            </label>
            <label class="block text-xs text-slate-400">
              Objectifs
              <textarea
                v-model="editableProfile.bio"
                rows="3"
                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              />
            </label>
            <button
              type="submit"
              class="w-full rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-500"
            >
              Enregistrer
            </button>
          </form>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-xs font-semibold text-white">Prochaine compétition</p>
                <template v-if="nextCompetition">
                  <p class="mt-1 text-lg font-bold tabular-nums text-white">{{ competitionCountdown }}</p>
                  <p class="truncate text-sm text-slate-300">{{ nextCompetition.name }}</p>
                  <p class="text-[11px] text-slate-500">{{ nextCompetitionDateLabel }}</p>
                  <p v-if="nextCompetition.goal" class="mt-0.5 text-xs text-slate-400">
                    Objectif : {{ nextCompetition.goal }}
                  </p>
                </template>
                <p v-else class="mt-1 text-sm text-slate-500">—</p>
              </div>
              <div class="flex shrink-0 flex-col gap-2">
              <Link
                v-if="nextCompetition && nextCompetition.days_until === 0 && athleteId"
                :href="`/athletes/${athleteId}/competitions/${nextCompetition.id}/live`"
                class="rounded-lg bg-emerald-600 px-2.5 py-1 text-center text-xs font-semibold text-white hover:bg-emerald-500"
              >
                Meet live
              </Link>
              <button
                v-if="nextCompetition"
                type="button"
                class="shrink-0 rounded-lg border border-amber-500/40 px-2.5 py-1 text-xs font-semibold text-amber-300 hover:bg-amber-500/10"
                @click="openNextCompetition"
              >
                Voir
              </button>
              <button
                v-else-if="canManageCompetitions"
                type="button"
                class="shrink-0 rounded-lg border border-blue-500/40 px-2.5 py-1 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
                @click="emit('add-competition')"
              >
                Ajouter
              </button>
              </div>
            </div>
          </div>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <div class="flex items-center justify-between gap-2">
              <p class="text-xs font-semibold text-white">Records personnels</p>
              <button
                v-if="canEditPrs && athleteId && !showPrForm"
                type="button"
                class="shrink-0 rounded-lg border border-slate-600/80 px-2.5 py-1 text-xs font-semibold text-slate-300 hover:bg-slate-800/70 hover:text-white"
                @click="openPrForm"
              >
                Modifier
              </button>
            </div>

            <ul v-if="!showPrForm" class="mt-2 space-y-1.5 text-sm">
              <li
                v-for="card in modalPrValues"
                :key="`modal-pr-${card.key}`"
                class="flex items-center justify-between gap-2"
              >
                <span class="text-slate-400">{{ card.label }}</span>
                <span class="font-semibold tabular-nums text-white">{{ formatKg(card.value) }}</span>
              </li>
            </ul>

            <form
              v-else
              class="mt-3 space-y-2.5"
              @submit.prevent="submitPr"
            >
              <div class="grid grid-cols-3 gap-2">
                <label class="text-[11px] text-slate-400">
                  Squat
                  <input
                    v-model.number="prForm.squat"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-sm text-white"
                  />
                </label>
                <label class="text-[11px] text-slate-400">
                  Bench
                  <input
                    v-model.number="prForm.bench"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-sm text-white"
                  />
                </label>
                <label class="text-[11px] text-slate-400">
                  Terre
                  <input
                    v-model.number="prForm.deadlift"
                    type="number"
                    min="0"
                    inputmode="numeric"
                    class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-sm text-white"
                  />
                </label>
              </div>
              <label class="block text-[11px] text-slate-400">
                Date
                <input
                  v-model="prForm.reference_date"
                  type="date"
                  class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-sm text-white"
                />
              </label>
              <p
                v-if="prForm.errors.squat || prForm.errors.bench || prForm.errors.deadlift || prForm.errors.reference_date"
                class="text-xs text-red-400"
              >
                {{ prForm.errors.squat || prForm.errors.bench || prForm.errors.deadlift || prForm.errors.reference_date }}
              </p>
              <div class="flex justify-end gap-2 pt-1">
                <button
                  type="button"
                  class="rounded-lg border border-slate-600 px-2.5 py-1.5 text-xs font-medium text-slate-300 hover:bg-slate-800"
                  @click="showPrForm = false"
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  :disabled="prForm.processing"
                  class="rounded-lg bg-blue-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                >
                  Enregistrer
                </button>
              </div>
            </form>
          </div>

          <div class="mt-4 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2.5">
            <div class="flex flex-wrap items-baseline gap-x-2">
              <p class="text-xs font-semibold text-white">Dernière compétition</p>
              <span class="text-[10px] text-slate-500">{{ latestCompetitionDateLabel }}</span>
            </div>
            <div class="mt-1.5 space-y-1 text-sm text-slate-300">
              <p v-for="lift in lifts" :key="`modal-comp-${lift.key}`">
                <span class="text-slate-500">{{ lift.label }}</span>
                {{ latestCompetitionBars[lift.key] }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
