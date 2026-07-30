<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import MessageThreadUnreadBadge from '../MessageThreadUnreadBadge.vue';
import SectionHeader from './SectionHeader.vue';
import {
  athleteInitials,
  cardHover,
  cardShell,
  relativeTimeFr,
  timeOfDayFr,
} from './dashboardUi';

const props = defineProps({
  threads: { type: Array, default: () => [] },
});

const sorted = computed(() =>
  [...(props.threads ?? [])].sort((a, b) => {
    const ua = (a.unread_messages_count ?? 0) > 0 ? 1 : 0;
    const ub = (b.unread_messages_count ?? 0) > 0 ? 1 : 0;
    if (ua !== ub) return ub - ua;
    return String(b.updated_at || '').localeCompare(String(a.updated_at || ''));
  }),
);
</script>

<template>
  <section :class="[cardShell, 'flex h-full min-h-0 min-w-0 flex-col overflow-hidden p-4 sm:p-5']">
    <SectionHeader
      eyebrow="Messagerie"
      title="Conversations"
    >
      <template #actions>
        <Link
          href="/messaging"
          class="text-xs font-medium text-blue-400 transition hover:text-blue-300 sm:text-sm"
        >
          Voir tout
        </Link>
      </template>
    </SectionHeader>

    <p
      v-if="!sorted.length"
      class="mt-6 flex flex-1 items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
    >
      Aucune conversation.
    </p>

    <ul
      v-else
      class="tc-scrollbar mt-5 min-h-0 min-w-0 flex-1 space-y-2 overflow-y-auto overflow-x-hidden pr-1 lg:max-h-[22rem]"
    >
      <motion.li
        v-for="(t, index) in sorted"
        :key="t.id"
        :initial="{ opacity: 0, y: 8 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: index * 0.03, duration: 0.3 }"
        class="min-w-0"
      >
        <Link
          :href="`/messaging?thread=${t.id}`"
          class="relative flex min-w-0 items-center gap-3 overflow-hidden rounded-[1.05rem] border px-3 py-3 pr-8 transition duration-200"
          :class="[
            (t.unread_messages_count ?? 0) > 0
              ? 'border-blue-500/35 bg-blue-950/25'
              : 'border-slate-800/80 bg-slate-950/40',
            cardHover,
          ]"
        >
          <MessageThreadUnreadBadge :count="t.unread_messages_count ?? 0" />
          <div class="relative shrink-0">
            <span
              class="flex h-11 w-11 items-center justify-center rounded-full bg-blue-500/15 text-sm font-semibold text-blue-100"
            >
              {{ athleteInitials(t.athlete?.name) }}
            </span>
            <span
              class="absolute bottom-0 right-0 h-3 w-3 rounded-full border-2 border-slate-950"
              :class="t.is_online ? 'bg-emerald-400' : 'bg-slate-600'"
            />
          </div>
          <div class="min-w-0 flex-1 overflow-hidden">
            <div class="flex min-w-0 items-center justify-between gap-2">
              <p class="min-w-0 flex-1 truncate text-sm font-semibold text-white">
                {{ t.athlete?.name ?? 'Athlète' }}
              </p>
              <span class="shrink-0 text-[11px] text-slate-500">
                {{ timeOfDayFr(t.updated_at) || relativeTimeFr(t.updated_at) }}
              </span>
            </div>
            <p class="mt-0.5 min-w-0 truncate text-xs text-slate-500">
              <template v-if="t.last_message">
                {{ t.last_message.is_mine ? 'Toi : ' : '' }}{{ t.last_message.content }}
              </template>
              <template v-else>Aucun message</template>
            </p>
          </div>
        </Link>
      </motion.li>
    </ul>
  </section>
</template>
