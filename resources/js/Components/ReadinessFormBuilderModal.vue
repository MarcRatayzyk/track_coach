<script setup>
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ReadinessDynamicFields from './ReadinessDynamicFields.vue';
import {
  READINESS_FIELD_TYPES,
  READINESS_OPTION_COLORS,
  READINESS_PRESET_CATALOG,
  cloneFields,
  createFieldId,
  defaultReadinessFields,
  emptyCustomField,
  emptySelectOption,
  emptyValuesForFields,
  validateReadinessFieldsDraft,
} from '../config/readinessFormFields';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  /** template | athlete | local */
  mode: {
    type: String,
    default: 'template',
  },
  athleteId: {
    type: [Number, String],
    default: null,
  },
  initialFields: {
    type: Array,
    default: null,
  },
  title: {
    type: String,
    default: 'Formulaire readiness',
  },
});

const emit = defineEmits(['close', 'save-local']);

const fields = ref([]);
const selectedIndex = ref(0);
const clientErrors = ref([]);
const previewValues = ref({});
const showPresetPicker = ref(false);

const form = useForm({
  fields: [],
});

const selectedField = computed(() => fields.value[selectedIndex.value] ?? null);

const usedPresetKeys = computed(() =>
  new Set(fields.value.map((field) => field.preset_key).filter(Boolean)),
);

const availablePresets = computed(() =>
  READINESS_PRESET_CATALOG.filter((preset) => !usedPresetKeys.value.has(preset.key)),
);

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }
    clientErrors.value = [];
    showPresetPicker.value = false;
    fields.value = cloneFields(
      props.initialFields?.length ? props.initialFields : defaultReadinessFields(),
    );
    selectedIndex.value = 0;
    previewValues.value = emptyValuesForFields(fields.value);
  },
);

watch(
  fields,
  (next) => {
    previewValues.value = {
      ...emptyValuesForFields(next),
      ...Object.fromEntries(
        Object.entries(previewValues.value).filter(([id]) => next.some((field) => field.id === id)),
      ),
    };
  },
  { deep: true },
);

function close() {
  emit('close');
}

function selectField(index) {
  selectedIndex.value = index;
}

function moveField(index, delta) {
  const target = index + delta;
  if (target < 0 || target >= fields.value.length) {
    return;
  }
  const copy = [...fields.value];
  const [item] = copy.splice(index, 1);
  copy.splice(target, 0, item);
  fields.value = copy.map((field, sortOrder) => ({ ...field, sort_order: sortOrder }));
  selectedIndex.value = target;
}

function removeField(index) {
  if (fields.value.length <= 1) {
    clientErrors.value = ['Garde au moins un champ.'];
    return;
  }
  fields.value = fields.value
    .filter((_, i) => i !== index)
    .map((field, sortOrder) => ({ ...field, sort_order: sortOrder }));
  selectedIndex.value = Math.min(selectedIndex.value, fields.value.length - 1);
}

function addPreset(preset) {
  const field = {
    id: `preset-${preset.key}-${createFieldId().slice(0, 8)}`,
    preset_key: preset.key,
    label: preset.label,
    type: preset.type,
    required: true,
    sort_order: fields.value.length,
    options: preset.type === 'select' ? (preset.options ?? []).map((opt) => ({ ...opt })) : [],
  };
  fields.value = [...fields.value, field];
  selectedIndex.value = fields.value.length - 1;
  showPresetPicker.value = false;
}

function addCustomField() {
  fields.value = [...fields.value, emptyCustomField(fields.value.length)];
  selectedIndex.value = fields.value.length - 1;
}

function onTypeChange(field, type) {
  field.type = type;
  if (type === 'select' && (!field.options || field.options.length === 0)) {
    field.options = [emptySelectOption()];
  }
  if (type !== 'select') {
    field.options = [];
  }
}

function addOption(field) {
  field.options = [...(field.options ?? []), emptySelectOption()];
}

