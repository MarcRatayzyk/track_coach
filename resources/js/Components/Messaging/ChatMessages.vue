<script setup>
import { useI18n } from 'vue-i18n';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import MessageBubble from './MessageBubble.vue';
import TypingIndicator from './TypingIndicator.vue';
import { messagingDateKey, messagingDateLabel } from '../../utils/messagingFormat';

const { t } = useI18n();

const props = defineProps({
  messages: {
    type: Array,
    default: () => [],
  },
  myId: {
    type: [Number, String],
    default: null,
  },
  isTyping: {
    type: Boolean,
    default: false,
  },
  typingName: {
    type: String,
    default: '',
  },
  reactionsByMessage: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['react']);

const scroller = ref(null);

const items = computed(() => {
  const result = [];
  let previousKey = null;
  let previousSender = null;

  props.messages.forEach((message, index) => {
    const dateKey = messagingDateKey(message.created_at);
    if (dateKey && dateKey !== previousKey) {
      result.push({
        type: 'separator',
        id: `sep-${dateKey}`,
        label: messagingDateLabel(message.created_at),
      });
      previousKey = dateKey;
      previousSender = null;
    }

    const mine = message.sender_id === props.myId;
    const grouped = previousSender === message.sender_id;
    const next = props.messages[index + 1];
    const showAvatar = !next || next.sender_id !== message.sender_id;
    const pinned = Boolean(message.session_feedback) && index === props.messages.findIndex((m) => m.session_feedback);

    result.push({
      type: 'message',
      id: message.id,
      message,
      mine,
      grouped,
      showAvatar,
      pinned,
    });

    previousSender = message.sender_id;
  });

  return result;
});

async function scrollToBottom(smooth = true) {
  await nextTick();
  const el = scroller.value;
  if (!el) {
    return;
  }
  el.scrollTo({
    top: el.scrollHeight,
    behavior: smooth ? 'smooth' : 'auto',
  });
}

watch(
  () => props.messages.length,
  () => scrollToBottom(true),
);

watch(
  () => props.messages.map((m) => m.id).join(','),
  () => scrollToBottom(false),
);

onMounted(() => scrollToBottom(false));

defineExpose({ scrollToBottom });
</script>

<template>
  <div
    ref="scroller"
    class="tc-scrollbar min-h-0 flex-1 space-y-1 overflow-y-auto overflow-x-hidden px-4 py-4 lg:px-6"
  >
    <template v-for="item in items" :key="item.id">
      <div v-if="item.type === 'separator'" class="my-5 flex items-center gap-3">
        <div class="h-px flex-1 bg-gradient-to-r from-transparent via-slate-700 to-transparent" />
        <span class="rounded-full border border-slate-800 bg-slate-900/80 px-3 py-1 text-[11px] font-medium capitalize text-slate-400">
          {{ item.label }}
        </span>
        <div class="h-px flex-1 bg-gradient-to-r from-transparent via-slate-700 to-transparent" />
      </div>

      <MessageBubble
        v-else
        :message="item.message"
        :mine="item.mine"
        :grouped="item.grouped"
        :show-avatar="item.showAvatar"
        :pinned="item.pinned"
        :reactions="reactionsByMessage[item.message.id] ?? []"
        @react="emit('react', item.message.id, $event)"
      />
    </template>

    <TypingIndicator v-if="isTyping" :name="typingName" />

    <p v-if="!messages.length" class="py-16 text-center text-sm text-slate-500">
      {{ t('app.messaging.startConversation') }}
    </p>
  </div>
</template>
