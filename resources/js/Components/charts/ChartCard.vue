<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  emptyMessage: {
    type: String,
    default: 'Aucune donnée sur cette période.',
  },
  hasData: {
    type: Boolean,
    default: true,
  },
  chartHeight: {
    type: String,
    default: 'h-64',
  },
});
</script>

<template>
  <article class="flex h-full flex-col rounded-xl border border-slate-800 bg-slate-950/40 p-4">
    <div v-if="title || subtitle" class="mb-3 shrink-0">
      <h3 v-if="title" class="text-sm font-semibold text-white">{{ title }}</h3>
      <p v-if="subtitle" class="mt-0.5 text-xs text-slate-500">{{ subtitle }}</p>
    </div>
    <div class="shrink-0">
      <slot name="header" />
    </div>
    <div v-if="hasData" :class="[chartHeight, 'relative min-h-0 w-full shrink-0 overflow-hidden']">
      <div class="absolute inset-0">
        <slot />
      </div>
    </div>
    <div v-if="hasData && $slots.footer" class="shrink-0">
      <slot name="footer" />
    </div>
    <p v-else class="py-8 text-center text-sm text-slate-500">
      {{ emptyMessage }}
    </p>
  </article>
</template>
