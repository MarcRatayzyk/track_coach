<script setup>
import { computed } from 'vue';
import ExercisePrescriptionEditor from './ExercisePrescriptionEditor.vue';
import { MAIN_LIFTS, defaultLiftName, emptyExerciseLine, formatPrescription } from '../utils/programBuilder';

const block = defineModel({ type: Object, required: true });

const emit = defineEmits(['remove', 'confirm']);

const liftLabel = computed(() => defaultLiftName(block.value.lift));

const summary = computed(() => {
  const parts = [];
  if (block.value.topset?.exercise_name) {
    parts.push(`Top: ${formatPrescription(block.value.topset)}`);
  }
  if (block.value.backoff?.exercise_name) {
    parts.push(`Back: ${formatPrescription(block.value.backoff)}`);
  }
  for (const acc of block.value.accessories ?? []) {
    if (acc.exercise_name) {
      parts.push(`Acc: ${formatPrescription(acc)}`);
    }
  }
  return parts.join(' · ') || 'Série vide';
});

function setLift(lift) {
  block.value.lift = lift;
}

function enableBackoff() {
  block.value.backoff = emptyExerciseLine('');
}

function removeBackoff() {
  block.value.backoff = null;
}

function addAccessory() {
  if (!block.value.accessories) {
    block.value.accessories = [];
  }
  block.value.accessories.push(emptyExerciseLine(''));
}

function removeAccessory(index) {
  block.value.accessories.splice(index, 1);
}

function confirmBlock() {
  if (!block.value.topset?.exercise_name?.trim()) {
    return;
  }
  block.value.collapsed = true;
  emit('confirm');
}

function expandBlock() {
  block.value.collapsed = false;
}
</script>

<template>
  <div class="rounded-xl border border-slate-800 bg-slate-900/40">
    <div
      v-if="block.collapsed"
      class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
    >
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-white">{{ liftLabel }}</p>
        <p class="mt-1 truncate text-xs text-slate-400">{{ summary }}</p>
      </div>
      <div class="flex shrink-0 gap-2">
        <button
          type="button"
          class="text-xs font-medium text-blue-400 hover:text-blue-300"
          @click="expandBlock"
        >
          Modifier
        </button>
        <button
          type="button"
          class="text-xs text-red-400 hover:text-red-300"
          @click="emit('remove')"
        >
          Supprimer
        </button>
      </div>
    </div>

    <div v-else class="space-y-4 p-4">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Main lift</p>
        <div class="flex flex-wrap gap-1.5">
          <button
            v-for="lift in MAIN_LIFTS"
            :key="lift.value"
            type="button"
            class="rounded-lg px-2.5 py-1 text-xs font-medium transition"
            :class="
              block.lift === lift.value
                ? 'bg-blue-600 text-white'
                : 'border border-slate-700 text-slate-400 hover:text-white'
            "
            @click="setLift(lift.value)"
          >
            {{ lift.label }}
          </button>
        </div>
      </div>

      <ExercisePrescriptionEditor v-model="block.topset" title="Top set" :lift="block.lift" accent="emerald" />

      <div class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h4 class="text-sm font-semibold text-white">Backoff</h4>
          <button
            v-if="!block.backoff"
            type="button"
            class="text-xs font-medium text-blue-400 hover:text-blue-300"
            @click="enableBackoff"
          >
            + Ajouter backoff
          </button>
          <button
            v-else
            type="button"
            class="text-xs text-red-400 hover:text-red-300"
            @click="removeBackoff"
          >
            Retirer backoff
          </button>
        </div>
        <ExercisePrescriptionEditor
          v-if="block.backoff"
          v-model="block.backoff"
          title="Backoff"
          :lift="block.lift"
          accent="slate"
        />
      </div>

      <div class="space-y-3">
        <div class="flex items-center justify-between gap-2">
          <h4 class="text-sm font-semibold text-white">Accessoires</h4>
          <button
            type="button"
            class="text-xs font-medium text-blue-400 hover:text-blue-300"
            @click="addAccessory"
          >
            + Accessoire
          </button>
        </div>
        <div v-if="block.accessories?.length" class="space-y-3">
          <div
            v-for="(acc, index) in block.accessories"
            :key="index"
            class="relative"
          >
            <button
              type="button"
              class="absolute right-2 top-2 z-10 text-xs text-red-400 hover:text-red-300"
              @click="removeAccessory(index)"
            >
              ✕
            </button>
            <ExercisePrescriptionEditor
              v-model="block.accessories[index]"
              :title="`Accessoire ${index + 1}`"
              :lift="block.lift"
              accent="zinc"
            />
          </div>
        </div>
        <p v-else class="text-xs text-slate-500">Aucun accessoire.</p>
      </div>

      <div class="flex flex-wrap gap-2 border-t border-slate-800 pt-4">
        <button
          type="button"
          class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
          @click="confirmBlock"
        >
          Valider cette série
        </button>
        <button
          type="button"
          class="rounded-xl border border-red-500/50 px-4 py-2 text-sm text-red-300 hover:bg-red-950/40"
          @click="emit('remove')"
        >
          Annuler
        </button>
      </div>
    </div>
  </div>
</template>
