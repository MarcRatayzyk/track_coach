<script setup>
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
  defaultLift: {
    type: String,
    default: 'squat',
  },
  exerciseVariantId: {
    type: [Number, null],
    default: null,
  },
  exerciseName: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['select']);

const filters = [
  { id: 'squat', label: 'Squat', lift: 'squat', category: 'main_lift' },
  { id: 'bench', label: 'Bench', lift: 'bench', category: 'main_lift' },
  { id: 'deadlift', label: 'Deadlift', lift: 'deadlift', category: 'main_lift' },
  { id: 'accessory', label: 'Accessoires', lift: '', category: 'accessory' },
];

const activeFilter = ref(
  ['squat', 'bench', 'deadlift'].includes(props.defaultLift) ? props.defaultLift : 'accessory',
);
const exercises = ref([]);
const loading = ref(false);
const stripRef = ref(null);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartScroll = ref(0);
const localSelectedKey = ref(null);

const chips = computed(() => {
  const items = [];
  for (const exercise of exercises.value) {
    const variants = exercise.variants ?? [];
    if (variants.length) {
      for (const variant of variants) {
        items.push({
          key: `v-${variant.id}`,
          exercise_variant_id: variant.id,
          exercise_name: variant.name,
          lift: exercise.lift,
        });
      }
    } else {
      items.push({
        key: `e-${exercise.id}`,
        exercise_variant_id: null,
        exercise_name: exercise.name,
        lift: exercise.lift,
      });
    }
  }
  return items;
});

async function fetchExercises() {
  loading.value = true;
  try {
    const filter = filters.find((item) => item.id === activeFilter.value);
    const params = new URLSearchParams();
    if (filter?.category) {
      params.set('category', filter.category);
    }
    if (filter?.lift) {
      params.set('lift', filter.lift);
    }

    const response = await fetch(`/coach/exercises?${params.toString()}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    });

    if (response.ok) {
      exercises.value = await response.json();
    }
  } finally {
    loading.value = false;
  }
}

function isChipSelected(chip) {
  if (localSelectedKey.value === chip.key) {
    return true;
  }
  if (props.exerciseVariantId != null && chip.exercise_variant_id != null) {
    return Number(props.exerciseVariantId) === Number(chip.exercise_variant_id);
  }
  const name = props.exerciseName?.trim();
  return Boolean(name) && chip.exercise_name === name;
}

function selectChip(chip) {
  localSelectedKey.value = chip.key;
  emit('select', {
    exercise_variant_id: chip.exercise_variant_id,
    exercise_name: chip.exercise_name,
    lift: chip.lift,
  });
}

watch(
  () => [props.exerciseVariantId, props.exerciseName, chips.value],
  () => {
    const match = chips.value.find((chip) => {
      if (props.exerciseVariantId != null && chip.exercise_variant_id != null) {
        return Number(props.exerciseVariantId) === Number(chip.exercise_variant_id);
      }
      const name = props.exerciseName?.trim();
      return Boolean(name) && chip.exercise_name === name;
    });
    localSelectedKey.value = match?.key ?? null;
  },
);

function onPointerDown(event) {
  if (!stripRef.value || event.button !== 0) {
    return;
  }
  isDragging.value = false;
  dragStartX.value = event.clientX;
  dragStartScroll.value = stripRef.value.scrollLeft;
  stripRef.value.setPointerCapture(event.pointerId);
}

function onPointerMove(event) {
  if (!stripRef.value?.hasPointerCapture(event.pointerId)) {
    return;
  }
  const delta = event.clientX - dragStartX.value;
  if (Math.abs(delta) > 4) {
    isDragging.value = true;
  }
  stripRef.value.scrollLeft = dragStartScroll.value - delta;
}

function onPointerUp(event) {
  if (!stripRef.value?.hasPointerCapture(event.pointerId)) {
    return;
  }
  stripRef.value.releasePointerCapture(event.pointerId);
  requestAnimationFrame(() => {
    isDragging.value = false;
  });
}

watch(activeFilter, fetchExercises);

watch(
  () => props.defaultLift,
  (lift) => {
    activeFilter.value = ['squat', 'bench', 'deadlift'].includes(lift) ? lift : 'accessory';
    fetchExercises();
  },
);

onMounted(fetchExercises);
</script>

<template>
  <div>
    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="filter in filters"
        :key="filter.id"
        type="button"
        class="rounded-lg px-2.5 py-1 text-xs font-medium transition"
        :class="
          activeFilter === filter.id
            ? 'bg-blue-600 text-white'
            : 'border border-slate-700 text-slate-400 hover:border-slate-600 hover:text-white'
        "
        @click="activeFilter = filter.id"
      >
        {{ filter.label }}
      </button>
    </div>

    <p v-if="loading" class="mt-2 text-xs text-slate-500">Chargement…</p>
    <div
      v-else
      ref="stripRef"
      class="tc-scrollbar mt-2 flex cursor-grab gap-2 overflow-x-auto pb-2 active:cursor-grabbing"
      @pointerdown="onPointerDown"
      @pointermove="onPointerMove"
      @pointerup="onPointerUp"
      @pointercancel="onPointerUp"
    >
      <button
        v-for="chip in chips"
        :key="chip.key"
        type="button"
        class="shrink-0 rounded-xl border px-3 py-2 text-left text-xs font-medium transition"
        :class="
          isChipSelected(chip)
            ? '!border-blue-500 !bg-blue-600 !text-white shadow-sm shadow-blue-900/40'
            : 'border-slate-700 bg-slate-950 text-slate-200 hover:border-blue-500/50 hover:bg-slate-900'
        "
        @pointerdown.stop
        @click.stop="selectChip(chip)"
      >
        {{ chip.exercise_name }}
      </button>
      <p v-if="!chips.length" class="shrink-0 py-2 text-xs text-slate-500">Aucun exercice.</p>
    </div>
    <p class="mt-1 text-[10px] text-slate-600">Glisser horizontalement pour parcourir · clic pour sélectionner</p>
  </div>
</template>
