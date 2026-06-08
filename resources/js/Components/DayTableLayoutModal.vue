<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ProgramTableDynamicCell from './ProgramTableDynamicCell.vue';
import {
  EXERCISE_MODE_OPTIONS,
  OPTIONAL_COLUMN_OPTIONS,
  emptyPreviewRow,
  normalizeTableLayout,
  resolveVisibleColumns,
  validateTableLayoutDraft,
} from '../config/dayTableColumns';
import { sectionRowClass } from '../config/programTableSections';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  layouts: {
    type: Array,
    default: () => [],
  },
  defaultLayoutId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close']);

const selectedLayoutId = ref(null);
const isCreating = ref(false);
const clientErrors = ref([]);

const form = useForm({
  name: '',
  columns: ['section', 'sets', 'reps', 'load'],
  exercise_mode: 'name',
  load_mode: 'kg',
  is_default: false,
});

const previewRow = ref(emptyPreviewRow());

const visibleColumns = computed(() => resolveVisibleColumns(form.data()));

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    clientErrors.value = [];
    const defaultLayout =
      props.layouts.find((layout) => layout.id === props.defaultLayoutId) ??
      props.layouts.find((layout) => layout.is_default) ??
      props.layouts[0] ??
      null;

    if (defaultLayout) {
      loadLayout(defaultLayout);
      isCreating.value = false;
    } else {
      startNewLayout();
    }
  },
);

function loadLayout(layout) {
  selectedLayoutId.value = layout.id;
  isCreating.value = false;
  form.defaults({
    name: layout.name,
    columns: [...(layout.columns ?? [])],
    exercise_mode: layout.exercise_mode ?? 'name',
    load_mode: layout.load_mode ?? 'kg',
    is_default: Boolean(layout.is_default),
  });
  form.reset();
  previewRow.value = emptyPreviewRow(form.load_mode);
}

function startNewLayout() {
  selectedLayoutId.value = null;
  isCreating.value = true;
  form.defaults({
    name: '',
    columns: ['section', 'sets', 'reps', 'load'],
    exercise_mode: 'name',
    load_mode: 'kg',
    is_default: props.layouts.length === 0,
  });
  form.reset();
  previewRow.value = emptyPreviewRow(form.load_mode);
}

function toggleColumn(columnId) {
  if (form.columns.includes(columnId)) {
    form.columns = form.columns.filter((column) => column !== columnId);
    return;
  }

  form.columns = [...form.columns, columnId];
}

function closeModal() {
  emit('close');
}

function updatePreviewRow(row) {
  previewRow.value = row;
}

function saveLayout() {
  clientErrors.value = validateTableLayoutDraft(form.data());

  if (clientErrors.value.length) {
    return;
  }

  const payload = normalizeTableLayout(form.data());
  form.name = form.name.trim();
  form.columns = payload.columns;
  form.exercise_mode = payload.exercise_mode;
  form.load_mode = payload.load_mode;

  if (selectedLayoutId.value && !isCreating.value) {
    form.put(`/coach/day-table-layouts/${selectedLayoutId.value}`, {
      preserveScroll: true,
      onSuccess: closeModal,
    });

    return;
  }

  form.post('/coach/day-table-layouts', {
    preserveScroll: true,
    onSuccess: closeModal,
  });
}

function deleteLayout() {
  if (!selectedLayoutId.value || isCreating.value) {
    return;
  }

  if (!window.confirm(`Supprimer le tableau « ${form.name} » ?`)) {
    return;
  }

  router.delete(`/coach/day-table-layouts/${selectedLayoutId.value}`, {
    preserveScroll: true,
    onSuccess: closeModal,
  });
}

