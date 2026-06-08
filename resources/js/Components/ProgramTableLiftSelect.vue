<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    default: 'squat',
  },
});

const emit = defineEmits(['update:modelValue']);

const options = [
  { value: 'squat', label: 'Squat' },
  { value: 'bench', label: 'Bench' },
  { value: 'deadlift', label: 'Deadlift' },
];

function onChange(event) {
  emit('update:modelValue', event.target.value);
}
</script>

<template>
  <select
    :value="modelValue"
    class="w-full border-0 bg-transparent px-1 py-1 text-center text-xs uppercase text-white outline-none"
    @change="onChange"
  >
    <option v-for="option in options" :key="option.value" :value="option.value">
      {{ option.label }}
    </option>
  </select>
</template>
