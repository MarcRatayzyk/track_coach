<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { filterExerciseCatalog } from '../utils/exerciseLibrary';

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
  accessoryPanel: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select']);

const page = usePage();
const catalog = computed(() => page.props.exerciseLibrary ?? []);

const filters = [
  { id: 'squat', label: 'Squat', lift: 'squat', category: 'main_lift' },
  { id: 'bench', label: 'Bench', lift: 'bench', category: 'main_lift' },
  { id: 'deadlift', label: 'Deadlift', lift: 'deadlift', category: 'main_lift' },
  { id: 'accessory', label: 'Accessoires', lift: '', category: 'accessory' },
];

const activeFilter = ref(
  ['squat', 'bench', 'deadlift'].includes(props.defaultLift) ? props.defaultLift : 'accessory',
);
const stripRef = ref(null);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartScroll = ref(0);
const localSelectedKey = ref(null);

const activeFilterConfig = computed(() => filters.find((item) => item.id === activeFilter.value) ?? filters[0]);

const exercises = computed(() => filterExerciseCatalog(catalog.value, {
  category: activeFilterConfig.value.category,
  lift: activeFilterConfig.value.lift || null,
}));

function buildChip(exercise, variant = null) {
  if (variant) {
    return {
      key: `v-${variant.id}`,
      exercise_variant_id: variant.id,
      exercise_name: variant.name,
      lift: exercise.lift,
      movement_pattern: exercise.movement_pattern ?? '',
    };
  }

  return {
    key: `e-${exercise.id}`,
    exercise_variant_id: null,
    exercise_name: exercise.name,
    lift: exercise.lift,
    movement_pattern: exercise.movement_pattern ?? '',
  };
}

const chips = computed(() => {
  const items = [];
  for (const exercise of exercises.value) {
    const variants = exercise.variants ?? [];
    if (variants.length) {
      for (const variant of variants) {
        items.push(buildChip(exercise, variant));
      }
    } else {
      items.push(buildChip(exercise));
    }
  }
  return items;
});

const accessoryGroups = computed(() => {
  if (!props.accessoryPanel || activeFilter.value !== 'accessory') {
    return [];
  }

  return exercises.value.map((exercise) => {
    const variants = exercise.variants ?? [];
    const groupChips = variants.length
      ? variants.map((variant) => buildChip(exercise, variant))
      : [buildChip(exercise)];

    return {
      id: exercise.id,
      label: exercise.name,
      chips: groupChips,
    };
  });
});

const showAccessoryPanel = computed(() => props.accessoryPanel && activeFilter.value === 'accessory');

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
    movement_pattern: chip.movement_pattern,
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

watch(
  () => props.defaultLift,
  (lift) => {
    activeFilter.value = ['squat', 'bench', 'deadlift'].includes(lift) ? lift : 'accessory';
  },
);
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

    <div
      v-if="showAccessoryPanel"
      class="mt-2 space-y-2.5 rounded-xl border border-slate-800 bg-slate-950/70 p-2.5"
    >
      <div
        v-for="group in accessoryGroups"
        :key="group.id"
        class="rounded-lg border border-slate-800/80 bg-slate-900/40 p-2"
      >
        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
          {{ group.label }}
        </p>
        <div class="mt-1.5 flex flex-wrap gap-1.5">
          <button
            v-for="chip in group.chips"
            :key="chip.key"
            type="button"
            class="rounded-lg border px-2.5 py-1.5 text-left text-xs font-medium transition"
            :class="
              isChipSelected(chip)
                ? '!border-emerald-500 !bg-emerald-600 !text-white shadow-sm shadow-emerald-900/30'
                : 'border-slate-700 bg-slate-950 text-slate-200 hover:border-emerald-500/40 hover:bg-slate-900'
            "
            @click.stop="selectChip(chip)"
          >
            {{ chip.exercise_name }}
          </button>
        </div>
      </div>
      <p v-if="!accessoryGroups.length" class="py-2 text-center text-xs text-slate-500">
        Aucun accessoire disponible.
      </p>
    </div>

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
    <p v-if="!showAccessoryPanel" class="mt-1 text-[10px] text-slate-600">
      Glisser horizontalement pour parcourir · clic pour sélectionner
    </p>
  </div>
</template>
