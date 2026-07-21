<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

const emit = defineEmits(['close', 'created']);

const page = usePage();
const open = defineModel('open', { type: Boolean, default: false });

const catalog = computed(() => page.props.exerciseLibrary ?? []);

const customExercises = computed(() =>
  catalog.value.filter((exercise) => exercise.is_custom),
);

const mainLiftExercises = computed(() =>
  catalog.value.filter((exercise) => !exercise.is_custom && exercise.category === 'main_lift'),
);

const accessoryExercises = computed(() =>
  catalog.value.filter((exercise) => exercise.category === 'accessory'),
);

const editingId = ref(null);
const search = ref('');

const form = useForm({
  name: '',
  lift: 'general',
  category: 'accessory',
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

const liftLabelByValue = Object.fromEntries(liftOptions.map((option) => [option.value, option.label]));
const categoryLabelByValue = Object.fromEntries(categoryOptions.map((option) => [option.value, option.label]));

function exerciseMeta(exercise) {
  const lift = liftLabelByValue[exercise.lift] ?? exercise.lift;
  const category = categoryLabelByValue[exercise.category] ?? exercise.category;
  return `${lift} · ${category}`;
}

function matchesSearch(exercise) {
  const term = search.value.trim().toLowerCase();
  if (!term) {
    return true;
  }
  if (String(exercise.name ?? '').toLowerCase().includes(term)) {
    return true;
  }
  return (exercise.variants ?? []).some((variant) =>
    String(variant.name ?? '').toLowerCase().includes(term),
  );
}

const filteredMainLifts = computed(() => mainLiftExercises.value.filter(matchesSearch));
const filteredAccessories = computed(() => accessoryExercises.value.filter(matchesSearch));
const filteredCustom = computed(() => customExercises.value.filter(matchesSearch));

function variantNames(exercise) {
  const variants = exercise.variants ?? [];
  if (!variants.length) {
    return [exercise.name];
  }
  return variants.map((variant) => variant.name);
}

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
  search.value = '';
  open.value = false;
  emit('close');
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-3 sm:p-4"
      @click.self="close"
    >
      <div
        class="flex max-h-[94vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
      >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-800 px-5 py-4 sm:px-6">
          <div>
            <h2 class="text-xl font-bold text-white">Mes exercices</h2>
            <p class="mt-1 text-sm text-slate-400">
              Catalogue complet : mouvements, accessoires et toutes les variantes.
            </p>
          </div>
          <button type="button" class="text-slate-400 hover:text-white" @click="close">✕</button>
        </div>

        <div class="tc-scrollbar min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-6">
          <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submit">
            <label class="sm:col-span-2 block text-sm lg:col-span-2">
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

            <div class="sm:col-span-2 flex flex-wrap gap-2 lg:col-span-4">
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

          <div class="mt-6">
            <label class="block text-sm">
              <span class="sr-only">Rechercher</span>
              <input
                v-model="search"
                type="search"
                placeholder="Rechercher un exercice ou une variante…"
                class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-500"
              />
            </label>
          </div>

          <section v-if="filteredCustom.length" class="mt-8">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Tes exercices ({{ filteredCustom.length }})
            </h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
              <article
                v-for="exercise in filteredCustom"
                :key="`custom-${exercise.id}`"
                class="rounded-xl border border-slate-800 bg-slate-950/60 p-3"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="min-w-0">
                    <p class="font-semibold text-white">{{ exercise.name }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ exerciseMeta(exercise) }}</p>
                  </div>
                  <div class="flex shrink-0 gap-2">
                    <button type="button" class="text-xs text-blue-400 hover:text-blue-300" @click="startEdit(exercise)">
                      Modifier
                    </button>
                    <button type="button" class="text-xs text-red-400 hover:text-red-300" @click="destroyExercise(exercise)">
                      Supprimer
                    </button>
                  </div>
                </div>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                  <span
                    v-for="name in variantNames(exercise)"
                    :key="`${exercise.id}-${name}`"
                    class="rounded-md border border-slate-700 bg-slate-900 px-2 py-1 text-xs text-slate-300"
                  >
                    {{ name }}
                  </span>
                </div>
              </article>
            </div>
          </section>

          <section v-if="filteredMainLifts.length" class="mt-8">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Mouvements principaux ({{ filteredMainLifts.length }})
            </h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <article
                v-for="exercise in filteredMainLifts"
                :key="`main-${exercise.id}`"
                class="rounded-xl border border-slate-800 bg-slate-950/50 p-3"
              >
                <p class="font-semibold text-white">{{ exercise.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ exerciseMeta(exercise) }}</p>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                  <span
                    v-for="name in variantNames(exercise)"
                    :key="`${exercise.id}-${name}`"
                    class="rounded-md border border-blue-500/30 bg-blue-950/30 px-2 py-1 text-xs text-blue-100"
                  >
                    {{ name }}
                  </span>
                </div>
              </article>
            </div>
          </section>

          <section v-if="filteredAccessories.length" class="mt-8">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
              Accessoires &amp; variantes ({{ filteredAccessories.length }})
            </h3>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
              <article
                v-for="exercise in filteredAccessories"
                :key="`acc-${exercise.id}`"
                class="rounded-xl border border-emerald-900/40 bg-slate-950/50 p-3"
              >
                <p class="font-semibold text-white">{{ exercise.name }}</p>
                <p class="mt-0.5 text-xs text-slate-500">{{ exerciseMeta(exercise) }}</p>
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                  <span
                    v-for="name in variantNames(exercise)"
                    :key="`${exercise.id}-${name}`"
                    class="rounded-md border border-emerald-500/30 bg-emerald-950/25 px-2 py-1 text-xs text-emerald-100"
                  >
                    {{ name }}
                  </span>
                </div>
              </article>
            </div>
          </section>

          <p
            v-if="!filteredCustom.length && !filteredMainLifts.length && !filteredAccessories.length"
            class="mt-8 text-sm text-slate-500"
          >
            Aucun exercice ne correspond à ta recherche.
          </p>
        </div>
      </div>
    </div>
  </Teleport>
</template>
