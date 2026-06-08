<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  athletes: {
    type: Array,
    default: () => [],
  },
  existingBlocks: {
    type: Array,
    default: () => [],
  },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  athlete_id: props.athletes[0]?.id ?? '',
  name: '',
  week_count: 4,
  date_start: today,
});

function submit() {
  form.post('/coach/program-blocks', { preserveScroll: true });
}

const deletingId = ref(null);

function openBlock(assignmentId) {
  router.get('/program-builder', { assignment: assignmentId }, { preserveState: false });
}

function deleteBlock(block) {
  if (
    !window.confirm(
      `Supprimer le bloc « ${block.name} » ? Toutes les séances programmées seront perdues.`,
    )
  ) {
    return;
  }

  deletingId.value = block.id;
  router.delete(`/coach/program-blocks/${block.id}`, {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null;
    },
  });
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <h2 class="text-lg font-semibold text-white">Nouveau bloc</h2>
    <p class="mt-2 text-sm text-slate-400">
      Choisis l’athlète, la durée et la date de début, puis programme les séances sur le calendrier.
    </p>

    <form class="mt-5 grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
      <label class="block text-sm text-slate-400 sm:col-span-2">
        Athlète
        <select
          v-model="form.athlete_id"
          required
          class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
        >
          <option v-for="athlete in athletes" :key="athlete.id" :value="athlete.id">
            {{ athlete.name }}
          </option>
        </select>
      </label>
      <label class="block text-sm text-slate-400">
        Nom du bloc
        <input
          v-model="form.name"
          type="text"
          required
          placeholder="Ex. Bloc force prépa compète"
          class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
        />
      </label>
      <label class="block text-sm text-slate-400">
        Nombre de semaines
        <input
          v-model.number="form.week_count"
          type="number"
          min="1"
          max="16"
          required
          class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
        />
      </label>
      <label class="block text-sm text-slate-400 sm:col-span-2">
        Date de début
        <input
          v-model="form.date_start"
          type="date"
          required
          class="mt-2 w-full max-w-xs rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
        />
        <span class="mt-1 block text-xs text-slate-500">
          Le calendrier affiche des semaines du lundi au dimanche (semaine contenant cette date).
        </span>
      </label>

      <p v-if="Object.keys(form.errors).length" class="text-sm text-red-400 sm:col-span-2">
        {{ Object.values(form.errors).flat().join(' ') }}
      </p>

      <div class="sm:col-span-2">
        <button
          type="submit"
          :disabled="form.processing || !athletes.length"
          class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-blue-500 disabled:opacity-50"
        >
          Créer le bloc
        </button>
      </div>
    </form>

    <div v-if="existingBlocks.length" class="mt-8 border-t border-slate-800 pt-6">
      <h3 class="text-sm font-semibold text-white">Reprendre un bloc</h3>
      <ul class="mt-3 space-y-2">
        <li
          v-for="block in existingBlocks"
          :key="block.id"
          class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
        >
          <div>
            <p class="font-medium text-white">{{ block.name }}</p>
            <p class="mt-1 text-sm text-slate-400">
              {{ block.athlete_name }} · {{ block.week_count }} sem. · du
              {{ block.date_start }}
              <span v-if="block.date_end"> au {{ block.date_end }}</span>
              <span
                v-if="block.status === 'draft'"
                class="ml-1 rounded bg-amber-500/15 px-1.5 py-0.5 text-xs text-amber-300"
              >
                Brouillon
              </span>
            </p>
          </div>
          <div class="flex shrink-0 flex-wrap gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm font-medium text-blue-300 hover:bg-slate-800"
              @click="openBlock(block.id)"
            >
              Ouvrir
            </button>
            <button
              type="button"
              class="rounded-lg border border-red-500/40 px-3 py-1.5 text-sm font-medium text-red-300 hover:bg-red-950/40 disabled:opacity-50"
              :disabled="deletingId === block.id"
              @click="deleteBlock(block)"
            >
              {{ deletingId === block.id ? 'Suppression…' : 'Supprimer' }}
            </button>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
