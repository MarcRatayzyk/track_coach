<script setup>
defineProps({
  rows: { type: Number, default: 3 },
  variant: {
    type: String,
    default: 'cards',
    validator: (v) => ['cards', 'list', 'kpi'].includes(v),
  },
});
</script>

<template>
  <div v-if="variant === 'kpi'" class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    <div
      v-for="i in 4"
      :key="i"
      class="h-[8.5rem] animate-pulse rounded-[18px] border border-slate-800/80 bg-slate-900/40"
    />
  </div>
  <div v-else-if="variant === 'cards'" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div
      v-for="i in rows"
      :key="i"
      class="h-36 animate-pulse rounded-[18px] border border-slate-800/80 bg-slate-900/40"
      :style="{ animationDelay: `${(i - 1) * 80}ms` }"
    />
  </div>
  <div v-else class="space-y-2">
    <div
      v-for="i in rows"
      :key="i"
      class="h-16 animate-pulse rounded-[16px] border border-slate-800/80 bg-slate-900/40"
      :style="{ animationDelay: `${(i - 1) * 60}ms` }"
    />
  </div>
</template>
