<script setup>
const props = defineProps({
  barbell: {
    type: Object,
    default: null,
  },
});

const plateClass = {
  red: 'bg-red-600 border-red-400 text-white',
  blue: 'bg-blue-600 border-blue-400 text-white',
  yellow: 'bg-yellow-400 border-yellow-300 text-slate-900',
  green: 'bg-emerald-500 border-emerald-300 text-white',
  white: 'bg-white border-slate-200 text-slate-900',
  black: 'bg-black border-slate-600 text-white',
  grey: 'bg-slate-400 border-slate-300 text-slate-900',
  silver: 'bg-slate-200 border-slate-300 text-slate-900',
};

function plateStyle(plate) {
  return {
    height: `${plate.height}px`,
    width: `${plate.width}px`,
  };
}
</script>

<template>
  <div v-if="barbell?.sidePlates?.length" class="mt-6">
    <div class="flex items-center justify-center gap-1">
      <div class="flex items-center">
        <div
          v-for="(plate, index) in barbell.sidePlates"
          :key="`left-${plate.weight}-${index}`"
          class="flex shrink-0 items-end justify-center rounded-sm border shadow-sm"
          :class="plateClass[plate.color]"
          :style="plateStyle(plate)"
          :title="`${plate.weight} kg`"
        >
          <span class="sr-only">{{ plate.weight }} kg</span>
        </div>
      </div>

      <div class="relative mx-1 flex h-3 min-w-[5.5rem] items-center sm:min-w-[7rem]">
        <div class="h-2.5 w-full rounded-full bg-gradient-to-r from-slate-500 via-slate-300 to-slate-500 shadow-inner" />
        <div
          class="absolute left-1/2 top-1/2 h-5 w-5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-slate-400 bg-slate-600 shadow"
          aria-hidden="true"
        />
      </div>

      <div class="flex items-center">
        <div
          v-for="(plate, index) in barbell.sidePlates"
          :key="`right-${plate.weight}-${index}`"
          class="flex shrink-0 items-end justify-center rounded-sm border shadow-sm"
          :class="plateClass[plate.color]"
          :style="plateStyle(plate)"
          :title="`${plate.weight} kg`"
        >
          <span class="sr-only">{{ plate.weight }} kg</span>
        </div>
      </div>
    </div>

    <p class="mt-3 text-center text-[11px] font-medium uppercase tracking-[0.2em] text-red-300/80">
      {{ barbell.loadLabel }} kg sur la barre
    </p>
  </div>
</template>
