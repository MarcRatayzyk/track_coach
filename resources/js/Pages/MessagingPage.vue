<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { useI18n } from 'vue-i18n';
const { t } = useI18n();
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { motion } from 'motion-v';
import AthleteContextPanel from '../Components/Messaging/AthleteContextPanel.vue';
import ChatHeader from '../Components/Messaging/ChatHeader.vue';
import ChatInput from '../Components/Messaging/ChatInput.vue';
import ChatMessages from '../Components/Messaging/ChatMessages.vue';
import ConversationSidebar from '../Components/Messaging/ConversationSidebar.vue';
import EmptyConversation from '../Components/Messaging/EmptyConversation.vue';
import MessageLayout from '../Components/Messaging/MessageLayout.vue';
import { formatCalendarFr } from '../utils/formatDates';
import { echo } from '../echo';
import { track } from '../utils/analytics';

const PIN_STORAGE_KEY = 'power-roster-messaging-pins';

const props = defineProps({
  role: {
    type: String,
    default: 'coach',
  },
  threads: {
    type: Array,
    default: () => [],
  },
  activeThread: {
    type: Object,
    default: null,
  },
  messages: {
    type: Array,
    default: () => [],
  },
  athletesForThread: {
    type: Array,
    default: () => [],
  },
  athleteContext: {
    type: Object,
    default: null,
  },
  feedbackContext: {
    type: Object,
    default: null,
  },
});

const page = usePage();
const myId = computed(() => page.props.auth?.user?.id);
const isCoach = computed(() => props.role === 'coach');
const localMessages = ref([...props.messages]);
const recordedAudioFiles = ref([]);
const pinnedIds = ref([]);
const showContext = ref(Boolean(props.activeThread && isCoach.value));
const mobilePane = ref(props.activeThread ? 'chat' : 'list');
const reactionsByMessage = ref({});
let echoChannel = null;

const messageForm = useForm({
  content: '',
  session_feedback_id: props.feedbackContext?.can_reply ? props.feedbackContext.id : null,
});

const conversationTitle = computed(() => {
  if (!props.activeThread) {
    return 'Conversation';
  }

  if (isCoach.value) {
    return props.activeThread.athlete?.name ?? 'Conversation';
  }

  return props.activeThread.coach?.name ?? 'Coach';
});

const isFeedbackReply = computed(() => messageForm.session_feedback_id != null);

function loadPins() {
  try {
    const raw = localStorage.getItem(PIN_STORAGE_KEY);
    const parsed = raw ? JSON.parse(raw) : [];
    pinnedIds.value = Array.isArray(parsed) ? parsed.map(Number) : [];
  } catch {
    pinnedIds.value = [];
  }
}

function persistPins() {
  localStorage.setItem(PIN_STORAGE_KEY, JSON.stringify(pinnedIds.value));
}

function togglePin(threadId) {
  const id = Number(threadId);
  if (pinnedIds.value.includes(id)) {
    pinnedIds.value = pinnedIds.value.filter((item) => item !== id);
  } else {
    pinnedIds.value = [...pinnedIds.value, id];
  }
  persistPins();
}

function onVoiceRecorded(file) {
  recordedAudioFiles.value = [...recordedAudioFiles.value, file];
}

function removeAudioFile(index) {
  recordedAudioFiles.value = recordedAudioFiles.value.filter((_, i) => i !== index);
}

function submitMessage() {
  if (!props.activeThread) {
    return;
  }

  const content = messageForm.content;
  const hasAudio = recordedAudioFiles.value.length > 0;
  const optimisticId = `pending-${Date.now()}`;
  let addedOptimistic = false;

  if (!hasAudio && content.trim() !== '') {
    localMessages.value.push({
      id: optimisticId,
      sender_id: myId.value,
      content,
      created_at: new Date().toISOString(),
      read_at: null,
      sender: {
        id: myId.value,
        name: page.props.auth?.user?.name ?? 'Moi',
      },
      audio_files: [],
      session_feedback: isFeedbackReply.value ? { id: messageForm.session_feedback_id } : null,
    });
    addedOptimistic = true;
  }

  messageForm
    .transform((data) => ({
      ...data,
      audio_files: recordedAudioFiles.value,
    }))
    .post(
      isCoach.value
        ? `/coach/threads/${props.activeThread.id}/messages`
        : `/messaging/threads/${props.activeThread.id}/messages`,
      {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        only: ['messages', 'threads', 'athleteContext', 'activeThread'],
        onSuccess: () => {
          track('message_sent', {
            role: props.role,
            has_audio: hasAudio,
          });
          messageForm.reset('content');
          messageForm.session_feedback_id = null;
          recordedAudioFiles.value = [];
        },
        onError: () => {
          if (addedOptimistic) {
            localMessages.value = localMessages.value.filter((message) => message.id !== optimisticId);
          }
        },
      },
    );
}

function addReaction(messageId, emoji) {
  const current = reactionsByMessage.value[messageId] ?? [];
  if (current.includes(emoji)) {
    reactionsByMessage.value = {
      ...reactionsByMessage.value,
      [messageId]: current.filter((item) => item !== emoji),
    };
    return;
  }
  reactionsByMessage.value = {
    ...reactionsByMessage.value,
    [messageId]: [...current, emoji],
  };
}

