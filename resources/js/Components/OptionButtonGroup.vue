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
  dense: {
    type: Boolean,
    default: false,
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

function valuesMatch(left, right) {
  if (left == null || left === '') {
    return right == null || right === '';
  }

  if (right == null || right === '') {
    return false;
  }

  const leftNumber = Number(left);
  const rightNumber = Number(right);

  if (!Number.isNaN(leftNumber) && !Number.isNaN(rightNumber)) {
    return leftNumber === rightNumber;
  }

  return left === right;
}

function isSelected(value) {
  return valuesMatch(props.modelValue, value);
}

function select(value) {
  emit('update:modelValue', isSelected(value) ? null : value);
}
</script>

<template>
  <div>
    <p v-if="label" class="font-medium text-slate-400" :class="dense ? 'text-[10px]' : 'text-xs'">
      {{ label }}
    </p>
    <div
      :class="[dense ? 'mt-1 gap-1' : 'mt-2 gap-1.5', columns ? 'grid' : 'flex flex-wrap']"
      :style="gridStyle"
    >
      <button
        v-for="opt in options"
        :key="String(optionValue(opt))"
        type="button"
        class="font-medium transition"
        :class="[
          dense
            ? 'min-w-[1.65rem] rounded px-1.5 py-1 text-[10px]'
            : 'min-w-[2.25rem] rounded-lg px-2.5 py-1.5 text-xs',
          isSelected(optionValue(opt))
            ? 'bg-blue-600 text-white shadow-sm shadow-blue-900/40 ring-1 ring-blue-400/30'
            : 'border border-slate-700 text-slate-400 hover:border-slate-600 hover:text-white',
        ]"
        @click="select(optionValue(opt))"
      >
        {{ optionLabel(opt) }}
      </button>
    </div>
  </div>
</template>
