<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AthleteMonthCalendar from '../Components/AthleteMonthCalendar.vue';
import AthleteProfileOverview from '../Components/AthleteProfileOverview.vue';
import AthleteStatsOverview from '../Components/AthleteStatsOverview.vue';
import CompetitionDetailPanel from '../Components/CompetitionDetailPanel.vue';
import MatchPlanBuilder from '../Components/MatchPlanBuilder.vue';
import TrainingSessionEditorModal from '../Components/TrainingSessionEditorModal.vue';
import { buildAthleteOverviewStats } from '../utils/athleteOverviewStats';
import { levelLabel as formatLevelLabel, weightCategoryLabel } from '../config/ipfWeightCategories';
import { formatCalendarFr } from '../utils/formatDates';
import { defaultStructuredPlan, matchPlanFromCompetition } from '../utils/matchPlan';

const props = defineProps({
  athlete: {
    type: Object,
    required: true,
  },
  activeProgram: {
    type: Object,
    default: null,
  },
  programBlock: {
    type: Object,
    default: null,
  },
  followUpStartedAt: {
    type: String,
    default: null,
  },
  todayReadiness: {
    type: Object,
    default: null,
  },
  readinessRecent: {
    type: Array,
    default: () => [],
  },
  todayBodyWeight: {
    type: Object,
    default: null,
  },
  bodyWeightRecent: {
    type: Array,
    default: () => [],
  },
  programHistory: {
    type: Array,
    default: () => [],
  },
  funStats: {
    type: Object,
    default: null,
  },
});

const today = new Date().toISOString().slice(0, 10);

const page = usePage();
const isCoach = computed(() => page.props.auth?.user?.role === 'coach');
const isOwnProfile = computed(() => page.props.auth?.user?.id === props.athlete.id);
const canManageCompetitions = computed(() => isCoach.value || isOwnProfile.value);
const canManageSessions = computed(
  () => isCoach.value || isOwnProfile.value,
);
const canProposeMatchPlan = computed(
  () => !isCoach.value && isOwnProfile.value,
);

function competitionBasePath() {
  return isCoach.value
    ? `/coach/athletes/${props.athlete.id}/competitions`
    : `/athletes/${props.athlete.id}/competitions`;
}
const readinessRecent = computed(() => props.readinessRecent ?? []);
const bodyWeightRecent = computed(() => props.bodyWeightRecent ?? []);
const compareIds = ref([]);
const comparisonBlocks = ref([]);

async function loadComparison() {
  if (compareIds.value.length !== 2) {
    comparisonBlocks.value = [];
    return;
  }

  const params = new URLSearchParams();
  compareIds.value.forEach((id) => params.append('ids[]', id));

  const response = await fetch(`/athletes/${props.athlete.id}/program-history/compare?${params.toString()}`, {
    headers: { Accept: 'application/json' },
  });

  if (response.ok) {
    const data = await response.json();
    comparisonBlocks.value = data.blocks ?? [];
  }
}

function toggleCompareId(id) {
  if (compareIds.value.includes(id)) {
    compareIds.value = compareIds.value.filter((value) => value !== id);
  } else if (compareIds.value.length < 2) {
    compareIds.value = [...compareIds.value, id];
  } else {
    compareIds.value = [compareIds.value[1], id];
  }
  loadComparison();
}

const compForm = useForm({
  name: '',
  competition_date: today,
  goal: '',
  location: '',
  match_plan: '',
  match_plan_data: defaultStructuredPlan(),
});

const showCompModal = ref(false);
const editingComp = ref(false);
const editingMatchPlan = ref(false);
const addingCompetition = ref(false);
const selectedCompetition = ref(null);
const sessionModalOpen = ref(false);
const editingSession = ref(null);
const selectedSessionDay = ref(null);

const editCompForm = useForm({
  name: '',
  competition_date: today,
  goal: '',
  location: '',
  match_plan: '',
  match_plan_data: defaultStructuredPlan(),
});

const matchPlanForm = useForm({
  match_plan_data: defaultStructuredPlan(),
});

function compDateKey(comp) {
  const s = String(comp.competition_date ?? '');
  const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : '';
}

const upcomingCompetitions = computed(() =>
  (props.athlete.competitions ?? []).filter((c) => compDateKey(c) >= today),
);

