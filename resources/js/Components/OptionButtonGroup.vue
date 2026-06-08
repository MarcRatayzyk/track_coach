<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Number, String, null],
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  label: {
    type: String,
    default: '',
  },
  columns: {
    type: Number,
    default: 0,
  },
});

const gridStyle = computed(() => {
  if (!props.columns) {
    return undefined;
  }
  return {
    gridTemplateColumns: `repeat(${props.columns}, minmax(2.25rem, 1fr))`,
  };
});

const emit = defineEmits(['update:modelValue']);

function optionValue(opt) {
  return typeof opt === 'object' ? opt.value : opt;
}

function optionLabel(opt) {
  return typeof opt === 'object' ? opt.label : String(opt);
}

function select(value) {
  emit('update:modelValue', props.modelValue === value ? null : value);
}
</script>

<template>
  <div>
    <p v-if="label" class="text-xs font-medium text-slate-400">{{ label }}</p>
    <div
      class="mt-2 gap-1.5"
      :class="columns ? 'grid' : 'flex flex-wrap'"
      :style="gridStyle"
    >
      <button
        v-for="opt in options"
        :key="String(optionValue(opt))"
        type="button"
        class="min-w-[2.25rem] rounded-lg px-2.5 py-1.5 text-xs font-medium transition"
        :class="
          modelValue === optionValue(opt)
            ? 'bg-blue-600 text-white'
            : 'border border-slate-700 text-slate-400 hover:border-slate-600 hover:text-white'
        "
        @click="select(optionValue(opt))"
      >
        {{ optionLabel(opt) }}
      </button>
    </div>
  </div>
</template>
