<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ProgramTableDayCard from './ProgramTableDayCard.vue';
import {
  clipboardSessionToOperation,
  clipboardWeekToOperations,
  incrementClipboardSessionLoads,
  incrementClipboardWeekLoads,
  sessionToClipboardPayload,
  weekSessionsToClipboard,
} from '../utils/programBuilderClipboard';

const props = defineProps({
  activeBlock: {
    type: Object,
    required: true,
  },
});

const weekNumbers = computed(() =>
  Array.from({ length: Number(props.activeBlock?.week_count ?? 0) }, (_, index) => index + 1),
);

function dayNumbersForWeek(weekNumber) {
  const days = new Set();

  for (const key of Object.keys(props.activeBlock?.sessions ?? {})) {
    const [week, day] = key.split('-').map(Number);
    if (week === weekNumber && Number.isFinite(day) && day > 0) {
      days.add(day);
    }
  }

  return [...days].sort((a, b) => a - b);
}

const weekDayOrders = ref({});
const dragState = ref({
  weekNumber: null,
  weekday: null,
  overWeekday: null,
  isDragging: false,
});

function syncWeekDayOrders() {
  const next = {};
  for (const weekNumber of weekNumbers.value) {
    next[weekNumber] = dayNumbersForWeek(weekNumber);
  }
  weekDayOrders.value = next;
}

watch(
  () => [props.activeBlock?.sessions, props.activeBlock?.week_count],
  () => {
    syncWeekDayOrders();
  },
  { immediate: true, deep: true },
);

function dayOrderForWeek(weekNumber) {
  return weekDayOrders.value[weekNumber] ?? [];
}

const daysPerWeek = computed(() =>
  weekNumbers.value.reduce((max, weekNumber) => Math.max(max, dayOrderForWeek(weekNumber).length), 0),
);

const clipboardSession = ref(null);
const clipboardWeek = ref(null);
const pasting = ref(false);
const deleting = ref(false);
const rearranging = ref(false);
const addingDay = ref(false);
const incrementModalOpen = ref(false);
const incrementModalTitle = ref('');
const incrementModalHint = ref('');
const incrementModalValue = ref('0');
const incrementModalError = ref('');
const pendingPasteAction = ref(null);

const clipboardStatus = computed(() => {
  if (clipboardWeek.value) {
    const count = Object.keys(clipboardWeek.value.sessions ?? {}).length;
    return `Semaine ${clipboardWeek.value.weekNumber} copiée (${count} séance${count > 1 ? 's' : ''})`;
  }

  if (clipboardSession.value) {
    const label = clipboardSession.value.session_label?.trim();
    return label ? `Séance copiée : ${label}` : 'Séance copiée';
  }

  return null;
});

function sessionFor(weekNumber, weekday) {
  return props.activeBlock?.sessions?.[`${weekNumber}-${weekday}`] ?? null;
}

function hasSessionsInWeek(weekNumber) {
  return Boolean(weekSessionsToClipboard(props.activeBlock?.sessions ?? {}, weekNumber));
}

function afterSessionChange() {
  router.reload({ only: ['activeBlock'], preserveScroll: true });
}

function openIncrementModal(action) {
  pendingPasteAction.value = action;
  incrementModalTitle.value = action.title;
  incrementModalHint.value = action.hint;
  incrementModalValue.value = '0';
  incrementModalError.value = '';
  incrementModalOpen.value = true;
}

function closeIncrementModal() {
  incrementModalOpen.value = false;
  incrementModalTitle.value = '';
  incrementModalHint.value = '';
  incrementModalValue.value = '0';
  incrementModalError.value = '';
  pendingPasteAction.value = null;
}

function parseIncrementValue() {
  const normalized =
    incrementModalValue.value.trim() === '' ? '0' : incrementModalValue.value.trim().replace(',', '.');
  const parsed = Number(normalized);

  if (!Number.isFinite(parsed)) {
    incrementModalError.value = 'Entre un nombre valide, par exemple 0, 2.5 ou -2.5.';
    return null;
  }

  incrementModalError.value = '';
  return parsed;
}

function copySession({ weekNumber, weekday }) {
  const payload = sessionToClipboardPayload(sessionFor(weekNumber, weekday));

  if (!payload) {
    return;
  }

  clipboardSession.value = payload;
  clipboardWeek.value = null;
}

