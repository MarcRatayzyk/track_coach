<script setup>
import { useI18n } from 'vue-i18n';
import ExerciseVariantStrip from './ExerciseVariantStrip.vue';
import ScrollPicker from './ScrollPicker.vue';
import { localizedExerciseName } from '../utils/exerciseNames';

const { t } = useI18n();

defineProps({
  saving: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  canDeleteSession: {
    type: Boolean,
    default: false,
  },
});

const exercises = defineModel('exercises', { type: Array, required: true });

const emit = defineEmits(['save', 'cancel', 'delete-session']);

const repOptions = Array.from({ length: 20 }, (_, index) => index + 1);
const kgOptions = (() => {
  const values = [];
  for (let kg = 0; kg <= 300; kg += 2.5) {
    values.push(Number(kg.toFixed(1)));
  }
  return values;
})();
const percentOptions = (() => {
  const values = [];
  for (let percent = 20; percent <= 110; percent += 2.5) {
    values.push(Number(percent.toFixed(1)));
  }
  return values;
})();

function withValue(options, value) {
  if (value == null || value === '' || options.some((option) => Math.abs(Number(option) - Number(value)) < 0.051)) {
    return options;
  }
  return [...options, Number(value)].sort((left, right) => left - right);
}

function blankSet(previous) {
  return {
    reps: previous?.reps ?? 5,
    load: previous?.load ?? null,
    load_percent: previous?.load_percent ?? null,
    load_mode: previous?.load_mode ?? 'kg',
  };
}

function addExercise() {
  exercises.value.push({
    section: 'accessory',
    exercise_variant_id: null,
    exercise_name: '',
    lift: 'squat',
    sets: [blankSet()],
  });
}

function removeExercise(index) {
  exercises.value.splice(index, 1);
}

function addSet(exercise) {
  const previous = exercise.sets[exercise.sets.length - 1];
  exercise.sets.push(blankSet(previous));
}

function removeSet(exercise, index) {
  exercise.sets.splice(index, 1);
  if (!exercise.sets.length) {
    const exerciseIndex = exercises.value.indexOf(exercise);
    if (exerciseIndex >= 0) {
      exercises.value.splice(exerciseIndex, 1);
    }
  }
}

function selectExercise(exercise, selected) {
  if (!selected) {
    return;
  }
  exercise.exercise_variant_id = selected.exercise_variant_id ?? null;
  exercise.exercise_name = selected.exercise_name ?? '';
  if (selected.lift) {
    exercise.lift = selected.lift;
  }
}

function setChargeMode(set, mode) {
  set.load_mode = mode;
  if (mode === 'kg') {
    set.load_percent = null;
    return;
  }
  set.load = null;
}
</script>

<template>
  <div class="mt-4 space-y-3 border-t border-slate-800 pt-4">
    <article
      v-for="(exercise, exerciseIndex) in exercises"
      :key="`${exercise.exercise_name}-${exerciseIndex}`"
      class="rounded-xl border border-slate-800 bg-slate-950/40 p-3"
    >
      <div class="flex items-start justify-between gap-2">
        <p class="text-sm font-semibold text-white">
          {{ exercise.exercise_name ? localizedExerciseName(exercise.exercise_name) : t('athleteUi.programSession.chooseExercise') }}
        </p>
        <button
          type="button"
          class="shrink-0 rounded-md border border-red-500/40 px-2 py-1 text-xs font-semibold text-red-300 hover:bg-red-950/40"
          @click="removeExercise(exerciseIndex)"
        >
          {{ t('athleteUi.programSession.removeExercise') }}
        </button>
      </div>

      <ExerciseVariantStrip
        class="mt-2"
        accessory-panel
        :default-lift="exercise.lift || 'squat'"
        :exercise-variant-id="exercise.exercise_variant_id"
        :exercise-name="exercise.exercise_name"
        @select="selectExercise(exercise, $event)"
      />

      <div
        v-for="(set, setIndex) in exercise.sets"
        :key="`${exerciseIndex}-${setIndex}`"
        class="mt-3 rounded-lg border border-slate-800 px-2 py-2"
      >
        <div class="mb-2 flex items-center justify-between gap-2">
          <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">
            {{ t('athleteUi.programSession.set') }} {{ setIndex + 1 }}
          </p>
          <button
            type="button"
            class="text-xs font-semibold text-red-300 hover:text-red-200"
            @click="removeSet(exercise, setIndex)"
          >
            {{ t('athleteUi.programSession.removeSet') }}
          </button>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div class="min-w-0">
            <p class="mb-1 text-center text-[10px] font-medium text-slate-400">{{ t('athleteUi.programSession.reps') }}</p>
            <ScrollPicker
              v-model="set.reps"
              :options="withValue(repOptions, set.reps)"
              :item-height="32"
              :visible-count="3"
              :aria-label="t('athleteUi.programSession.reps')"
            />
          </div>
          <div class="min-w-0">
            <div class="mb-1 flex items-center justify-center gap-1">
              <button
                type="button"
                class="rounded px-2 py-0.5 text-[10px] font-semibold"
                :class="set.load_mode === 'kg' ? 'bg-blue-600 text-white' : 'border border-slate-700 text-slate-400'"
                @click="setChargeMode(set, 'kg')"
              >
                kg
              </button>
              <button
                type="button"
                class="rounded px-2 py-0.5 text-[10px] font-semibold"
                :class="set.load_mode === 'percent' ? 'bg-blue-600 text-white' : 'border border-slate-700 text-slate-400'"
                @click="setChargeMode(set, 'percent')"
              >
                %
              </button>
            </div>
            <ScrollPicker
              v-if="set.load_mode !== 'percent'"
              v-model="set.load"
              allow-empty
              :options="withValue(kgOptions, set.load)"
              :item-height="32"
              :visible-count="3"
              :aria-label="t('athleteUi.programSession.load')"
            />
            <ScrollPicker
              v-else
              v-model="set.load_percent"
              allow-empty
              :options="withValue(percentOptions, set.load_percent)"
              :format="(value) => `${value}%`"
              :item-height="32"
              :visible-count="3"
              :aria-label="t('athleteUi.programSession.load')"
            />
          </div>
        </div>
      </div>

      <button
        type="button"
        class="mt-3 rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-slate-500"
        @click="addSet(exercise)"
      >
        {{ t('athleteUi.programSession.addSet') }}
      </button>
    </article>

    <p v-if="error" class="text-xs text-red-400">{{ error }}</p>

    <div class="flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs font-semibold text-slate-200 hover:border-slate-500"
        @click="addExercise"
      >
        {{ t('athleteUi.programSession.addExercise') }}
      </button>
      <button
        type="button"
        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-60"
        :disabled="saving"
        @click="emit('save')"
      >
        {{ t('athleteUi.programSession.save') }}
      </button>
      <button
        type="button"
        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-slate-400 hover:text-slate-200"
        @click="emit('cancel')"
      >
        {{ t('common.cancel') }}
      </button>
      <button
        v-if="canDeleteSession"
        type="button"
        class="rounded-lg px-3 py-1.5 text-xs font-semibold text-red-300 hover:text-red-200"
        :disabled="saving"
        @click="emit('delete-session')"
      >
        {{ t('athleteUi.programSession.deleteSession') }}
      </button>
    </div>
  </div>
</template>
