<script setup>
import { PROGRAM_TABLE_SECTIONS } from '../config/programTableSections';

const props = defineProps({
  modelValue: {
    type: String,
    default: 'accessory',
  },
  preview: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

function selectSection(value) {
  if (props.preview) {
    return;
  }

  emit('update:modelValue', value);
}
</script>

<template>
  <div class="flex flex-col gap-0.5">
    <button
      v-for="option in PROGRAM_TABLE_SECTIONS"
      :key="option.value"
      type="button"
      class="rounded px-1 py-0.5 text-[9px] font-medium uppercase tracking-wide transition"
      :class="
        modelValue === option.value ? option.buttonActiveClass : option.buttonInactiveClass
      "
      :disabled="preview"
      @click="selectSection(option.value)"
    >
      {{ option.shortLabel }}
    </button>
  </div>
</template>
