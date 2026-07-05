<script>
export default {
  layout: null,
};
</script>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineProps({
  status: {
    type: String,
    default: null,
  },
});

const form = useForm({});

function resend() {
  form.post('/email/verification-notification');
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 px-4 py-12 text-slate-100">
    <Head title="Vérifie ton e-mail" />
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
      <h1 class="text-2xl font-bold text-white">Vérifie ton e-mail</h1>
      <p class="mt-3 text-slate-400">
        Nous t’avons envoyé un lien de confirmation. Clique dessus pour accéder à ton dashboard.
      </p>

      <p
        v-if="status === 'verification-link-sent'"
        class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200"
      >
        Un nouveau lien de confirmation a été envoyé.
      </p>

      <button
        type="button"
        :disabled="form.processing"
        class="mt-8 w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        @click="resend"
      >
        Renvoyer l’e-mail
      </button>

      <button
        type="button"
        class="mt-4 w-full text-sm text-slate-500 hover:text-slate-300"
        @click="router.post('/logout')"
      >
        Se déconnecter
      </button>

      <p class="mt-6 text-center text-sm text-slate-500">
        <Link href="/" class="text-blue-400 hover:text-blue-300">Retour à l’accueil</Link>
      </p>
    </div>
  </div>
</template>