const canDelete = computed(
  () => Boolean(selectedLayoutId.value && !isCreating.value && props.layouts.length > 1),
);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="closeModal"
    >
      <div
        class="flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
      >
        <div class="flex items-start justify-between gap-3 border-b border-slate-800 px-5 py-4">
          <div>
            <h3 class="text-lg font-semibold text-white">Mon tableau jour</h3>
            <p class="mt-1 text-sm text-slate-400">
              Choisis les colonnes visibles dans le tableur et prévisualise le résultat.
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-5 py-3">
          <button
            v-for="layout in layouts"
            :key="layout.id"
            type="button"
            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
            :class="
              selectedLayoutId === layout.id && !isCreating
                ? 'border-blue-500 bg-blue-600/20 text-blue-200'
                : 'border-slate-700 text-slate-300 hover:bg-slate-800'
            "
            @click="loadLayout(layout)"
          >
            {{ layout.name }}
            <span v-if="layout.is_default" class="ml-1 text-[10px] text-slate-500">(défaut)</span>
          </button>
          <button
            type="button"
            class="rounded-lg border border-dashed border-slate-600 px-3 py-1.5 text-xs font-medium text-slate-300 hover:border-slate-500 hover:text-white"
            @click="startNewLayout"
          >
            + Nouveau
          </button>
        </div>

        <div class="grid flex-1 gap-6 overflow-y-auto px-5 py-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
          <div class="space-y-5">
            <label class="block text-sm text-slate-400">
              Nom du tableau
              <input
                v-model="form.name"
                type="text"
                maxlength="255"
                placeholder="Ex. Mon tableur PL"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
              />
            </label>

            <fieldset class="space-y-3">
              <legend class="text-sm font-medium text-white">Exercice</legend>
              <label
                v-for="option in EXERCISE_MODE_OPTIONS"
                :key="option.value"
                class="flex cursor-pointer items-center gap-2 text-sm text-slate-300"
              >
                <input
                  v-model="form.exercise_mode"
                  type="radio"
                  :value="option.value"
                  class="accent-blue-500"
                />
                {{ option.label }}
              </label>
            </fieldset>

            <fieldset class="space-y-3">
              <legend class="text-sm font-medium text-white">Colonnes</legend>
              <label
                v-for="option in OPTIONAL_COLUMN_OPTIONS"
                :key="option.id"
                class="flex cursor-pointer items-center gap-2 text-sm text-slate-300"
              >
                <input
                  type="checkbox"
                  class="accent-blue-500"
                  :checked="form.columns.includes(option.id)"
                  @change="toggleColumn(option.id)"
                />
                {{ option.label }}
              </label>
            </fieldset>

            <label class="flex items-center gap-2 text-sm text-slate-300">
              <input v-model="form.is_default" type="checkbox" class="accent-blue-500" />
              Utiliser par défaut à la création de bloc
            </label>
          </div>

          <div class="space-y-3">
            <p class="text-sm font-medium text-white">Aperçu live</p>
            <div class="overflow-x-auto rounded-xl border border-slate-700 bg-slate-950">
              <table class="w-full table-auto border-collapse">
                <thead class="bg-slate-950">
                  <tr class="text-center text-[11px] font-medium uppercase tracking-wide text-slate-300">
                    <th
                      v-for="column in visibleColumns"
                      :key="column.id"
                      class="border-b border-r border-slate-700 px-2 py-1.5 last:border-r-0"
                      :class="[
                        column.widthClass,
                        column.align === 'left' ? 'text-left' : 'text-center',
                      ]"
                    >
                      {{ column.label }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="align-top transition-colors" :class="sectionRowClass(previewRow.section)">
                    <td
                      v-for="(column, index) in visibleColumns"
                      :key="`${column.id}-preview`"
                      class="border-b border-r border-slate-800 px-1 py-1 last:border-r-0"
                    >
                      <ProgramTableDynamicCell
                        :column-id="column.id"
                        :row="previewRow"
                        default-lift="squat"
                        :preview="!['load', 'section'].includes(column.id)"
                        @update="updatePreviewRow"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p class="text-xs text-slate-500">
              L’aperçu montre une ligne fictive. La colonne Charge permet de choisir kg, % ou RPE par exercice.
            </p>
          </div>
        </div>

        <div class="border-t border-slate-800 px-5 py-4">
          <p v-if="clientErrors.length" class="mb-3 text-sm text-red-400">
            {{ clientErrors.join(' ') }}
          </p>
          <p v-if="Object.keys(form.errors).length" class="mb-3 text-sm text-red-400">
            {{ Object.values(form.errors).flat().join(' ') }}
          </p>

          <div class="flex flex-wrap justify-between gap-2">
            <button
              v-if="canDelete"
              type="button"
              class="rounded-md border border-red-900 px-3 py-2 text-sm text-red-300 hover:bg-red-950/40"
              @click="deleteLayout"
            >
              Supprimer
            </button>
            <div v-else />

            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800"
                @click="closeModal"
              >
                Annuler
              </button>
              <button
                type="button"
                class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                :disabled="form.processing"
                @click="saveLayout"
              >
                {{ form.processing ? 'Enregistrement…' : 'Enregistrer' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