const nextCompetition = computed(() => {
  const upcoming = [...upcomingCompetitions.value].sort((a, b) =>
    compDateKey(a).localeCompare(compDateKey(b)),
  );

  if (!upcoming.length) {
    return null;
  }

  const comp = upcoming[0];
  const compDate = new Date(compDateKey(comp));
  compDate.setHours(12, 0, 0, 0);
  const todayDate = new Date(today);
  todayDate.setHours(12, 0, 0, 0);
  const daysUntil = Math.floor((compDate.getTime() - todayDate.getTime()) / (24 * 60 * 60 * 1000));

  return {
    ...comp,
    days_until: daysUntil,
  };
});

function isUpcomingCompetition(comp) {
  return compDateKey(comp) >= today;
}

function competitionHasMatchPlan(comp) {
  if (!comp) {
    return false;
  }
  if (String(comp.match_plan ?? '').trim()) {
    return true;
  }
  return Boolean(comp.match_plan_data?.scenarios?.length);
}

function competitionForPanel(comp) {
  return {
    ...comp,
    athlete_id: props.athlete.id,
    athlete: { name: props.athlete.name },
  };
}

function openCompetition(comp) {
  selectedCompetition.value = competitionForPanel(comp);
  editingComp.value = false;
  editingMatchPlan.value = false;
  addingCompetition.value = false;
  showCompModal.value = true;
}

function openAddCompetitionModal() {
  selectedCompetition.value = null;
  editingComp.value = false;
  editingMatchPlan.value = false;
  addingCompetition.value = true;
  compForm.reset();
  compForm.competition_date = today;
  compForm.match_plan_data = defaultStructuredPlan();
  showCompModal.value = true;
}

function maybeOpenCompetitionFromQuery() {
  if (typeof window === 'undefined') {
    return;
  }

  const searchParams = new URLSearchParams(window.location.search);
  const competitionId = Number(searchParams.get('competition'));

  if (!Number.isInteger(competitionId)) {
    return;
  }

  const targetCompetition = (props.athlete.competitions ?? []).find(
    (competition) => competition.id === competitionId,
  );

  if (!targetCompetition) {
    return;
  }

  openCompetition(targetCompetition);

  if (searchParams.get('edit') === '1' && canManageCompetitions.value) {
    startEditCompetition();
  }
}

function closeCompModal() {
  showCompModal.value = false;
  editingComp.value = false;
  editingMatchPlan.value = false;
  addingCompetition.value = false;
  selectedCompetition.value = null;
}

function startEditMatchPlan() {
  if (!selectedCompetition.value) {
    return;
  }
  matchPlanForm.match_plan_data = matchPlanFromCompetition(selectedCompetition.value);
  editingMatchPlan.value = true;
}

function cancelEditMatchPlan() {
  editingMatchPlan.value = false;
  matchPlanForm.reset();
}

function submitMatchPlan() {
  if (!selectedCompetition.value) {
    return;
  }

  matchPlanForm.patch(
    `/athletes/${props.athlete.id}/competitions/${selectedCompetition.value.id}/match-plan`,
    {
      preserveScroll: true,
      onSuccess: () => {
        editingMatchPlan.value = false;
        closeCompModal();
      },
    },
  );
}

function startEditCompetition() {
  if (!selectedCompetition.value) {
    return;
  }
  const c = selectedCompetition.value;
  editCompForm.name = c.name ?? '';
  editCompForm.competition_date = compDateKey(c) || today;
  editCompForm.goal = c.goal ?? '';
  editCompForm.location = c.location ?? '';
  editCompForm.match_plan = c.match_plan ?? '';
  editCompForm.match_plan_data = matchPlanFromCompetition(c);
  editingComp.value = true;
}

function submitEditCompetition() {
  if (!selectedCompetition.value) {
    return;
  }
  editCompForm.patch(
    `${competitionBasePath()}/${selectedCompetition.value.id}`,
    {
      preserveScroll: true,
      onSuccess: () => closeCompModal(),
    },
  );
}

function deleteSelectedCompetition() {
  if (!selectedCompetition.value) {
    return;
  }

  const confirmed = window.confirm(
    'Supprimer cette competition ? Cette action est irreversible.',
  );

  if (!confirmed) {
    return;
  }

  editCompForm.delete(
    `${competitionBasePath()}/${selectedCompetition.value.id}`,
    {
      preserveScroll: true,
      onSuccess: () => closeCompModal(),
    },
  );
}

