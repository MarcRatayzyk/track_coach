<script setup>
import { ref } from 'vue';
import ExercisePicker from './ExercisePicker.vue';

const props = defineProps({
  section: {
    type: String,
    default: 'accessory',
  },
  exerciseVariantId: {
    type: [Number, null],
    default: null,
  },
  exerciseName: {
    type: String,
    default: '',
  },
  defaultLift: {
    type: String,
    default: 'squat',
  },
  /** Si false (tableur V2), le clic ouvre l’édition rapide via @activate au lieu du picker. */
  pickerEnabled: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['select', 'activate']);

const open = ref(false);

function onTriggerClick() {
  if (!props.pickerEnabled) {
    emit('activate');
    return;
  }
  open.value = true;
}

function closePicker() {
  open.value = false;
}

function handleSelect(payload) {
  emit('select', payload);
}
</script>

<template>
  <div class="relative z-30">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-1 border-0 bg-transparent px-1 py-1 text-left text-xs text-white outline-none"
      @click.stop="onTriggerClick"
    >
      <span v-if="exerciseName" class="block whitespace-normal break-words">{{ exerciseName }}</span>
      <span v-else class="text-slate-500">{{ pickerEnabled ? 'Choisir un exercice' : 'Sélectionner' }}</span>
      <span v-if="pickerEnabled" class="shrink-0 text-[10px] text-slate-400">▾</span>
    </button>

    <ExercisePicker
      v-if="pickerEnabled"
      :open="open"
      :section="props.section"
      :main-lift="props.defaultLift"
      @close="closePicker"
      @select="handleSelect"
    />
  </div>
</template>
