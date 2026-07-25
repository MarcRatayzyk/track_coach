<script setup>
import { computed } from 'vue';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  exercises: {
    type: Array,
    default: () => [],
  },
  title: {
    type: String,
    default: 'Choisir une série',
  },
});

const emit = defineEmits(['update:open', 'update:modelValue']);

const selectedId = computed(() =>
  props.modelValue === null || props.modelValue === undefined ? '' : String(props.modelValue),
);

function close() {
  emit('update:open', false);
}

function pick(value) {
  emit('update:modelValue', value === '' ? '' : Number(value) || value);
  close();
}

function isSelected(value) {
  return selectedId.value === String(value);
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center"
      role="dialog"
      aria-modal="true"
      :aria-label="title"
    >
      <button
        type="button"
        class="absolute inset-0 bg-slate-950/70 backdrop-blur-sm"
        aria-label="Fermer"
        @click="close"
      />

      <div
        class="relative z-10 flex max-h-[min(85vh,36rem)] w-full flex-col overflow-hidden rounded-t-3xl border border-slate-700/80 bg-slate-900 shadow-2xl shadow-black/50 sm:mx-4 sm:max-w-md sm:rounded-3xl"
      >
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-800 px-5 pb-3 pt-4">
          <div class="min-w-0">
            <p class="text-[11px] font-semibold uppercase tracking-widest text-slate-500">
              Retour vidéo
            </p>
            <h2 class="mt-0.5 truncate text-base font-semibold text-white">
              {{ title }}
            </h2>
          </div>
          <button
            type="button"
            class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            <UiIcon name="x-mark" class="h-5 w-5" />
          </button>
        </div>

        <div class="tc-scrollbar min-h-0 flex-1 overflow-y-auto px-3 py-3">
          <button
            type="button"
            class="flex w-full items-center gap-3 rounded-2xl border px-3.5 py-3 text-left transition"
            :class="
              isSelected('')
                ? 'border-blue-500/50 bg-blue-600/15 ring-1 ring-blue-500/30'
                : 'border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-800/40'
            "
            @click="pick('')"
          >
            <span
              class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
              :class="
                isSelected('')
                  ? 'border-blue-400 bg-blue-500'
                  : 'border-slate-600 bg-transparent'
              "
            >
              <span v-if="isSelected('')" class="h-2 w-2 rounded-full bg-white" />
            </span>
            <span class="min-w-0">
              <span class="block text-sm font-medium text-white">Aucune série</span>
              <span class="mt-0.5 block text-xs text-slate-500">
                Ne pas rattacher cette vidéo à un exercice
              </span>
            </span>
          </button>

          <ul class="mt-2 space-y-2">
            <li v-for="exercise in exercises" :key="exercise.id">
              <button
                type="button"
                class="flex w-full items-start gap-3 rounded-2xl border px-3.5 py-3 text-left transition"
                :class="
                  isSelected(exercise.id)
                    ? 'border-blue-500/50 bg-blue-600/15 ring-1 ring-blue-500/30'
                    : 'border-slate-800 bg-slate-950/50 hover:border-slate-700 hover:bg-slate-800/40'
                "
                @click="pick(exercise.id)"
              >
                <span
                  class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                  :class="
                    isSelected(exercise.id)
                      ? 'border-blue-400 bg-blue-500'
                      : 'border-slate-600 bg-transparent'
                  "
                >
                  <span
                    v-if="isSelected(exercise.id)"
                    class="h-2 w-2 rounded-full bg-white"
                  />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-sm font-semibold text-white">
                    {{ exercise.exercise_name || exercise.label }}
                  </span>
                  <span class="mt-1 flex flex-wrap items-center gap-1.5">
                    <span
                      v-if="exercise.section_label"
                      class="rounded-md bg-slate-800 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-300"
                    >
                      {{ exercise.section_label }}
                    </span>
                    <span
                      v-if="exercise.summary"
                      class="text-xs text-slate-400"
                    >
                      {{ exercise.summary }}
                    </span>
                  </span>
                </span>
              </button>
            </li>
          </ul>
        </div>

        <div
          class="shrink-0 border-t border-slate-800 px-4 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3"
        >
          <button
            type="button"
            class="w-full rounded-xl border border-slate-700 bg-slate-800/60 px-4 py-2.5 text-sm font-medium text-slate-200 transition hover:bg-slate-800"
            @click="close"
          >
            Annuler
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
