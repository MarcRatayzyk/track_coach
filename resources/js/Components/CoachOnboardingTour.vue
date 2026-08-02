<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { markCoachOnboardingDone } from '../utils/coachOnboarding';
import { track } from '../utils/analytics';
import AppLogo from './AppLogo.vue';
import UiIcon from './UiIcon.vue';

const { t } = useI18n();

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'skip', 'add-athlete']);

const currentStep = ref(0);

const steps = computed(() => [
  {
    icon: 'logo',
    title: t('modals.onboarding.steps.welcomeTitle'),
    description: t('modals.onboarding.steps.welcomeDesc'),
    accent: 'text-blue-400',
    ring: 'border-blue-500/30 bg-blue-600/10',
  },
  {
    icon: 'users',
    title: t('modals.onboarding.steps.rosterTitle'),
    description: t('modals.onboarding.steps.rosterDesc'),
    accent: 'text-emerald-400',
    ring: 'border-emerald-500/30 bg-emerald-600/10',
  },
  {
    icon: 'clipboard',
    title: t('modals.onboarding.steps.programsTitle'),
    description: t('modals.onboarding.steps.programsDesc'),
    accent: 'text-violet-400',
    ring: 'border-violet-500/30 bg-violet-600/10',
  },
  {
    icon: 'video',
    title: t('modals.onboarding.steps.videoTitle'),
    description: t('modals.onboarding.steps.videoDesc'),
    accent: 'text-amber-400',
    ring: 'border-amber-500/30 bg-amber-600/10',
  },
  {
    icon: 'chat',
    title: t('modals.onboarding.steps.messagingTitle'),
    description: t('modals.onboarding.steps.messagingDesc'),
    accent: 'text-cyan-400',
    ring: 'border-cyan-500/30 bg-cyan-600/10',
  },
  {
    icon: 'trophy',
    title: t('modals.onboarding.steps.readyTitle'),
    description: t('modals.onboarding.steps.readyDesc'),
    accent: 'text-blue-400',
    ring: 'border-blue-500/30 bg-blue-600/10',
  },
]);

const step = computed(() => steps.value[currentStep.value]);
const isLastStep = computed(() => currentStep.value === steps.value.length - 1);
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
  track('onboarding_tour_skipped', { step: currentStep.value });
  emit('skip');
  close();
}

function next() {
  if (isLastStep.value) {
    markCoachOnboardingDone();
    track('onboarding_tour_completed');
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
            {{ t('modals.onboarding.discovery', { current: currentStep + 1, total: steps.length }) }}
          </p>
          <button
            type="button"
            class="rounded-lg px-2 py-1 text-sm text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="skip"
          >
            {{ t('modals.onboarding.skip') }}
          </button>
        </div>

        <div
          v-if="step.icon === 'logo'"
          class="mt-6"
        >
          <AppLogo :with-wordmark="false" mark-class="h-16 w-16" />
        </div>
        <div
          v-else
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
            {{ t('common.back') }}
          </button>
          <span v-else />

          <div class="flex flex-wrap gap-3">
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
              @click="skip"
            >
              {{ t('modals.onboarding.skip') }}
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-500"
              @click="next"
            >
              {{ isLastStep ? t('modals.onboarding.addAthlete') : t('common.next') }}
              <span v-if="!isLastStep" aria-hidden="true">→</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
