<script setup>
defineProps({
  /** Affiche le texte « Power Roster » à côté du pictogramme */
  withWordmark: {
    type: Boolean,
    default: true,
  },
  /** Taille du pictogramme (classes Tailwind) */
  markClass: {
    type: String,
    default: 'h-9 w-9',
  },
  /** Classes du texte */
  wordmarkClass: {
    type: String,
    default: 'text-base font-bold tracking-tight text-white',
  },
});
</script>

<template>
  <span class="inline-flex min-w-0 items-center gap-2.5">
    <span
      class="relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 shadow-lg shadow-blue-900/40"
      :class="markClass"
      aria-hidden="true"
    >
      <svg
        class="h-[62%] w-[62%] text-white"
        viewBox="0 0 32 32"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <!-- Piste / arc -->
        <path
          d="M6 22c4.5-7 15.5-7 20 0"
          stroke="currentColor"
          stroke-width="2.2"
          stroke-linecap="round"
          opacity="0.55"
        />
        <!-- Barre -->
        <path
          d="M7 14.5h18"
          stroke="currentColor"
          stroke-width="2.4"
          stroke-linecap="round"
        />
        <!-- Disques gauche -->
        <rect x="3.2" y="10.2" width="3.2" height="8.6" rx="1" fill="currentColor" />
        <rect x="5.6" y="11.4" width="2" height="6.2" rx="0.7" fill="currentColor" opacity="0.85" />
        <!-- Disques droite -->
        <rect x="25.6" y="10.2" width="3.2" height="8.6" rx="1" fill="currentColor" />
        <rect x="24.4" y="11.4" width="2" height="6.2" rx="0.7" fill="currentColor" opacity="0.85" />
        <!-- Manchon central -->
        <circle cx="16" cy="14.5" r="1.6" fill="currentColor" />
      </svg>
    </span>
    <span v-if="withWordmark" class="truncate" :class="wordmarkClass">Power Roster</span>
  </span>
</template>
