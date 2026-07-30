<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import OnlineIndicator from './OnlineIndicator.vue';
import { messagingInitials, messagingRelativeTime } from '../../utils/messagingFormat';

const props = defineProps({
  thread: {
    type: Object,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  pinned: {
    type: Boolean,
    default: false,
  },
  displayName: {
    type: String,
    required: true,
  },
});

const emit = defineEmits(['toggle-pin']);

const preview = computed(() => {
  const last = props.thread?.last_message;
  if (!last) {
    return 'Aucun message';
  }
  const prefix = last.is_mine ? 'Toi : ' : '';
  return `${prefix}${last.content}`;
});

const timeLabel = computed(() =>
  messagingRelativeTime(props.thread?.last_message?.created_at ?? props.thread?.updated_at),
);

const initials = computed(() => messagingInitials(props.displayName));
const unread = computed(() => props.thread.unread_messages_count ?? 0);
</script>

<template>
  <div class="group relative">
    <Link
      :href="`/messaging?thread=${thread.id}`"
      preserve-state
      class="relative flex items-start gap-3 overflow-hidden rounded-[18px] border px-3.5 py-3.5 transition-all duration-200"
      :class="
        active
          ? 'border-blue-500/40 bg-gradient-to-br from-blue-600/25 via-slate-900/80 to-slate-900/60 shadow-lg shadow-blue-900/30 ring-1 ring-blue-500/30'
          : unread > 0
            ? 'border-blue-500/20 bg-blue-950/30 hover:-translate-y-0.5 hover:border-blue-500/35 hover:bg-blue-950/45 hover:shadow-lg hover:shadow-blue-950/20'
            : 'border-slate-800/80 bg-slate-900/40 hover:-translate-y-0.5 hover:border-slate-700 hover:bg-slate-900/70 hover:shadow-lg hover:shadow-black/20'
      "
    >
      <div class="relative shrink-0">
        <div
          class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700/80 transition duration-200"
          :class="thread.is_online ? 'shadow-[0_0_0_3px_rgba(59,130,246,0.25)]' : ''"
        >
          {{ initials }}
        </div>
        <OnlineIndicator :online="Boolean(thread.is_online)" />
      </div>

      <div class="min-w-0 flex-1 overflow-hidden">
        <div class="flex min-w-0 items-center justify-between gap-2">
          <div class="flex min-w-0 flex-1 items-center gap-1.5 overflow-hidden">
            <p class="min-w-0 flex-1 truncate text-[15px] font-semibold text-white">
              {{ displayName }}
            </p>
            <svg
              v-if="pinned"
              class="h-3.5 w-3.5 shrink-0 text-blue-400"
              fill="currentColor"
              viewBox="0 0 20 20"
              aria-hidden="true"
            >
              <path
                d="M10.75 3.5a.75.75 0 0 0-1.5 0v5.19L7.03 6.47a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 1 0-1.06-1.06l-2.22 2.22V3.5Z"
              />
              <path d="M10 12.25a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-1.5 0v-3a.75.75 0 0 1 .75-.75Z" />
            </svg>
          </div>
          <span
            class="shrink-0 text-[11px] font-medium"
            :class="active ? 'text-blue-200/80' : 'text-slate-500'"
          >
            {{ timeLabel }}
          </span>
        </div>
        <div class="mt-1 flex min-w-0 items-center justify-between gap-2">
          <p
            class="min-w-0 flex-1 truncate text-sm leading-snug"
            :class="active ? 'text-blue-100/80' : 'text-slate-400'"
          >
            {{ preview }}
          </p>
          <span
            v-if="unread > 0"
            class="inline-flex h-5 min-w-5 shrink-0 animate-pulse items-center justify-center rounded-full bg-blue-500 px-1.5 text-[10px] font-bold text-white shadow-md shadow-blue-900/40"
            :title="`${unread} non lu${unread > 1 ? 's' : ''}`"
          >
            {{ unread > 9 ? '9+' : unread }}
          </span>
        </div>
      </div>
    </Link>

    <button
      type="button"
      class="absolute bottom-2.5 right-2.5 z-10 rounded-full p-1.5 text-slate-500 opacity-0 transition duration-200 hover:bg-slate-800 hover:text-blue-400 group-hover:opacity-100"
      :title="pinned ? 'Désépingler' : 'Épingler'"
      @click.prevent="emit('toggle-pin', thread.id)"
    >
      <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 17.25V21m0-3.75 4.5-4.5V6.75A.75.75 0 0 0 15.75 6h-7.5a.75.75 0 0 0-.75.75v5.25L12 17.25Z"
        />
      </svg>
    </button>
  </div>
</template>