function subscribeToThread(threadId) {
  if (!echo || !threadId) {
    return;
  }

  if (echoChannel) {
    echo.leave(`private-threads.${echoChannel}`);
    echoChannel = null;
  }

  echoChannel = threadId;
  echo.private(`threads.${threadId}`).listen('.message.sent', (payload) => {
    const incoming = payload?.message;
    if (!incoming || incoming.id == null) {
      return;
    }

    if (localMessages.value.some((message) => message.id === incoming.id)) {
      return;
    }

    localMessages.value.push(incoming);
  });
}

function backToList() {
  mobilePane.value = 'list';
}

function toggleContext() {
  showContext.value = !showContext.value;
  if (showContext.value && typeof window !== 'undefined' && window.innerWidth < 1280) {
    mobilePane.value = 'context';
  }
}

function closeContext() {
  showContext.value = false;
  if (mobilePane.value === 'context') {
    mobilePane.value = 'chat';
  }
}

watch(
  () => props.messages,
  (value) => {
    localMessages.value = [...value];
  },
);

watch(
  () => props.activeThread?.id,
  (threadId) => {
    subscribeToThread(threadId);
    if (threadId) {
      mobilePane.value = 'chat';
      showContext.value = isCoach.value;
    } else {
      mobilePane.value = 'list';
      showContext.value = false;
    }
  },
  { immediate: true },
);

watch(
  () => props.feedbackContext,
  (context) => {
    messageForm.session_feedback_id = context?.can_reply ? context.id : null;
  },
  { immediate: true },
);

onMounted(() => {
  loadPins();
});

onUnmounted(() => {
  if (echo && echoChannel) {
    echo.leave(`private-threads.${echoChannel}`);
  }
});
</script>

<template>
  <MessageLayout
    :show-sidebar="isCoach"
    :show-context="isCoach && Boolean(activeThread) && showContext"
    :mobile-pane="mobilePane"
  >
    <template #sidebar>
      <ConversationSidebar
        v-if="isCoach"
        :threads="threads"
        :active-thread-id="activeThread?.id"
        :athletes-for-thread="athletesForThread"
        :pinned-ids="pinnedIds"
        :is-coach="isCoach"
        @toggle-pin="togglePin"
      />
    </template>

    <template #chat>
      <motion.div
        class="flex h-full min-h-0 w-full flex-col overflow-hidden rounded-[18px] border border-slate-800 bg-slate-900/50 shadow-xl backdrop-blur-md"
        :initial="{ opacity: 0 }"
        :animate="{ opacity: 1 }"
        :transition="{ duration: 0.2 }"
        :key="activeThread?.id ?? 'empty'"
      >
        <template v-if="activeThread">
          <ChatHeader
            :title="conversationTitle"
            :online="Boolean(activeThread.is_online)"
            :last-session="athleteContext?.last_session"
            :goal="athleteContext?.goal"
            :profile-url="athleteContext?.profile_url"
            :is-coach="isCoach"
            @back="backToList"
            @toggle-context="toggleContext"
          />

          <div
            v-if="feedbackContext?.can_reply"
            class="border-b border-blue-500/25 bg-blue-950/20 px-4 py-2.5 lg:px-5"
          >
            <p class="text-sm font-medium text-blue-200">
              {{ t('app.messaging.replyToFeedback', { date: formatCalendarFr(feedbackContext.session_date) }) }}
              <span v-if="feedbackContext.session_label"> — {{ feedbackContext.session_label }}</span>
            </p>
            <Link
              :href="`/feedbacks?feedback=${feedbackContext.id}`"
              class="mt-1 inline-block text-xs text-blue-400/80 transition hover:text-blue-300"
            >
              {{ t('app.messaging.seeFeedback') }}
            </Link>
          </div>

          <ChatMessages
            :messages="localMessages"
            :my-id="myId"
            :reactions-by-message="reactionsByMessage"
            @react="addReaction"
          />

          <form class="shrink-0 border-t border-slate-800/80 p-3 lg:p-4" @submit.prevent="submitMessage">
            <ChatInput
              v-model="messageForm.content"
              :placeholder="isFeedbackReply
                ? (isCoach ? 'Commentaire pour l’athlète…' : 'Répondre au coach…')
                : 'Écrire un message…'"
              :processing="messageForm.processing"
              :audio-files="recordedAudioFiles"
              :allow-voice="false"
              @submit="submitMessage"
              @recorded="onVoiceRecorded"
              @remove-audio="removeAudioFile"
            />

            <p v-if="Object.keys(messageForm.errors).length" class="mt-2 text-sm text-red-400">
              {{ Object.values(messageForm.errors).flat().join(' ') }}
            </p>
          </form>
        </template>

        <EmptyConversation v-else :is-coach="isCoach" />
      </motion.div>
    </template>

    <template #context>
      <AthleteContextPanel
        v-if="isCoach"
        :context="athleteContext"
        @close="closeContext"
      />
    </template>
  </MessageLayout>
</template>
