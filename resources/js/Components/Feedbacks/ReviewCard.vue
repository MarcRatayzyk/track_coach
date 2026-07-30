<script setup>
import { computed } from 'vue';
import { motion } from 'motion-v';
import { formatCalendarFr } from '../../utils/formatDates';
import { messagingInitials, messagingRelativeTime } from '../../utils/messagingFormat';
import ReviewStatusBadge from './ReviewStatusBadge.vue';
import FeedbackFrequencyPill from './FeedbackFrequencyPill.vue';
import UiIcon from '../UiIcon.vue';

const props = defineProps({
  item: { type: Object, required: true },
  selected: { type: Boolean, default: false },
  urgency: { type: String, default: 'normal' },
  mode: { type: String, default: 'coach' },
});

defineEmits(['select']);

const title = computed(() =>
  props.mode === 'athlete'
    ? props.item.session_label || 'Séance'
    : props.item.athlete_name || 'Athlète',
);

const subtitle = computed(() => {
  const date = formatCalendarFr(props.item.session_date, 'medium');
  if (props.mode === 'athlete') {
    return date;
  }
  return props.item.session_label ? `${date} · ${props.item.session_label}` : date;
});

const avatarLabel = computed(() =>
  props.mode === 'athlete'
    ? props.item.session_label || props.item.athlete_name
    : props.item.athlete_name,
);

const notePreview = computed(() => {
  const notes = (props.item.athlete_notes || '').trim();
  if (!notes) return props.mode === 'athlete' ? 'Sans commentaire' : 'Sans commentaire';
  return notes.length > 72 ? `${notes.slice(0, 72)}…` : notes;
});
</script>

<template>
  <motion.button
    type="button"
    :initial="{ opacity: 0, y: 6 }"
    :animate="{ opacity: 1, y: 0 }"
    :transition="{ duration: 0.2 }"
    :whileHover="{ y: -1 }"
    class="group w-full rounded-[18px] border p-3 text-left shadow-md backdrop-blur-sm transition duration-200"
    :class="
      selected
        ? 'border-blue-500/60 bg-blue-600/15 shadow-[0_0_28px_rgba(59,130,246,0.22)]'
        : mode === 'coach' && urgency === 'overdue'
          ? 'border-rose-500/25 bg-rose-950/15 hover:border-rose-500/40 hover:shadow-[0_0_20px_rgba(244,63,94,0.1)]'
          : 'border-slate-800/80 bg-slate-950/40 hover:border-slate-700 hover:bg-slate-900/50 hover:shadow-[0_0_20px_rgba(59,130,246,0.1)]'
    "
    @click="$emit('select', item.id)"
  >
    <div class="flex items-start gap-3">
      <div
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-xs font-semibold text-white ring-1 ring-slate-700/80 transition duration-200 group-hover:ring-blue-500/40"
      >
        {{ messagingInitials(avatarLabel) }}
      </div>
      <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2">
          <div class="flex min-w-0 flex-wrap items-center gap-1.5">
            <p class="truncate text-sm font-semibold text-white">{{ title }}</p>
            <FeedbackFrequencyPill :frequency="item.feedback_frequency" compact />
          </div>
          <ReviewStatusBadge :status="item.status" :urgency="urgency" :mode="mode" />
        </div>
        <p class="mt-0.5 truncate text-xs text-slate-400">
          {{ subtitle }}
        </p>
      </div>
    </div>

    <p
      class="mt-2.5 line-clamp-2 text-xs leading-relaxed"
      :class="item.athlete_notes ? 'text-slate-300' : 'text-slate-600'"
    >
      {{ notePreview }}
    </p>

    <div class="mt-2.5 flex items-center justify-between gap-2 text-[11px] text-slate-400">
      <span class="inline-flex items-center gap-1">
        <UiIcon name="video" class="h-3.5 w-3.5" />
        {{ item.video_count || 0 }} vidéo{{ (item.video_count || 0) > 1 ? 's' : '' }}
      </span>
      <span class="tabular-nums">{{ messagingRelativeTime(item.submitted_at) }}</span>
    </div>
  </motion.button>
</template>
