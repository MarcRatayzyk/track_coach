<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  open: { type: Boolean, default: false },
  section: { type: String, default: 'accessory' },
  mainLift: { type: String, default: '' },
});

const emit = defineEmits(['close', 'select']);

const exercises = ref([]);
const loading = ref(false);
const activeTab = ref('squat');
const accessoryLiftFilter = ref('');
const accessoryEquipmentFilter = ref('');

const selectorOptions = [
  { id: 'squat', label: 'Squat' },
  { id: 'bench', label: 'Bench' },
  { id: 'deadlift', label: 'Deadlift' },
  { id: 'accessory', label: 'Accessoires' },
];

const isAccessoryTab = computed(() => activeTab.value === 'accessory');

watch(
  () => props.open,
  (isOpen) => {
    if (!isOpen) {
      return;
    }

    activeTab.value = props.section === 'accessory'
      ? 'accessory'
      : ['squat', 'bench', 'deadlift'].includes(props.mainLift)
        ? props.mainLift
        : 'squat';
    accessoryLiftFilter.value = ['squat', 'bench', 'deadlift'].includes(props.mainLift)
      ? props.mainLift
      : '';
    accessoryEquipmentFilter.value = '';
    fetchExercises();
  },
);

watch([activeTab, accessoryLiftFilter, accessoryEquipmentFilter], () => {
  if (props.open) {
    fetchExercises();
  }
});

async function fetchExercises() {
  loading.value = true;

  try {
    const params = new URLSearchParams();

    if (isAccessoryTab.value) {
      params.set('category', 'accessory');
      if (accessoryLiftFilter.value) {
        params.set('lift', accessoryLiftFilter.value);
      }
      if (accessoryEquipmentFilter.value) {
        params.set('equipment', accessoryEquipmentFilter.value);
      }
    } else {
      params.set('category', 'main_lift');
      params.set('lift', activeTab.value);
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

function selectExercise(exercise, variant = null) {
  emit('select', {
    exercise_variant_id: variant?.id ?? null,
    exercise_name: variant?.name ?? exercise.name,
    lift: ['squat', 'bench', 'deadlift'].includes(exercise.lift) ? exercise.lift : null,
    movement_pattern: exercise.movement_pattern ?? null,
  });
  emit('close');
}

function close() {
  emit('close');
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="close"
    >
      <div
        class="tc-scrollbar max-h-[85vh] w-full max-w-4xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
        @click.stop
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-white">Choisir un exercice</h3>
            <p class="mt-1 text-xs text-slate-500">
              Clique directement sur un main lift ou une variante pour enregistrer le choix.
            </p>
          </div>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="close"
          >
            ✕
          </button>
        </div>

        <div class="mt-4 grid grid-cols-2 gap-2 md:grid-cols-4">
          <button
            v-for="option in selectorOptions"
            :key="option.id"
            type="button"
            class="rounded-xl border px-3 py-2 text-sm font-medium transition"
            :class="
              activeTab === option.id
                ? 'border-blue-500 bg-blue-600 text-white'
                : 'border-slate-700 bg-slate-950 text-slate-300 hover:border-slate-600 hover:text-white'
            "
            @click="activeTab = option.id"
          >
            {{ option.label }}
          </button>
        </div>

        <div v-if="isAccessoryTab" class="mt-4 grid gap-3 md:grid-cols-2">
          <label class="block text-xs text-slate-500">
            Mouvement
            <select
              v-model="accessoryLiftFilter"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
            >
              <option value="">Tous</option>
              <option value="squat">Squat</option>
              <option value="bench">Bench</option>
              <option value="deadlift">Deadlift</option>
              <option value="general">Général</option>
            </select>
          </label>

          <label class="block text-xs text-slate-500">
            Équipement
            <select
              v-model="accessoryEquipmentFilter"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-2.5 py-2 text-sm text-white"
            >
              <option value="">Tous</option>
              <option value="barbell">Barre</option>
              <option value="dumbbell">Haltères</option>
              <option value="machine">Machine</option>
              <option value="cable">Poulie</option>
              <option value="bodyweight">Poids du corps</option>
            </select>
          </label>
        </div>

        <p v-if="loading" class="mt-6 text-center text-sm text-slate-500">Chargement…</p>

        <div
          v-else-if="exercises.length"
          class="mt-6 grid gap-3 lg:grid-cols-2"
        >
          <section
            v-for="exercise in exercises"
            :key="exercise.id"
            class="rounded-xl border border-slate-800 bg-slate-950/70 p-3"
          >
            <div class="mb-3 flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-white">{{ exercise.name }}</p>
                <p class="mt-1 text-[11px] uppercase tracking-wide text-slate-500">
                  {{ exercise.lift }} · {{ exercise.category }}
                </p>
              </div>
            </div>

            <div class="flex flex-wrap gap-2">
              <button
                type="button"
                class="rounded-lg border border-emerald-600/50 bg-emerald-600/10 px-3 py-2 text-sm font-medium text-emerald-200 hover:bg-emerald-600/20"
                @click="selectExercise(exercise)"
              >
                {{ exercise.name }}
              </button>

              <button
                v-for="variant in exercise.variants ?? []"
                :key="variant.id"
                type="button"
                class="rounded-lg border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-200 hover:border-slate-600 hover:bg-slate-800"
                @click="selectExercise(exercise, variant)"
              >
                {{ variant.name }}
              </button>
            </div>
          </section>
        </div>

        <p v-else class="mt-6 text-center text-sm text-slate-500">
          Aucun exercice trouvé.
        </p>
      </div>
    </div>
  </Teleport>
</template>
