<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  tone: {
    type: String,
    default: 'neutral', // neutral | success | empty | error
  },
});
</script>

<template>
  <div
    class="flex min-h-[12rem] flex-col items-center justify-center rounded-[18px] border border-dashed px-6 py-10 text-center transition duration-200"
    :class="{
      'border-slate-700/80 bg-slate-950/30': tone === 'neutral' || tone === 'empty',
      'border-emerald-500/30 bg-emerald-950/20': tone === 'success',
      'border-rose-500/30 bg-rose-950/20': tone === 'error',
    }"
  >
    <div
      class="mb-4 flex h-14 w-14 items-center justify-center rounded-full border"
      :class="{
        'border-slate-700 bg-slate-900 text-slate-400': tone === 'neutral' || tone === 'empty',
        'border-emerald-500/40 bg-emerald-500/10 text-emerald-300': tone === 'success',
        'border-rose-500/40 bg-rose-500/10 text-rose-300': tone === 'error',
      }"
      aria-hidden="true"
    >
      <svg
        v-if="tone === 'success'"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.8"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
      </svg>
      <svg
        v-else-if="tone === 'error'"
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.8"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12v-.008zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <svg
        v-else
        class="h-6 w-6"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.8"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M12 3.75v16.5" />
      </svg>
    </div>
    <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
    <p v-if="description" class="mt-2 max-w-xs text-xs leading-relaxed text-slate-500">
      {{ description }}
    </p>
    <slot />
  </div>
</template>
