<script setup>
import UiIcon from './UiIcon.vue';

defineProps({
  count: {
    type: Number,
    default: 0,
  },
});
</script>

<template>
  <span
    v-if="count > 0"
    class="absolute right-2 top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-blue-500 px-1 text-[10px] font-bold text-white shadow-md ring-2 ring-slate-900"
    :title="`${count} message${count > 1 ? 's' : ''} non lu${count > 1 ? 's' : ''}`"
    aria-label="Messages non lus"
  >
    <UiIcon v-if="count === 1" name="chat-dot" class="h-3 w-3" />
    <span v-else>{{ count > 9 ? '9+' : count }}</span>
  </span>
</template>