const profileForm = useForm({
  feedback_frequency: props.athlete.profile?.feedback_frequency ?? 'weekly',
});

const ownProfileForm = useForm({
  birth_date: props.athlete.profile?.birth_date?.slice?.(0, 10) ?? props.athlete.profile?.birth_date ?? '',
  height_cm: props.athlete.profile?.height_cm ?? null,
  sex: props.athlete.profile?.sex ?? '',
  weight_category: props.athlete.profile?.weight_category ?? '',
  level: props.athlete.profile?.level ?? '',
  injuries_notes: props.athlete.profile?.injuries_notes ?? '',
  profession: props.athlete.profile?.profession ?? '',
  bio: props.athlete.profile?.bio ?? '',
});

const coachProfileForm = useForm({
  birth_date: props.athlete.profile?.birth_date?.slice?.(0, 10) ?? props.athlete.profile?.birth_date ?? '',
  height_cm: props.athlete.profile?.height_cm ?? null,
  sex: props.athlete.profile?.sex ?? '',
  weight_category: props.athlete.profile?.weight_category ?? '',
  level: props.athlete.profile?.level ?? '',
  injuries_notes: props.athlete.profile?.injuries_notes ?? '',
  profession: props.athlete.profile?.profession ?? '',
  bio: props.athlete.profile?.bio ?? '',
});

const editableProfileForm = computed(() => {
  if (isOwnProfile.value) {
    return ownProfileForm;
  }
  if (isCoach.value) {
    return coachProfileForm;
  }
  return null;
});

const canEditProfile = computed(() => isOwnProfile.value || isCoach.value);

const feedbackLabels = {
  daily: 'Journalier',
  weekly: 'Hebdomadaire',
};

const timeRange = ref('6m');

const timeRangeOptions = [
  { value: '1m', label: '1 mois' },
  { value: '6m', label: '6 mois' },
  { value: '1y', label: '1 an' },
  { value: 'all', label: 'Depuis le début du suivi' },
];

const currentFeedbackFrequency = computed(
  () => props.athlete.profile?.feedback_frequency ?? 'weekly',
);

const nextFeedbackFrequency = computed(() =>
  currentFeedbackFrequency.value === 'daily' ? 'weekly' : 'daily',
);

const nextFeedbackButtonLabel = computed(() =>
  nextFeedbackFrequency.value === 'daily'
    ? 'Passer en Journalier'
    : 'Passer en Hebdomadaire',
);

const followUpStartedAtLabel = computed(() =>
  props.followUpStartedAt ? formatCalendarFr(props.followUpStartedAt, 'medium') : '—',
);

const trainingSessions = computed(() => props.athlete.training_sessions ?? []);

const trainingPrs = computed(() =>
  trainingSessions.value.reduce(
    (acc, session) => ({
      squat: Math.max(acc.squat, Number(session.squat ?? 0)),
      bench: Math.max(acc.bench, Number(session.bench ?? 0)),
      deadlift: Math.max(acc.deadlift, Number(session.deadlift ?? 0)),
    }),
    { squat: 0, bench: 0, deadlift: 0 },
  ),
);

const referenceLifts = computed(() => {
  const pr = props.athlete.latest_pr;
  const fromSessions = trainingPrs.value;
  return {
    squat: Math.max(Number(pr?.squat ?? 0), fromSessions.squat),
    bench: Math.max(Number(pr?.bench ?? 0), fromSessions.bench),
    deadlift: Math.max(Number(pr?.deadlift ?? 0), fromSessions.deadlift),
  };
});

const latestPastCompetition = computed(() => {
  const past = (props.athlete.competitions ?? []).filter((c) => compDateKey(c) < today);
  if (!past.length) {
    return null;
  }
  return [...past].sort((a, b) => compDateKey(b).localeCompare(compDateKey(a)))[0];
});

