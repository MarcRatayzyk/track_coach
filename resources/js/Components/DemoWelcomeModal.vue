<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const open = ref(false);

const welcome = computed(() => page.props.flash?.demo_welcome ?? null);

watch(
  welcome,
  (value) => {
    open.value = Boolean(value);
  },
  { immediate: true },
);

function close() {
  open.value = false;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && welcome"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <div
        class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl lg:p-8"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <h2 class="text-base font-semibold text-white">Sandbox démo activée</h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            ✕
          </button>
        </div>

        <p class="mt-3 text-slate-400">
          Tu disposes de <strong class="text-slate-200">{{ welcome.hours }} heures</strong>
          <template v-if="welcome.expires_label">
            (expire le {{ welcome.expires_label }})
          </template>
          pour explorer Power Roster.
        </p>
        <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-400">
          <li>Des athlètes et données de démo sont déjà prêts.</li>
          <li>Tu ne peux pas ajouter de vrais athlètes en mode démo.</li>
          <li>Un e-mail de confirmation a été envoyé à {{ welcome.email }}.</li>
        </ul>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500"
            @click="close"
          >
            Explorer le dashboard
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
