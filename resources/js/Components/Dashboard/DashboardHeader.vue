<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import { messagingInitials } from '../../utils/messagingFormat';

const { t } = useI18n();

const props = defineProps({
  summary: {
    type: Object,
    default: () => ({
      pendingReviews: 0,
      importantAlerts: 0,
      activePrograms: 0,
      nextCompetitionDays: null,
      unreadMessages: 0,
    }),
  },
  rosterAthletes: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['add-athlete', 'open-competition']);

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const firstName = computed(() => {
  const name = user.value?.name?.trim() || 'Coach';
  return name.split(/\s+/)[0];
});
const initials = computed(() => messagingInitials(user.value?.name));
const search = ref('');
const searchOpen = ref(false);

const searchResults = computed(() => {
  const q = search.value.trim().toLowerCase();
  if (!q) {
    return [];
  }
  return props.rosterAthletes
    .filter((a) => a.name?.toLowerCase().includes(q))
    .slice(0, 6);
});

const chips = computed(() => {
  const s = props.summary;
  const items = [
    {
      key: 'reviews',
      label: t('app.coachDash.pendingToValidate', s.pendingReviews),
      tone: s.pendingReviews > 0 ? 'amber' : 'slate',
      href: '/feedbacks?filter=pending',
    },
    {
      key: 'alerts',
      label: t('app.coachDash.importantAlertsCount', s.importantAlerts),
      tone: s.importantAlerts > 0 ? 'rose' : 'slate',
      href: '#dashboard-alerts',
    },
    {
      key: 'programs',
      label: t('app.coachDash.activeProgramsCount', s.activePrograms),
      tone: 'blue',
      href: '/program-builder',
    },
  ];

  if (s.nextCompetitionDays != null) {
    items.push({
      key: 'comp',
      label:
        s.nextCompetitionDays === 0
          ? t('app.coachDash.competitionToday')
          : t('app.coachDash.nextCompetitionIn', s.nextCompetitionDays),
      tone: 'indigo',
      href: '/competitions',
    });
  }

  return items;
});

const toneClass = {
  amber: 'border-amber-500/30 bg-amber-500/10 text-amber-200',
  rose: 'border-rose-500/30 bg-rose-500/10 text-rose-200',
  blue: 'border-blue-500/30 bg-blue-500/10 text-blue-200',
  indigo: 'border-indigo-500/30 bg-indigo-500/10 text-indigo-200',
  slate: 'border-slate-700 bg-slate-900/60 text-slate-400',
};

function goAthlete(id) {
  searchOpen.value = false;
  search.value = '';
  router.visit(`/athletes/${id}`);
}

function onSearchSubmit() {
  if (searchResults.value[0]) {
    goAthlete(searchResults.value[0].id);
    return;
  }
  if (search.value.trim()) {
    router.visit(`/athletes?q=${encodeURIComponent(search.value.trim())}`);
  }
}

let blurTimer = 0;
function onSearchBlur() {
  clearTimeout(blurTimer);
  blurTimer = window.setTimeout(() => {
    searchOpen.value = false;
  }, 150);
}
</script>

<template>
  <motion.header
    :initial="{ opacity: 0, y: -8 }"
    :animate="{ opacity: 1, y: 0 }"
    :transition="{ duration: 0.35, ease: [0.22, 1, 0.36, 1] }"
    class="relative overflow-hidden rounded-[20px] border border-blue-500/20 bg-gradient-to-br from-slate-900/90 via-slate-900/70 to-blue-950/40 p-5 shadow-xl shadow-blue-950/20 backdrop-blur-sm sm:p-6"
  >
    <div
      class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"
      aria-hidden="true"
    />
    <div
      class="pointer-events-none absolute -bottom-24 left-1/3 h-40 w-40 rounded-full bg-indigo-500/10 blur-3xl"
      aria-hidden="true"
    />

    <div class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
      <div class="min-w-0 flex-1">
        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-blue-400/80">
          Power Roster
        </p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl">
          {{ t('app.coachDash.hello', { name: firstName }) }}
          <span class="inline-block origin-bottom-left animate-[wave_1.2s_ease-in-out_1]" aria-hidden="true">👋</span>
        </h1>
        <p class="mt-2 text-sm text-slate-400">{{ t('app.dashboard.today') }}</p>
        <div class="mt-2.5 flex flex-wrap gap-2">
          <component
            :is="chip.href.startsWith('#') ? 'a' : Link"
            v-for="chip in chips"
            :key="chip.key"
            :href="chip.href"
            class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium transition duration-200 hover:brightness-110"
            :class="toneClass[chip.tone]"
          >
            {{ chip.label }}
          </component>
        </div>
      </div>

      <div class="flex w-full flex-col gap-3 sm:max-w-md lg:w-auto lg:min-w-[20rem]">
        <div class="relative">
          <div
            class="flex items-center gap-2 rounded-[14px] border border-slate-700/80 bg-slate-950/60 px-3 py-2.5 shadow-inner transition duration-200 focus-within:border-blue-500/40 focus-within:shadow-[0_0_20px_rgba(59,130,246,0.15)]"
          >
            <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
            </svg>
            <input
              v-model="search"
              type="search"
              :placeholder="t('app.feedbacks.searchAthlete')"
              class="w-full bg-transparent text-sm text-white outline-none placeholder:text-slate-600"
              @focus="searchOpen = true"
              @blur="onSearchBlur"
              @keydown.enter.prevent="onSearchSubmit"
            />
          </div>
          <div
            v-if="searchOpen && searchResults.length"
            class="absolute left-0 right-0 top-full z-20 mt-1.5 overflow-hidden rounded-[14px] border border-slate-700 bg-slate-900/95 shadow-2xl backdrop-blur-md"
          >
            <button
              v-for="athlete in searchResults"
              :key="athlete.id"
              type="button"
              class="flex w-full items-center gap-3 px-3 py-2.5 text-left text-sm text-slate-200 transition hover:bg-blue-600/15"
              @mousedown.prevent="goAthlete(athlete.id)"
            >
              <span class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-800 text-xs font-semibold text-white">
                {{ messagingInitials(athlete.name) }}
              </span>
              {{ athlete.name }}
            </button>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2">
          <Link
            href="/messaging"
            class="relative flex h-10 w-10 items-center justify-center rounded-[12px] border border-slate-700/80 bg-slate-950/50 text-slate-300 transition duration-200 hover:border-blue-500/40 hover:text-blue-300 hover:shadow-[0_0_16px_rgba(59,130,246,0.2)]"
            title="Messages"
          >
            <UiIcon name="chat" class="h-4 w-4" />
            <span
              v-if="summary.unreadMessages > 0"
              class="absolute -right-1 -top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-blue-500 px-1 text-[9px] font-bold text-white"
            >
              {{ summary.unreadMessages > 9 ? '9+' : summary.unreadMessages }}
            </span>
          </Link>
          <Link
            href="/coach/profile"
            class="flex h-10 items-center gap-2 rounded-[12px] border border-slate-700/80 bg-slate-950/50 pl-1.5 pr-3 text-slate-300 transition duration-200 hover:border-blue-500/40 hover:text-white"
            title="Profil"
          >
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-slate-800 text-[11px] font-semibold text-white">
              {{ initials }}
            </span>
            <span class="hidden text-xs font-medium sm:inline">Profil</span>
          </Link>
        </div>
      </div>
    </div>

    <div class="relative mt-5 flex flex-wrap gap-2 border-t border-white/5 pt-4">
      <Link
        href="/program-builder"
        class="inline-flex items-center gap-2 rounded-[14px] bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition duration-200 hover:bg-blue-500 hover:shadow-[0_0_24px_rgba(59,130,246,0.35)]"
      >
        <UiIcon name="clipboard" class="h-4 w-4" />
        {{ t('app.coachDash.createProgram') }}
      </Link>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[14px] border border-blue-500/40 bg-blue-950/30 px-4 py-2.5 text-sm font-semibold text-blue-100 transition duration-200 hover:bg-blue-950/50 hover:shadow-[0_0_16px_rgba(59,130,246,0.2)]"
        @click="emit('add-athlete')"
      >
        <UiIcon name="users" class="h-4 w-4" />
        {{ t('app.coachDash.addAthlete') }}
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-[14px] border border-slate-700 bg-slate-950/40 px-4 py-2.5 text-sm font-medium text-slate-300 transition duration-200 hover:border-slate-600 hover:bg-slate-900/70"
        @click="emit('open-competition')"
      >
        <UiIcon name="trophy" class="h-4 w-4" />
        {{ t('app.competitions.new') }}
      </button>
    </div>
  </motion.header>
</template>

<style scoped>
@keyframes wave {
  0%,
  100% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(14deg);
  }
  50% {
    transform: rotate(-8deg);
  }
  75% {
    transform: rotate(10deg);
  }
}
</style>
