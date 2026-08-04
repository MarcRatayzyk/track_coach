<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  awards: {
    type: Object,
    default: null,
  },
  theme: {
    type: Object,
    default: null,
  },
  copy: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const slideIndex = ref(0);

function parseHex(hex) {
  if (!hex || typeof hex !== 'string') {
    return null;
  }
  let h = hex.trim().replace('#', '');
  if (h.length === 3) {
    h = h.split('').map((c) => c + c).join('');
  }
  if (!/^[0-9a-fA-F]{6}$/.test(h)) {
    return null;
  }
  const n = Number.parseInt(h, 16);
  return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255, hex: `#${h}` };
}

function gradientFromHex(hex) {
  const rgb = parseHex(hex);
  if (!rgb) {
    return null;
  }
  return `linear-gradient(to bottom, rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.82), #020617 55%, #020617)`;
}

const slides = computed(() => {
  const data = props.awards;
  if (!data?.screens?.length) {
    return [];
  }

  return [
    {
      id: 'intro',
      kind: 'intro',
      title: t('modals.rosterAwards.title'),
      subtitle: data.month_label ?? t('modals.rosterAwards.thisMonth'),
      hint: props.copy?.intro_hint || t('modals.rosterAwards.hint'),
    },
    ...data.screens,
    {
      id: 'outro',
      kind: 'outro',
      title: props.copy?.outro_title || t('modals.rosterAwards.outroTitle'),
      subtitle: props.copy?.outro_subtitle || t('modals.rosterAwards.outroSubtitle'),
    },
  ];
});

const currentSlide = computed(() => slides.value[slideIndex.value] ?? null);
const isFirst = computed(() => slideIndex.value <= 0);
const isLast = computed(() => slideIndex.value >= slides.value.length - 1);

watch(
  () => props.open,
  (open) => {
    if (open) {
      slideIndex.value = 0;
    }
  },
);

function close() {
  emit('close');
}

function next() {
  if (isLast.value) {
    close();
    return;
  }
  slideIndex.value += 1;
}

function prev() {
  if (!isFirst.value) {
    slideIndex.value -= 1;
  }
}

const activeAccentHex = computed(() => {
  if (!props.theme) {
    return null;
  }
  const key = currentSlide.value?.award_key;
  if (key && props.theme[key]) {
    return props.theme[key];
  }
  return props.theme.default_accent || null;
});

const awardAccent = computed(() => {
  if (activeAccentHex.value) {
    return '';
  }
  const key = currentSlide.value?.award_key;
  if (key === 'steps') {
    return 'from-emerald-900/80 via-slate-950 to-slate-950';
  }
  if (key === 'kcal') {
    return 'from-amber-900/80 via-slate-950 to-slate-950';
  }
  if (key === 'sommeil') {
    return 'from-sky-900/80 via-slate-950 to-slate-950';
  }
  return 'from-violet-950 via-slate-950 to-slate-950';
});

const bgStyle = computed(() => {
  const gradient = gradientFromHex(activeAccentHex.value);
  return gradient ? { backgroundImage: gradient } : null;
});

const accentTextStyle = computed(() => {
  const hex = parseHex(activeAccentHex.value);
  return hex ? { color: hex.hex } : null;
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && awards"
      class="fixed inset-0 z-[70] flex flex-col text-white"
      :class="['bg-gradient-to-b', awardAccent]"
      :style="bgStyle"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-center justify-between px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="flex gap-1">
          <span
            v-for="(slide, index) in slides"
            :key="slide.id"
            class="h-1 rounded-full transition-all"
            :class="index === slideIndex ? 'w-6 bg-white' : 'w-2 bg-white/30'"
          />
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-300 hover:bg-white/10 hover:text-white"
          :aria-label="t('common.close')"
          @click="close"
        >
          ✕
        </button>
      </div>

      <div class="relative flex flex-1 flex-col px-6 pb-6" @click="next">
        <div class="flex flex-1 flex-col justify-center">
          <template v-if="currentSlide?.kind === 'intro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-200" :style="accentTextStyle">{{ t('modals.rosterAwards.brand') }}</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-200">{{ currentSlide.subtitle }}</p>
            <p class="mt-8 text-sm text-slate-400">{{ currentSlide.hint }}</p>
          </template>

          <template v-else-if="currentSlide?.kind === 'roster_award'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">
              {{ currentSlide.eyebrow }}
            </p>
            <h2 class="mt-4 text-3xl font-bold leading-tight sm:text-4xl">
              {{ currentSlide.title }}
            </h2>
            <p class="mt-10 text-5xl font-bold tracking-tight sm:text-6xl">
              {{ currentSlide.athlete_name }}
            </p>
            <p class="mt-4 text-2xl font-semibold text-white/90">
              {{ currentSlide.value_label }}
            </p>
            <p class="mt-8 text-base text-slate-200/90">
              {{ currentSlide.punchline }}
            </p>
            <p class="mt-4 text-xs text-slate-400">
              {{ currentSlide.footnote }}
            </p>
          </template>

          <template v-else-if="currentSlide?.kind === 'outro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-200" :style="accentTextStyle">{{ t('modals.rosterAwards.upNext') }}</p>
            <h2 class="mt-4 text-3xl font-bold">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
          </template>
        </div>

        <div class="flex items-center justify-between gap-3 pt-4">
          <button
            type="button"
            class="rounded-xl border border-white/20 px-4 py-2 text-sm text-slate-200 hover:bg-white/10 disabled:opacity-30"
            :disabled="isFirst"
            @click.stop="prev"
          >
            {{ t('common.back') }}
          </button>
          <button
            type="button"
            class="rounded-xl bg-white/15 px-5 py-2 text-sm font-semibold text-white hover:bg-white/25"
            @click.stop="next"
          >
            {{ isLast ? t('common.close') : t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
