<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import AthletePrForm from '../Components/AthletePrForm.vue';
import AthleteProfileOverview from '../Components/AthleteProfileOverview.vue';
import AthleteReadinessCheckIn from '../Components/AthleteReadinessCheckIn.vue';
import AthleteStatsOverview from '../Components/AthleteStatsOverview.vue';
import CompetitionDetailPanel from '../Components/CompetitionDetailPanel.vue';
import MatchPlanBuilder from '../Components/MatchPlanBuilder.vue';
import ProgramSessionEditorModal from '../Components/ProgramSessionEditorModal.vue';
import TrainingSessionEditorModal from '../Components/TrainingSessionEditorModal.vue';
import TrainingSessionsCalendar from '../Components/TrainingSessionsCalendar.vue';
import { buildAthleteOverviewStats } from '../utils/athleteOverviewStats';
import { formatCalendarFr } from '../utils/formatDates';
import { defaultStructuredPlan, matchPlanFromCompetition } from '../utils/matchPlan';
import {
  BLOCK_TYPES,
  SECTION_LABELS,
  cellDate,
  cellKey,
  formatLineRecap,
  formatPrescription,
  sessionCardTitle,
  weekDaysWithSessions,
} from '../utils/programBuilder';

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
});

const page = usePage();
const isCoach = computed(() => page.props.auth?.user?.role === 'coach');
const canManageSessions = computed(
  () => isCoach.value || page.props.auth?.user?.id === props.athlete.id,
);
const canLogReadiness = computed(
  () => !isCoach.value && page.props.auth?.user?.id === props.athlete.id,
);
const canManageOfficialPr = computed(
  () => isCoach.value || page.props.auth?.user?.id === props.athlete.id,
);
const showPrForm = ref(false);
const readinessRecent = computed(() => props.readinessRecent ?? []);
const todayReadiness = computed(() => props.todayReadiness);
const showReadinessSection = computed(() => isCoach.value);

const today = new Date().toISOString().slice(0, 10);

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
const selectedCompetition = ref(null);
const showAddCompetitionForm = ref(false);
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

function compDateKey(comp) {
  const s = String(comp.competition_date ?? '');
  const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
  return m ? m[1] : '';
}

const upcomingCompetitions = computed(() =>
  (props.athlete.competitions ?? []).filter((c) => compDateKey(c) >= today),
);

const pastCompetitions = computed(() =>
  (props.athlete.competitions ?? []).filter((c) => compDateKey(c) < today),
);

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

  if (searchParams.get('edit') === '1' && isCoach.value) {
    startEditCompetition();
  }
}

function closeCompModal() {
  showCompModal.value = false;
  editingComp.value = false;
  selectedCompetition.value = null;
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
    `/coach/athletes/${props.athlete.id}/competitions/${selectedCompetition.value.id}`,
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
    `/coach/athletes/${props.athlete.id}/competitions/${selectedCompetition.value.id}`,
    {
      preserveScroll: true,
      onSuccess: () => closeCompModal(),
    },
  );
}

const profileForm = useForm({
  feedback_frequency: props.athlete.profile?.feedback_frequency ?? 'weekly',
});

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
  if (!practiceStartDate.value) {
    return '—';
  }
  const start = new Date(practiceStartDate.value);
  const now = new Date();
  let months = (now.getFullYear() - start.getFullYear()) * 12 + (now.getMonth() - start.getMonth());
  if (months < 0) {
    months = 0;
  }
  const years = Math.floor(months / 12);
  const remainingMonths = months % 12;
  if (years === 0) {
    return `${remainingMonths} mois`;
  }
  if (remainingMonths === 0) {
    return `${years} an${years > 1 ? 's' : ''}`;
  }
  return `${years} an${years > 1 ? 's' : ''} ${remainingMonths} mois`;
});

