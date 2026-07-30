<script setup>
import { Link } from '@inertiajs/vue3';
import UiIcon from '../UiIcon.vue';

defineProps({
  onAddAthlete: { type: Function, default: null },
});
</script>

<template>
  <div class="flex flex-wrap gap-2">
    <Link
      href="/program-builder"
      class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-3.5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-900/40 transition duration-200 hover:bg-blue-500"
    >
      <UiIcon name="bolt" class="h-4 w-4" />
      Créer un programme
    </Link>
    <button
      type="button"
      class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/40 px-3.5 py-2 text-sm font-medium text-slate-200 transition duration-200 hover:border-blue-500/40 hover:bg-slate-900"
      @click="onAddAthlete?.()"
    >
      <UiIcon name="users" class="h-4 w-4" />
      Ajouter un athlète
    </button>
    <Link
      href="/competitions"
      class="inline-flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-950/40 px-3.5 py-2 text-sm font-medium text-slate-200 transition duration-200 hover:border-blue-500/40 hover:bg-slate-900"
    >
      <UiIcon name="calendar" class="h-4 w-4" />
      Nouvelle compétition
    </Link>
  </div>
</template>
