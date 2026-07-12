<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  wrapped: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'share']);

const slideIndex = ref(0);

const slides = computed(() => {
  const data = props.wrapped;
  if (!data) {
    return [];
  }

  const list = [
    {
      id: 'intro',
      kind: 'intro',
      title: data.label,
      subtitle: `${data.period_start} → ${data.period_end}`,
    },
    {
      id: 'overview',
      kind: 'overview',
      title: 'Ton volume',
      metrics: data.overview ?? {},
    },
  ];

  for (const lift of data.lifts ?? []) {
    list.push({
      id: `${lift.key}-heaviest`,
      kind: 'lift',
      lift,
      metricKey: 'heaviest_bar',
      title: lift.label,
      headline: 'Barre la plus lourde',
      unit: 'kg',
    });
    list.push({
      id: `${lift.key}-e1rm`,
      kind: 'lift',
      lift,
      metricKey: 'top_e1rm',
      title: lift.label,
      headline: 'e1RM le plus élevé',
      unit: 'kg',
    });
    list.push({
      id: `${lift.key}-tonnage`,
      kind: 'lift',
      lift,
      metricKey: 'tonnage',
      title: lift.label,
      headline: 'Tonnage sur le mouvement',
      unit: 'kg',
    });
  }

  list.push({
    id: 'outro',
    kind: 'outro',
    title: data.label,
    subtitle: 'Continue comme ça 💪',
  });

  return list;
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

function formatDelta(delta) {
  if (!delta) {
    return null;
  }

  const sign = delta.direction === 'up' ? '+' : delta.direction === 'down' ? '−' : '';
  const value = Math.abs(delta.absolute ?? 0);
  const pct = delta.percent;

  if (pct === null) {
    return `${sign}${value}`;
  }

  const pctSign = delta.direction === 'down' ? '−' : '+';
  const pctValue = Math.abs(pct);

  return `${sign}${value} (${pctSign}${pctValue}%)`;
}

function deltaClass(delta) {
  if (!delta) {
    return 'text-slate-400';
  }
  if (delta.direction === 'up') {
    return 'text-emerald-400';
  }
  if (delta.direction === 'down') {
    return 'text-rose-400';
  }
  return 'text-slate-400';
}

function metricValue(metric) {
  if (!metric || metric.value === null || metric.value === undefined) {
    return '—';
  }
  return metric.value;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && wrapped"
      class="fixed inset-0 z-[70] flex flex-col bg-gradient-to-b from-violet-950 via-slate-950 to-slate-950 text-white"
      role="dialog"
      aria-modal="true"
    >
      <div class="flex items-center justify-between px-4 pb-2 pt-[max(0.75rem,env(safe-area-inset-top))]">
        <div class="flex gap-1">
          <span
            v-for="(slide, index) in slides"
            :key="slide.id"
            class="h-1 rounded-full transition-all"
            :class="index === slideIndex ? 'w-6 bg-violet-400' : 'w-2 bg-slate-600'"
          />
        </div>
        <button
          type="button"
          class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
          aria-label="Fermer"
          @click="close"
        >
          ✕
        </button>
      </div>

      <div class="relative flex flex-1 flex-col px-6 pb-6" @click="next">
        <div class="flex flex-1 flex-col justify-center">
          <template v-if="currentSlide?.kind === 'intro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Wrapped</p>
            <h2 class="mt-4 text-4xl font-bold leading-tight">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
            <p class="mt-8 text-sm text-slate-400">Tape pour découvrir ton récap →</p>
          </template>

          <template v-else-if="currentSlide?.kind === 'overview'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Volume</p>
            <h2 class="mt-3 text-2xl font-bold">{{ currentSlide.title }}</h2>
            <div class="mt-8 grid gap-3">
              <div
                v-for="item in [
                  { key: 'total_tonnage', label: 'Tonnage', suffix: 'kg' },
                  { key: 'adherence_percent', label: 'Adhérence', suffix: '%' },
                  { key: 'total_sets', label: 'Séries', suffix: '' },
                  { key: 'total_reps', label: 'Reps', suffix: '' },
                  { key: 'tonnage_per_set', label: 'Tonnage / série', suffix: 'kg' },
                ]"
                :key="item.key"
                class="rounded-2xl border border-slate-700/80 bg-slate-900/60 px-4 py-3"
              >
                <p class="text-xs text-slate-400">{{ item.label }}</p>
                <div class="mt-1 flex items-end justify-between gap-3">
                  <p class="text-2xl font-bold">
                    {{ metricValue(currentSlide.metrics[item.key]) }}<span
                      v-if="item.suffix && metricValue(currentSlide.metrics[item.key]) !== '—'"
                      class="ml-1 text-sm font-medium text-slate-400"
                    >{{ item.suffix }}</span>
                  </p>
                  <p
                    v-if="currentSlide.metrics[item.key]?.delta"
                    class="text-xs font-semibold"
                    :class="deltaClass(currentSlide.metrics[item.key].delta)"
                  >
                    vs {{ wrapped?.comparison_label ?? 'la période précédente' }}
                    {{ formatDelta(currentSlide.metrics[item.key].delta) }}
                  </p>
                </div>
              </div>
            </div>
          </template>

          <template v-else-if="currentSlide?.kind === 'lift'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">
              {{ currentSlide.title }}
            </p>
            <h2 class="mt-3 text-3xl font-bold">{{ currentSlide.headline }}</h2>
            <p class="mt-10 text-6xl font-bold tracking-tight">
              {{
                currentSlide.lift?.[currentSlide.metricKey]?.value ?? '—'
              }}<span
                v-if="currentSlide.lift?.[currentSlide.metricKey]?.value"
                class="ml-2 text-2xl font-semibold text-slate-400"
              >{{ currentSlide.unit }}</span>
            </p>
            <p
              v-if="currentSlide.lift?.[currentSlide.metricKey]?.delta"
              class="mt-6 text-sm font-semibold"
              :class="deltaClass(currentSlide.lift[currentSlide.metricKey].delta)"
            >
              vs {{ wrapped?.comparison_label ?? 'la semaine précédente' }}
              {{ formatDelta(currentSlide.lift[currentSlide.metricKey].delta) }}
            </p>
          </template>

          <template v-else-if="currentSlide?.kind === 'outro'">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-300">Bravo</p>
            <h2 class="mt-4 text-3xl font-bold">{{ currentSlide.title }}</h2>
            <p class="mt-3 text-lg text-slate-300">{{ currentSlide.subtitle }}</p>
            <button
              type="button"
              class="mt-10 w-full rounded-2xl bg-violet-600 px-4 py-3 text-sm font-semibold text-white hover:bg-violet-500"
              @click.stop="emit('share', wrapped.share_payload)"
            >
              Partager mon recap
            </button>
          </template>
        </div>

        <div class="flex items-center justify-between gap-3 pt-4">
          <button
            type="button"
            class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-300 hover:bg-slate-900 disabled:opacity-30"
            :disabled="isFirst"
            @click.stop="prev"
          >
            Retour
          </button>
          <button
            type="button"
            class="rounded-xl bg-violet-600 px-5 py-2 text-sm font-semibold text-white hover:bg-violet-500"
            @click.stop="next"
          >
            {{ isLast ? 'Fermer' : 'Suivant' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
