<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['close', 'created']);

const page = usePage();
const open = defineModel('open', { type: Boolean, default: false });

const catalog = computed(() => page.props.exerciseLibrary ?? []);
const customExercises = computed(() => catalog.value.filter((exercise) => exercise.is_custom));

const editingId = ref(null);

const form = useForm({
  name: '',
  lift: 'general',
  category: 'accessory',
  equipment: 'barbell',
  movement_pattern: '',
});

const liftOptions = [
  { value: 'squat', label: 'Squat' },
  { value: 'bench', label: 'Bench' },
  { value: 'deadlift', label: 'Deadlift' },
  { value: 'general', label: 'Général' },
];

const categoryOptions = [
  { value: 'main_lift', label: 'Mouvement principal' },
  { value: 'accessory', label: 'Accessoire' },
];

const equipmentOptions = [
  { value: 'barbell', label: 'Barre' },
  { value: 'dumbbell', label: 'Haltères' },
  { value: 'machine', label: 'Machine' },
  { value: 'cable', label: 'Poulie' },
  { value: 'bodyweight', label: 'Poids du corps' },
  { value: 'other', label: 'Autre' },
];

function resetForm() {
  editingId.value = null;
  form.reset();
  form.clearErrors();
}

function startEdit(exercise) {
  editingId.value = exercise.id;
  form.name = exercise.name;
  form.lift = exercise.lift;
  form.category = exercise.category;
  form.equipment = exercise.equipment;
  form.movement_pattern = exercise.movement_pattern ?? '';
}

function submit() {
  if (editingId.value) {
    form.put(`/coach/exercises/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        resetForm();
        router.reload({ only: ['exerciseLibrary'], preserveScroll: true });
      },
    });

    return;
  }

  form.post('/coach/exercises', {
    preserveScroll: true,
    onSuccess: () => {
      resetForm();
      emit('created');
      router.reload({ only: ['exerciseLibrary'], preserveScroll: true });
    },
  });
}

function destroyExercise(exercise) {
  if (!window.confirm(`Supprimer « ${exercise.name} » ?`)) {
    return;
  }

  router.delete(`/coach/exercises/${exercise.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      if (editingId.value === exercise.id) {
        resetForm();
      }
      router.reload({ only: ['exerciseLibrary'], preserveScroll: true });
    },
  });
}

function close() {
  resetForm();
  open.value = false;
  emit('close');
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4"
      @click.self="close"
    >
      <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-4">
          <div>
            <h2 class="text-xl font-bold text-white">Mes exercices</h2>
            <p class="mt-1 text-sm text-slate-400">
              Crée des exercices personnalisés visibles uniquement dans ton catalogue.
            </p>
          </div>
          <button type="button" class="text-slate-400 hover:text-white" @click="close">✕</button>
        </div>

        <form class="mt-6 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
          <label class="sm:col-span-2 block text-sm">
            <span class="text-slate-300">Nom</span>
            <input
              v-model="form.name"
              type="text"
              required
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white"
            />
            <span v-if="form.errors.name" class="text-xs text-red-400">{{ form.errors.name }}</span>
          </label>

          <label class="block text-sm">
            <span class="text-slate-300">Lift</span>
            <select v-model="form.lift" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
              <option v-for="option in liftOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label class="block text-sm">
            <span class="text-slate-300">Catégorie</span>
            <select v-model="form.category" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
              <option v-for="option in categoryOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label class="block text-sm">
            <span class="text-slate-300">Équipement</span>
            <select v-model="form.equipment" class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white">
              <option v-for="option in equipmentOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <label class="block text-sm">
            <span class="text-slate-300">Pattern (optionnel)</span>
            <input
              v-model="form.movement_pattern"
              type="text"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-white"
            />
          </label>

          <div class="sm:col-span-2 flex gap-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            >
              {{ editingId ? 'Mettre à jour' : 'Ajouter' }}
            </button>
            <button
              v-if="editingId"
              type="button"
              class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-300"
              @click="resetForm"
            >
              Annuler l’édition
            </button>
          </div>
        </form>

        <div v-if="customExercises.length" class="mt-8 border-t border-slate-800 pt-6">
          <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Exercices personnalisés</h3>
          <ul class="mt-3 space-y-2">
            <li
              v-for="exercise in customExercises"
              :key="exercise.id"
              class="flex items-center justify-between rounded-lg border border-slate-800 bg-slate-950/60 px-3 py-2"
            >
              <div>
                <p class="font-medium text-white">{{ exercise.name }}</p>
                <p class="text-xs text-slate-500">{{ exercise.lift }} · {{ exercise.category }} · {{ exercise.equipment }}</p>
              </div>
              <div class="flex gap-2">
                <button type="button" class="text-xs text-blue-400 hover:text-blue-300" @click="startEdit(exercise)">
                  Modifier
                </button>
                <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="destroyExercise(exercise)">
                  Supprimer
                </button>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </Teleport>
</template>
