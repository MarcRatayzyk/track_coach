<script setup>
import { ref } from 'vue';
import TrainingDayEditor from './TrainingDayEditor.vue';
import { BLOCK_TYPES, emptyDay } from '../utils/programBuilder';

const week = defineModel({ type: Object, required: true });

const emit = defineEmits(['remove']);

const activeDayIndex = ref(0);

function addDay() {
  const lifts = ['squat', 'bench', 'deadlift'];
  const nextDay = week.value.days.length + 1;
  week.value.days.push(emptyDay(nextDay, lifts[(nextDay - 1) % 3]));
  activeDayIndex.value = week.value.days.length - 1;
}

function removeDay(index) {
  if (week.value.days.length <= 1) {
    return;
  }
  week.value.days.splice(index, 1);
  week.value.days.forEach((day, i) => {
    day.day_number = i + 1;
  });
  activeDayIndex.value = Math.min(activeDayIndex.value, week.value.days.length - 1);
}
</script>

<template>
  <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="flex flex-wrap items-center gap-3">
        <h3 class="text-base font-semibold text-white">Semaine {{ week.week_number }}</h3>
        <select
          v-model="week.block_type"
          class="rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1 text-sm text-white"
        >
          <option v-for="block in BLOCK_TYPES" :key="block.value" :value="block.value">
            {{ block.label }}
          </option>
        </select>
      </div>
      <button type="button" class="text-sm text-red-400 hover:text-red-300" @click="emit('remove')">
        Supprimer la semaine
      </button>
    </div>

    <div class="mt-4 flex flex-wrap gap-2 border-b border-slate-800 pb-2">
      <button
        v-for="(day, index) in week.days"
        :key="index"
        type="button"
        class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
        :class="
          activeDayIndex === index
            ? 'bg-blue-600 text-white'
            : 'bg-slate-800 text-slate-300 hover:bg-slate-700'
        "
        @click="activeDayIndex = index"
      >
        Jour {{ day.day_number }}
      </button>
      <button
        type="button"
        class="rounded-lg border border-dashed border-slate-600 px-3 py-1.5 text-sm text-slate-400 hover:border-blue-500/50 hover:text-blue-300"
        @click="addDay"
      >
        + Jour
      </button>
    </div>

    <div v-if="week.days[activeDayIndex]" class="mt-4">
      <div class="mb-3 flex justify-end">
        <button
          v-if="week.days.length > 1"
          type="button"
          class="text-xs text-red-400 hover:text-red-300"
          @click="removeDay(activeDayIndex)"
        >
          Supprimer ce jour
        </button>
      </div>
      <TrainingDayEditor v-model="week.days[activeDayIndex]" />
    </div>
  </div>
</template>