function pasteSession({ weekNumber, weekday }) {
  if (!clipboardSession.value || !props.activeBlock?.id || pasting.value) {
    return;
  }

  openIncrementModal({
    type: 'session',
    weekNumber,
    weekday,
    title: `Coller la séance sur Jour ${weekday}`,
    hint: "Entre l'incrément de charge en kg à appliquer aux lignes avec charge.",
  });
}

function executePasteSession(weekNumber, weekday, incrementKg) {
  const payload = incrementClipboardSessionLoads(clipboardSession.value, incrementKg);

  pasting.value = true;
  router.post(
    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,
    {
      operations: [clipboardSessionToOperation(payload, weekNumber, weekday)],
    },
    {
      preserveScroll: true,
      onSuccess: afterSessionChange,
      onFinish: () => {
        pasting.value = false;
      },
    },
  );
}

function deleteSession({ weekNumber, weekday }) {
  if (!props.activeBlock?.id || deleting.value) {
    return;
  }

  if (
    !window.confirm(
      `Supprimer le Jour ${weekday} en semaine ${weekNumber} ? Toutes les données programmées pour ce jour seront effacées.`,
    )
  ) {
    return;
  }

  deleting.value = true;
  router.delete(`/coach/program-blocks/${props.activeBlock.id}/sessions`, {
    data: {
      week_number: weekNumber,
      weekday,
      builder_tab: 'table',
    },
    preserveScroll: true,
    onSuccess: afterSessionChange,
    onFinish: () => {
      deleting.value = false;
    },
  });
}

function nextAvailableWeekday(weekNumber) {
  const used = new Set(dayOrderForWeek(weekNumber));
  for (let day = 1; day <= 7; day += 1) {
    if (!used.has(day)) {
      return day;
    }
  }
  return null;
}

function addDay(weekNumber) {
  const weekday = nextAvailableWeekday(weekNumber);
  if (!weekday || !props.activeBlock?.id || addingDay.value) {
    return;
  }

  addingDay.value = true;
  router.post(
    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,
    {
      operations: [
        {
          week_number: weekNumber,
          weekday,
          main_lift: 'squat',
          session_label: `Jour ${weekday}`,
          items: [],
          blocks: [],
        },
      ],
    },
    {
      preserveScroll: true,
      onSuccess: afterSessionChange,
      onFinish: () => {
        addingDay.value = false;
      },
    },
  );
}

function persistWeekArrangement(weekNumber, orderedWeekdays) {
  if (!props.activeBlock?.id) {
    return;
  }

  const targetSlots = [...orderedWeekdays].sort((a, b) => a - b);
  const operations = orderedWeekdays
    .map((sourceWeekday, index) => {
      const targetWeekday = targetSlots[index];
      const payload = sessionToClipboardPayload(sessionFor(weekNumber, sourceWeekday));
      if (!payload) {
        return null;
      }

      return clipboardSessionToOperation(payload, weekNumber, targetWeekday);
    })
    .filter(Boolean);

  if (!operations.length) {
    return;
  }

  rearranging.value = true;
  router.post(
    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,
    { operations },
    {
      preserveScroll: true,
      onSuccess: afterSessionChange,
      onFinish: () => {
        rearranging.value = false;
      },
    },
  );
}

function isDraggingSource(weekNumber, weekday) {
  const { isDragging, weekNumber: dragWeek, weekday: dragWeekday } = dragState.value;
  return isDragging && dragWeek === weekNumber && dragWeekday === weekday;
}

function isDropTarget(weekNumber, weekday) {
  const { isDragging, weekNumber: dragWeek, weekday: dragWeekday, overWeekday } = dragState.value;
  return (
    isDragging &&
    dragWeek === weekNumber &&
    overWeekday === weekday &&
    dragWeekday !== weekday
  );
}

function onDragStart(weekNumber, weekday, event) {
  if (rearranging.value) {
    event.preventDefault();
    return;
  }

  dragState.value = {
    weekNumber,
    weekday,
    overWeekday: null,
    isDragging: true,
  };

  event.dataTransfer.effectAllowed = 'move';
  event.dataTransfer.setData('text/plain', `${weekNumber}-${weekday}`);

  const card = event.currentTarget?.closest('[data-day-card]');
  if (card) {
    event.dataTransfer.setDragImage(card, card.offsetWidth / 2, 32);
  }

  document.body.classList.add('tc-day-dragging');
}

