<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);

const form = useForm({
  title: '',
  category: 'bug',
  severity: 'medium',
  description: '',
  page_url: '',
  screenshot: null,
});

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      form.reset();
      form.clearErrors();
      form.category = 'bug';
      form.severity = 'medium';
      form.page_url = typeof window !== 'undefined' ? window.location.href : '';
      form.screenshot = null;
      if (fileInput.value) {
        fileInput.value.value = '';
      }
    }
  },
);

function closeModal() {
  emit('update:modelValue', false);
}

function onScreenshotChange(event) {
  const file = event.target.files?.[0] ?? null;
  form.screenshot = file;
}

function submit() {
  form.post('/support/bug-report', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModal();
    },
  });
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="bug-report-title"
      @click.self="closeModal"
    >
      <div
        class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 id="bug-report-title" class="text-base font-semibold text-white">
              Signaler un problème
            </h2>
            <p class="mt-1 text-sm text-slate-400">
              Bug, correctif ou idée — on lit chaque signalement.
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <form class="mt-5 space-y-4" @submit.prevent="submit">
          <label class="block text-sm font-medium text-slate-400">
            Titre
            <input
              v-model="form.title"
              type="text"
              required
              maxlength="160"
              class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              placeholder="Ex. Impossible d’enregistrer un retour"
            >
            <p v-if="form.errors.title" class="mt-1 text-sm text-red-400">{{ form.errors.title }}</p>
          </label>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label class="block text-sm font-medium text-slate-400">
              Catégorie
              <select
                v-model="form.category"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
                <option value="bug">Bug</option>
                <option value="fix">Correctif</option>
                <option value="idea">Idée</option>
                <option value="other">Autre</option>
              </select>
              <p v-if="form.errors.category" class="mt-1 text-sm text-red-400">{{ form.errors.category }}</p>
            </label>

            <label class="block text-sm font-medium text-slate-400">
              Sévérité
              <select
                v-model="form.severity"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
                <option value="low">Faible</option>
                <option value="medium">Moyenne</option>
                <option value="high">Haute</option>
              </select>
              <p v-if="form.errors.severity" class="mt-1 text-sm text-red-400">{{ form.errors.severity }}</p>
            </label>
          </div>

          <label class="block text-sm font-medium text-slate-400">
            Description
            <textarea
              v-model="form.description"
              required
              rows="5"
              maxlength="5000"
              class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              placeholder="Que s’est-il passé ? Sur quelle page ? Étapes pour reproduire…"
            />
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-400">{{ form.errors.description }}</p>
          </label>

          <label class="block text-sm font-medium text-slate-400">
            Capture d’écran
            <span class="font-normal text-slate-500">(optionnel, max 4 Mo)</span>
            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/png,image/webp"
              class="mt-2 block w-full text-sm text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600/20 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-blue-200 hover:file:bg-blue-600/30"
              @change="onScreenshotChange"
            >
            <p v-if="form.errors.screenshot" class="mt-1 text-sm text-red-400">{{ form.errors.screenshot }}</p>
          </label>

          <div class="flex flex-wrap gap-3 pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {{ form.processing ? 'Envoi…' : 'Envoyer' }}
            </button>
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
              @click="closeModal"
            >
              Annuler
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
