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

const plateWidthByColor = {
  red: 9,
  blue: 8,
  yellow: 6,
  green: 5,
  default: 4,
};

function plateStyle(plate) {
  return {
    height: `${plate.height}px`,
    width: `${plateWidthByColor[plate.color] ?? plateWidthByColor.default}px`,
  };
}
</script>

<template>
  <div v-if="barbell?.sidePlates?.length" class="mt-4">
    <div class="flex items-center justify-center gap-1">
      <div class="flex items-center">
        <div
          class="mr-0.5 h-2 w-3.5 rounded-[3px] bg-gradient-to-b from-slate-200 via-slate-50 to-slate-200 shadow-sm"
          aria-hidden="true"
        />
        <div
          v-for="(plate, index) in [...barbell.sidePlates].reverse()"
          :key="`left-${plate.weight}-${index}`"
          class="flex shrink-0 items-end justify-center rounded-[1.5px] border shadow-sm"
          :class="plateClass[plate.color]"
          :style="plateStyle(plate)"
          :title="`${plate.weight} kg`"
        >
          <span class="sr-only">{{ plate.weight }} kg</span>
        </div>
      </div>

      <div class="relative mx-1 flex h-3 min-w-[5.25rem] items-center sm:min-w-[6.5rem]">
        <div class="h-2.5 w-full rounded-full bg-gradient-to-r from-slate-500 via-slate-300 to-slate-500 shadow-inner" />
      </div>

      <div class="flex items-center">
        <div
          v-for="(plate, index) in barbell.sidePlates"
          :key="`right-${plate.weight}-${index}`"
          class="flex shrink-0 items-end justify-center rounded-[1.5px] border shadow-sm"
          :class="plateClass[plate.color]"
          :style="plateStyle(plate)"
          :title="`${plate.weight} kg`"
        >
          <span class="sr-only">{{ plate.weight }} kg</span>
        </div>
        <div
          class="ml-0.5 h-2 w-3.5 rounded-[3px] bg-gradient-to-b from-slate-200 via-slate-50 to-slate-200 shadow-sm"
          aria-hidden="true"
        />
      </div>
    </div>

  </div>
</template>
