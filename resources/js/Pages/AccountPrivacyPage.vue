<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { resetAnalytics, track } from '../utils/analytics';

const showDeleteConfirm = ref(false);

const deleteForm = useForm({
  password: '',
});

function submitDelete() {
  track('account_deleted');
  resetAnalytics();
  deleteForm.delete('/account', {
    preserveScroll: true,
    onError: () => {
      showDeleteConfirm.value = true;
    },
  });
}
</script>

<template>
  <div class="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6">
    <Head title="Confidentialité et données" />

    <header class="mb-8">
      <h1 class="text-2xl font-bold tracking-tight text-white">Confidentialité et données</h1>
      <p class="mt-2 text-sm text-slate-400">
        Gère tes données personnelles conformément au RGPD : télécharge une copie de tes
        informations ou supprime définitivement ton compte.
      </p>
    </header>

    <section class="mb-6 rounded-2xl border border-slate-700/80 bg-slate-800/40 p-6">
      <h2 class="text-lg font-semibold text-white">Exporter mes données</h2>
      <p class="mt-2 text-sm text-slate-400">
        Télécharge l'ensemble des données que nous conservons sur toi (profil, entraînements,
        records, messages…) au format JSON.
      </p>
      <a
        href="/account/data-export"
        class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
      >
        Télécharger mes données (JSON)
      </a>
    </section>

    <section class="rounded-2xl border border-red-500/40 bg-red-950/20 p-6">
      <h2 class="text-lg font-semibold text-red-300">Supprimer mon compte</h2>
      <p class="mt-2 text-sm text-slate-400">
        Cette action est <strong class="text-red-300">irréversible</strong>. Ton compte, ton profil,
        tes programmes, retours vidéo, messages et médias associés seront définitivement effacés.
      </p>

      <button
        v-if="!showDeleteConfirm"
        type="button"
        class="mt-4 inline-flex items-center gap-2 rounded-xl border border-red-500/60 px-4 py-2.5 text-sm font-semibold text-red-300 transition hover:bg-red-500/10"
        @click="showDeleteConfirm = true"
      >
        Supprimer mon compte
      </button>

      <form v-else class="mt-4 space-y-4" @submit.prevent="submitDelete">
        <div>
          <label for="delete-password" class="block text-sm font-medium text-slate-300">
            Confirme ton mot de passe
          </label>
          <input
            id="delete-password"
            v-model="deleteForm.password"
            type="password"
            autocomplete="current-password"
            class="mt-1 block w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-white focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
          />
          <p v-if="deleteForm.errors.password" class="mt-1 text-sm text-red-400">
            {{ deleteForm.errors.password }}
          </p>
        </div>

        <div class="flex flex-wrap gap-3">
          <button
            type="submit"
            :disabled="deleteForm.processing"
            class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-60"
          >
            {{ deleteForm.processing ? 'Suppression…' : 'Confirmer la suppression définitive' }}
          </button>
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-slate-800"
            @click="showDeleteConfirm = false"
          >
            Annuler
          </button>
        </div>
      </form>
    </section>

    <p class="mt-8 text-sm text-slate-500">
      Consulte notre
      <Link href="/confidentialite" class="text-blue-400 hover:underline">politique de confidentialité</Link>
      pour en savoir plus sur le traitement de tes données.
    </p>
  </div>
</template>