function competitionLiftBar(comp, lift) {
  if (!comp) {
    return '—';
  }
  const plan = matchPlanFromCompetition(comp);
  if (plan.mode !== 'structured') {
    return '—';
  }
  const scenario = plan.scenarios?.[0];
  if (!scenario?.lifts?.[lift]) {
    return '—';
  }
  const a1 = scenario.lifts[lift].attempt1 ?? '—';
  const a2 = scenario.lifts[lift].attempt2 ?? '—';
  const a3 = scenario.lifts[lift].attempt3 ?? '—';
  return `${a1} / ${a2} / ${a3}`;
}

const latestCompetitionBars = computed(() => ({
  squat: competitionLiftBar(latestPastCompetition.value, 'squat'),
  bench: competitionLiftBar(latestPastCompetition.value, 'bench'),
  deadlift: competitionLiftBar(latestPastCompetition.value, 'deadlift'),
}));

const latestCompetitionDateLabel = computed(() =>
  latestPastCompetition.value
    ? formatCalendarFr(latestPastCompetition.value.competition_date, 'medium')
    : '—',
);

const practiceStartDate = computed(() => {
  const sessionDates = trainingSessions.value
    .map((session) => String(session.session_date ?? '').slice(0, 10))
    .filter(Boolean);
  if (sessionDates.length) {
    return [...sessionDates].sort()[0];
  }
  return props.followUpStartedAt ?? null;
});

const practiceDurationLabel = computed(() => {
  const years = props.athlete.profile?.years_training;
  if (years != null && years !== '') {
    const value = Number(years);
    if (value === 0) {
      return "Moins d'1 an";
    }
    if (value === 1) {
      return '1 an';
    }
    return `${value} ans`;
  }

  if (!practiceStartDate.value) {
    return '—';
  }
  const start = new Date(practiceStartDate.value);
  const now = new Date();
  let months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());
  if (months < 0) {
    months = 0;
  }
  const yearsFromStart = Math.floor(months / 12);
  const remainingMonths = months % 12;
  if (yearsFromStart === 0) {
    return `${remainingMonths} mois`;
  }
  if (remainingMonths === 0) {
    return `${yearsFromStart} an${yearsFromStart > 1 ? 's' : ''}`;
  }
  return `${yearsFromStart} an${yearsFromStart > 1 ? 's' : ''} ${remainingMonths} mois`;
});

const ageLabel = computed(() => {
  const birthDate = props.athlete.profile?.birth_date;
  if (!birthDate) {
    return '—';
  }
  const birth = new Date(String(birthDate).slice(0, 10));
  if (!Number.isFinite(birth.getTime())) {
    return '—';
  }
  const now = new Date();
  let age = now.getFullYear() - birth.getFullYear();
  const monthDiff = now.getMonth() - birth.getMonth();
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) {
    age -= 1;
  }
  return age > 0 ? `${age} ans` : '—';
});

const heightLabel = computed(() => {
  const height = props.athlete.profile?.height_cm;
  return height ? `${height} cm` : '—';
});

const athleteLevelLabel = computed(() =>
  formatLevelLabel(props.athlete.profile?.level) ?? '—',
);

const athleteWeightCategoryLabel = computed(() =>
  weightCategoryLabel(props.athlete.profile?.weight_category) ?? '—',
);

const programUpcomingLabel = computed(() => {
  if (!props.programBlock?.starts_in_future || !props.programBlock?.date_start) {
    return null;
  }
  const days = Number(props.programBlock.days_until_start ?? 0);
  const dateLabel = formatCalendarFr(props.programBlock.date_start, 'medium');
  if (days <= 1) {
    return `Démarre demain (${dateLabel})`;
  }
  return `Démarre le ${dateLabel}`;
});

function subtractMonths(date, months) {
  const d = new Date(date);
  d.setMonth(d.getMonth() - months);
  return d;
}

const personalRecords = computed(() => props.athlete.personal_records ?? []);

const sortedPersonalRecords = computed(() =>
  [...personalRecords.value].sort((a, b) =>
    String(a.reference_date ?? '').localeCompare(String(b.reference_date ?? '')),
  ),
);

const latestOfficialPr = computed(() => {
  const records = sortedPersonalRecords.value;
  return records.length ? records[records.length - 1] : props.athlete.latest_pr ?? null;
});

