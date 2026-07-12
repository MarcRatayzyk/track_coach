<script setup>
import { computed, ref, watch } from 'vue';
import { markCoachOnboardingDone } from '../utils/coachOnboarding';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'skip', 'add-athlete']);

const currentStep = ref(0);

const steps = [
  {
    icon: 'bolt',
    title: 'Bienvenue sur Track Coach',
    description:
      'Tu viens de créer ton espace coach. En quelques étapes, découvre comment structurer ton coaching powerlifting.',
    accent: 'text-blue-400',
    ring: 'border-blue-500/30 bg-blue-600/10',
  },
  {
    icon: 'users',
    title: 'Ta roster d’athlètes',
    description:
      'Ajoute tes athlètes : un lien d’activation est généré à partager (WhatsApp, SMS…).',
    accent: 'text-emerald-400',
    ring: 'border-emerald-500/30 bg-emerald-600/10',
  },
  {
    icon: 'clipboard',
    title: 'Programmes & blocs',
    description:
      'Construis des templates de force dans le Program Builder, puis assigne-les à chaque athlète avec des dates de début et de fin.',
    accent: 'text-violet-400',
    ring: 'border-violet-500/30 bg-violet-600/10',
  },
  {
    icon: 'video',
    title: 'Retours vidéo',
    description:
      'Tes athlètes envoient des vidéos de séance. Tu les retrouves sur le dashboard et tu peux y répondre via la messagerie.',
    accent: 'text-amber-400',
    ring: 'border-amber-500/30 bg-amber-600/10',
  },
  {
    icon: 'chat',
    title: 'Messagerie intégrée',
    description:
      'Échange avec chaque athlète sans quitter l’app — consignes, retours audio et suivi au quotidien.',
    accent: 'text-cyan-400',
    ring: 'border-cyan-500/30 bg-cyan-600/10',
  },
  {
    icon: 'trophy',
    title: 'C’est parti !',
    description:
      'Commence par inviter ton premier athlète. Le dashboard se remplira automatiquement au fur et à mesure.',
    accent: 'text-blue-400',
    ring: 'border-blue-500/30 bg-blue-600/10',
  },
];

const step = computed(() => steps[currentStep.value]);
const isLastStep = computed(() => currentStep.value === steps.length - 1);
const isFirstStep = computed(() => currentStep.value === 0);

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      currentStep.value = 0;
    }
  },
);

function close() {
  emit('update:modelValue', false);
}

function skip() {
  markCoachOnboardingDone();
  emit('skip');
  close();
}

function next() {
  if (isLastStep.value) {
    markCoachOnboardingDone();
    emit('add-athlete');
    close();
    return;
  }
  currentStep.value += 1;
}

function back() {
  if (!isFirstStep.value) {
    currentStep.value -= 1;
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/85 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="onboarding-title"
    >
      <div
        class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl lg:p-8"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">
            Découverte · {{ currentStep + 1 }} / {{ steps.length }}
          </p>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="skip"
          >
            Passer
          </button>
        </div>

        <div
          class="mt-6 flex h-14 w-14 items-center justify-center rounded-2xl border"
          :class="step.ring"
        >
          <UiIcon :name="step.icon" class="h-7 w-7" :class="step.accent" />
        </div>

        <h2 id="onboarding-title" class="mt-5 text-2xl font-bold text-white">
          {{ step.title }}
        </h2>
        <p class="mt-3 leading-relaxed text-slate-400">
          {{ step.description }}
        </p>

        <div class="mt-8 flex gap-1.5">
          <span
            v-for="(_, index) in steps"
            :key="index"
            class="h-1.5 flex-1 rounded-full transition"
            :class="index <= currentStep ? 'bg-blue-500' : 'bg-slate-700'"
          />
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-between gap-3">
          <button
            v-if="!isFirstStep"
            type="button"
            class="rounded-xl border border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
            @click="back"
          >
            Retour
          </button>
          <span v-else />

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
              @click="skip"
            >
              Passer
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-500"
              @click="next"
            >
              {{ isLastStep ? 'Ajouter un athlète' : 'Suivant' }}
              <span v-if="!isLastStep" aria-hidden="true">→</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
