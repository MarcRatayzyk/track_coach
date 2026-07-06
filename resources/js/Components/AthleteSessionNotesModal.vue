<script setup>
defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Instructions',
  },
  notes: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['close']);
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      @click.self="emit('close')"
    >
      <div class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
          <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
          <button
            type="button"
            class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="emit('close')"
          >
            ✕
          </button>
        </div>

        <p class="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-slate-200">
          {{ notes }}
        </p>

        <div class="mt-5 flex justify-end">
          <button
            type="button"
            class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
            @click="emit('close')"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
