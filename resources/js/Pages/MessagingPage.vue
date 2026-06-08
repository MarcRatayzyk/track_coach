<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import MessageThreadUnreadBadge from '../Components/MessageThreadUnreadBadge.vue';
import { echo } from '../echo';

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
});

const page = usePage();
const myId = computed(() => page.props.auth?.user?.id);
const isCoach = computed(() => props.role === 'coach');
const localMessages = ref([...props.messages]);
let echoChannel = null;

const threadForm = useForm({
  athlete_id: props.athletesForThread.length ? props.athletesForThread[0].id : '',
});

const messageForm = useForm({
  content: '',
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

function openThreadUrl(id) {
  return `/messaging?thread=${id}`;
}

function submitNewThread() {
  threadForm.post('/coach/threads', { preserveScroll: true });
}

function submitMessage() {
  if (!props.activeThread) {
    return;
  }
  messageForm.post(`/coach/threads/${props.activeThread.id}/messages`, {
    preserveScroll: true,
    onSuccess: () => messageForm.reset('content'),
  });
}

function isMine(senderId) {
  return senderId === myId.value;
}

function formatTime(iso) {
  if (!iso) {
    return '';
  }
  try {
    return new Date(iso).toLocaleString('fr-FR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return iso;
  }
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
  },
  { immediate: true },
);

onUnmounted(() => {
  if (echo && echoChannel) {
    echo.leave(`private-threads.${echoChannel}`);
  }
});
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-white">Messagerie</h1>
    <p class="mt-3 max-w-3xl text-base leading-relaxed text-slate-400">
      <template v-if="isCoach">Échange avec tes athlètes par conversation.</template>
      <template v-else>Échange directement avec ton coach.</template>
    </p>

    <div class="mt-4 grid gap-8" :class="isCoach ? 'lg:grid-cols-12' : ''">
      <aside
        v-if="isCoach"
        class="min-w-0 rounded-2xl border border-slate-800 bg-slate-900/50 p-6 shadow-xl lg:col-span-4 lg:p-8"
      >
        <h2 class="text-base font-semibold uppercase tracking-wide text-slate-500">
          Conversations
        </h2>
        <ul class="tc-scrollbar mt-4 max-h-[32rem] space-y-2 overflow-x-hidden overflow-y-auto pr-1.5">
          <li v-for="t in threads" :key="t.id">
            <Link
              :href="openThreadUrl(t.id)"
              preserve-state
              class="relative block rounded-xl px-4 py-4 pr-10 text-lg transition lg:px-5 lg:py-3"
              :class="
                activeThread?.id === t.id
                  ? 'bg-blue-600 text-white shadow-lg'
                  : (t.unread_messages_count ?? 0) > 0
                    ? 'bg-blue-950/40 text-slate-100 ring-1 ring-blue-500/30 hover:bg-blue-950/60'
                    : 'text-slate-200 hover:bg-slate-800'
              "
            >
              <MessageThreadUnreadBadge :count="t.unread_messages_count ?? 0" />
              <span class="text-xl font-semibold">{{ t.athlete?.name ?? 'Athlète' }}</span>
              <span class="mt-1 block text-base opacity-80">
                {{ t.messages_count ?? 0 }} message(s)
              </span>
            </Link>
          </li>
        </ul>

        <div class="mt-5 border-t border-slate-800 pt-6">
          <h3 class="text-sm font-semibold text-white">Nouvelle conversation</h3>
          <form class="mt-4 space-y-4" @submit.prevent="submitNewThread">
            <select
              v-model="threadForm.athlete_id"
              required
              class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-lg text-white"
            >
              <option value="" disabled>Choisir un athlète</option>
              <option v-for="a in athletesForThread" :key="a.id" :value="a.id">
                {{ a.name }}
              </option>
            </select>
            <p v-if="Object.keys(threadForm.errors).length" class="text-base text-red-400">
              {{ Object.values(threadForm.errors).flat().join(' ') }}
            </p>
            <button
              type="submit"
              :disabled="threadForm.processing"
              class="w-full rounded-xl bg-slate-700 py-4 text-sm font-semibold text-white shadow hover:bg-slate-600 disabled:opacity-50"
            >
              Ouvrir
            </button>
          </form>
        </div>
      </aside>

      <section
        class="flex min-h-[28rem] flex-col rounded-2xl border border-slate-800 bg-slate-900/50 shadow-xl lg:min-h-[32rem]"
        :class="isCoach ? 'lg:col-span-8' : ''"
      >
        <template v-if="activeThread">
          <div class="border-b border-slate-800 px-6 py-3 lg:px-8 lg:py-6">
            <h2 class="text-sm font-semibold text-white">{{ conversationTitle }}</h2>
            <p class="mt-1 text-xs text-slate-500">Fil #{{ activeThread.id }}</p>
          </div>
          <div
            class="tc-scrollbar max-h-[26rem] flex-1 space-y-4 overflow-x-hidden overflow-y-auto p-6 pr-5 lg:max-h-[28rem] lg:p-8 lg:pr-7"
          >
            <div
              v-for="m in localMessages"
              :key="m.id"
              class="flex"
              :class="isMine(m.sender_id) ? 'justify-end' : 'justify-start'"
            >
              <div
                class="max-w-[90%] rounded-2xl px-5 py-4 text-lg"
                :class="
                  isMine(m.sender_id)
                    ? 'bg-blue-600 text-white'
                    : 'bg-slate-800 text-slate-100'
                "
              >
                <p class="text-base opacity-75">
                  {{ m.sender?.name ?? '?' }} · {{ formatTime(m.created_at) }}
                </p>
                <p class="mt-2 whitespace-pre-wrap leading-relaxed">{{ m.content }}</p>
              </div>
            </div>
          </div>
          <form class="border-t border-slate-800 p-3 p-4" @submit.prevent="submitMessage">
            <label class="sr-only">Message</label>
            <textarea
              v-model="messageForm.content"
              rows="4"
              required
              placeholder="Écrire un message…"
              class="w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-4 text-lg text-white placeholder:text-slate-600"
            />
            <p v-if="Object.keys(messageForm.errors).length" class="mt-2 text-base text-red-400">
              {{ Object.values(messageForm.errors).flat().join(' ') }}
            </p>
            <button
              type="submit"
              :disabled="messageForm.processing"
              class="mt-4 rounded-xl bg-blue-600 px-8 py-4 text-sm font-semibold text-white shadow-lg hover:bg-blue-500 disabled:opacity-50"
            >
              Envoyer
            </button>
          </form>
        </template>
        <div
          v-else
          class="flex flex-1 flex-col items-center justify-center p-12 text-center text-slate-500 lg:p-16"
        >
          <p class="max-w-md leading-relaxed">
            <template v-if="isCoach">
              Sélectionne une conversation à gauche ou ouvre-en une nouvelle.
            </template>
            <template v-else>
              Aucun coach associé pour le moment.
            </template>
          </p>
        </div>
      </section>
    </div>
  </div>
</template>
