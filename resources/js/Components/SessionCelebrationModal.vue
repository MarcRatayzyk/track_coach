<script setup>
import { computed, ref, watch } from 'vue';
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
        class="fixed inset-0 z-[80] flex min-h-[100dvh] flex-col bg-gradient-to-b from-emerald-950 via-slate-950 to-blue-950 text-white"
        role="dialog"
        aria-modal="true"
      >
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
          <div class="absolute -left-20 top-1/4 h-72 w-72 rounded-full bg-emerald-500/20 blur-3xl" />
          <div class="absolute -right-16 bottom-1/4 h-80 w-80 rounded-full bg-blue-500/20 blur-3xl" />
          <span
            v-for="particle in 32"
            :key="particle"
            class="tc-celebration-particle absolute block h-2 w-2 rounded-full"
            :style="{
              left: `${(particle * 17) % 100}%`,
              top: `${(particle * 23) % 100}%`,
              animationDelay: `${(particle % 8) * 0.12}s`,
              background:
                particle % 3 === 0
                  ? 'rgb(52, 211, 153)'
                  : particle % 3 === 1
                    ? 'rgb(96, 165, 250)'
                    : 'rgb(251, 191, 36)',
            }"
          />
        </div>

        <header
          class="relative z-10 flex items-center justify-end px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]"
        >
          <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full border border-white/10 bg-white/5 text-xl text-slate-300 transition hover:bg-white/10 hover:text-white"
            aria-label="Fermer"
            @click="close"
          >
            ✕
          </button>
        </header>

        <div class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 pb-[max(1.5rem,env(safe-area-inset-bottom))]">
          <Transition
            appear
            enter-active-class="duration-700 ease-out"
            enter-from-class="opacity-0 translate-y-8 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
          >
            <div v-if="open && celebration" class="w-full max-w-lg text-center">
              <div
                class="mx-auto flex h-20 w-20 items-center justify-center rounded-3xl border border-emerald-400/40 bg-emerald-500/20 text-4xl shadow-2xl shadow-emerald-500/25"
              >
                ✓
              </div>

              <p class="mt-8 text-xs font-bold uppercase tracking-[0.32em] text-emerald-300">
                Séance validée
              </p>
              <h2 class="mt-4 text-4xl font-bold leading-tight sm:text-5xl">
                {{ celebration.sessionTitle }}
              </h2>
              <p class="mt-4 text-lg font-medium text-emerald-100/90 sm:text-xl">
                {{ celebration.topsetSubtitle }}
              </p>

              <div
                class="mt-10 flex items-center justify-center gap-4 rounded-2xl border border-white/10 bg-slate-950/40 px-6 py-5 backdrop-blur-sm"
              >
                <div class="min-w-0 flex-1 text-center">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Tonnage</p>
                  <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">
                    {{ celebration.tonnageLabel }}
                  </p>
                </div>
                <div class="h-12 w-px bg-slate-600" />
                <div class="min-w-0 flex-1 text-center">
                  <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Reps</p>
                  <p class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">
                    {{ celebration.repsLabel }}
                  </p>
                </div>
              </div>

              <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-5 py-3 text-sm font-semibold text-slate-950 transition hover:bg-emerald-400"
                  @click="nativeShare"
                >
                  <UiIcon name="share" class="h-4 w-4" />
                  Partager
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-pink-500/40 bg-pink-500/15 px-5 py-3 text-sm font-semibold text-pink-100 transition hover:bg-pink-500/25"
                  @click="shareForInstagram"
                >
                  <span class="text-base leading-none">📸</span>
                  Instagram
                </button>
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-xl border border-slate-500 bg-slate-900/60 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-400"
                  @click="copyShareText"
                >
                  Copier
                </button>
              </div>

              <p v-if="shareFeedback" class="mt-4 text-sm text-emerald-300">
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
  opacity: 0.65;
}

@keyframes tc-celebration-float {
  0% {
    transform: translate3d(0, 0, 0) scale(0.8);
    opacity: 0.2;
  }
  100% {
    transform: translate3d(0, -18px, 0) scale(1.15);
    opacity: 0.9;
  }
}
</style>
