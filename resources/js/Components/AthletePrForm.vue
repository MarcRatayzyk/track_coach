<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  athleteId: {
    type: Number,
    required: true,
  },
  latestPr: {
    type: Object,
    default: null,
  },
  title: {
    type: String,
    default: 'Enregistrer un PR officiel',
  },
  description: {
    type: String,
    default: 'Squat, bench et terre — utilisés pour les charges du programme.',
  },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  squat: props.latestPr?.squat ?? '',
  bench: props.latestPr?.bench ?? '',
  deadlift: props.latestPr?.deadlift ?? '',
  reference_date: today,
});

function submit() {
  form.post(`/athletes/${props.athleteId}/prs`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reference_date = today;
    },
  });
}
</script>

<template>
  <form
    class="rounded-xl border border-slate-800 bg-slate-950/40 p-4"
    @submit.prevent="submit"
  >
    <h3 class="text-sm font-semibold text-white">{{ title }}</h3>
    <p class="mt-1 text-xs text-slate-500">{{ description }}</p>

    <div class="mt-3 grid gap-3 sm:grid-cols-4">
      <label class="text-xs text-slate-400">
        Squat
        <input
          v-model.number="form.squat"
          type="number"
          min="0"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
        <p v-if="form.errors.squat" class="mt-1 text-xs text-red-400">{{ form.errors.squat }}</p>
      </label>
      <label class="text-xs text-slate-400">
        Bench
        <input
          v-model.number="form.bench"
          type="number"
          min="0"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
        <p v-if="form.errors.bench" class="mt-1 text-xs text-red-400">{{ form.errors.bench }}</p>
      </label>
      <label class="text-xs text-slate-400">
        Terre
        <input
          v-model.number="form.deadlift"
          type="number"
          min="0"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
        <p v-if="form.errors.deadlift" class="mt-1 text-xs text-red-400">{{ form.errors.deadlift }}</p>
      </label>
      <label class="text-xs text-slate-400">
        Date
        <input
          v-model="form.reference_date"
          type="date"
          class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
        />
        <p v-if="form.errors.reference_date" class="mt-1 text-xs text-red-400">{{ form.errors.reference_date }}</p>
      </label>
      <div class="sm:col-span-4">
        <button
          type="submit"
          :disabled="form.processing"
          class="rounded-lg bg-blue-600 px-4 py-2 text-xs font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Enregistrer
        </button>
      </div>
    </div>
  </form>
</template>
