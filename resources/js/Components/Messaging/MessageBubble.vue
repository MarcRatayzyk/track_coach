<script setup>
import { computed } from 'vue';
import { motion } from 'motion-v';
import FilePreview from './FilePreview.vue';
import MessageReaction from './MessageReaction.vue';
import { messagingClock, messagingInitials } from '../../utils/messagingFormat';

const props = defineProps({
  message: {
    type: Object,
    required: true,
  },
  mine: {
    type: Boolean,
    default: false,
  },
  showAvatar: {
    type: Boolean,
    default: true,
  },
  grouped: {
    type: Boolean,
    default: false,
  },
  pinned: {
    type: Boolean,
    default: false,
  },
  reactions: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['react']);

const time = computed(() => messagingClock(props.message.created_at));
const initials = computed(() => messagingInitials(props.message.sender?.name));
const readState = computed(() => {
  if (!props.mine) {
    return null;
  }
  if (String(props.message.id).startsWith('pending-')) {
    return 'sending';
  }
  if (props.message.read_at) {
    return 'read';
  }
  return 'sent';
});
</script>

<template>
  <motion.div
    class="flex gap-2"
    :class="[mine ? 'justify-end' : 'justify-start', grouped ? 'mt-1' : 'mt-4']"
    :initial="{ opacity: 0, y: 8 }"
    :animate="{ opacity: 1, y: 0 }"
    :transition="{ duration: 0.2, ease: [0.22, 1, 0.36, 1] }"
  >
    <div v-if="!mine" class="flex w-8 shrink-0 flex-col justify-end">
      <div
        v-if="showAvatar"
        class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-[10px] font-semibold text-white ring-1 ring-slate-700"
      >
        {{ initials }}
      </div>
    </div>

    <div class="group/bubble relative max-w-[min(85%,28rem)]" :class="mine ? 'items-end' : 'items-start'">
      <div
        v-if="pinned"
        class="mb-1.5 inline-flex items-center gap-1 rounded-full border border-blue-500/30 bg-blue-600/10 px-2 py-0.5 text-[10px] font-medium text-blue-300"
      >
        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
          <path d="M10 2a1 1 0 0 1 1 1v5.586l1.707-1.707a1 1 0 1 1 1.414 1.414l-3.5 3.5a1 1 0 0 1-1.414 0l-3.5-3.5a1 1 0 0 1 1.414-1.414L9 8.586V3a1 1 0 0 1 1-1Z" />
        </svg>
        Message épinglé
      </div>

      <div
        class="rounded-[18px] px-4 py-3 text-[15px] leading-relaxed shadow-lg transition duration-200"
        :class="
          mine
            ? 'rounded-br-md bg-blue-600 text-white shadow-blue-900/30'
            : 'rounded-bl-md border border-slate-700/60 bg-slate-800 text-slate-100 shadow-black/20'
        "
      >
        <p
          v-if="!grouped"
          class="mb-1 text-[11px] font-medium"
          :class="mine ? 'text-blue-100/80' : 'text-slate-400'"
        >
          {{ message.sender?.name ?? '?' }}
        </p>

        <span
          v-if="message.session_feedback"
          class="mb-2 inline-block rounded-full bg-blue-500/20 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
          :class="mine ? 'text-blue-100' : 'text-blue-300'"
        >
          Retour séance
        </span>

        <p v-if="message.content" class="whitespace-pre-wrap">{{ message.content }}</p>

        <div v-if="message.audio_files?.length" class="mt-2 space-y-2">
          <FilePreview
            v-for="audio in message.audio_files"
            :key="audio.id"
            :file="audio"
          />
        </div>

        <div class="mt-1.5 flex items-center justify-end gap-1.5">
          <span class="text-[10px]" :class="mine ? 'text-blue-100/70' : 'text-slate-500'">
            {{ time }}
          </span>
          <span v-if="readState === 'sending'" class="text-[10px] text-blue-100/60">…</span>
          <span v-else-if="readState === 'sent'" class="text-[10px] text-blue-100/70" title="Envoyé">✓</span>
          <span v-else-if="readState === 'read'" class="text-[10px] text-blue-200" title="Lu">✓✓</span>
        </div>
      </div>

      <MessageReaction
        :reactions="reactions"
        :mine="mine"
        @react="emit('react', $event)"
      />
    </div>
  </motion.div>
</template>
