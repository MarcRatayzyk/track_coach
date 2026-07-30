<script setup>
import logoUrl from '../../images/logo.png';

defineProps({
  /** Affiche le texte « Power Roster » à côté du pictogramme */
  withWordmark: {
    type: Boolean,
    default: true,
  },
  /** Taille du pictogramme (classes Tailwind) */
  markClass: {
    type: String,
    default: 'h-14 w-14',
  },
  /** Classes du texte */
  wordmarkClass: {
    type: String,
    default: 'text-xl font-bold tracking-tight text-white',
  },
});
</script>

<template>
  <span class="inline-flex min-w-0 items-center gap-3.5">
    <img
      :src="logoUrl"
      alt=""
      aria-hidden="true"
      class="shrink-0 rounded-full object-cover"
      :class="markClass"
    />
    <span v-if="withWordmark" class="truncate" :class="wordmarkClass">Power Roster</span>
  </span>
</template>