function filterByTimeRange(items, dateField) {
  if (!items.length) {
    return items;
  }

  if (timeRange.value === 'all') {
    if (!props.followUpStartedAt) {
      return items;
    }
    const minFollowUp = new Date(props.followUpStartedAt);
    return items.filter((item) => {
      const d = new Date(String(item[dateField] ?? ''));
      return Number.isFinite(d.getTime()) && d >= minFollowUp;
    });
  }

  const now = new Date();
  let minDate = null;
  if (timeRange.value === '1m') {
    minDate = subtractMonths(now, 1);
  } else if (timeRange.value === '6m') {
    minDate = subtractMonths(now, 6);
  } else if (timeRange.value === '1y') {
    minDate = subtractMonths(now, 12);
  }

  if (!minDate) {
    return items;
  }

  return items.filter((item) => {
    const d = new Date(String(item[dateField] ?? ''));
    return Number.isFinite(d.getTime()) && d >= minDate;
  });
}

const filteredPrRecords = computed(() =>
  filterByTimeRange(sortedPersonalRecords.value, 'reference_date'),
);

const overviewStats = computed(() =>
  buildAthleteOverviewStats({
    trainingSessions: trainingSessions.value,
    programBlock: props.programBlock,
    oneRm: referenceLifts.value,
  }),
);

function submitComp() {
  compForm.post(competitionBasePath(), {
    preserveScroll: true,
    onSuccess: () => {
      compForm.reset();
      compForm.competition_date = today;
      compForm.match_plan_data = defaultStructuredPlan();
      closeCompModal();
    },
  });
}

function openEditSession(session) {
  editingSession.value = session;
  sessionModalOpen.value = true;
}

function closeSessionModal() {
  sessionModalOpen.value = false;
  editingSession.value = null;
}

function onSessionSaved({ sessionDate } = {}) {
  if (sessionDate) {
    selectedSessionDay.value = String(sessionDate).slice(0, 10);
  }
  closeSessionModal();
}

function submitOwnProfile() {
  if (isCoach.value) {
    coachProfileForm.patch(`/coach/athletes/${props.athlete.id}/profile`, {
      preserveScroll: true,
    });
    return;
  }

  ownProfileForm.patch(`/athletes/${props.athlete.id}/profile`, {
    preserveScroll: true,
  });
}

function submitProfile() {
  profileForm.patch(`/coach/athletes/${props.athlete.id}/profile`, {
    preserveScroll: true,
  });
}

function toggleFeedbackFrequency() {
  profileForm.feedback_frequency = nextFeedbackFrequency.value;
  submitProfile();
}

onMounted(() => {
  maybeOpenCompetitionFromQuery();
});
</script>

