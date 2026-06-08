<script setup>
import { computed, watch } from 'vue';
import ExercisePrescriptionEditor from './ExercisePrescriptionEditor.vue';
import {
  createSessionItem,
  findSessionItem,
  formatLineRecap,
  itemSectionTitle,
  sessionItemHasContent,
} from '../utils/programBuilder';

const day = defineModel({ type: Object, required: true });

const items = computed({
  get: () => day.value.items ?? [],
  set(value) {
    day.value.items = value;
  },
});

const editingId = computed({
  get: () => day.value.editingId ?? null,
  set(value) {
    day.value.editingId = value;
  },
});

const editingItem = computed(() => items.value.find((item) => item.id === editingId.value) ?? null);

const editingIndex = computed(() =>
  editingItem.value ? items.value.findIndex((item) => item.id === editingItem.value.id) : -1,
);

const listedItems = computed(() =>
  items.value.filter((item) => item.id !== editingId.value),
);

const editingRecap = computed(() => formatLineRecap(editingItem.value?.line));

const editingRecapText = computed(
  () => editingRecap.value ?? 'Choisis un exercice, puis séries, reps et charge.',
);

const accentBySection = {
  topset: 'emerald',
  backoff: 'slate',
  accessory: 'zinc',
};

const borderBySection = {
  topset: 'border-emerald-500/20 bg-emerald-950/10',
  backoff: 'border-slate-800 bg-slate-950/40',
  accessory: 'border-slate-800 bg-slate-950/40',
};

const titleClassBySection = {
  topset: 'text-emerald-400/90',
  backoff: 'text-slate-500',
  accessory: 'text-slate-500',
};

watch(
  () => items.value.find((item) => item.section === 'topset')?.line?.lift,
  (lift) => {
    if (lift) {
      day.value.lift = lift;
    }
  },
);

function ensureItems() {
  if (!day.value.items) {
    day.value.items = [];
  }
}

function startSection(section) {
  ensureItems();

  if (section === 'backoff' && findSessionItem(day.value, 'backoff')) {
    return;
  }

  const item = createSessionItem(section);
  day.value.items.push(item);
  editingId.value = item.id;
}

function finishEditing() {
  editingId.value = null;
}

function removeItem(itemId) {
  const index = items.value.findIndex((item) => item.id === itemId);
  if (index === -1) {
    return;
  }
  day.value.items.splice(index, 1);
  if (editingId.value === itemId) {
    finishEditing();
  }
}

function editItem(itemId) {
  editingId.value = itemId;
}

function moveItem(index, delta) {
  const target = index + delta;
  if (target < 0 || target >= items.value.length) {
    return;
  }
  const next = [...items.value];
  [next[index], next[target]] = [next[target], next[index]];
  items.value = next;
}

function itemRecap(item) {
  return formatLineRecap(item.line);
}

function itemIndex(itemId) {
  return items.value.findIndex((item) => item.id === itemId);
}
</script>

