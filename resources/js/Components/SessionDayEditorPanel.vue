<script setup>
import TrainingDayEditor from './TrainingDayEditor.vue';

defineProps({
  title: {
    type: String,
    required: true,
  },
  processing: {
    type: Boolean,
    default: false,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  showDelete: {
    type: Boolean,
    default: false,
  },
  showDateField: {
    type: Boolean,
    default: false,
  },
  showNotes: {
    type: Boolean,
    default: false,
  },
});

const sessionLabel = defineModel('sessionLabel', { type: String, default: '' });
const sessionDate = defineModel('sessionDate', { type: String, default: '' });
const day = defineModel('day', { type: Object, required: true });
const notes = defineModel('notes', { type: String, default: '' });

defineEmits(['save', 'delete', 'close']);

function onSessionLabelInput(event) {
  sessionLabel.value = event.target.value.toUpperCase();
}

function formatErrorMessages(errors) {
  return Object.values(errors)
    .flat()
    .map((message) => {
      if (typeof message === 'string' && message.startsWith('validation.')) {
        return 'Certaines valeurs numériques sont invalides.';
      }
      return message;
    })
    .join(' ');
}
</script>

<template>
  <div class="flex max-h-[min(90vh,52rem)] flex-col">
    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-800 pb-4">
      <div class="min-w-0 flex-1">
        <p class="text-xs font-medium uppercase tracking-wide text-blue-300">Séance</p>
        <h3 class="mt-1 text-lg font-semibold text-white">
          {{ title }}
        </h3>
        <label v-if="showDateField" class="mt-3 block text-sm text-slate-400">
          Date
          <input
            v-model="sessionDate"
            type="date"
            required
            class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <label class="mt-3 block text-sm text-slate-400">
          Nom de la séance
          <input
            :value="sessionLabel"
            type="text"
            maxlength="255"
            placeholder="Ex. Force jambes, Volume bench…"
            class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm uppercase text-white placeholder:text-slate-600"
            @input="onSessionLabelInput"
          />
        </label>
      </div>
      <div class="flex shrink-0 flex-wrap gap-2">
        <button
          v-if="showDelete"
          type="button"
          class="text-sm font-medium text-red-400 hover:text-red-300 disabled:opacity-50"
          :disabled="processing"
          @click="$emit('delete')"
        >
          Supprimer
        </button>
        <button
          type="button"
          class="text-sm font-medium text-blue-400 hover:text-blue-300"
          @click="$emit('close')"
        >
          ← Retour
        </button>
      </div>
    </div>

    <div class="tc-scrollbar mt-4 flex-1 overflow-y-auto pr-1">
      <TrainingDayEditor v-model="day" />
    </div>

    <label v-if="showNotes" class="mt-4 block text-sm text-slate-400">
      Notes (optionnel)
      <textarea
        v-model="notes"
        rows="2"
        class="mt-1.5 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white placeholder:text-slate-600"
        placeholder="Ressenti, consignes, remarques…"
      />
    </label>

    <p v-if="Object.keys(errors).length" class="mt-3 text-sm text-red-400">
      {{ formatErrorMessages(errors) }}
    </p>

    <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-800 pt-4">
      <button
        type="button"
        :disabled="processing"
        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        @click="$emit('save')"
      >
        Enregistrer
      </button>
      <button
        v-if="showDelete"
        type="button"
        :disabled="processing"
        class="rounded-xl border border-red-500/50 px-4 py-2 text-sm font-medium text-red-300 hover:bg-red-950/40 disabled:opacity-50"
        @click="$emit('delete')"
      >
        Supprimer la séance
      </button>
    </div>
  </div>
</template>