<template>
  <div>
    <Link
      v-if="isCoach"
      href="/athletes"
      class="text-sm font-medium text-blue-400 hover:text-blue-300"
    >
      ← Retour à la liste
    </Link>

    <div :class="isCoach ? 'mt-4' : ''">
      <AthleteProfileOverview
        :name="athlete.name"
        :email="athlete.email"
        :weight-class="athleteWeightCategoryLabel"
        :height-label="heightLabel"
        :level-label="athleteLevelLabel"
        :injuries-notes="athlete.profile?.injuries_notes ?? ''"
        :practice-duration-label="practiceDurationLabel"
        :profession="athlete.profile?.profession ?? '—'"
        :age-label="ageLabel"
        :bio="athlete.profile?.bio ?? ''"
        :can-edit-profile="canEditProfile"
        :editable-profile="editableProfileForm"
        :latest-competition-date-label="latestCompetitionDateLabel"
        :latest-competition-bars="latestCompetitionBars"
        :personal-records="personalRecords"
        :next-competition="nextCompetition"
        :athlete-id="athlete.id"
        :is-coach="isCoach"
        :can-manage-competitions="canManageCompetitions"
        :feedback-frequency-label="feedbackLabels[currentFeedbackFrequency]"
        :next-feedback-button-label="isCoach ? nextFeedbackButtonLabel : ''"
        :program-url="programBlock ? `/program-builder?assignment=${programBlock.id}` : null"
        :program-export-url="programBlock ? `/coach/program-blocks/${programBlock.id}/export-pdf` : null"
        @open-competition="openCompetition"
        @add-competition="openAddCompetitionModal"
        @toggle-feedback-frequency="toggleFeedbackFrequency"
        @save-profile="submitOwnProfile"
      />

      <section class="mt-3 min-w-0 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 pt-4 pb-2 shadow-lg lg:p-5 lg:pb-5">
        <h2 class="text-sm font-semibold text-white">Calendrier</h2>

        <AthleteMonthCalendar
          class="mt-2"
          :program-block="programBlock"
          :training-sessions="trainingSessions"
          :competitions="athlete.competitions ?? []"
          :can-edit="canManageSessions"
          @edit-session="openEditSession"
        />

        <TrainingSessionEditorModal
          :open="sessionModalOpen"
          :athlete-id="athlete.id"
          :session="editingSession"
          :default-date="selectedSessionDay || today"
          @close="closeSessionModal"
          @saved="onSessionSaved"
        />
      </section>

      <div class="mt-3">
        <AthleteStatsOverview
          :stats="overviewStats"
          :has-active-program="Boolean(programBlock)"
          :program-upcoming-label="programUpcomingLabel"
          :pr-records="filteredPrRecords"
          :readiness-recent="readinessRecent"
          :body-weight-recent="bodyWeightRecent"
          :training-sessions="trainingSessions"
          :fun-stats="funStats"
          :time-range="timeRange"
          :time-range-options="timeRangeOptions"
          @update:time-range="timeRange = $event"
        />
      </div>

      <section
        v-if="isCoach && programHistory.length"
        class="mt-3 rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg lg:p-5"
      >
        <h2 class="text-sm font-semibold text-white">Historique des blocs</h2>
        <p class="mt-1 text-xs text-slate-500">Sélectionnez deux blocs pour les comparer.</p>

        <ul class="mt-4 space-y-2">
          <li
            v-for="block in programHistory"
            :key="block.id"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2"
          >
            <div>
              <p class="font-medium text-white">{{ block.name }}</p>
              <p class="text-xs text-slate-500">
                {{ block.date_start }} → {{ block.date_end }}
                · Adhérence {{ block.adherence_percentage ?? '—' }}%
                · Volume {{ block.volume_sets_reps }}
              </p>
            </div>
            <label class="flex items-center gap-2 text-xs text-slate-300">
              <input
                type="checkbox"
                :checked="compareIds.includes(block.id)"
                @change="toggleCompareId(block.id)"
              />
              Comparer
            </label>
          </li>
        </ul>

        <div v-if="comparisonBlocks.length === 2" class="mt-4 overflow-x-auto">
          <table class="min-w-full text-left text-sm">
            <thead>
              <tr class="border-b border-slate-800 text-slate-400">
                <th class="px-2 py-2">Métrique</th>
                <th v-for="block in comparisonBlocks" :key="block.id" class="px-2 py-2">{{ block.name }}</th>
              </tr>
            </thead>
            <tbody class="text-slate-200">
              <tr class="border-b border-slate-900">
                <td class="px-2 py-2 text-slate-400">Période</td>
                <td v-for="block in comparisonBlocks" :key="`p-${block.id}`" class="px-2 py-2">
                  {{ block.date_start }} → {{ block.date_end }}
                </td>
              </tr>
              <tr class="border-b border-slate-900">
                <td class="px-2 py-2 text-slate-400">Adhérence</td>
                <td v-for="block in comparisonBlocks" :key="`a-${block.id}`" class="px-2 py-2">
                  {{ block.adherence_percentage ?? '—' }} %
                </td>
              </tr>
              <tr class="border-b border-slate-900">
                <td class="px-2 py-2 text-slate-400">Volume (séries×reps)</td>
                <td v-for="block in comparisonBlocks" :key="`v-${block.id}`" class="px-2 py-2">
                  {{ block.volume_sets_reps }}
                </td>
              </tr>
              <tr>
                <td class="px-2 py-2 text-slate-400">Total SBD (1RM)</td>
                <td v-for="block in comparisonBlocks" :key="`t-${block.id}`" class="px-2 py-2">
                  {{ block.sbd_total }} kg
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div
        v-if="showCompModal && (selectedCompetition || addingCompetition)"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="closeCompModal"
      >
        <div
          class="tc-scrollbar max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <h2 class="text-base font-semibold text-white">
              {{ addingCompetition ? 'Ajouter une compétition' : 'Détail compétition' }}
            </h2>
            <button
              type="button"
              class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="closeCompModal"
            >
              ✕
            </button>
          </div>

          <form
            v-if="addingCompetition && canManageCompetitions"
            class="mt-4 space-y-4"
            @submit.prevent="submitComp"
          >
            <label class="block text-sm text-slate-400">
              Nom
              <input
                v-model="compForm.name"
                type="text"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Date
              <input
                v-model="compForm.competition_date"
                type="date"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Lieu
              <input
                v-model="compForm.location"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Objectif
              <input
                v-model="compForm.goal"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <div class="block text-sm text-slate-400">
              <span class="block">Plan de match (optionnel)</span>
              <MatchPlanBuilder v-model="compForm.match_plan_data" class="mt-3" />
            </div>
            <p v-if="Object.keys(compForm.errors).length" class="text-sm text-red-400">
              {{ Object.values(compForm.errors).flat().join(' ') }}
            </p>
            <div class="flex flex-wrap gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
                @click="closeCompModal"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="compForm.processing"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                Enregistrer
              </button>
            </div>
          </form>

          <form
            v-else-if="editingComp && canManageCompetitions"
            class="mt-4 space-y-4"
            @submit.prevent="submitEditCompetition"
          >
            <label class="block text-sm text-slate-400">
              Nom
              <input
                v-model="editCompForm.name"
                type="text"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Date
              <input
                v-model="editCompForm.competition_date"
                type="date"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Lieu
              <input
                v-model="editCompForm.location"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <label class="block text-sm text-slate-400">
              Objectif
              <input
                v-model="editCompForm.goal"
                type="text"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              />
            </label>
            <div class="block text-sm text-slate-400">
              <span class="block">Plan de match</span>
              <MatchPlanBuilder v-model="editCompForm.match_plan_data" class="mt-3" />
            </div>
            <p v-if="Object.keys(editCompForm.errors).length" class="text-sm text-red-400">
              {{ Object.values(editCompForm.errors).flat().join(' ') }}
            </p>
            <div class="flex flex-wrap gap-3">
              <button
                type="button"
                :disabled="editCompForm.processing"
                class="rounded-xl border border-red-500/50 px-4 py-2 text-sm font-medium text-red-300 hover:bg-red-500/10 disabled:opacity-50"
                @click="deleteSelectedCompetition"
              >
                Supprimer
              </button>
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
                @click="editingComp = false"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="editCompForm.processing"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                Enregistrer
              </button>
            </div>
          </form>

          <form
            v-else-if="editingMatchPlan && canProposeMatchPlan"
            class="mt-4 space-y-4"
            @submit.prevent="submitMatchPlan"
          >
            <p class="text-sm text-slate-400">
              Propose tes barres visées pour {{ selectedCompetition.name }}.
            </p>
            <MatchPlanBuilder v-model="matchPlanForm.match_plan_data" />
            <p v-if="Object.keys(matchPlanForm.errors).length" class="text-sm text-red-400">
              {{ Object.values(matchPlanForm.errors).flat().join(' ') }}
            </p>
            <div class="flex flex-wrap gap-3">
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
                @click="cancelEditMatchPlan"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="matchPlanForm.processing"
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                Enregistrer le plan
              </button>
            </div>
          </form>

          <template v-else>
            <CompetitionDetailPanel :competition="selectedCompetition" class="mt-4" />
            <button
              v-if="canProposeMatchPlan && isUpcomingCompetition(selectedCompetition)"
              type="button"
              class="mt-6 rounded-xl border border-blue-500/50 bg-blue-600/15 px-4 py-2 text-sm font-semibold text-blue-200 hover:bg-blue-600/25"
              @click="startEditMatchPlan"
            >
              {{ competitionHasMatchPlan(selectedCompetition) ? 'Mettre à jour mon plan' : 'Proposer mon plan de match' }}
            </button>
            <button
              v-if="canManageCompetitions"
              type="button"
              class="mt-6 rounded-xl border border-slate-600 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800"
              @click="startEditCompetition"
            >
              Modifier
            </button>
            <button
              v-if="canManageCompetitions"
              type="button"
              class="mt-3 rounded-xl border border-red-500/50 px-4 py-2 text-sm font-medium text-red-300 hover:bg-red-500/10"
              @click="deleteSelectedCompetition"
            >
              Supprimer
            </button>
          </template>
        </div>
      </div>
    </Teleport>
  </div>
</template>
