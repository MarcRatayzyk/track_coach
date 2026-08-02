<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
const { t } = useI18n();

const props = defineProps({
  modelValue: { type: String, default: 'all' },
  search: { type: String, default: '' },
  mode: { type: String, default: 'coach' },
  counts: {
    type: Object,
    default: () => ({
      all: 0,
      today: 0,
      overdue: 0,
      done: 0,
      pending: 0,
    }),
  },
});

const emit = defineEmits(['update:modelValue', 'update:search']);

const filters = computed(() => {
  if (props.mode === 'athlete') {
    return [
      { value: 'all', label: t('app.feedbacks.filterAll') },
      { value: 'pending', label: t('app.dashboard.waiting') },
      { value: 'done', label: t('app.feedbacks.replied') },
    ];
  }
  return [
    { value: 'all', label: t('app.feedbacks.filterAll') },
    { value: 'today', label: t('app.dashboard.today') },
    { value: 'overdue', label: t('app.feedbacks.overdue') },
    { value: 'done', label: t('app.feedbacks.replied') },
  ];
});

const searchPlaceholder = computed(() =>
  props.mode === 'athlete' ? t('app.feedbacks.searchSession') : t('app.feedbacks.searchAthlete'),
);
</script>

<template>
  <div class="space-y-3">
    <div class="relative">
      <svg
        class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1.8"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
        />
      </svg>
      <input
        :value="search"
        type="search"
        :placeholder="searchPlaceholder"
        class="w-full rounded-[14px] border border-slate-700 bg-slate-950/70 py-2.5 pl-9 pr-3 text-sm text-white placeholder:text-slate-600 transition duration-200 focus:border-blue-500/50 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
        @input="emit('update:search', $event.target.value)"
      />
    </div>

    <div class="flex flex-wrap gap-1.5">
      <button
        v-for="filter in filters"
        :key="filter.value"
        type="button"
        class="rounded-full border px-2.5 py-1 text-[11px] font-medium transition duration-200"
        :class="
          modelValue === filter.value
            ? 'border-blue-500/60 bg-blue-600/20 text-white shadow-[0_0_16px_rgba(59,130,246,0.18)]'
            : 'border-slate-700 text-slate-400 hover:border-slate-600 hover:bg-slate-800/60 hover:text-slate-200'
        "
        @click="emit('update:modelValue', filter.value)"
      >
        {{ filter.label }}
        <span class="ml-1 tabular-nums text-slate-400">{{ counts[filter.value] ?? 0 }}</span>
      </button>
    </div>
  </div>
</template>
