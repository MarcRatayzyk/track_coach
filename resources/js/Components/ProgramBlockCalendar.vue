<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { WEEKDAY_LABELS, buildCalendarRows } from '../utils/programBuilder';

const props = defineProps({
  weekCount: {
    type: Number,
    required: true,
  },
  dateStart: {
    type: String,
    required: true,
  },
  sessions: {
    type: Object,
    default: () => ({}),
  },
  selectedCell: {
    type: Object,
    default: null,
  },
  compact: {
    type: Boolean,
    default: false,
  },
  pasteMode: {
    type: Boolean,
    default: false,
  },
  hasSessionClipboard: {
    type: Boolean,
    default: false,
  },
  hasWeekClipboard: {
    type: Boolean,
    default: false,
  },
  copiedWeekNumber: {
    type: Number,
    default: null,
  },
  readOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['select', 'copy-week', 'paste-week']);

const isNarrowViewport = ref(false);
let narrowMediaQuery = null;

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    narrowMediaQuery = window.matchMedia('(max-width: 1023px)');
    isNarrowViewport.value = narrowMediaQuery.matches;
    narrowMediaQuery.addEventListener('change', onViewportChange);
});

onUnmounted(() => {
    if (narrowMediaQuery) {
        narrowMediaQuery.removeEventListener('change', onViewportChange);
    }
});

function onViewportChange(event) {
    isNarrowViewport.value = event.matches;
}

const isCompactLayout = computed(() => props.compact || isNarrowViewport.value);

const rows = computed(() => buildCalendarRows(props.weekCount, props.dateStart, props.sessions));

function isSelected(cell) {
  return (
    props.selectedCell?.weekNumber === cell.weekNumber &&
    props.selectedCell?.weekday === cell.weekday
  );
}

function selectCell(cell) {
  emit('select', {
    weekNumber: cell.weekNumber,
    weekday: cell.weekday,
    date: cell.date,
    key: cell.key,
  });
}

function dayLabel(dateKey) {
  const d = new Date(dateKey + 'T12:00:00');
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function weekHasSessions(weekNumber) {
  return Object.keys(props.sessions ?? {}).some((key) => key.startsWith(`${weekNumber}-`));
}
</script>

<template>
  <div class="overflow-x-auto">
    <table class="w-full min-w-[28rem] border-collapse text-sm sm:min-w-[32rem]">
      <thead>
        <tr>
          <th class="w-24 pb-2 text-left text-xs font-medium text-slate-500">Sem.</th>
          <th
            v-for="label in WEEKDAY_LABELS"
            :key="label"
            class="pb-2 text-center text-xs font-medium text-slate-500"
          >
            {{ label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in rows" :key="row.weekNumber" class="border-t border-slate-800/80">
          <td class="py-2 pr-1 align-top">
            <div class="flex flex-col gap-1">
              <span class="text-xs font-semibold text-slate-400">S{{ row.weekNumber }}</span>
              <div v-if="!readOnly" class="flex flex-wrap gap-0.5">
                <button
                  type="button"
                  title="Copier la semaine"
                  class="rounded border border-slate-700 px-1 py-0.5 text-[10px] text-slate-400 hover:border-slate-500 hover:text-white disabled:opacity-30"
                  :disabled="!weekHasSessions(row.weekNumber)"
                  @click.stop="emit('copy-week', row.weekNumber)"
                >
                  Copier
                </button>
                <button
                  type="button"
                  title="Coller la semaine copiée"
                  class="rounded border border-slate-700 px-1 py-0.5 text-[10px] text-blue-300 hover:border-blue-500/50 hover:bg-blue-950/40 disabled:opacity-30"
                  :disabled="!hasWeekClipboard"
                  @click.stop="emit('paste-week', row.weekNumber)"
                >
                  Coller
                </button>
              </div>
              <p
                v-if="!readOnly && hasWeekClipboard && copiedWeekNumber === row.weekNumber"
                class="text-[9px] text-amber-400/90"
              >
                source
              </p>
            </div>
          </td>
          <td v-for="cell in row.cells" :key="cell.key" class="p-0.5">
            <button
              type="button"
              class="flex w-full flex-col items-center rounded-lg border px-1 py-2 transition"
              :class="[
                cell.hasSession
                  ? isSelected(cell)
                    ? 'border-emerald-500 bg-emerald-600 text-white shadow-md shadow-emerald-900/30'
                    : 'border-emerald-500/40 bg-emerald-500/20 text-emerald-100 hover:bg-emerald-500/35'
                  : isSelected(cell)
                    ? 'border-blue-500 bg-blue-600/20 text-white'
                    : 'border-slate-800 bg-slate-950/40 text-slate-400 hover:border-slate-600 hover:bg-slate-900',
                pasteMode && hasSessionClipboard
                  ? 'ring-1 ring-blue-500/40 ring-offset-1 ring-offset-slate-950'
                  : '',
                isCompactLayout ? 'min-h-[3rem] py-1.5' : 'min-h-[4rem]',
              ]"
              @click="selectCell(cell)"
            >
              <span class="text-[10px] font-medium uppercase opacity-80">{{ dayLabel(cell.date) }}</span>
              <span
                v-if="cell.hasSession"
                class="mt-1 h-1.5 w-1.5 rounded-full"
                :class="isSelected(cell) ? 'bg-white' : 'bg-emerald-400'"
              />
            </button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
