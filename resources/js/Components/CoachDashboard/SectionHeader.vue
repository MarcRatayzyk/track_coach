<script setup>
defineProps({
  title: { type: String, required: true },
  subtitle: { type: String, default: '' },
  eyebrow: { type: String, default: '' },
});
</script>

<template>
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div class="min-w-0">
      <p
        v-if="eyebrow"
        class="text-[10px] font-semibold uppercase tracking-[0.18em] text-blue-300/70"
      >
        {{ eyebrow }}
      </p>
      <h2 class="mt-1 text-lg font-semibold tracking-tight text-white">{{ title }}</h2>
      <p v-if="subtitle" class="mt-1 text-sm text-slate-400">{{ subtitle }}</p>
    </div>
    <div v-if="$slots.actions" class="shrink-0">
      <slot name="actions" />
    </div>
  </div>
</template>
