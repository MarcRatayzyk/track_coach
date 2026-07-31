<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  assignmentId: {
    type: Number,
    required: true,
  },
  builderTab: {
    type: String,
    default: 'table_v2',
  },
  weekCount: {
    type: Number,
    default: 0,
  },
});

const emit = defineEmits(['close', 'imported']);

const step = ref('source'); // source | review
const loading = ref(false);
const errorMessage = ref('');
const aiEnabled = ref(false);

const selectedFile = ref(null);
const fileInputKey = ref(0);

const operations = ref([]);
const warnings = ref([]);
const unmatchedExercises = ref([]);
const exercisesToCreate = ref([]);
const sessionCount = ref(0);
const exerciseCount = ref(0);

const canConfirm = computed(() => operations.value.length > 0 && !loading.value);

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function xsrfTokenFromCookie() {
  const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
  if (!match?.[1]) {
    return '';
  }

  try {
    return decodeURIComponent(match[1]);
  } catch {
    return match[1];
  }
}

function importRequestHeaders() {
  const headers = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  const metaToken = csrfToken();
  if (metaToken) {
    headers['X-CSRF-TOKEN'] = metaToken;
  }

  const xsrf = xsrfTokenFromCookie();
  if (xsrf) {
    headers['X-XSRF-TOKEN'] = xsrf;
  }

  return headers;
}

async function loadMeta() {
  try {
    const response = await fetch('/coach/program-blocks/import/meta', {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!response.ok) {
      return;
    }
    const data = await response.json();
    aiEnabled.value = Boolean(data.ai_enabled ?? data.vision_enabled);
  } catch {
    // ignore
  }
}

function resetState() {
  step.value = 'source';
  loading.value = false;
  errorMessage.value = '';
  selectedFile.value = null;
  fileInputKey.value += 1;
  operations.value = [];
  warnings.value = [];
  unmatchedExercises.value = [];
  exercisesToCreate.value = [];
  sessionCount.value = 0;
  exerciseCount.value = 0;
}

watch(
  () => props.open,
  (open) => {
    if (open) {
      resetState();
      loadMeta();
    }
  },
);

function close() {
  emit('close');
}

function onFileChange(event) {
  selectedFile.value = event.target.files?.[0] ?? null;
  errorMessage.value = '';
}

async function parseJsonResponse(response) {
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 419) {
      throw new Error('Session expirée (CSRF). Recharge la page puis réessaie.');
    }
    if (response.status === 504) {
      throw new Error(
        data?.message
        || 'Analyse trop longue. Réessaie, ou utilise un CSV/XLSX plutôt qu’une grosse capture d’écran.',
      );
    }
    const message =
      data?.message
      || data?.errors?.file?.[0]
      || Object.values(data?.errors ?? {})?.[0]?.[0]
      || 'Import impossible.';
    throw new Error(message);
  }
  return data;
}

function applyReadyDraft(data) {
  operations.value = data.operations ?? [];
  warnings.value = data.warnings ?? [];
  unmatchedExercises.value = data.unmatched_exercises ?? [];
  exercisesToCreate.value = data.exercises_to_create ?? [];
  sessionCount.value = data.session_count ?? operations.value.length;
  exerciseCount.value = data.exercise_count ?? 0;
  step.value = 'review';
}

