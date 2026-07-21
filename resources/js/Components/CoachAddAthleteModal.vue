<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ReadinessFormBuilderModal from './ReadinessFormBuilderModal.vue';
import {
  cloneFields,
  defaultReadinessFields,
} from '../config/readinessFormFields';

const page = usePage();
const manualActivationLinks = computed(() => page.props.appConfig?.manualActivationLinks ?? true);

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  coachReadinessForm: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['update:modelValue', 'invited']);

const modalStep = ref('form');
const invitationUrl = ref('');
const showReadinessBuilder = ref(false);
const readinessFields = ref(defaultReadinessFields());

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  feedback_frequency: 'weekly',
  fields: [],
});

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      modalStep.value = 'form';
      invitationUrl.value = '';
      readinessFields.value = cloneFields(
        props.coachReadinessForm?.fields?.length
          ? props.coachReadinessForm.fields
          : defaultReadinessFields(),
      );
      form.clearErrors();
    }
  },
);

function closeModal() {
  emit('update:modelValue', false);
  modalStep.value = 'form';
  invitationUrl.value = '';
}

function onReadinessFieldsSaved(fields) {
  readinessFields.value = cloneFields(fields);
}

function submitNewAthlete() {
  form.fields = readinessFields.value;
  form.post('/coach/athletes', {
    preserveScroll: true,
    onSuccess: (page) => {
      invitationUrl.value = page.props.flash?.first_login_url ?? '';
      modalStep.value = 'invite';
      form.reset();
      form.clearErrors();
      emit('invited');
    },
  });
}

async function copyInvitation() {
  if (!invitationUrl.value) {
    return;
  }
  try {
    await navigator.clipboard.writeText(invitationUrl.value);
  } catch {
    window.prompt('Copie ce lien :', invitationUrl.value);
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="closeModal"
    >
      <div
        class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl lg:p-8"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <h2 class="text-base font-semibold text-white">
            {{ modalStep === 'invite' ? (manualActivationLinks ? 'Lien d’activation' : 'Invitation envoyée') : 'Nouvel athlète' }}
          </h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            aria-label="Fermer"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <template v-if="modalStep === 'form'">
          <p class="mt-3 text-slate-400">
            Renseigne le prénom, le nom et l’e-mail. Un lien d’activation sera généré pour que
            l’athlète choisisse son mot de passe et complète son profil.
          </p>
          <form class="mt-4 space-y-4" @submit.prevent="submitNewAthlete">
            <label class="block text-sm font-medium text-slate-400">
              Prénom
              <input
                v-model="form.first_name"
                type="text"
                required
                autocomplete="given-name"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
              <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-400">
                {{ form.errors.first_name }}
              </p>
            </label>
            <label class="block text-sm font-medium text-slate-400">
              Nom
              <input
                v-model="form.last_name"
                type="text"
                required
                autocomplete="family-name"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
              <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-400">
                {{ form.errors.last_name }}
              </p>
            </label>
            <label class="block text-sm font-medium text-slate-400">
              E-mail (identifiant de connexion)
              <input
                v-model="form.email"
                type="email"
                required
                autocomplete="email"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">
                {{ form.errors.email }}
              </p>
            </label>
            <label class="block text-sm font-medium text-slate-400">
              Suivi des retours
              <select
                v-model="form.feedback_frequency"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
                <option value="daily">Journalier (retour à chaque séance)</option>
                <option value="weekly">Hebdomadaire (1 retour par semaine)</option>
              </select>
            </label>

            <div class="rounded-xl border border-slate-700 bg-slate-950/50 p-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <p class="text-sm font-medium text-white">Formulaire readiness</p>
                  <p class="mt-1 text-xs text-slate-400">
                    {{ readinessFields.length }} champ{{ readinessFields.length > 1 ? 's' : '' }} —
                    prérempli depuis ton modèle par défaut.
                  </p>
                </div>
                <button
                  type="button"
                  class="shrink-0 rounded-lg border border-blue-500/40 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-200 hover:bg-blue-500/20"
                  @click="showReadinessBuilder = true"
                >
                  Personnaliser
                </button>
              </div>
              <ul class="mt-2 flex flex-wrap gap-1.5">
                <li
                  v-for="field in readinessFields"
                  :key="field.id"
                  class="rounded-md border border-slate-700 bg-slate-900 px-2 py-0.5 text-[10px] uppercase tracking-wide text-slate-300"
                >
                  {{ field.label }}
                </li>
              </ul>
              <p v-if="form.errors.fields" class="mt-2 text-sm text-red-400">
                {{ form.errors.fields }}
              </p>
            </div>

            <div class="flex flex-wrap gap-3 pt-2">
              <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                Créer et inviter
              </button>
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
                @click="closeModal"
              >
                Annuler
              </button>
            </div>
          </form>
        </template>

        <template v-else>
          <p class="mt-3 text-slate-400">
            <template v-if="manualActivationLinks">
              Copie le lien ci-dessous et envoie-le à l’athlète (WhatsApp, SMS, etc.). Valable 14
              jours.
            </template>
            <template v-else>
              L’invitation a été envoyée par e-mail. Tu peux aussi copier le lien ci-dessous en
              secours (valable 14 jours).
            </template>
          </p>
          <div class="mt-4 rounded-xl border border-slate-700 bg-slate-950 p-3">
            <p class="break-all font-mono text-xs text-slate-300">{{ invitationUrl }}</p>
          </div>
          <div class="mt-4 flex flex-wrap gap-3">
            <button
              type="button"
              class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-500"
              @click="copyInvitation"
            >
              Copier le lien
            </button>
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
              @click="closeModal"
            >
              Fermer
            </button>
          </div>
        </template>
      </div>
    </div>
  </Teleport>

  <ReadinessFormBuilderModal
    :open="showReadinessBuilder"
    mode="local"
    title="Formulaire readiness de cet athlète"
    :initial-fields="readinessFields"
    @close="showReadinessBuilder = false"
    @save-local="onReadinessFieldsSaved"
  />
</template>
