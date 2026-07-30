<script setup>
defineProps({
  rows: { type: Number, default: 3 },
});
</script>

<template>
  <div class="space-y-3" aria-hidden="true">
    <div
      v-for="i in rows"
      :key="i"
      class="h-16 animate-pulse rounded-[1.15rem] border border-slate-800/60 bg-slate-900/40"
      :style="{ animationDelay: `${(i - 1) * 80}ms` }"
    />
  </div>
</template>