async function analyze() {
  if (!aiEnabled.value) {
    errorMessage.value = 'Configurez PROGRAM_IMPORT_OPENAI_API_KEY dans .env pour l’analyse IA.';
    return;
  }
  if (!selectedFile.value) {
    errorMessage.value = 'Choisissez un fichier (CSV, XLSX, photo ou PDF).';
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  try {
    const body = new FormData();
    body.append('file', selectedFile.value);
    const token = csrfToken() || xsrfTokenFromCookie();
    if (token) {
      body.append('_token', token);
    }

    const response = await fetch(`/coach/program-blocks/${props.assignmentId}/import/preview`, {
      method: 'POST',
      headers: importRequestHeaders(),
      credentials: 'same-origin',
      body,
    });

    const data = await parseJsonResponse(response);
    applyReadyDraft(data);
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : 'Analyse impossible.';
  } finally {
    loading.value = false;
  }
}

function confirmImport() {
  if (!canConfirm.value) {
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  router.post(
    `/coach/program-blocks/${props.assignmentId}/import/apply`,
    {
      operations: operations.value,
      exercises_to_create: exercisesToCreate.value,
      builder_tab: props.builderTab,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        emit('imported');
        emit('close');
        router.reload({ only: ['exerciseLibrary', 'activeBlock'], preserveScroll: true });
      },
      onError: (errors) => {
        errorMessage.value =
          errors?.operations
          || Object.values(errors ?? {})[0]
          || 'Impossible d’appliquer l’import.';
      },
      onFinish: () => {
        loading.value = false;
      },
    },
  );
}

function itemSummary(operation) {
  return (operation.items ?? [])
    .map((item) => {
      const createTag = item.will_create ? ' ✚' : '';
      const load =
        item.load_percent != null && item.load_percent !== ''
          ? `${item.load_percent}%`
          : item.load != null && item.load !== ''
            ? `${item.load} kg`
            : '';
      const rpe = item.rpe != null && item.rpe !== '' ? `@${item.rpe}` : '';
      return `${item.exercise_name}${createTag} ${item.sets}×${item.reps}${load ? ` ${load}` : ''}${rpe ? ` ${rpe}` : ''}`.trim();
    })
    .join(' · ');
}
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/70 p-0 sm:items-center sm:p-4"
    @click.self="close"
  >
    <div
      class="flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-t-2xl border border-slate-700 bg-slate-900 shadow-xl sm:rounded-2xl"
      role="dialog"
      aria-modal="true"
      aria-labelledby="program-import-title"
    >
      <header class="flex items-start justify-between gap-3 border-b border-slate-800 px-4 py-3 sm:px-5">
        <div>
          <h2 id="program-import-title" class="text-base font-semibold text-white">
            Importer un programme
          </h2>
          <p class="mt-0.5 text-sm text-slate-400">
            Analyse IA (CSV, Excel, photo ou PDF) — revue avant écriture
            <span v-if="weekCount">(bloc {{ weekCount }} sem.)</span>.
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-800 hover:text-white"
          @click="close"
        >
          Fermer
        </button>
      </header>

      <div class="overflow-y-auto px-4 py-4 sm:px-5">
        <p v-if="errorMessage" class="mb-3 rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
          {{ errorMessage }}
        </p>

        <template v-if="step === 'source'">
          <p v-if="!aiEnabled" class="mb-3 rounded-lg border border-amber-500/40 bg-amber-500/10 px-3 py-2 text-sm text-amber-100">
            Clé OpenAI manquante : ajoutez <code class="text-amber-50">PROGRAM_IMPORT_OPENAI_API_KEY</code> dans `.env`.
          </p>

          <p class="mb-3 text-sm text-slate-300">
            Formats : <strong class="font-medium text-slate-100">PDF</strong> (recommandé), CSV, Excel ou photo.
            Exporte ton programme Excel en PDF puis importe-le — l’IA lit le document page par page.
            Les exercices inconnus seront ajoutés à la banque (mouvement parent + variante).
          </p>

          <input
            :key="fileInputKey"
            type="file"
            class="block w-full text-sm text-slate-300 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-700 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-slate-600"
            accept=".csv,.txt,.xlsx,.pdf,image/*,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/pdf"
            @change="onFileChange"
          >
        </template>

        <template v-else>
          <div class="mb-3 flex flex-wrap gap-3 text-sm text-slate-300">
            <span class="rounded-md bg-slate-800 px-2 py-1">{{ sessionCount }} séance(s)</span>
            <span class="rounded-md bg-slate-800 px-2 py-1">{{ exerciseCount }} exercice(s)</span>
            <span
              v-if="exercisesToCreate.length"
              class="rounded-md bg-emerald-500/15 px-2 py-1 text-emerald-200"
            >
              {{ exercisesToCreate.length }} à créer dans la banque
            </span>
          </div>

          <div
            v-if="warnings.length"
            class="mb-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-100"
          >
            <p class="font-medium">Avertissements</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-amber-100/90">
              <li v-for="(warning, idx) in warnings" :key="idx">{{ warning }}</li>
            </ul>
          </div>

          <div
            v-if="exercisesToCreate.length"
            class="mb-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-100"
          >
            <p class="font-medium">Nouveaux exercices (parent + variante)</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4">
              <li v-for="(exo, idx) in exercisesToCreate" :key="idx">
                {{ exo.parent_name }} → {{ exo.variant_name }}
                <span class="text-emerald-200/70">({{ exo.lift }})</span>
              </li>
            </ul>
          </div>

          <div class="space-y-2">
            <div
              v-for="operation in operations"
              :key="`${operation.week_number}-${operation.weekday}`"
              class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2"
            >
              <p class="text-sm font-semibold text-white">
                S{{ operation.week_number }} · Jour {{ operation.weekday }}
                <span v-if="operation.session_label" class="font-normal text-slate-400">
                  — {{ operation.session_label }}
                </span>
              </p>
              <p class="mt-1 text-xs leading-relaxed text-slate-400">
                {{ itemSummary(operation) }}
              </p>
            </div>
          </div>

          <p v-if="!operations.length" class="text-sm text-slate-400">
            Aucune séance détectée. Réessayez avec un autre fichier ou une photo plus nette.
          </p>
        </template>
      </div>

      <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-800 px-4 py-3 sm:px-5">
        <button
          v-if="step === 'review'"
          type="button"
          class="rounded-xl border border-slate-600 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
          :disabled="loading"
          @click="step = 'source'"
        >
          Retour
        </button>
        <button
          type="button"
          class="rounded-xl border border-slate-600 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
          :disabled="loading"
          @click="close"
        >
          Annuler
        </button>
        <button
          v-if="step === 'source'"
          type="button"
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="loading || !selectedFile || !aiEnabled"
          @click="analyze"
        >
          {{ loading ? 'Analyse IA en cours (1–3 min)…' : 'Analyser avec l’IA' }}
        </button>
        <button
          v-else
          type="button"
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="!canConfirm"
          @click="confirmImport"
        >
          {{ loading ? 'Import…' : 'Importer dans le bloc' }}
        </button>
      </footer>
    </div>
  </div>
</template>
