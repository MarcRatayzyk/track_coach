<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import OnlineIndicator from '../Messaging/OnlineIndicator.vue';
import SectionHeader from './SectionHeader.vue';
import { messagingInitials, messagingRelativeTime } from '../../utils/messagingFormat';

const props = defineProps({
  threads: {
    type: Array,
    default: () => [],
  },
});

const sorted = computed(() =>
  [...(props.threads ?? [])].sort((a, b) => {
    const ua = (a.unread_messages_count ?? 0) > 0 ? 1 : 0;
    const ub = (b.unread_messages_count ?? 0) > 0 ? 1 : 0;
    if (ub !== ua) {
      return ub - ua;
    }
    return Date.parse(b.updated_at ?? 0) - Date.parse(a.updated_at ?? 0);
  }),
);

function preview(thread) {
  const last = thread?.last_message;
  if (!last) {
    return 'Aucun message';
  }
  return `${last.is_mine ? 'Toi : ' : ''}${last.content}`;
}
</script>

<template>
  <section
    class="flex h-full min-h-0 flex-col rounded-[20px] border border-slate-800/80 bg-slate-900/50 p-4 shadow-lg backdrop-blur-sm sm:p-5"
  >
    <SectionHeader
      eyebrow="Messagerie"
      title="Conversations"
    >
      <template #actions>
        <Link
          href="/messaging"
          class="rounded-[12px] border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 transition hover:bg-blue-950/50"
        >
          Voir toute la messagerie
        </Link>
      </template>
    </SectionHeader>

    <p
      v-if="!sorted.length"
      class="mt-6 flex flex-1 items-center justify-center rounded-[16px] border border-dashed border-slate-700 px-4 py-8 text-center text-sm text-slate-500"
    >
      Aucune conversation.
    </p>

    <ul v-else class="tc-scrollbar mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1 lg:max-h-[22rem]">
      <motion.li
        v-for="(thread, index) in sorted"
        :key="thread.id"
        :initial="{ opacity: 0, y: 8 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ duration: 0.25, delay: index * 0.04 }"
      >
        <Link
          :href="`/messaging?thread=${thread.id}`"
          class="group flex items-start gap-3 rounded-[16px] border px-3 py-3 transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_0_20px_rgba(59,130,246,0.12)]"
          :class="
            (thread.unread_messages_count ?? 0) > 0
              ? 'border-blue-500/30 bg-blue-950/25 hover:border-blue-500/45'
              : 'border-slate-800/80 bg-slate-950/40 hover:border-slate-700 hover:bg-slate-900/60'
          "
        >
          <div class="relative shrink-0">
            <div
              class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-semibold text-white ring-1 ring-slate-700/80"
              :class="thread.is_online ? 'shadow-[0_0_0_3px_rgba(59,130,246,0.25)]' : ''"
            >
              {{ messagingInitials(thread.athlete?.name) }}
            </div>
            <OnlineIndicator :online="Boolean(thread.is_online)" />
          </div>
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2">
              <p class="truncate text-sm font-semibold text-white">
                {{ thread.athlete?.name ?? 'Athlète' }}
              </p>
              <span class="shrink-0 text-[11px] text-slate-500">
                {{ messagingRelativeTime(thread.last_message?.created_at ?? thread.updated_at) }}
              </span>
            </div>
            <div class="mt-1 flex items-center justify-between gap-2">
              <p class="truncate text-xs text-slate-400">{{ preview(thread) }}</p>
              <span
                v-if="(thread.unread_messages_count ?? 0) > 0"
                class="inline-flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full bg-blue-500 px-1.5 text-[10px] font-bold text-white shadow-md shadow-blue-900/40"
              >
                {{ thread.unread_messages_count > 9 ? '9+' : thread.unread_messages_count }}
              </span>
            </div>
          </div>
        </Link>
      </motion.li>
    </ul>
  </section>
</template>
