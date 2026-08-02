<script setup>
import { useI18n } from 'vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import { athleteInitials, daysUntil } from './dashboardUi';
const { t } = useI18n();

const props = defineProps({
  athleteCount: { type: Number, default: 0 },
  activePrograms: { type: Number, default: 0 },
  nextCompetitionDate: { type: String, default: null },
  nextCompetitionName: { type: String, default: null },
  alertsCount: { type: Number, default: 0 },
});

const emit = defineEmits(['add-athlete']);

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const unread = computed(() => page.props.messagingInbox?.total_unread ?? 0);
const firstName = computed(() => {
  const name = user.value?.name?.trim() || 'Coach';
  return name.split(/\s+/)[0];
});

const daysToComp = computed(() => daysUntil(props.nextCompetitionDate));

const chips = computed(() => {
  const items = [
    {
      key: 'programs',
      label: t('app.coachDash.activeProgramsCount', props.activePrograms, {
        count: props.activePrograms,
      }),
      href: '/program-builder',
      tone: 'blue',
    },
    {
      key: 'athletes',
      label: t('app.coachDash.athletesFollowedCount', props.athleteCount, {
        count: props.athleteCount,
      }),
      href: '/athletes',
      tone: 'sky',
    },
  ];

  if (daysToComp.value !== null) {
    items.push({
      key: 'comp',
      label:
        daysToComp.value <= 0
          ? t('app.coachDash.competitionToday')
          : t('app.coachDash.nextCompetitionIn', daysToComp.value, {
              days: daysToComp.value,
            }),
      href: '/competitions',
      tone: daysToComp.value <= 7 ? 'rose' : 'slate',
    });
  }

  return items;
});

const chipClass = {
  amber: 'border-amber-500/30 bg-amber-500/10 text-amber-100',
  rose: 'border-rose-500/30 bg-rose-500/10 text-rose-100',
  blue: 'border-blue-500/30 bg-blue-500/10 text-blue-100',
  sky: 'border-sky-500/30 bg-sky-500/10 text-sky-100',
  slate: 'border-slate-700/80 bg-slate-950/40 text-slate-300',
};
</script>

<template>
  <motion.header
    :initial="{ opacity: 0, y: 16 }"
    :animate="{ opacity: 1, y: 0 }"
    :transition="{ duration: 0.45, ease: [0.22, 1, 0.36, 1] }"
    class="relative overflow-hidden rounded-[1.25rem] border border-slate-800/80 bg-gradient-to-br from-slate-900/80 via-slate-900/60 to-blue-950/25 p-5 shadow-xl shadow-blue-950/25 backdrop-blur-md sm:p-6"
  >
    <div
      class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"
    />
    <div
      class="pointer-events-none absolute -bottom-24 left-10 h-48 w-48 rounded-full bg-sky-400/5 blur-3xl"
    />

    <div class="relative flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-blue-300/70">
          Power Roster
        </p>
        <h1 class="mt-2 text-2xl font-bold tracking-tight text-white sm:text-3xl">
          {{ t('app.coachDash.hello', { name: firstName }) }}
          <span aria-hidden="true">👋</span>
        </h1>
        <p class="mt-2 text-sm text-slate-400">
          {{ t('app.coachDash.overviewHint') }}
        </p>

        <div class="-mx-1 mt-4 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:thin] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 sm:pb-0">
          <component
            :is="chip.href.startsWith('#') ? 'a' : Link"
            v-for="chip in chips"
            :key="chip.key"
            :href="chip.href"
            class="inline-flex shrink-0 items-center rounded-full border px-3 py-1.5 text-xs font-medium transition duration-200 hover:scale-[1.02]"
            :class="chipClass[chip.tone]"
          >
            {{ chip.label }}
          </component>
        </div>

        <div class="mt-5 flex flex-col gap-2 sm:flex-row sm:flex-wrap">
          <Link
            href="/program-builder"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition duration-200 hover:bg-blue-500 sm:w-auto sm:justify-start sm:py-2"
          >
            <UiIcon name="bolt" class="h-4 w-4" />
            {{ t('app.coachDash.createProgram') }}
          </Link>
          <button
            type="button"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-950/40 px-3.5 py-2.5 text-sm font-medium text-slate-200 transition duration-200 hover:border-blue-500/40 hover:bg-slate-900 sm:w-auto sm:justify-start sm:py-2"
            @click="emit('add-athlete')"
          >
            <UiIcon name="users" class="h-4 w-4" />
            {{ t('app.coachDash.addAthlete') }}
          </button>
          <Link
            href="/competitions"
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-700 bg-slate-950/40 px-3.5 py-2.5 text-sm font-medium text-slate-200 transition duration-200 hover:border-blue-500/40 hover:bg-slate-900 sm:w-auto sm:justify-start sm:py-2"
          >
            <UiIcon name="calendar" class="h-4 w-4" />
            {{ t('app.competitions.new') }}
          </Link>
        </div>
      </div>

      <div class="flex items-center gap-2 self-start xl:self-auto">
        <Link
          href="#dashboard-alerts"
          class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-950/50 text-slate-300 transition duration-200 hover:border-blue-500/40 hover:text-white"
          :title="t('app.coachDash.notifications')"
        >
          <UiIcon name="alert" class="h-5 w-5" />
          <span
            v-if="alertsCount > 0"
            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white"
          >
            {{ alertsCount > 9 ? '9+' : alertsCount }}
          </span>
        </Link>
        <Link
          href="/messaging"
          class="relative inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-700 bg-slate-950/50 text-slate-300 transition duration-200 hover:border-blue-500/40 hover:text-white"
          :title="t('nav.messages')"
        >
          <UiIcon name="chat" class="h-5 w-5" />
          <span
            v-if="unread > 0"
            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-500 px-1 text-[10px] font-bold text-white"
          >
            {{ unread > 9 ? '9+' : unread }}
          </span>
        </Link>
        <Link
          href="/coach/profile"
          class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/50 px-3 text-sm font-medium text-slate-200 transition duration-200 hover:border-blue-500/40"
        >
          <span
            class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-600/20 text-xs font-semibold text-blue-200"
          >
            {{ athleteInitials(user?.name) }}
          </span>
          <span class="hidden sm:inline">Profil</span>
        </Link>
      </div>
    </div>
  </motion.header>
</template>
