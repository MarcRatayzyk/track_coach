<script setup>
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  formatLineRecap,
  formatPrescription,
  sessionDayOrdinalInWeek,
} from '../utils/programBuilder';
import { sectionBadgeClass, sectionOption } from '../config/programTableSections';

const props = defineProps({
  programBlock: {
    type: Object,
    required: true,
  },
  selectedCell: {
    type: Object,
    default: null,
  },
  session: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close']);

const headerTitle = computed(() => {
  const week = props.selectedCell.weekNumber;
  const dayNum = sessionDayOrdinalInWeek(
    props.programBlock.sessions,
    week,
    props.selectedCell.weekday,
  );
  const label = props.session?.session_label?.trim();

  if (label) {
    return `Semaine ${week} · Jour ${dayNum} — ${label}`;
  }

  return `Semaine ${week} · Jour ${dayNum}`;
});

const resolvedWarmup = computed(() => props.session?.warmup ?? null);

const hasWarmup = computed(() => {
  const warmup = resolvedWarmup.value;
  if (!warmup) {
    return false;
  }
  return Boolean(String(warmup.notes ?? '').trim()) || (warmup.items?.length ?? 0) > 0;
});

const exerciseLines = computed(() => {
  const items = (props.session?.items ?? []).filter((item) => item.section !== 'warmup');
  const order = { topset: 0, backoff: 1, accessory: 2 };

  return [...items].sort((a, b) => (order[a.section] ?? 9) - (order[b.section] ?? 9));
});

function exerciseLineText(line) {
  return formatLineRecap(line) || formatPrescription(line);
}

function sectionLabel(section) {
  return sectionOption(section).label;
}
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">
    <div class="flex items-start justify-between gap-3">
      <div>
        <h3 class="text-sm font-semibold text-white">{{ headerTitle }}</h3>
        <p v-if="selectedCell?.date" class="mt-1 text-xs text-slate-500">
          {{ formatCalendarFr(selectedCell.date, 'medium') }}
        </p>
      </div>
      <button
        v-if="selectedCell"
        type="button"
        class="text-xs text-slate-500 hover:text-slate-300"
        @click="emit('close')"
      >
        Fermer
      </button>
    </div>

    <div
      v-if="hasWarmup"
      class="mt-4 space-y-2 rounded-xl border border-sky-500/25 bg-sky-950/20 px-3 py-3"
    >
      <p class="text-[10px] font-semibold uppercase tracking-widest text-sky-300/90">
        Échauffement
      </p>
      <p
        v-if="resolvedWarmup.notes?.trim()"
        class="whitespace-pre-wrap text-sm leading-relaxed text-slate-200"
      >
        {{ resolvedWarmup.notes }}
      </p>
      <ul v-if="resolvedWarmup.items?.length" class="space-y-1">
        <li
          v-for="(item, index) in resolvedWarmup.items"
          :key="`${item.exercise_name}-${index}`"
          class="text-sm text-slate-300"
        >
          {{ formatLineRecap(item) || item.exercise_name }}
        </li>
      </ul>
    </div>

    <template v-if="session && exerciseLines.length">
      <ul class="mt-4 space-y-2 border-t border-slate-800 pt-4">
        <li
          v-for="(line, index) in exerciseLines"
          :key="`${line.section}-${index}`"
          class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-sm"
        >
          <span
            class="inline-flex shrink-0 rounded-md border px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
            :class="sectionBadgeClass(line.section)"
          >
            {{ sectionLabel(line.section) }}
          </span>
          <span class="text-slate-200">{{ exerciseLineText(line) }}</span>
        </li>
      </ul>
    </template>

    <p
      v-else-if="!hasWarmup"
      class="mt-4 border-t border-slate-800 pt-4 text-sm text-slate-500"
    >
      Aucune séance programmée pour ce jour.
    </p>
  </section>
</template>
