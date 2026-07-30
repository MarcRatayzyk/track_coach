<script setup>
defineProps({
  reactions: {
    type: Array,
    default: () => [],
  },
  mine: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['react']);

const quick = ['👍', '🔥', '💪', '✅', '👀'];
</script>

<template>
  <div
    class="absolute -bottom-3 flex items-center gap-1 opacity-0 transition duration-200 group-hover/bubble:opacity-100"
    :class="mine ? 'right-2' : 'left-2'"
  >
    <button
      v-for="emoji in quick"
      :key="emoji"
      type="button"
      class="rounded-full border border-slate-700 bg-slate-900 px-1.5 py-0.5 text-[11px] shadow-md transition hover:scale-110 hover:border-blue-500/40"
      @click.stop="emit('react', emoji)"
    >
      {{ emoji }}
    </button>
  </div>
  <div v-if="reactions.length" class="mt-1.5 flex flex-wrap gap-1" :class="mine ? 'justify-end' : 'justify-start'">
    <span
      v-for="reaction in reactions"
      :key="reaction"
      class="rounded-full border border-slate-700 bg-slate-900/90 px-2 py-0.5 text-[11px] text-slate-200"
    >
      {{ reaction }}
    </span>
  </div>
</template>
