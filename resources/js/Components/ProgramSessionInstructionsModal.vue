<script setup>
import { ref, watch } from 'vue';
import { uppercaseSessionLabel } from '../utils/programBuilder';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Instructions de séance',
  },
  sessionLabel: {
    type: String,
    default: '',
  },
  sessionNotes: {
    type: String,
    default: '',
  },
  defaultSessionLabel: {
    type: String,
    default: '',
  },
  processing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['confirm', 'cancel']);

const draftLabel = ref('');
const draftNotes = ref('');

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      draftLabel.value = uppercaseSessionLabel(props.sessionLabel);
      draftNotes.value = props.sessionNotes;
    }
  },
);

function onLabelInput(event) {
  draftLabel.value = event.target.value.toUpperCase();
}

function onConfirm() {
  emit('confirm', {
    sessionLabel: uppercaseSessionLabel(draftLabel.value),
    sessionNotes: draftNotes.value,
  });
}

function onCancel() {
  emit('cancel');
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="onCancel"
    >
      <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
          <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="onCancel"
          >
            ✕
          </button>
        </div>

        <label class="mt-4 block text-xs text-slate-500">
          Titre de la séance
          <input
            :value="draftLabel"
            type="text"
            maxlength="255"
            :placeholder="defaultSessionLabel"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm uppercase text-white outline-none placeholder:text-slate-600"
            @input="onLabelInput"
          />
        </label>

        <label class="mt-4 block text-xs text-slate-500">
          Notes
          <textarea
            v-model="draftNotes"
            rows="4"
            maxlength="2000"
            placeholder="Consignes, remarques…"
            class="mt-1 w-full resize-y rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none placeholder:text-slate-600"
          />
        </label>

        <div class="mt-5 flex justify-end gap-2">
          <button
            type="button"
            class="rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800"
            :disabled="processing"
            @click="onCancel"
          >
            Annuler
          </button>
          <button
            type="button"
            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            :disabled="processing"
            @click="onConfirm"
          >
            {{ processing ? 'Enregistrement…' : 'Enregistrer' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
