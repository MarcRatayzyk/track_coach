<script setup>
import { computed, ref, watch } from 'vue';
import CelebrationBarbell from './CelebrationBarbell.vue';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  celebration: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const shareFeedback = ref('');

const shareUrl = computed(() => {
  if (typeof window === 'undefined') {
    return '';
  }
  return `${window.location.origin}/athlete/dashboard`;
});

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      shareFeedback.value = '';
    }
  },
);

function close() {
  emit('close');
}

async function copyShareText() {
  if (!props.celebration?.shareText || typeof navigator === 'undefined') {
    return;
  }

  try {
    await navigator.clipboard.writeText(props.celebration.shareText);
    shareFeedback.value = 'Texte copié.';
  } catch (_error) {
    shareFeedback.value = 'Copie impossible sur cet appareil.';
  }
}

async function nativeShare() {
  if (!props.celebration || typeof navigator === 'undefined') {
    return;
  }

  const shareData = {
    title: props.celebration.sessionTitle,
    text: props.celebration.shareText,
    url: shareUrl.value,
  };

  try {
    if (navigator.share) {
      await navigator.share(shareData);
      shareFeedback.value = 'Partage envoyé.';
      return;
    }

    await copyShareText();
  } catch (_error) {
    if (_error?.name !== 'AbortError') {
      shareFeedback.value = 'Partage non disponible.';
    }
  }
}

async function shareForInstagram() {
  await copyShareText();
  shareFeedback.value = 'Texte copié — colle-le dans ta story Instagram.';
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="duration-500 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="duration-300 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open && celebration"
        class="fixed inset-0 z-[80] flex min-h-[100dvh] flex-col bg-gradient-to-b from-black via-slate-950 to-red-950 text-white"
        role="dialog"
        aria-modal="true"
      >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
          <div class="absolute -left-24 top-1/4 h-80 w-80 rounded-full bg-red-600/15 blur-3xl" />
          <div class="absolute -right-20 bottom-1/3 h-72 w-72 rounded-full bg-red-900/25 blur-3xl" />
          <span
            v-for="particle in 28"
            :key="particle"
            class="tc-celebration-particle absolute block h-1.5 w-1.5 rounded-full"
            :style="{
              left: `${(particle * 17) % 100}%`,
              top: `${(particle * 23) % 100}%`,
              animationDelay: `${(particle % 8) * 0.12}s`,
              background:
                particle % 3 === 0
                  ? 'rgb(239, 68, 68)'
                  : particle % 3 === 1
                    ? 'rgb(148, 163, 184)'
                    : 'rgb(220, 38, 38)',
            }"
          />
        </div>

        <header
          class="relative z-10 flex items-center justify-end px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]"
        >
          <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full border border-red-500/30 bg-red-950/40 text-xl text-red-100 transition hover:bg-red-900/50 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            ✕
          </button>
        </header>

        <div
          class="relative z-10 flex flex-1 flex-col items-center justify-center px-4 pb-[max(1.25rem,env(safe-area-inset-bottom))]"
        >
          <Transition
            appear
            enter-active-class="duration-700 ease-out"
            enter-from-class="opacity-0 translate-y-8 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
          >
            <div v-if="open && celebration" class="w-full max-w-lg text-center">
              <div
                class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl border border-red-500/50 bg-red-600/20 text-4xl shadow-2xl shadow-red-600/30 sm:h-20 sm:w-20"
              >
                ✓
              </div>

              <p class="mt-6 text-xs font-bold uppercase tracking-[0.32em] text-red-400 sm:mt-8">
                Séance validée
              </p>
              <h2 class="mt-3 text-3xl font-bold leading-tight sm:mt-4 sm:text-5xl">
                {{ celebration.sessionTitle }}
              </h2>

              <div
                class="mt-5 rounded-2xl border border-red-500/30 bg-red-950/25 px-4 py-4 shadow-lg shadow-red-900/20 sm:mt-6"
              >
                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-red-400/90">
                  {{ (celebration.topsets?.length ?? 0) > 1 ? 'Topsets' : 'Topset' }}
                </p>

                <div v-if="(celebration.topsets?.length ?? 0) > 1" class="mt-3 space-y-3 sm:space-y-4">
                  <div
                    v-for="(topset, index) in celebration.topsets"
                    :key="`${topset.lift}-${index}`"
                    class="rounded-2xl border border-red-500/20 bg-black/30 px-3 py-3"
                  >
                    <p class="text-sm font-bold leading-snug tracking-wide text-white">
                      {{ topset.subtitle }}
                    </p>
                    <CelebrationBarbell :barbell="topset.barbell" />
                  </div>
                </div>

                <template v-else>
                  <p class="mt-2 text-lg font-bold leading-snug tracking-wide text-white sm:text-xl">
                    {{ celebration.topsetSubtitle }}
                  </p>
                  <CelebrationBarbell :barbell="celebration.barbell" />
                </template>
              </div>

              <div
                class="mt-6 grid grid-cols-3 gap-2 rounded-2xl border border-white/10 bg-black/50 px-3 py-4 backdrop-blur-sm sm:mt-8 sm:gap-3 sm:px-4"
              >
                <div class="min-w-0 text-center">
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Adhérence
                  </p>
                  <p class="mt-1 text-xl font-bold tabular-nums text-red-300 sm:text-2xl">
                    {{ celebration.adherenceLabel }}
                  </p>
                </div>
                <div class="min-w-0 border-x border-slate-800 text-center">
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Tonnage
                  </p>
                  <p class="mt-1 text-xl font-bold tabular-nums text-white sm:text-2xl">
                    {{ celebration.tonnageLabel }}
                  </p>
                </div>
                <div class="min-w-0 text-center">
                  <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                    Reps
                  </p>
                  <p class="mt-1 text-xl font-bold tabular-nums text-white sm:text-2xl">
                    {{ celebration.repsLabel }}
                  </p>
                </div>
              </div>

              <div class="mt-6 flex flex-wrap items-center justify-center gap-3 sm:mt-8">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-red-500"
                  @click="nativeShare"
                >
                  <UiIcon name="share" class="h-4 w-4" />
                  Partager
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-pink-500/40 bg-pink-500/10 px-5 py-3 text-sm font-semibold text-pink-100 transition hover:bg-pink-500/20"
                  @click="shareForInstagram"
                >
                  <span class="text-base leading-none">📸</span>
                  Instagram
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-600 bg-slate-900/80 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500"
                  @click="copyShareText"
                >
                  Copier
                </button>
              </div>

              <p v-if="shareFeedback" class="mt-4 text-sm text-red-300">
                {{ shareFeedback }}
              </p>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.tc-celebration-particle {
  animation: tc-celebration-float 2.8s ease-in-out infinite alternate;
  opacity: 0.55;
}

@keyframes tc-celebration-float {
  0% {
    transform: translate3d(0, 0, 0) scale(0.8);
    opacity: 0.15;
  }
  100% {
    transform: translate3d(0, -18px, 0) scale(1.15);
    opacity: 0.85;
  }
}
</style>
