<script setup>
import { computed } from 'vue';
import { formatCalendarFr } from '../utils/formatDates';
import {
  SECTION_LABELS,
  formatLineRecap,
  formatPrescription,
  sessionDayOrdinalInWeek,
} from '../utils/programBuilder';

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

const exerciseLines = computed(() => {
  const items = props.session?.items ?? [];
  const order = { topset: 0, backoff: 1, accessory: 2 };

  return [...items].sort((a, b) => (order[a.section] ?? 9) - (order[b.section] ?? 9));
});

function exerciseLineText(line) {
  return formatLineRecap(line) || formatPrescription(line);
}

function sectionTextClass(section) {
  if (section === 'topset') {
    return 'text-emerald-400';
  }
  if (section === 'backoff') {
    return 'text-amber-300';
  }
  return 'text-slate-400';
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

    <template v-if="session && exerciseLines.length">
      <ul class="mt-4 space-y-2 border-t border-slate-800 pt-4">
        <li
          v-for="(line, index) in exerciseLines"
          :key="`${line.section}-${index}`"
          class="rounded-lg border border-slate-800 bg-slate-950/50 px-3 py-2 text-sm"
        >
          <span class="text-xs font-medium uppercase" :class="sectionTextClass(line.section)">
            {{ SECTION_LABELS[line.section] ?? line.section }}
          </span>
          <p class="mt-1 text-slate-200">{{ exerciseLineText(line) }}</p>
        </li>
      </ul>
    </template>

    <p v-else class="mt-4 border-t border-slate-800 pt-4 text-sm text-slate-500">
      Aucune séance programmée pour ce jour.
    </p>
  </section>
</template>
