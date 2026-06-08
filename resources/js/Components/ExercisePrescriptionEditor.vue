<script setup>
import { computed } from 'vue';
import ExerciseVariantStrip from './ExerciseVariantStrip.vue';
import LoadModePicker from './LoadModePicker.vue';
import OptionButtonGroup from './OptionButtonGroup.vue';
import { SET_OPTIONS, REP_OPTIONS, formatLineRecap } from '../utils/programBuilder';

const line = defineModel({ type: Object, required: true });

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  lift: {
    type: String,
    default: 'squat',
  },
  accent: {
    type: String,
    default: 'emerald',
  },
  embedded: {
    type: Boolean,
    default: false,
  },
});

const accentBorder = {
  emerald: 'border-emerald-500/30 bg-emerald-950/20',
  slate: 'border-slate-800 bg-slate-950/40',
  zinc: 'border-slate-800/80 bg-slate-950/30',
};

const accentTitle = {
  emerald: 'text-emerald-200',
  slate: 'text-white',
  zinc: 'text-slate-300',
};

const recap = computed(() => formatLineRecap(line.value));

const recapText = computed(
  () => recap.value ?? 'Choisis un exercice, puis séries, reps et charge.',
);

function onExerciseSelected(selected) {
  if (!line.value) {
    return;
  }
  line.value.exercise_variant_id = selected.exercise_variant_id ?? null;
  line.value.exercise_name = selected.exercise_name ?? '';
  if (selected.lift) {
    line.value.lift = selected.lift;
  }
}

function updateField(field, value) {
  if (!line.value) {
    return;
  }
  line.value[field] = value;
}
</script>

<template>
  <component :is="embedded ? 'div' : 'section'" :class="embedded ? '' : ['rounded-xl border p-4', accentBorder[accent] ?? accentBorder.slate]">
    <h4
      v-if="!embedded"
      class="text-sm font-semibold"
      :class="accentTitle[accent] ?? accentTitle.slate"
    >
      {{ title }}
    </h4>

    <ExerciseVariantStrip
      :default-lift="lift"
      :exercise-variant-id="line.exercise_variant_id"
      :exercise-name="line.exercise_name ?? ''"
      @select="onExerciseSelected"
    />

    <div class="mt-4 grid gap-4 sm:grid-cols-2">
      <OptionButtonGroup
        :model-value="line.sets"
        :options="SET_OPTIONS"
        :columns="5"
        label="Séries"
        @update:model-value="updateField('sets', $event)"
      />
      <OptionButtonGroup
        :model-value="line.reps"
        :options="REP_OPTIONS"
        :columns="6"
        label="Reps"
        @update:model-value="updateField('reps', $event)"
      />
    </div>

    <LoadModePicker v-model="line" />

    <div v-if="!embedded" class="mt-4 rounded-lg border-2 border-blue-500/40 bg-blue-950/40 px-3 py-2.5">
      <p class="text-xs font-medium uppercase tracking-wide text-blue-300">Récap</p>
      <p
        class="mt-1 text-base font-semibold"
        :class="recap ? 'text-white' : 'text-slate-500'"
      >
        {{ recapText }}
      </p>
    </div>
  </component>
</template>
