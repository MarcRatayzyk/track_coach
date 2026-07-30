<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConversationCard from './ConversationCard.vue';
import SearchBar from './SearchBar.vue';

const props = defineProps({
  threads: {
    type: Array,
    default: () => [],
  },
  activeThreadId: {
    type: [Number, String],
    default: null,
  },
  athletesForThread: {
    type: Array,
    default: () => [],
  },
  pinnedIds: {
    type: Array,
    default: () => [],
  },
  isCoach: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['toggle-pin']);

const search = ref('');
const filter = ref('all');
const showNewModal = ref(false);

const threadForm = useForm({
  athlete_id: '',
});

function displayName(thread) {
  if (props.isCoach) {
    return thread.athlete?.name ?? 'Athlète';
  }
  return thread.coach?.name ?? 'Coach';
}

const filteredThreads = computed(() => {
  const query = search.value.trim().toLowerCase();
  return props.threads.filter((thread) => {
    const name = displayName(thread).toLowerCase();
    const preview = (thread.last_message?.content ?? '').toLowerCase();
    const matchesSearch = !query || name.includes(query) || preview.includes(query);
    if (!matchesSearch) {
      return false;
    }
    if (filter.value === 'unread') {
      return (thread.unread_messages_count ?? 0) > 0;
    }
    if (filter.value === 'pinned') {
      return props.pinnedIds.includes(thread.id);
    }
    return true;
  });
});

const pinnedThreads = computed(() =>
  filteredThreads.value.filter((thread) => props.pinnedIds.includes(thread.id)),
);

const regularThreads = computed(() =>
  filteredThreads.value.filter((thread) => !props.pinnedIds.includes(thread.id)),
);

function openNewConversation() {
  showNewModal.value = true;
}

function createThread() {
  if (!threadForm.athlete_id) {
    return;
  }
  threadForm.post('/coach/threads', {
    onSuccess: () => {
      showNewModal.value = false;
      threadForm.reset();
    },
  });
}

const filters = [
  { id: 'all', label: 'Tous' },
  { id: 'unread', label: 'Non lus' },
  { id: 'pinned', label: 'Épinglés' },
];
</script>

<template>
  <aside
    class="flex h-full min-h-0 w-full flex-col overflow-hidden rounded-[18px] border border-slate-800 bg-slate-900/50 shadow-xl backdrop-blur-md lg:w-[340px] lg:shrink-0"
  >
    <div class="border-b border-slate-800/80 p-4">
      <div class="flex items-center justify-between gap-3">
        <div>
          <h1 class="text-lg font-bold text-white">Messagerie</h1>
          <p class="mt-0.5 text-xs text-slate-500">
            {{ threads.length }} conversation{{ threads.length > 1 ? 's' : '' }}
          </p>
        </div>
        <button
          v-if="isCoach"
          type="button"
          class="inline-flex items-center gap-1.5 rounded-[14px] bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-lg shadow-blue-900/40 transition duration-200 hover:bg-blue-500"
          @click="openNewConversation"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
          </svg>
          Nouvelle
        </button>
      </div>

      <div class="mt-4">
        <SearchBar v-model="search" />
      </div>

      <div class="mt-3 flex gap-1.5">
        <button
          v-for="item in filters"
          :key="item.id"
          type="button"
          class="rounded-full px-3 py-1 text-xs font-medium transition duration-200"
          :class="
            filter === item.id
              ? 'bg-blue-600/20 text-blue-300 ring-1 ring-blue-500/40'
              : 'text-slate-500 hover:bg-slate-800 hover:text-slate-300'
          "
          @click="filter = item.id"
        >
          {{ item.label }}
        </button>
      </div>
    </div>

    <div class="tc-scrollbar min-h-0 flex-1 space-y-4 overflow-y-auto overflow-x-hidden p-3">
      <section v-if="pinnedThreads.length">
        <h2 class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
          Épinglées
        </h2>
        <div class="space-y-2">
          <ConversationCard
            v-for="thread in pinnedThreads"
            :key="`pinned-${thread.id}`"
            :thread="thread"
            :display-name="displayName(thread)"
            :active="activeThreadId === thread.id"
            :pinned="true"
            @toggle-pin="emit('toggle-pin', $event)"
          />
        </div>
      </section>

      <section>
        <h2
          v-if="pinnedThreads.length && regularThreads.length"
          class="mb-2 px-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500"
        >
          Récentes
        </h2>
        <div class="space-y-2">
          <ConversationCard
            v-for="thread in regularThreads"
            :key="thread.id"
            :thread="thread"
            :display-name="displayName(thread)"
            :active="activeThreadId === thread.id"
            :pinned="false"
            @toggle-pin="emit('toggle-pin', $event)"
          />
        </div>
      </section>

      <p
        v-if="!filteredThreads.length"
        class="px-2 py-8 text-center text-sm leading-relaxed text-slate-500"
      >
        <template v-if="threads.length === 0">
          Aucune conversation pour le moment. Ouvre-en une depuis le profil d’un athlète.
        </template>
        <template v-else>
          Aucun résultat pour cette recherche.
        </template>
      </p>
    </div>

    <Teleport to="body">
      <div
        v-if="showNewModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
        @click.self="showNewModal = false"
      >
        <div class="w-full max-w-md rounded-[18px] border border-slate-800 bg-slate-900 p-6 shadow-2xl">
          <h3 class="text-lg font-semibold text-white">Nouvelle conversation</h3>
          <p class="mt-1 text-sm text-slate-400">Choisis un athlète pour ouvrir l’échange.</p>
          <form class="mt-5 space-y-4" @submit.prevent="createThread">
            <select
              v-model="threadForm.athlete_id"
              class="w-full rounded-[14px] border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-blue-500/50 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
              required
            >
              <option value="" disabled>Sélectionner un athlète…</option>
              <option v-for="athlete in athletesForThread" :key="athlete.id" :value="athlete.id">
                {{ athlete.name }}
              </option>
            </select>
            <div class="flex justify-end gap-2">
              <button
                type="button"
                class="rounded-[14px] px-4 py-2 text-sm text-slate-400 transition hover:bg-slate-800 hover:text-white"
                @click="showNewModal = false"
              >
                Annuler
              </button>
              <button
                type="submit"
                class="rounded-[14px] bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition hover:bg-blue-500 disabled:opacity-50"
                :disabled="threadForm.processing || !threadForm.athlete_id"
              >
                Ouvrir
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>
  </aside>
</template>
