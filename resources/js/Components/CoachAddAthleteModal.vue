<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { track } from '../utils/analytics';

const { t } = useI18n();
const page = usePage();
const manualActivationLinks = computed(() => page.props.appConfig?.manualActivationLinks ?? false);

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'invited']);

const modalStep = ref('form');
const invitationUrl = ref('');
const invitationEmail = ref('');
const invitationEmailSent = ref(null);

const form = useForm({
  first_name: '',
  last_name: '',
  email: '',
  feedback_frequency: '',
});

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      modalStep.value = 'form';
      invitationUrl.value = '';
      invitationEmail.value = '';
      invitationEmailSent.value = null;
      form.clearErrors();
    }
  },
);

const inviteTitle = computed(() => {
  if (invitationEmail.value && invitationEmailSent.value !== false && !manualActivationLinks.value) {
    return t('modals.addAthlete.inviteSentTitle');
  }
  return t('modals.addAthlete.activationLinkTitle');
});

const inviteMessage = computed(() => {
  if (invitationEmail.value && invitationEmailSent.value === true && !manualActivationLinks.value) {
    return t('modals.addAthlete.inviteEmailSent', { email: invitationEmail.value });
  }
  if (invitationEmail.value && invitationEmailSent.value === false && !manualActivationLinks.value) {
    return t('modals.addAthlete.inviteEmailFailed', { email: invitationEmail.value });
  }
  return t('modals.addAthlete.inviteLinkOnly');
});

function closeModal() {
  emit('update:modelValue', false);
  modalStep.value = 'form';
  invitationUrl.value = '';
  invitationEmail.value = '';
  invitationEmailSent.value = null;
}

function submitNewAthlete() {
  form.post('/coach/athletes', {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      track('athlete_invited');
      invitationUrl.value = page.props.flash?.first_login_url ?? '';
      invitationEmail.value = page.props.flash?.invitation_email ?? '';
      invitationEmailSent.value = page.props.flash?.invitation_email_sent ?? null;
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
    window.prompt(t('modals.addAthlete.copyPrompt'), invitationUrl.value);
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
            {{ modalStep === 'invite' ? inviteTitle : t('modals.addAthlete.title') }}
          </h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            :aria-label="t('common.close')"
            @click="closeModal"
          >
            ✕
          </button>
        </div>

        <template v-if="modalStep === 'form'">
          <p class="mt-3 text-slate-400">
            {{ t('modals.addAthlete.intro') }}
          </p>
          <form class="mt-4 space-y-4" @submit.prevent="submitNewAthlete">
            <label class="block text-sm font-medium text-slate-400">
              {{ t('modals.addAthlete.firstName') }}
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
              {{ t('modals.addAthlete.lastName') }}
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
              {{ t('modals.addAthlete.emailOptional') }}
              <span class="font-normal text-slate-500"> ({{ t('common.optional') }})</span>
              <input
                v-model="form.email"
                type="email"
                autocomplete="email"
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
                :placeholder="t('modals.addAthlete.emailPlaceholder')"
              >
              <p class="mt-1 text-xs text-slate-500">
                {{ t('modals.addAthlete.emailHint') }}
              </p>
              <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">
                {{ form.errors.email }}
              </p>
            </label>
            <label class="block text-sm font-medium text-slate-400">
              {{ t('modals.addAthlete.coachingType') }}
              <select
                v-model="form.feedback_frequency"
                required
                class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 text-white"
              >
                <option value="" disabled>{{ t('modals.addAthlete.choose') }}</option>
                <option value="daily">{{ t('modals.addAthlete.daily') }}</option>
                <option value="weekly">{{ t('modals.addAthlete.weekly') }}</option>
              </select>
              <p v-if="form.errors.feedback_frequency" class="mt-1 text-sm text-red-400">
                {{ form.errors.feedback_frequency }}
              </p>
            </label>

            <div class="flex flex-wrap gap-3 pt-2">
              <button
                type="submit"
                :disabled="form.processing"
                class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
              >
                {{ t('modals.addAthlete.createInvite') }}
              </button>
              <button
                type="button"
                class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
                @click="closeModal"
              >
                {{ t('common.cancel') }}
              </button>
            </div>
          </form>
        </template>

        <template v-else>
          <p class="mt-3 text-slate-400">
            {{ inviteMessage }}
          </p>
          <div
            v-if="invitationUrl"
            class="mt-4 rounded-xl border border-slate-700 bg-slate-950 p-3"
          >
            <p class="break-all font-mono text-xs text-slate-300">{{ invitationUrl }}</p>
          </div>
          <div class="mt-4 flex flex-wrap gap-3">
            <button
              v-if="invitationUrl"
              type="button"
              class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-500"
              @click="copyInvitation"
            >
              {{ t('modals.addAthlete.copyLink') }}
            </button>
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-6 py-3 font-medium text-slate-200 hover:bg-slate-800"
              @click="closeModal"
            >
              {{ t('common.close') }}
            </button>
          </div>
        </template>
      </div>
    </div>
  </Teleport>
</template>
