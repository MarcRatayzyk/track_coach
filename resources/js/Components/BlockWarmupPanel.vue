<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { emptyExerciseLine } from '../utils/programBuilder';

const props = defineProps({
  activeBlock: {
    type: Object,
    required: true,
  },
  builderTab: {
    type: String,
    default: 'calendar',
  },
});

const emit = defineEmits(['saved']);

const open = ref(false);

const form = useForm({
  default_warmup_notes: '',
  default_warmup_items: [],
  builder_tab: props.builderTab,
});

function syncFromBlock() {
  form.default_warmup_notes = props.activeBlock?.default_warmup_notes ?? '';
  form.default_warmup_items = (props.activeBlock?.default_warmup_items ?? []).map((item) => ({
    ...emptyExerciseLine(''),
    ...item,
  }));
  form.builder_tab = props.builderTab;
  form.clearErrors();
}

watch(
  () => [props.activeBlock?.id, props.activeBlock?.default_warmup_notes, props.activeBlock?.default_warmup_items],
  () => syncFromBlock(),
  { immediate: true, deep: true },
);

watch(
  () => props.builderTab,
  (tab) => {
    form.builder_tab = tab;
  },
);

const hasContent = computed(() => {
  return (
    Boolean(String(form.default_warmup_notes ?? '').trim()) ||
    form.default_warmup_items.some((item) => String(item.exercise_name ?? '').trim())
  );
});

function addItem() {
  form.default_warmup_items.push({
    ...emptyExerciseLine(''),
    sets: 1,
    reps: 10,
  });
}

function removeItem(index) {
  form.default_warmup_items.splice(index, 1);
}

function save() {
  form
    .transform((data) => ({
      ...data,
      default_warmup_items: (data.default_warmup_items ?? [])
        .filter((item) => String(item.exercise_name ?? '').trim())
        .map((item) => ({
          exercise_variant_id: item.exercise_variant_id || null,
          exercise_name: String(item.exercise_name).trim(),
          lift: item.lift || null,
          sets: item.sets != null && item.sets !== '' ? Number(item.sets) : null,
          reps: item.reps != null && item.reps !== '' ? Number(item.reps) : null,
          load: item.load != null && item.load !== '' ? Number(item.load) : null,
          load_percent:
            item.load_percent != null && item.load_percent !== ''
              ? Number(item.load_percent)
              : null,
          rpe: item.rpe != null && item.rpe !== '' ? Number(item.rpe) : null,
          rest_seconds:
            item.rest_seconds != null && item.rest_seconds !== ''
              ? Number(item.rest_seconds)
              : null,
        })),
    }))
    .put(`/coach/program-blocks/${props.activeBlock.id}/warmup`, {
      preserveScroll: true,
      onSuccess: () => emit('saved'),
    });
}

const inputClass =
  'mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white placeholder:text-slate-600';
</script>

<template>
  <section class="rounded-xl border border-sky-500/25 bg-sky-950/15">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
      @click="open = !open"
    >
      <div>
        <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
          Échauffement du bloc
        </p>
        <p class="mt-0.5 text-sm text-slate-300">
          {{
            hasContent
              ? 'Appliqué à toutes les séances (sauf surcharge)'
              : 'Aucun échauffement défini — optionnel'
          }}
        </p>
      </div>
      <span class="text-slate-500">{{ open ? '▾' : '▸' }}</span>
    </button>

    <div v-if="open" class="space-y-4 border-t border-sky-500/20 px-4 py-4">
      <label class="block text-xs font-medium text-slate-400">
        Instructions (texte libre)
        <textarea
          v-model="form.default_warmup_notes"
          rows="3"
          maxlength="5000"
          placeholder="Ex. 5 min vélo, mobilité hanches, activations…"
          :class="inputClass"
        />
      </label>

      <div class="space-y-2">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-medium text-slate-400">Exercices d’échauffement</p>
          <button
            type="button"
            class="rounded-lg border border-slate-700 px-2.5 py-1 text-xs font-semibold text-slate-200 hover:border-slate-500"
            @click="addItem"
          >
            Ajouter
          </button>
        </div>

        <div
          v-for="(item, index) in form.default_warmup_items"
          :key="index"
          class="grid gap-2 rounded-lg border border-slate-800 bg-slate-950/50 p-3 sm:grid-cols-[1fr_4.5rem_4.5rem_auto]"
        >
          <label class="block text-xs text-slate-500">
            Exercice
            <input
              v-model="item.exercise_name"
              type="text"
              placeholder="Ex. Hip flexor stretch"
              :class="inputClass"
            />
          </label>
          <label class="block text-xs text-slate-500">
            Séries
            <input
              v-model.number="item.sets"
              type="number"
              min="1"
              max="10"
              :class="inputClass"
            />
          </label>
          <label class="block text-xs text-slate-500">
            Reps
            <input
              v-model.number="item.reps"
              type="number"
              min="1"
              max="20"
              :class="inputClass"
            />
          </label>
          <div class="flex items-end">
            <button
              type="button"
              class="rounded-lg border border-red-500/40 px-2.5 py-2 text-xs text-red-300 hover:bg-red-950/40"
              @click="removeItem(index)"
            >
              Retirer
            </button>
          </div>
        </div>

        <p v-if="!form.default_warmup_items.length" class="text-xs text-slate-500">
          Aucun exercice — le texte seul suffit si tu veux.
        </p>
      </div>

      <p v-if="Object.keys(form.errors).length" class="text-sm text-red-400">
        {{ Object.values(form.errors).flat().join(' ') }}
      </p>

      <button
        type="button"
        class="rounded-lg bg-sky-600 px-3 py-2 text-xs font-semibold text-white hover:bg-sky-500 disabled:opacity-50"
        :disabled="form.processing"
        @click="save"
      >
        {{ form.processing ? 'Enregistrement…' : 'Enregistrer l’échauffement du bloc' }}
      </button>
    </div>
  </section>
</template>
