<script setup>
defineProps({
  name: {
    type: String,
    default: '',
  },
});
</script>

<template>
  <div class="flex items-center gap-2 px-1 py-1">
    <div class="flex items-center gap-1 rounded-2xl bg-slate-800 px-3 py-2.5">
      <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:0ms]" />
      <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:150ms]" />
      <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-400 [animation-delay:300ms]" />
    </div>
    <span v-if="name" class="text-xs text-slate-500">{{ name }} écrit…</span>
  </div>
</template>