function removeOption(field, index) {
  field.options = (field.options ?? []).filter((_, i) => i !== index);
}

function payloadFields() {
  return fields.value.map((field, index) => ({
    id: field.id,
    preset_key: field.preset_key,
    label: field.label,
    type: field.type,
    required: field.required !== false,
    sort_order: index,
    options: field.type === 'select'
      ? (field.options ?? []).map((opt) => ({
          value: opt.value || opt.label,
          label: opt.label,
          color: opt.color || '#64748b',
        }))
      : [],
  }));
}

function submit() {
  const draft = payloadFields();
  const errors = validateReadinessFieldsDraft(draft);
  clientErrors.value = errors;
  if (errors.length) {
    return;
  }

  if (props.mode === 'local') {
    emit('save-local', draft);
    emit('close');
    return;
  }

  form.fields = draft;
  const url = props.mode === 'athlete'
    ? `/coach/athletes/${props.athleteId}/readiness-form`
    : '/coach/readiness-form';

  form.put(url, {
    preserveScroll: true,
    onSuccess: () => emit('close'),
  });
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-3 backdrop-blur-sm sm:p-4"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <div
        class="flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
        @click.stop
      >
        <div class="flex items-center justify-between border-b border-slate-800 px-4 py-3 sm:px-6">
          <div>
            <h2 class="text-lg font-bold text-white">{{ title }}</h2>
            <p class="text-xs text-slate-400">Compose le questionnaire — aperçu en temps réel à droite.</p>
          </div>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="close"
          >
            Fermer
          </button>
        </div>

        <div class="grid min-h-0 flex-1 gap-0 overflow-hidden lg:grid-cols-2">
          <div class="min-h-0 overflow-y-auto border-b border-slate-800 p-4 lg:border-b-0 lg:border-r sm:p-5">
            <div class="mb-3 flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg border border-slate-600 bg-slate-800 px-3 py-1.5 text-xs font-semibold text-slate-100 hover:bg-slate-700"
                @click="showPresetPicker = !showPresetPicker"
              >
                + Facteur catalogue
              </button>
              <button
                type="button"
                class="rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/20"
                @click="addCustomField"
              >
                + Champ custom
              </button>
            </div>

            <div
              v-if="showPresetPicker"
              class="mb-3 rounded-xl border border-slate-700 bg-slate-950/80 p-2"
            >
              <p v-if="!availablePresets.length" class="px-2 py-1 text-xs text-slate-500">
                Tous les facteurs du catalogue sont déjà ajoutés.
              </p>
              <button
                v-for="preset in availablePresets"
                :key="preset.key"
                type="button"
                class="mb-1 block w-full rounded-lg px-3 py-2 text-left text-sm text-slate-200 hover:bg-slate-800"
                @click="addPreset(preset)"
              >
                <span class="font-semibold">{{ preset.label }}</span>
                <span class="ml-2 text-xs uppercase text-slate-500">{{ preset.type }}</span>
              </button>
            </div>

            <ul class="space-y-2">
              <li
                v-for="(field, index) in fields"
                :key="field.id"
                class="rounded-xl border p-3"
                :class="index === selectedIndex ? 'border-blue-500 bg-slate-800/80' : 'border-slate-700 bg-slate-950/40'"
              >
                <button
                  type="button"
                  class="flex w-full items-center justify-between gap-2 text-left"
                  @click="selectField(index)"
                >
                  <span class="text-sm font-semibold text-white">{{ field.label }}</span>
                  <span class="text-[10px] uppercase tracking-wide text-slate-500">{{ field.type }}</span>
                </button>
                <div class="mt-2 flex flex-wrap gap-1">
                  <button
                    type="button"
                    class="rounded px-2 py-0.5 text-[11px] text-slate-400 hover:bg-slate-800"
                    :disabled="index === 0"
                    @click="moveField(index, -1)"
                  >
                    ↑
                  </button>
                  <button
                    type="button"
                    class="rounded px-2 py-0.5 text-[11px] text-slate-400 hover:bg-slate-800"
                    :disabled="index === fields.length - 1"
                    @click="moveField(index, 1)"
                  >
                    ↓
                  </button>
                  <button
                    type="button"
                    class="rounded px-2 py-0.5 text-[11px] text-red-400 hover:bg-red-500/10"
                    @click="removeField(index)"
                  >
                    Retirer
                  </button>
                </div>
              </li>
            </ul>

            <div
              v-if="selectedField"
              class="mt-4 space-y-3 rounded-xl border border-slate-700 bg-slate-950/60 p-3"
            >
              <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Édition</h3>
              <label class="block text-xs text-slate-400">
                Libellé
                <input
                  v-model="selectedField.label"
                  type="text"
                  class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white"
                  maxlength="80"
                >
              </label>
              <label class="block text-xs text-slate-400">
                Type de saisie
                <select
                  class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-white"
                  :value="selectedField.type"
                  @change="onTypeChange(selectedField, $event.target.value)"
                >
                  <option
                    v-for="type in READINESS_FIELD_TYPES"
                    :key="type.value"
                    :value="type.value"
                  >
                    {{ type.label }}
                  </option>
                </select>
              </label>
              <label class="flex items-center gap-2 text-xs text-slate-300">
                <input v-model="selectedField.required" type="checkbox" class="rounded border-slate-600">
                Champ requis
              </label>

              <div v-if="selectedField.type === 'select'" class="space-y-2">
                <div class="flex items-center justify-between">
                  <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Options</p>
                  <button
                    type="button"
                    class="text-xs font-semibold text-blue-300 hover:text-blue-200"
                    @click="addOption(selectedField)"
                  >
                    + Option
                  </button>
                </div>
                <div
                  v-for="(option, optIndex) in selectedField.options"
                  :key="optIndex"
                  class="grid grid-cols-[1fr_auto_auto] gap-2"
                >
                  <input
                    v-model="option.label"
                    type="text"
                    class="rounded-lg border border-slate-700 bg-slate-900 px-2 py-1.5 text-sm text-white"
                    placeholder="Libellé"
                    @input="option.value = option.value || option.label"
                  >
                  <select
                    v-model="option.color"
                    class="rounded-lg border border-slate-700 bg-slate-900 px-1 text-xs text-white"
                    :style="{ borderColor: option.color }"
                  >
                    <option
                      v-for="color in READINESS_OPTION_COLORS"
                      :key="color"
                      :value="color"
                    >
                      {{ color }}
                    </option>
                  </select>
                  <button
                    type="button"
                    class="rounded-lg px-2 text-xs text-red-400 hover:bg-red-500/10"
                    @click="removeOption(selectedField, optIndex)"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="min-h-0 overflow-y-auto bg-slate-950/40 p-4 sm:p-5">
            <h3 class="mb-3 text-sm font-semibold text-white">Aperçu athlète</h3>
            <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
              <p class="mb-3 text-xs text-slate-500">Check-in du jour — tel que l’athlète le verra.</p>
              <ReadinessDynamicFields
                v-model="previewValues"
                :fields="fields"
                preview
              />
            </div>
          </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-800 px-4 py-3 sm:px-6">
          <div class="space-y-1">
            <p
              v-for="(error, index) in clientErrors"
              :key="index"
              class="text-xs text-red-400"
            >
              {{ error }}
            </p>
            <p
              v-if="form.errors.fields"
              class="text-xs text-red-400"
            >
              {{ form.errors.fields }}
            </p>
          </div>
          <div class="flex gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300 hover:bg-slate-800"
              @click="close"
            >
              Annuler
            </button>
            <button
              type="button"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              :disabled="form.processing"
              @click="submit"
            >
              {{ mode === 'local' ? 'Utiliser ce formulaire' : 'Enregistrer' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
