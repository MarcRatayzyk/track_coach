<script setup>
import { computed } from 'vue';
import { resolveOptionColor } from '../config/readinessFormFields';

const props = defineProps({
  fields: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  preview: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const sortedFields = computed(() =>
  [...(props.fields ?? [])].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0)),
);

function valueFor(fieldId) {
  return props.modelValue?.[fieldId] ?? '';
}

function setValue(fieldId, value) {
  emit('update:modelValue', {
    ...props.modelValue,
    [fieldId]: value,
  });
}

function errorFor(fieldId) {
  return props.errors?.[`values.${fieldId}`] ?? props.errors?.[fieldId] ?? null;
}

function isSelected(fieldId, optionValue) {
  return String(valueFor(fieldId) ?? '') === String(optionValue ?? '');
}

function selectOption(fieldId, optionValue) {
  if (props.disabled || props.preview) {
    return;
  }
  setValue(fieldId, isSelected(fieldId, optionValue) ? '' : optionValue);
}

function optionButtonStyle(field, option, selected) {
  const color = option?.color || resolveOptionColor(field, option?.value);
  if (!color) {
    return {};
  }
  if (selected) {
    return {
      backgroundColor: `${color}40`,
      borderColor: color,
      color: '#f8fafc',
    };
  }
  return {
    borderColor: `${color}66`,
    color: '#94a3b8',
  };
}
</script>

<template>
  <div class="space-y-3">
    <div
      v-for="field in sortedFields"
      :key="field.id"
      class="space-y-1.5"
    >
      <div class="flex items-center justify-between gap-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
        <span>{{ field.label }}</span>
        <span v-if="field.required" class="font-normal normal-case text-slate-500">requis</span>
      </div>

      <input
        v-if="field.type === 'number'"
        type="number"
        inputmode="decimal"
        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
        :value="valueFor(field.id)"
        :disabled="disabled || preview"
        :required="field.required && !preview"
        @input="setValue(field.id, $event.target.value === '' ? '' : Number($event.target.value))"
      >

      <input
        v-else-if="field.type === 'text'"
        type="text"
        class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none focus:border-blue-500"
        :value="valueFor(field.id)"
        :disabled="disabled || preview"
        :required="field.required && !preview"
        maxlength="500"
        @input="setValue(field.id, $event.target.value)"
      >

      <div
        v-else
        class="flex flex-wrap gap-1.5"
        role="group"
        :aria-label="field.label"
      >
        <button
          v-for="option in field.options ?? []"
          :key="option.value"
          type="button"
          class="rounded-lg border px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide transition disabled:cursor-not-allowed disabled:opacity-50"
          :class="
            isSelected(field.id, option.value)
              ? 'shadow-sm'
              : 'bg-slate-950/60 hover:bg-slate-900 hover:text-white'
          "
          :style="optionButtonStyle(field, option, isSelected(field.id, option.value))"
          :disabled="disabled || preview"
          :aria-pressed="isSelected(field.id, option.value)"
          @click="selectOption(field.id, option.value)"
        >
          {{ option.label }}
        </button>
      </div>

      <p v-if="errorFor(field.id)" class="text-xs text-red-400">
        {{ errorFor(field.id) }}
      </p>
    </div>
  </div>
</template>