function onDragEnd() {
  dragState.value = {
    weekNumber: null,
    weekday: null,
    overWeekday: null,
    isDragging: false,
  };
  document.body.classList.remove('tc-day-dragging');
}

function onDragOver(weekNumber, weekday) {
  if (
    !dragState.value.isDragging ||
    dragState.value.weekNumber !== weekNumber ||
    dragState.value.weekday === weekday
  ) {
    return;
  }

  dragState.value = { ...dragState.value, overWeekday: weekday };
}

function onDragLeave(weekNumber, weekday) {
  if (dragState.value.overWeekday === weekday && dragState.value.weekNumber === weekNumber) {
    dragState.value = { ...dragState.value, overWeekday: null };
  }
}

function onDrop(weekNumber, targetWeekday) {
  const { weekNumber: sourceWeek, weekday: sourceWeekday } = dragState.value;
  onDragEnd();

  if (
    rearranging.value ||
    sourceWeek !== weekNumber ||
    !sourceWeekday ||
    sourceWeekday === targetWeekday
  ) {
    return;
  }

  const order = [...dayOrderForWeek(weekNumber)];
  const from = order.indexOf(sourceWeekday);
  const to = order.indexOf(targetWeekday);
  if (from === -1 || to === -1) {
    return;
  }

  const [moved] = order.splice(from, 1);
  order.splice(to, 0, moved);
  weekDayOrders.value = { ...weekDayOrders.value, [weekNumber]: order };
  persistWeekArrangement(weekNumber, order);
}

function copyWeek(weekNumber) {
  const data = weekSessionsToClipboard(props.activeBlock?.sessions ?? {}, weekNumber);

  if (!data) {
    return;
  }

  clipboardWeek.value = data;
  clipboardSession.value = null;
}

function pasteWeek(targetWeekNumber) {
  if (!clipboardWeek.value || !props.activeBlock?.id || pasting.value) {
    return;
  }

  if (
    !window.confirm(
      `Coller la semaine ${clipboardWeek.value.weekNumber} sur la semaine ${targetWeekNumber} ? Les séances existantes sur ces jours seront remplacées.`,
    )
  ) {
    return;
  }

  openIncrementModal({
    type: 'week',
    targetWeekNumber,
    title: `Coller sur la semaine ${targetWeekNumber}`,
    hint: "Entre l'incrément de charge en kg à appliquer à toutes les séances collées.",
  });
}

function executePasteWeek(targetWeekNumber, incrementKg) {
  const payload = incrementClipboardWeekLoads(clipboardWeek.value, incrementKg);

  pasting.value = true;
  router.post(
    `/coach/program-blocks/${props.activeBlock.id}/sessions/bulk`,
    {
      operations: clipboardWeekToOperations(payload, targetWeekNumber),
    },
    {
      preserveScroll: true,
      onSuccess: afterSessionChange,
      onFinish: () => {
        pasting.value = false;
      },
    },
  );
}

function confirmIncrementModal() {
  const incrementKg = parseIncrementValue();
  if (incrementKg === null || !pendingPasteAction.value) {
    return;
  }

  const action = pendingPasteAction.value;
  closeIncrementModal();

  if (action.type === 'session') {
    executePasteSession(action.weekNumber, action.weekday, incrementKg);
    return;
  }

  if (action.type === 'week') {
    executePasteWeek(action.targetWeekNumber, incrementKg);
  }
}
</script>

