<script setup>
import { computed } from 'vue';
import TrainingDayEditor from './TrainingDayEditor.vue';
import { createSessionItem, emptyExerciseLine, formatLineRecap } from '../utils/programBuilder';

const props = defineProps({
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
  showWarmup: {
    type: Boolean,
    default: false,
  },
  defaultWarmup: {
    type: Object,
    default: () => ({ notes: null, items: [] }),
  },
});

const sessionLabel = defineModel('sessionLabel', { type: String, default: '' });
const sessionDate = defineModel('sessionDate', { type: String, default: '' });
const day = defineModel('day', { type: Object, required: true });
const notes = defineModel('notes', { type: String, default: '' });

defineEmits(['save', 'delete', 'close']);

const warmupOverride = computed({
  get: () => Boolean(day.value?.warmup_override),
  set(value) {
    day.value.warmup_override = Boolean(value);
    if (value) {
      seedWarmupFromDefault();
    } else {
      // Keep inherited items out of the editable list when returning to block default.
      day.value.items = (day.value.items ?? []).filter((item) => item.section !== 'warmup');
      day.value.warmup_notes = '';
    }
  },
});

const inheritedWarmup = computed(() => ({
  notes: props.defaultWarmup?.notes ?? null,
  items: props.defaultWarmup?.items ?? [],
}));

const hasInheritedWarmup = computed(() => {
  return (
    Boolean(String(inheritedWarmup.value.notes ?? '').trim()) ||
    (inheritedWarmup.value.items?.length ?? 0) > 0
  );
});

function seedWarmupFromDefault() {
  if (!day.value.warmup_notes?.trim() && inheritedWarmup.value.notes) {
    day.value.warmup_notes = inheritedWarmup.value.notes;
  }

  const hasWarmupItems = (day.value.items ?? []).some((item) => item.section === 'warmup');
  if (!hasWarmupItems && (inheritedWarmup.value.items?.length ?? 0) > 0) {
    const seeded = inheritedWarmup.value.items.map((row) =>
      createSessionItem('warmup', { ...emptyExerciseLine(''), ...row }),
    );
    day.value.items = [...seeded, ...(day.value.items ?? [])];
  }
}

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
      <div
        v-if="showWarmup"
        class="mb-4 space-y-3 rounded-xl border border-sky-500/25 bg-sky-950/15 px-3 py-3"
      >
        <div class="flex flex-wrap items-center justify-between gap-2">
          <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
            Échauffement
          </p>
          <label class="flex items-center gap-2 text-xs text-slate-300">
            <input
              v-model="warmupOverride"
              type="checkbox"
              class="rounded border-slate-600 bg-slate-950 text-sky-500"
            />
            Personnaliser pour cette séance
          </label>
        </div>

        <template v-if="!warmupOverride">
          <p v-if="hasInheritedWarmup" class="text-xs text-slate-400">
            Hérité du bloc
          </p>
          <p
            v-if="inheritedWarmup.notes?.trim()"
            class="whitespace-pre-wrap text-sm text-slate-200"
          >
            {{ inheritedWarmup.notes }}
          </p>
          <ul v-if="inheritedWarmup.items?.length" class="space-y-1">
            <li
              v-for="(item, index) in inheritedWarmup.items"
              :key="`${item.exercise_name}-${index}`"
              class="text-sm text-slate-300"
            >
              {{ formatLineRecap(item) || item.exercise_name }}
            </li>
          </ul>
          <p v-else-if="!inheritedWarmup.notes?.trim()" class="text-xs text-slate-500">
            Aucun échauffement défini sur le bloc.
          </p>
        </template>

        <template v-else>
          <label class="block text-xs font-medium text-slate-400">
            Instructions
            <textarea
              v-model="day.warmup_notes"
              rows="2"
              maxlength="5000"
              placeholder="Instructions spécifiques à cette séance…"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white placeholder:text-slate-600"
            />
          </label>
          <p class="text-xs text-slate-500">
            Ajoute les exercices d’échauffement avec le bouton ci-dessous.
          </p>
        </template>
      </div>

      <TrainingDayEditor v-model="day" :allow-warmup="showWarmup && warmupOverride" />
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
