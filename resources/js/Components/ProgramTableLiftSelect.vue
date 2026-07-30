<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

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

const open = ref(false);
const triggerEl = ref(null);
const menuEl = ref(null);
const menuStyle = ref({});

const selectedLabel = computed(
  () => options.find((option) => option.value === props.modelValue)?.label ?? 'Squat',
);

function updateMenuPosition() {
  const trigger = triggerEl.value;
  if (!trigger) {
    return;
  }

  const rect = trigger.getBoundingClientRect();
  const menuHeight = 108;
  const spaceBelow = window.innerHeight - rect.bottom;
  const openUpward = spaceBelow < menuHeight && rect.top > menuHeight;
  const width = Math.max(rect.width, 6.5 * 16);

  menuStyle.value = {
    position: 'fixed',
    left: `${rect.left}px`,
    width: `${width}px`,
    top: openUpward ? 'auto' : `${rect.bottom + 2}px`,
    bottom: openUpward ? `${window.innerHeight - rect.top + 2}px` : 'auto',
    zIndex: 80,
  };
}

async function toggle() {
  open.value = !open.value;
  if (open.value) {
    await nextTick();
    updateMenuPosition();
  }
}

function selectOption(value) {
  emit('update:modelValue', value);
  open.value = false;
}

function onDocumentClick(event) {
  if (
    triggerEl.value?.contains(event.target)
    || menuEl.value?.contains(event.target)
  ) {
    return;
  }
  open.value = false;
}

function onViewportChange() {
  if (open.value) {
    updateMenuPosition();
  }
}

watch(open, (isOpen) => {
  if (isOpen) {
    window.addEventListener('scroll', onViewportChange, true);
    window.addEventListener('resize', onViewportChange);
  } else {
    window.removeEventListener('scroll', onViewportChange, true);
    window.removeEventListener('resize', onViewportChange);
  }
});

onMounted(() => {
  document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDocumentClick);
  window.removeEventListener('scroll', onViewportChange, true);
  window.removeEventListener('resize', onViewportChange);
});
</script>

<template>
  <div class="relative">
    <button
      ref="triggerEl"
      type="button"
      class="flex w-full items-center justify-center gap-1 border-0 bg-transparent px-1 py-1 text-center text-xs uppercase text-white outline-none"
      @click.stop="toggle"
    >
      <span>{{ selectedLabel }}</span>
      <span class="text-[10px] text-slate-400">▾</span>
    </button>

    <Teleport to="body">
      <div
        v-if="open"
        ref="menuEl"
        class="overflow-hidden rounded-md border border-slate-600 bg-slate-900 shadow-xl"
        :style="menuStyle"
      >
        <button
          v-for="option in options"
          :key="option.value"
          type="button"
          class="block w-full px-2.5 py-1.5 text-left text-xs text-slate-200 hover:bg-slate-800"
          :class="modelValue === option.value ? 'bg-blue-600/30 text-white' : ''"
          @click.stop="selectOption(option.value)"
        >
          {{ option.label }}
        </button>
      </div>
    </Teleport>
  </div>
</template>
