<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import OnlineIndicator from './OnlineIndicator.vue';
import { messagingInitials } from '../../utils/messagingFormat';
import UiIcon from '../UiIcon.vue';

const { t } = useI18n();

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  online: {
    type: Boolean,
    default: false,
  },
  lastSession: {
    type: Object,
    default: null,
  },
  goal: {
    type: String,
    default: null,
  },
  profileUrl: {
    type: String,
    default: null,
  },
  isCoach: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['toggle-context', 'back']);

const showOptions = ref(false);
const initials = computed(() => messagingInitials(props.title));

const statusLabel = computed(() =>
  props.online ? t('app.messaging.online') : t('app.messaging.offline'),
);
</script>

<template>
  <header
    class="flex shrink-0 items-center justify-between gap-2 border-b border-slate-800/80 bg-slate-900/40 px-3 py-3 backdrop-blur-md sm:gap-3 sm:px-4 lg:px-5"
  >
    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
      <button
        type="button"
        class="shrink-0 rounded-full p-2 text-slate-400 transition duration-200 hover:bg-slate-800 hover:text-white lg:hidden"
        :aria-label="t('common.back')"
        @click="emit('back')"
      >
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
        </svg>
      </button>

      <div class="relative shrink-0">
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700 sm:h-11 sm:w-11"
          :class="online ? 'shadow-[0_0_0_3px_rgba(59,130,246,0.28)]' : ''"
        >
          {{ initials }}
        </div>
        <OnlineIndicator :online="online" />
      </div>

      <div class="min-w-0 flex-1 overflow-hidden">
        <h2 class="truncate text-base font-semibold text-white">{{ title }}</h2>
        <p
          class="mt-0.5 truncate text-xs"
          :class="online ? 'text-blue-400' : 'text-slate-500'"
        >
          {{ statusLabel }}
          <template v-if="lastSession">
            <span class="hidden text-slate-600 sm:inline"> · </span>
            <span class="hidden sm:inline">
              {{ t('app.messaging.lastSession') }}
              <template v-if="lastSession.label"> — {{ lastSession.label }}</template>
              <template v-else-if="lastSession.date"> — {{ lastSession.date }}</template>
            </span>
          </template>
          <template v-if="goal">
            <span class="hidden text-slate-600 md:inline"> · </span>
            <span class="hidden text-slate-300 md:inline">{{ t('app.messaging.goalLabel', { goal }) }}</span>
          </template>
        </p>
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-1 sm:gap-1.5">
      <Link
        v-if="profileUrl && isCoach"
        :href="profileUrl"
        class="inline-flex items-center justify-center gap-1.5 rounded-[14px] border border-slate-800 bg-slate-950/60 p-2 text-xs font-medium text-slate-300 transition duration-200 hover:border-blue-500/30 hover:bg-slate-800 hover:text-white sm:px-3 sm:py-2"
        :title="t('nav.profile')"
      >
        <UiIcon name="user-circle" class="h-4 w-4" />
        <span class="hidden sm:inline">{{ t('nav.profile') }}</span>
      </Link>

      <button
        v-if="isCoach"
        type="button"
        class="inline-flex items-center justify-center gap-1.5 rounded-[14px] border border-slate-800 bg-slate-950/60 p-2 text-xs font-medium text-slate-300 transition duration-200 hover:border-slate-700 hover:bg-slate-800 hover:text-white sm:px-3 sm:py-2 xl:hidden"
        :title="t('app.messaging.context')"
        @click="emit('toggle-context')"
      >
        <UiIcon name="list" class="h-4 w-4 sm:hidden" />
        <span class="hidden sm:inline">{{ t('app.messaging.context') }}</span>
      </button>

      <div class="relative">
        <button
          type="button"
          class="rounded-[14px] border border-slate-800 bg-slate-950/60 p-2 text-slate-400 transition duration-200 hover:bg-slate-800 hover:text-white"
          :aria-label="t('app.messaging.options')"
          @click="showOptions = !showOptions"
        >
          <UiIcon name="ellipsis-vertical" class="h-4 w-4" />
        </button>
        <div
          v-if="showOptions"
          class="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-[14px] border border-slate-800 bg-slate-900 shadow-xl"
        >
          <button
            type="button"
            class="block w-full px-3 py-2.5 text-left text-sm text-slate-300 transition hover:bg-slate-800"
            @click="showOptions = false; emit('toggle-context')"
          >
            {{ t('app.messaging.viewContext') }}
          </button>
          <Link
            v-if="profileUrl && isCoach"
            :href="profileUrl"
            class="block w-full px-3 py-2.5 text-left text-sm text-slate-300 transition hover:bg-slate-800"
            @click="showOptions = false"
          >
            {{ t('app.messaging.openProfile') }}
          </Link>
        </div>
      </div>
    </div>
  </header>
</template>
