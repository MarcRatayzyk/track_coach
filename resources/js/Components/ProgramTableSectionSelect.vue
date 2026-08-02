<script setup>
import { computed } from 'vue';
import ScrollingLabel from './ScrollingLabel.vue';
import {
  PROGRAM_TABLE_SECTIONS,
  sectionWithSchemeLabel,
  schemeShortLabel,
} from '../config/programTableSections';

const props = defineProps({
  modelValue: {
    type: String,
    default: 'accessory',
  },
  setScheme: {
    type: String,
    default: 'standard',
  },
  preview: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const hasSpecialScheme = computed(() => Boolean(schemeShortLabel(props.setScheme)));

function selectSection(value) {
  if (props.preview) {
    return;
  }

  emit('update:modelValue', value);
}

function buttonLabel(option) {
  if (props.modelValue === option.value && hasSpecialScheme.value) {
    return sectionWithSchemeLabel(option.value, props.setScheme);
  }
  return option.shortLabel;
}
</script>

<template>
  <div class="flex min-w-0 flex-col gap-0.5">
    <button
      v-for="option in PROGRAM_TABLE_SECTIONS"
      :key="option.value"
      type="button"
      class="min-w-0 rounded px-1 py-0.5 text-[9px] font-medium tracking-wide transition"
      :class="[
        modelValue === option.value ? option.buttonActiveClass : option.buttonInactiveClass,
        modelValue === option.value && hasSpecialScheme ? 'normal-case' : 'uppercase',
      ]"
      :disabled="preview"
      :title="buttonLabel(option)"
      @click="selectSection(option.value)"
    >
      <ScrollingLabel
        v-if="modelValue === option.value && hasSpecialScheme"
        :text="buttonLabel(option)"
      />
      <template v-else>
        {{ option.shortLabel }}
      </template>
    </button>
  </div>
</template>
