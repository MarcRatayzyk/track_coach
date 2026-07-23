<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { track } from '../utils/analytics';

const props = defineProps({
  athletes: {
    type: Array,
    default: () => [],
  },
  existingBlocks: {
    type: Array,
    default: () => [],
  },
  starterPrograms: {
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
  form.post('/coach/program-blocks', {
    preserveScroll: true,
    onSuccess: () => {
      track('program_created', { source: 'blank' });
    },
  });
}

const starterForm = useForm({
  key: props.starterPrograms[0]?.key ?? '',
  athlete_id: props.athletes[0]?.id ?? '',
  date_start: today,
});

function createStarter() {
  starterForm.post('/coach/program-starters', {
    preserveScroll: true,
    onSuccess: () => {
      track('starter_program_used', { starter_key: starterForm.key });
    },
  });
}

const deletingId = ref(null);
const duplicatingId = ref(null);
const bulkForBlockId = ref(null);
const bulkAthleteIds = ref([]);
const bulkProcessing = ref(false);
const bulkError = ref('');

function openBlock(assignmentId) {
  router.get('/program-builder', { assignment: assignmentId }, { preserveState: false });
}

function toggleBulkPanel(block) {
  if (bulkForBlockId.value === block.id) {
    bulkForBlockId.value = null;
    return;
  }
  bulkForBlockId.value = block.id;
  bulkAthleteIds.value = [];
  bulkError.value = '';
}

function submitBulkAssign(block) {
  if (!bulkAthleteIds.value.length) {
    bulkError.value = 'Sélectionne au moins un athlète.';
    return;
  }

  bulkProcessing.value = true;
  bulkError.value = '';
  const athleteCount = bulkAthleteIds.value.length;
  router.post(
    `/coach/program-blocks/${block.id}/bulk-assign`,
    { athlete_ids: bulkAthleteIds.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        track('program_bulk_assigned', {
          athlete_count: athleteCount,
          block_id: block.id,
        });
        bulkForBlockId.value = null;
        bulkAthleteIds.value = [];
      },
      onError: (errors) => {
        bulkError.value = Object.values(errors).flat().join(' ');
      },
      onFinish: () => {
        bulkProcessing.value = false;
      },
    },
  );
}

function duplicateBlock(block) {
  duplicatingId.value = block.id;
  router.post(
    `/coach/program-blocks/${block.id}/duplicate`,
    {},
    {
      preserveScroll: true,
      onFinish: () => {
        duplicatingId.value = null;
      },
    },
  );
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

    <div v-if="starterPrograms.length && athletes.length" class="mt-8 border-t border-slate-800 pt-6">
      <h3 class="text-sm font-semibold text-white">Partir d'un modèle prêt à l'emploi</h3>
      <p class="mt-1 text-sm text-slate-400">
        Gagne du temps : choisis une trame éprouvée, elle sera créée en brouillon que tu pourras
        ajuster puis assigner.
      </p>

      <form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="createStarter">
        <label class="block text-sm text-slate-400 sm:col-span-2">
          Modèle
          <select
            v-model="starterForm.key"
            required
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          >
            <option v-for="starter in starterPrograms" :key="starter.key" :value="starter.key">
              {{ starter.name }} · {{ starter.week_count }} sem.
            </option>
          </select>
          <span
            v-if="starterPrograms.find((s) => s.key === starterForm.key)?.summary"
            class="mt-1 block text-xs text-slate-500"
          >
            {{ starterPrograms.find((s) => s.key === starterForm.key)?.summary }}
          </span>
        </label>
        <label class="block text-sm text-slate-400">
          Athlète
          <select
            v-model="starterForm.athlete_id"
            required
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          >
            <option v-for="athlete in athletes" :key="athlete.id" :value="athlete.id">
              {{ athlete.name }}
            </option>
          </select>
        </label>
        <label class="block text-sm text-slate-400">
          Date de début
          <input
            v-model="starterForm.date_start"
            type="date"
            required
            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
          />
        </label>

        <p v-if="Object.keys(starterForm.errors).length" class="text-sm text-red-400 sm:col-span-2">
          {{ Object.values(starterForm.errors).flat().join(' ') }}
        </p>

        <div class="sm:col-span-2">
          <button
            type="submit"
            :disabled="starterForm.processing"
            class="rounded-xl border border-blue-500/50 bg-blue-600/10 px-4 py-2 text-sm font-semibold text-blue-200 hover:bg-blue-600/20 disabled:opacity-50"
          >
            {{ starterForm.processing ? 'Création…' : 'Créer depuis ce modèle' }}
          </button>
        </div>
      </form>
    </div>

    <div v-if="existingBlocks.length" class="mt-8 border-t border-slate-800 pt-6">
      <h3 class="text-sm font-semibold text-white">Reprendre un bloc</h3>
      <ul class="mt-3 space-y-2">
        <li
          v-for="block in existingBlocks"
          :key="block.id"
          class="flex flex-col gap-3 rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3"
        >
          <div class="flex w-full flex-wrap items-center justify-between gap-3">
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
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800 disabled:opacity-50"
                :disabled="duplicatingId === block.id"
                @click="duplicateBlock(block)"
              >
                {{ duplicatingId === block.id ? 'Duplication…' : 'Dupliquer' }}
              </button>
              <button
                v-if="athletes.length > 1"
                type="button"
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
                @click="toggleBulkPanel(block)"
              >
                Assigner à plusieurs
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
          </div>

          <div
            v-if="bulkForBlockId === block.id"
            class="rounded-xl border border-slate-700/70 bg-slate-900/60 p-4"
          >
            <p class="text-sm font-medium text-white">Assigner « {{ block.name }} » à plusieurs athlètes</p>
            <p class="mt-1 text-xs text-slate-500">
              Chaque athlète reçoit une copie indépendante, activée immédiatement (son bloc actif
              précédent est archivé).
            </p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2">
              <label
                v-for="athlete in athletes"
                :key="athlete.id"
                class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-sm text-slate-200"
              >
                <input
                  v-model="bulkAthleteIds"
                  type="checkbox"
                  :value="athlete.id"
                  class="h-4 w-4 rounded border-slate-600 bg-slate-900 text-blue-500"
                />
                {{ athlete.name }}
              </label>
            </div>
            <p v-if="bulkError" class="mt-2 text-sm text-red-400">{{ bulkError }}</p>
            <div class="mt-3 flex gap-2">
              <button
                type="button"
                :disabled="bulkProcessing"
                class="rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                @click="submitBulkAssign(block)"
              >
                {{ bulkProcessing ? 'Assignation…' : `Assigner (${bulkAthleteIds.length})` }}
              </button>
              <button
                type="button"
                class="rounded-lg border border-slate-700 px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
                @click="bulkForBlockId = null"
              >
                Annuler
              </button>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