const sortedTrainingSessions = computed(() =>
  [...trainingSessions.value].sort((a, b) =>
    String(a.session_date ?? '').localeCompare(String(b.session_date ?? '')),
  ),
);

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

const filteredTrainingSessions = computed(() =>
  filterByTimeRange(sortedTrainingSessions.value, 'session_date'),
);

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
  compForm.post(`/coach/athletes/${props.athlete.id}/competitions`, {
    preserveScroll: true,
    onSuccess: () => {
      compForm.reset();
      compForm.competition_date = today;
      compForm.match_plan_data = defaultStructuredPlan();
      showAddCompetitionForm.value = false;
    },
  });
}

function openAddSessionForm() {
  editingSession.value = null;
  sessionModalOpen.value = true;
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

function submitProfile() {
  profileForm.patch(`/coach/athletes/${props.athlete.id}/profile`, {
    preserveScroll: true,
  });
}

function toggleFeedbackFrequency() {
  profileForm.feedback_frequency = nextFeedbackFrequency.value;
  submitProfile();
}

function blockTypeLabel(type) {
  return BLOCK_TYPES.find((item) => item.value === type)?.label ?? type;
}

function sortedExercisesForDay(day) {
  return [...(day.exercises ?? [])].sort(
    (a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0),
  );
}

function sessionDateLabel(day, weekNumber, dateStart) {
  if (!dateStart) {
    return null;
  }
  return formatCalendarFr(cellDate(dateStart, weekNumber, day.day_number), 'medium');
}

function exerciseLineText(line) {
  return formatLineRecap(line) || formatPrescription(line);
}

function sectionTextClass(section) {
  if (section === 'topset') {
    return 'text-emerald-400';
  }
  if (section === 'backoff') {
    return 'text-slate-400';
  }
  return 'text-slate-500';
}

const expandedWeeks = ref(new Set());
const programSessionModalOpen = ref(false);
const selectedProgramCell = ref(null);

watch(
  () => props.activeProgram?.template?.weeks,
  () => {
    expandedWeeks.value = new Set();
    programSessionModalOpen.value = false;
    selectedProgramCell.value = null;
  },
  { immediate: true },
);

function isWeekExpanded(weekId) {
  return expandedWeeks.value.has(weekId);
}

function toggleWeek(weekId) {
  const next = new Set(expandedWeeks.value);
  if (next.has(weekId)) {
    next.delete(weekId);
  } else {
    next.add(weekId);
  }
  expandedWeeks.value = next;
}

function canEditProgramSession() {
  return isCoach.value && Boolean(props.programBlock);
}

function openProgramSessionEditor(week, day) {
  if (!canEditProgramSession() || !props.activeProgram?.date_start) {
    return;
  }
  selectedProgramCell.value = {
    weekNumber: week.week_number,
    weekday: day.day_number,
    key: cellKey(week.week_number, day.day_number),
    date: cellDate(props.activeProgram.date_start, week.week_number, day.day_number),
  };
  programSessionModalOpen.value = true;
}

function closeProgramSessionModal() {
  programSessionModalOpen.value = false;
  selectedProgramCell.value = null;
}

function onProgramSessionSaved() {
  router.reload({ only: ['activeProgram', 'programBlock'], preserveScroll: true });
  closeProgramSessionModal();
}

onMounted(() => {
  maybeOpenCompetitionFromQuery();
});
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-4">
      <div>
        <Link
          v-if="isCoach"
          href="/athletes"
          class="text-sm font-medium text-blue-400 hover:text-blue-300 "
        >
          ← Retour à la liste
        </Link>
        <h1 class="mt-3 text-2xl font-bold text-white">
          {{ athlete.name }}
        </h1>
        <p class="mt-2 text-slate-400 ">
          {{ athlete.email }}
        </p>
      </div>
    </div>

    <div class="mt-10">
      <AthleteProfileOverview
        :weight-class="athlete.profile?.weight_class ?? '—'"
        :feedback-label="feedbackLabels[athlete.profile?.feedback_frequency] ?? 'Hebdomadaire'"
        :is-coach="isCoach"
        :feedback-processing="profileForm.processing"
        :next-feedback-button-label="nextFeedbackButtonLabel"
        :practice-duration-label="practiceDurationLabel"
        :follow-up-started-at-label="followUpStartedAtLabel"
        :latest-competition-date-label="latestCompetitionDateLabel"
        :latest-competition-bars="latestCompetitionBars"
        :training-prs="trainingPrs"
        @toggle-feedback="toggleFeedbackFrequency"
      />

      <div class="mt-5">
        <AthleteStatsOverview
          :stats="overviewStats"
          :has-active-program="Boolean(programBlock)"
          :pr-records="filteredPrRecords"
          :time-range="timeRange"
          :time-range-options="timeRangeOptions"
          @update:time-range="timeRange = $event"
        />
      </div>

      <div v-if="showReadinessSection" class="mt-5">
        <AthleteReadinessCheckIn
          :athlete-id="athlete.id"
          :today-readiness="todayReadiness"
          :readiness-recent="readinessRecent"
          :can-edit="canLogReadiness"
        />
      </div>

      <section
        v-if="activeProgram && activeProgram.template"
        class="mt-5 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6"
      >
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-sm font-semibold text-white">Programme en cours</h2>
          <Link
            v-if="isCoach && activeProgram.id"
            :href="`/program-builder?assignment=${activeProgram.id}`"
            class="shrink-0 rounded-xl border border-blue-500/50 bg-blue-600/20 px-3 py-1.5 text-xs font-semibold text-blue-300 transition hover:bg-blue-600/30"
          >
            Modifier le programme
          </Link>
        </div>
        <p class="mt-3 text-slate-400 ">
          {{ activeProgram.template.name }} · du
          {{ formatCalendarFr(activeProgram.date_start, 'medium') }} au
          {{
            activeProgram.date_end
              ? formatCalendarFr(activeProgram.date_end, 'medium')
              : '—'
          }}
        </p>
        <div
          v-for="week in activeProgram.template.weeks ?? []"
          :key="week.id"
          class="mt-4 rounded-2xl border border-slate-800 bg-slate-950/60"
        >
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-2xl px-4 py-3 text-left transition hover:bg-slate-900/60"
            :aria-expanded="isWeekExpanded(week.id)"
            @click="toggleWeek(week.id)"
          >
            <span
              class="inline-block w-4 shrink-0 text-center text-lg font-semibold text-slate-400 transition-transform duration-200"
              :class="isWeekExpanded(week.id) ? 'rotate-90' : ''"
              aria-hidden="true"
            >
              &gt;
            </span>
            <span class="min-w-0 flex-1 text-base font-semibold text-white">
              Semaine {{ week.week_number }}
              <span class="text-slate-400">— {{ blockTypeLabel(week.block_type) }}</span>
            </span>
            <span class="shrink-0 text-xs text-slate-500">
              {{ weekDaysWithSessions(week).length }}
              séance{{ weekDaysWithSessions(week).length > 1 ? 's' : '' }}
            </span>
          </button>
          <div v-show="isWeekExpanded(week.id)" class="border-t border-slate-800/80 px-4 pb-4 pt-1">
          <div
            v-if="weekDaysWithSessions(week).length"
            class="mt-3 grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4"
          >
            <article
              v-for="day in weekDaysWithSessions(week)"
              :key="day.id"
              class="flex flex-col rounded-xl border border-slate-800 bg-slate-950/50 p-4"
              :class="
                canEditProgramSession()
                  ? 'cursor-pointer transition hover:border-slate-600 hover:bg-slate-900/80 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500/70'
                  : ''
              "
              :role="canEditProgramSession() ? 'button' : undefined"
              :tabindex="canEditProgramSession() ? 0 : undefined"
              @click="openProgramSessionEditor(week, day)"
              @keydown.enter.prevent="openProgramSessionEditor(week, day)"
              @keydown.space.prevent="openProgramSessionEditor(week, day)"
            >
              <h4 class="text-sm font-semibold text-white">
                {{ sessionCardTitle(day, weekDaysWithSessions(week)) }}
              </h4>
              <p v-if="sessionDateLabel(day, week.week_number, activeProgram.date_start)" class="mt-1 text-xs text-slate-500">
                {{ sessionDateLabel(day, week.week_number, activeProgram.date_start) }}
              </p>
              <ul class="mt-3 flex-1 space-y-2 border-t border-slate-800/80 pt-3">
                <li
                  v-for="line in sortedExercisesForDay(day)"
                  :key="line.id"
                  class="text-sm leading-snug"
                >
                  <span class="font-medium" :class="sectionTextClass(line.section)">
                    {{ SECTION_LABELS[line.section] ?? line.section }}
                  </span>
                  <span class="text-slate-300"> — {{ exerciseLineText(line) }}</span>
                </li>
              </ul>
              <p
                v-if="!sortedExercisesForDay(day).length"
                class="mt-3 text-sm text-slate-500"
              >
                Aucun exercice renseigné.
              </p>
            </article>
          </div>
          <p v-else class="mt-3 text-sm text-slate-500">
            Aucune séance programmée cette semaine.
          </p>
          </div>
        </div>

        <ProgramSessionEditorModal
          :open="programSessionModalOpen"
          :program-block="programBlock"
          :selected-cell="selectedProgramCell"
          @close="closeProgramSessionModal"
          @saved="onProgramSessionSaved"
          @cleared="onProgramSessionSaved"
        />
      </section>

      <section
        v-else
        class="mt-5 rounded-2xl border border-dashed border-slate-700 bg-slate-900/30 p-5 lg:p-6"
      >
        <h2 class="text-sm font-semibold text-white">Programme en cours</h2>
        <p class="mt-3 text-slate-500 ">
          Aucun programme actif assigné pour le moment.
        </p>
      </section>
    </div>

    <section class="mt-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-white">Historique des séances</h2>
        <button
          v-if="canManageSessions"
          type="button"
          class="rounded-xl border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
          @click="openAddSessionForm"
        >
          Ajouter une séance
        </button>
      </div>

      <TrainingSessionsCalendar
        v-model:selected-day="selectedSessionDay"
        :sessions="trainingSessions"
        :reference-lifts="referenceLifts"
        :can-edit="canManageSessions"
        class="mt-4"
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

    <section class="mt-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="text-sm font-semibold text-white">Progression des PR officiels</h2>
          <p v-if="latestOfficialPr" class="mt-1 text-xs text-slate-500">
            Actuel — S {{ latestOfficialPr.squat }} · B {{ latestOfficialPr.bench }} · D
            {{ latestOfficialPr.deadlift }} kg
          </p>
        </div>
        <button
          v-if="canManageOfficialPr"
          type="button"
          class="rounded-xl border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
          @click="showPrForm = !showPrForm"
        >
          {{ showPrForm ? 'Fermer' : isCoach ? 'Ajouter un PR' : 'Mettre à jour mes PR' }}
        </button>
      </div>

      <AthletePrForm
        v-if="showPrForm && canManageOfficialPr"
        class="mt-4"
        :athlete-id="athlete.id"
        :latest-pr="latestOfficialPr"
        title="Nouveau PR officiel"
        description="Ajoute une nouvelle entrée pour mettre à jour tes records (squat, bench, terre)."
      />

    </section>

    <section class="mt-4 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-white">Compétitions</h2>
        <button
          v-if="isCoach"
          type="button"
          class="rounded-xl border border-blue-500/50 px-3 py-2 text-xs font-semibold text-blue-300 hover:bg-blue-500/10"
          @click="showAddCompetitionForm = !showAddCompetitionForm"
        >
          {{ showAddCompetitionForm ? 'Fermer' : 'Ajouter compétition' }}
        </button>
      </div>

      <div v-if="upcomingCompetitions.length" class="mt-4">
        <p class="text-xs font-medium uppercase tracking-wide text-rose-400/90">À venir</p>
        <ul class="mt-2 space-y-2">
          <li v-for="comp in upcomingCompetitions" :key="comp.id">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-left transition hover:border-rose-500/40 hover:bg-slate-800/50"
              @click="openCompetition(comp)"
            >
              <span class="font-medium text-white">{{ comp.name }}</span>
              <span class="text-slate-500">
                — {{ formatCalendarFr(comp.competition_date, 'medium') }}
              </span>
              <p v-if="comp.location" class="mt-1 text-xs text-slate-400">{{ comp.location }}</p>
            </button>
          </li>
        </ul>
      </div>

      <div v-if="pastCompetitions.length" class="mt-6">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Passées</p>
        <ul class="mt-2 space-y-2">
          <li v-for="comp in pastCompetitions" :key="comp.id">
            <button
              type="button"
              class="w-full rounded-xl border border-slate-800/80 px-4 py-3 text-left text-slate-400 transition hover:border-slate-600 hover:bg-slate-800/30"
              @click="openCompetition(comp)"
            >
              <span class="font-medium text-slate-300">{{ comp.name }}</span>
              <span class="text-slate-500">
                — {{ formatCalendarFr(comp.competition_date, 'medium') }}
              </span>
            </button>
          </li>
        </ul>
      </div>

      <p
        v-if="!upcomingCompetitions.length && !pastCompetitions.length"
        class="mt-4 text-slate-500"
      >
        Aucune compétition planifiée.
      </p>
      <form
        v-if="isCoach && showAddCompetitionForm"
        class="mt-6 space-y-5 rounded-2xl border border-slate-800 bg-slate-950/40 p-4"
        @submit.prevent="submitComp"
      >
        <h3 class="text-sm font-semibold text-white">Ajouter une compétition</h3>
        <label class="block text-base text-slate-400 ">
          Nom
          <input
            v-model="compForm.name"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          />
        </label>
        <label class="block text-base text-slate-400 ">
          Date
          <input
            v-model="compForm.competition_date"
            type="date"
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          />
        </label>
        <label class="block text-base text-slate-400 ">
          Lieu (optionnel)
          <input
            v-model="compForm.location"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          />
        </label>
        <label class="block text-base text-slate-400 ">
          Objectif (optionnel)
          <input
            v-model="compForm.goal"
            type="text"
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          />
        </label>
        <div class="block text-base text-slate-400">
          <span class="block">Plan de match (optionnel)</span>
          <MatchPlanBuilder v-model="compForm.match_plan_data" class="mt-3" />
        </div>
        <p v-if="Object.keys(compForm.errors).length" class="text-sm text-red-400">
          {{ Object.values(compForm.errors).flat().join(' ') }}
        </p>
        <button
          type="submit"
          :disabled="compForm.processing"
          class="rounded-xl bg-blue-600 px-8 py-4 text-sm font-semibold text-white shadow-lg hover:bg-blue-500 disabled:opacity-50"
        >
          Enregistrer
        </button>
      </form>
    </section>

    <Teleport to="body">
      <div
        v-if="showCompModal && selectedCompetition"
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
            <h2 class="text-base font-semibold text-white">Détail compétition</h2>
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
            v-if="editingComp && isCoach"
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

          <template v-else>
            <CompetitionDetailPanel :competition="selectedCompetition" class="mt-4" />
            <button
              v-if="isCoach"
              type="button"
              class="mt-6 rounded-xl border border-slate-600 px-4 py-2 text-sm font-medium text-slate-200 hover:bg-slate-800"
              @click="startEditCompetition"
            >
              Modifier
            </button>
            <button
              v-if="isCoach"
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
