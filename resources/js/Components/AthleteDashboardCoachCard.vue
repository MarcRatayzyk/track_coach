<script setup>
import { Link } from '@inertiajs/vue3';
import { formatCalendarFr } from '../utils/formatDates';

defineProps({
  coachThread: {
    type: Object,
    default: null,
  },
});

function formatTime(iso) {
  if (!iso) {
    return '';
  }
  try {
    return formatCalendarFr(iso.slice(0, 10), 'medium');
  } catch {
    return '';
  }
}
</script>

<template>
  <section
    v-if="coachThread"
    class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-lg"
  >
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold text-white">Coach — {{ coachThread.coach_name }}</h2>
      <Link
        :href="`/messaging?thread=${coachThread.id}`"
        class="text-xs font-medium text-blue-400 hover:text-blue-300"
      >
        {{ coachThread.unread_count > 0 ? 'Répondre' : 'Messagerie' }} →
      </Link>
    </div>

    <p
      v-if="coachThread.unread_count > 0"
      class="mt-2 text-xs font-medium text-blue-300"
    >
      {{ coachThread.unread_count }} message{{ coachThread.unread_count > 1 ? 's' : '' }} non lu{{ coachThread.unread_count > 1 ? 's' : '' }}
    </p>

    <p
      v-if="coachThread.last_message"
      class="mt-2 line-clamp-2 text-sm text-slate-300"
    >
      <span class="text-slate-500">
        {{ coachThread.last_message.is_mine ? 'Toi' : coachThread.last_message.sender_name }} ·
        {{ formatTime(coachThread.last_message.created_at) }} —
      </span>
      {{ coachThread.last_message.content }}
    </p>
    <p v-else class="mt-2 text-sm text-slate-500">Aucun message pour l’instant.</p>
  </section>
</template>