<template>
  <div class="space-y-4">
    <div
      class="sticky top-0 z-10 -mx-1 rounded-xl border border-slate-800/80 bg-slate-950/95 px-2 py-3 backdrop-blur-sm"
    >
      <p v-if="items.length === 0" class="mb-2 text-sm text-slate-400">
        Choisis ce que tu veux programmer pour ce jour.
      </p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="min-w-0 flex-1 rounded-xl border border-slate-600 bg-slate-950 px-2 py-2.5 text-center text-xs font-semibold text-white hover:border-slate-500 sm:px-3 sm:text-sm"
          @click="startSection('topset')"
        >
          Ajouter top set
        </button>
        <button
          v-if="!items.some((item) => item.section === 'backoff')"
          type="button"
          class="min-w-0 flex-1 rounded-xl border border-slate-600 bg-slate-950 px-2 py-2.5 text-center text-xs font-semibold text-white hover:border-slate-500 sm:px-3 sm:text-sm"
          @click="startSection('backoff')"
        >
          Ajouter backoff
        </button>
        <button
          type="button"
          class="min-w-0 flex-1 rounded-xl border border-slate-600 bg-slate-950 px-2 py-2.5 text-center text-xs font-semibold text-white hover:border-slate-500 sm:px-3 sm:text-sm"
          @click="startSection('accessory')"
        >
          Ajouter accessoire
        </button>
      </div>
    </div>

    <div
      v-if="editingItem && editingIndex >= 0"
      class="rounded-xl border px-4 py-3"
      :class="borderBySection[editingItem.section] ?? borderBySection.accessory"
    >
      <div class="mb-3 flex items-center justify-between gap-2">
        <p class="text-sm font-semibold" :class="titleClassBySection[editingItem.section]">
          {{ itemSectionTitle(editingItem, items) }}
        </p>
        <div class="flex shrink-0 items-center gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-slate-500 hover:text-white disabled:opacity-30"
            :disabled="editingIndex === 0"
            title="Monter"
            @click="moveItem(editingIndex, -1)"
          >
            ↑
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-slate-500 hover:text-white disabled:opacity-30"
            :disabled="editingIndex === items.length - 1"
            title="Descendre"
            @click="moveItem(editingIndex, 1)"
          >
            ↓
          </button>
        </div>
      </div>

      <div class="mb-3 rounded-lg border-2 border-blue-500/40 bg-blue-950/40 px-3 py-2.5">
        <p class="text-xs font-medium uppercase tracking-wide text-blue-300">Récap</p>
        <p
          class="mt-1 text-base font-semibold"
          :class="editingRecap ? 'text-white' : 'text-slate-500'"
        >
          {{ editingRecapText }}
        </p>
      </div>

      <ExercisePrescriptionEditor
        :key="`${editingItem.id}-edit`"
        v-model="day.items[editingIndex].line"
        embedded
        :lift="day.lift"
        :accent="accentBySection[editingItem.section] ?? 'zinc'"
        :title="itemSectionTitle(editingItem, items)"
      />

      <div class="mt-4 flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-xl border border-slate-600 px-4 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800"
          @click="finishEditing"
        >
          ← Retour
        </button>
        <button
          type="button"
          class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
          @click="finishEditing"
        >
          Valider
        </button>
      </div>
    </div>

    <div
      v-for="item in listedItems"
      :key="item.id"
      class="rounded-xl border px-4 py-3"
      :class="borderBySection[item.section] ?? borderBySection.accessory"
    >
      <div class="mb-2 flex items-center justify-between gap-2">
        <p class="text-xs font-medium" :class="titleClassBySection[item.section]">
          {{ itemSectionTitle(item, items) }}
        </p>
        <div class="flex shrink-0 items-center gap-1">
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-slate-500 hover:text-white disabled:opacity-30"
            :disabled="itemIndex(item.id) === 0"
            title="Monter"
            @click="moveItem(itemIndex(item.id), -1)"
          >
            ↑
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2 py-1 text-xs text-slate-400 hover:border-slate-500 hover:text-white disabled:opacity-30"
            :disabled="itemIndex(item.id) === items.length - 1"
            title="Descendre"
            @click="moveItem(itemIndex(item.id), 1)"
          >
            ↓
          </button>
        </div>
      </div>

      <template v-if="sessionItemHasContent(item) && itemRecap(item)">
        <p class="text-sm font-semibold text-white">{{ itemRecap(item) }}</p>
        <div class="mt-2 flex flex-wrap gap-2">
          <button type="button" class="text-xs text-blue-400 hover:text-blue-300" @click="editItem(item.id)">
            Modifier
          </button>
          <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="removeItem(item.id)">
            Retirer
          </button>
        </div>
      </template>
      <template v-else>
        <p class="text-sm text-slate-500">En cours de saisie…</p>
        <button type="button" class="mt-2 text-xs text-blue-400 hover:text-blue-300" @click="editItem(item.id)">
          Reprendre
        </button>
      </template>
    </div>
  </div>
</template>