<template>
  <section class="space-y-6 rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg lg:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-white">Builder tableur</h2>
        <p class="mt-2 text-sm text-slate-400">
          Une carte = un jour. Chaque ligne du tableau représente un exercice.
        </p>
      </div>
      <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-4 py-3 text-sm text-slate-400">
        <span class="font-medium text-white">{{ daysPerWeek }}</span> jour{{
          daysPerWeek > 1 ? 's' : ''
        }}
        affiché{{ daysPerWeek > 1 ? 's' : '' }} par semaine
      </div>
    </div>

    <div
      v-if="clipboardStatus"
      class="rounded-xl border border-slate-800 bg-slate-950/40 px-4 py-3 text-sm text-slate-400"
    >
      <span class="font-medium text-white">{{ clipboardStatus }}</span>
      <span class="ml-2 text-slate-500">Le collage demandera un incrément en kg.</span>
    </div>

    <div class="space-y-8">
      <section
        v-for="weekNumber in weekNumbers"
        :key="weekNumber"
        class="rounded-2xl border border-slate-800 bg-slate-950/30 p-4"
      >
        <div class="mb-4 flex items-center justify-between gap-3">
          <div>
            <h3 class="text-base font-semibold text-white">Semaine {{ weekNumber }}</h3>
            <p class="mt-1 text-xs text-slate-500">
              Une seule ligne par semaine. Glisse la poignée d’un jour pour réordonner.
            </p>
          </div>
          <div class="flex flex-wrap items-center justify-end gap-2">
            <button
              type="button"
              :disabled="!nextAvailableWeekday(weekNumber) || addingDay || rearranging || deleting || pasting"
              class="rounded-md border border-emerald-700 px-3 py-1.5 text-xs font-medium text-emerald-200 hover:bg-emerald-900/40 disabled:cursor-not-allowed disabled:opacity-40"
              @click="addDay(weekNumber)"
            >
              + Jour
            </button>
            <button
              type="button"
              :disabled="!hasSessionsInWeek(weekNumber)"
              class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
              @click="copyWeek(weekNumber)"
            >
              Copier la semaine
            </button>
            <button
              type="button"
              :disabled="!clipboardWeek || pasting"
              class="rounded-md border border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-200 hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-40"
              @click="pasteWeek(weekNumber)"
            >
              Coller la semaine
            </button>
          </div>
        </div>

        <div class="tc-scrollbar overflow-x-auto pb-3">
          <div class="flex min-w-max flex-nowrap items-stretch gap-3">
            <div
              v-for="weekday in dayOrderForWeek(weekNumber)"
              :key="`${weekNumber}-${weekday}`"
              data-day-card
              class="shrink-0 transition-all duration-200 ease-out"
              :class="{
                'opacity-45 scale-[0.97] blur-[0.5px]': isDraggingSource(weekNumber, weekday),
                'z-10 scale-[1.02] shadow-xl shadow-blue-900/40 ring-2 ring-blue-400 ring-offset-2 ring-offset-slate-950':
                  isDropTarget(weekNumber, weekday),
              }"
              @dragover.prevent="onDragOver(weekNumber, weekday)"
              @dragleave="onDragLeave(weekNumber, weekday)"
              @drop="onDrop(weekNumber, weekday)"
            >
              <ProgramTableDayCard
                :assignment-id="activeBlock.id"
                :week-number="weekNumber"
                :weekday="weekday"
                :session="sessionFor(weekNumber, weekday)"
                :table-layout="activeBlock.table_layout"
                :default-session-label="`Jour ${weekday}`"
                :has-session-clipboard="Boolean(clipboardSession)"
                :is-pasting="pasting || rearranging || addingDay"
                :reorderable="!rearranging && !addingDay && !deleting && !pasting"
                :is-dragging="isDraggingSource(weekNumber, weekday)"
                @drag-start="onDragStart(weekNumber, weekday, $event)"
                @drag-end="onDragEnd"
                @copy-session="copySession"
                @paste-session="pasteSession"
                @delete-session="deleteSession"
              />
            </div>
          </div>
        </div>
      </section>
    </div>

    <Teleport to="body">
      <div
        v-if="incrementModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        @click.self="closeIncrementModal"
      >
        <div
          class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-white">{{ incrementModalTitle }}</h3>
              <p class="mt-1 text-xs text-slate-500">
                {{ incrementModalHint }}
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white"
              @click="closeIncrementModal"
            >
              ✕
            </button>
          </div>

          <label class="mt-4 block text-xs text-slate-500">
            Incrément en kg
            <input
              v-model="incrementModalValue"
              type="text"
              inputmode="decimal"
              placeholder="0"
              class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white outline-none"
              @keydown.enter.prevent="confirmIncrementModal"
            />
          </label>

          <p v-if="incrementModalError" class="mt-2 text-xs text-red-400">
            {{ incrementModalError }}
          </p>

          <div class="mt-5 flex justify-end gap-2">
            <button
              type="button"
              class="rounded-md border border-slate-700 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800"
              @click="closeIncrementModal"
            >
              Annuler
            </button>
            <button
              type="button"
              class="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
              @click="confirmIncrementModal"
            >
              Confirmer
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>

<style>
body.tc-day-dragging,
body.tc-day-dragging * {
  cursor: grabbing !important;
}
</style>
