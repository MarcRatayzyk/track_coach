<script setup>
defineProps({
  online: {
    type: Boolean,
    default: false,
  },
  size: {
    type: String,
    default: 'md',
  },
});
</script>

<template>
  <span
    class="absolute bottom-0 right-0 rounded-full border-2 border-slate-900 transition-all duration-200"
    :class="[
      size === 'lg' ? 'h-3.5 w-3.5' : size === 'sm' ? 'h-2.5 w-2.5' : 'h-3 w-3',
      online
        ? 'bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.85)]'
        : 'bg-slate-600',
    ]"
    :title="online ? 'En ligne' : 'Hors ligne'"
    aria-hidden="true"
  />
</template>
