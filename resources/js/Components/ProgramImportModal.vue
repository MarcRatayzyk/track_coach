<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { router } from '@inertiajs/vue3';

const { t } = useI18n();

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
const sourceMode = ref('json'); // json only for now (file AI = bientôt)
const loading = ref(false);
const errorMessage = ref('');
const copyFeedback = ref('');

const pastedJson = ref('');
const externalAiPrompt = ref('');

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

function importRequestHeaders(extra = {}) {
  const headers = {
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...extra,
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
    const params = new URLSearchParams();
    if (props.weekCount > 0) {
      params.set('week_count', String(props.weekCount));
    }
    const qs = params.toString();
    const response = await fetch(`/coach/program-blocks/import/meta${qs ? `?${qs}` : ''}`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!response.ok) {
      return;
    }
    const data = await response.json();
    externalAiPrompt.value = data.external_ai_prompt ?? '';
  } catch {
    // ignore
  }
}

function resetState() {
  step.value = 'source';
  sourceMode.value = 'json';
  loading.value = false;
  errorMessage.value = '';
  copyFeedback.value = '';
  pastedJson.value = '';
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

async function copyText(text, label) {
  if (!text) {
    return;
  }
  try {
    await navigator.clipboard.writeText(text);
    copyFeedback.value = t('programBuilder.importModal.copied', { label });
    window.setTimeout(() => {
      if (copyFeedback.value === t('programBuilder.importModal.copied', { label })) {
        copyFeedback.value = '';
      }
    }, 2000);
  } catch {
    errorMessage.value = t('programBuilder.importModal.copyFailed');
  }
}

async function parseJsonResponse(response) {
  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 419) {
      throw new Error(t('programBuilder.importModal.csrfExpired'));
    }
    if (response.status === 504) {
      throw new Error(
        data?.message
        || t('programBuilder.importModal.timeout'),
      );
    }
    const message =
      data?.message
      || data?.errors?.json?.[0]
      || data?.errors?.file?.[0]
      || Object.values(data?.errors ?? {})?.[0]?.[0]
      || t('programBuilder.importModal.importImpossible');
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

async function importPastedJson() {
  const raw = pastedJson.value.trim();
  if (!raw) {
    errorMessage.value = t('programBuilder.importModal.pasteJsonRequired');
    return;
  }

  loading.value = true;
  errorMessage.value = '';

  try {
    const response = await fetch(`/coach/program-blocks/${props.assignmentId}/import/preview-json`, {
      method: 'POST',
      headers: importRequestHeaders({ 'Content-Type': 'application/json' }),
      credentials: 'same-origin',
      body: JSON.stringify({
        json: raw,
        _token: csrfToken() || xsrfTokenFromCookie(),
      }),
    });

    const data = await parseJsonResponse(response);
    applyReadyDraft(data);
  } catch (error) {
    errorMessage.value = error instanceof Error ? error.message : t('programBuilder.importModal.jsonImportFailed');
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
          || t('programBuilder.importModal.applyFailed');
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
            {{ t('programBuilder.importModal.title') }}
          </h2>
          <p class="mt-0.5 text-sm text-slate-400">
            {{ t('programBuilder.importModal.subtitle') }}
            <span v-if="weekCount">{{ t('programBuilder.importModal.weekCount', { count: weekCount }) }}</span>.
          </p>
        </div>
        <button
          type="button"
          class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-800 hover:text-white"
          @click="close"
        >
          {{ t('common.close') }}
        </button>
      </header>

      <div class="overflow-y-auto px-4 py-4 sm:px-5">
        <p v-if="errorMessage" class="mb-3 rounded-lg border border-rose-500/40 bg-rose-500/10 px-3 py-2 text-sm text-rose-200">
          {{ errorMessage }}
        </p>
        <p v-if="copyFeedback" class="mb-3 text-sm text-emerald-300">
          {{ copyFeedback }}
        </p>

        <template v-if="step === 'source'">
          <div class="mb-4 flex gap-2">
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white"
              @click="sourceMode = 'json'; errorMessage = ''"
            >
              {{ t('programBuilder.importModal.jsonTab') }}
            </button>
            <button
              type="button"
              class="cursor-not-allowed rounded-lg bg-slate-800/80 px-3 py-1.5 text-sm font-medium text-slate-500"
              disabled
:title="t('programBuilder.importModal.comingSoonTitle')"
            >
              {{ t('programBuilder.importModal.aiTab') }}
              <span class="ml-1 rounded bg-slate-700 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-slate-300">
                {{ t('programBuilder.importModal.comingSoon') }}
              </span>
            </button>
          </div>

          <template v-if="sourceMode === 'json'">
            <ol class="mb-4 list-decimal space-y-1 pl-5 text-sm text-slate-300">
              <li>{{ t('programBuilder.importModal.step1') }}</li>
              <li>{{ t('programBuilder.importModal.step2') }}</li>
              <li>{{ t('programBuilder.importModal.step3') }}</li>
            </ol>

            <p class="mb-3 text-sm text-slate-400">
              {{ t('programBuilder.importModal.adapts') }}
            </p>

            <div class="mb-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
                :disabled="!externalAiPrompt"
                @click="copyText(externalAiPrompt, 'Prompt')"
              >
                {{ t('programBuilder.importModal.copyPrompt') }}
              </button>
            </div>

            <label class="mb-1 block text-sm font-medium text-slate-300" for="program-import-json">
              {{ t('programBuilder.importModal.jsonFilled') }}
            </label>
            <textarea
              id="program-import-json"
              v-model="pastedJson"
              rows="12"
              class="w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 font-mono text-xs text-slate-200 placeholder:text-slate-600 focus:border-blue-500 focus:outline-none"
              placeholder='{ "format": "track_coach_program_v1", "weeks": [ ... ] }'
              @input="errorMessage = ''"
            />
          </template>
        </template>

        <template v-else>
          <div class="mb-3 flex flex-wrap gap-3 text-sm text-slate-300">
<span class="rounded-md bg-slate-800 px-2 py-1">{{ t('programBuilder.importModal.sessionsBadge', { count: sessionCount }) }}</span>
<span class="rounded-md bg-slate-800 px-2 py-1">{{ t('programBuilder.importModal.exercisesBadge', { count: exerciseCount }) }}</span>
            <span
              v-if="exercisesToCreate.length"
              class="rounded-md bg-emerald-500/15 px-2 py-1 text-emerald-200"
            >
{{ t('programBuilder.importModal.toCreate', { count: exercisesToCreate.length }) }}
            </span>
          </div>

          <div
            v-if="warnings.length"
            class="mb-3 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-100"
          >
<p class="font-medium">{{ t('programBuilder.importModal.warnings') }}</p>
            <ul class="mt-1 list-disc space-y-0.5 pl-4 text-amber-100/90">
              <li v-for="(warning, idx) in warnings" :key="idx">{{ warning }}</li>
            </ul>
          </div>

          <div
            v-if="exercisesToCreate.length"
            class="mb-3 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-100"
          >
<p class="font-medium">{{ t('programBuilder.importModal.newExercises') }}</p>
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
                {{ t('programBuilder.importModal.weekDay', { week: operation.week_number, day: operation.weekday }) }}
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
            {{ t('programBuilder.importModal.noSessions') }}
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
          {{ t('common.back') }}
        </button>
        <button
          type="button"
          class="rounded-xl border border-slate-600 px-3 py-2 text-sm text-slate-200 hover:bg-slate-800"
          :disabled="loading"
          @click="close"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          v-if="step === 'source'"
          type="button"
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="loading || !pastedJson.trim()"
          @click="importPastedJson"
        >
{{ loading ? t('programBuilder.importModal.readingJson') : t('programBuilder.importModal.importJson') }}
        </button>
        <button
          v-else
          type="button"
          class="rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
          :disabled="!canConfirm"
          @click="confirmImport"
        >
{{ loading ? t('programBuilder.importModal.importing') : t('programBuilder.importModal.importIntoBlock') }}
        </button>
      </footer>
    </div>
  </div>
</template>
