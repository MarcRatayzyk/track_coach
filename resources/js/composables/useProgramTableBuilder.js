import { computed, ref, toValue } from 'vue';
import { router } from '@inertiajs/vue3';
import { columnHeading, weekdayShortLabel } from '../utils/programBuilder';
import {
  applyClipboardWeekIncrements,
  clipboardSessionToOperation,
  clipboardWeekToOperations,
  collectClipboardExerciseNames,
  prepareClipboardSessionForPaste,
  sessionToClipboardPayload,
  weekSessionsToClipboard,
} from '../utils/programBuilderClipboard';
import { programSessionVisitOptions } from '../utils/programBuilderVisit';

export const ALL_WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

export function useProgramTableBuilder(activeBlock, builderTab = 'table') {
  const dragState = ref({
    weekNumber: null,
    weekday: null,
    overWeekday: null,
    isDragging: false,
  });

  const clipboardSession = ref(null);
  const clipboardWeek = ref(null);
  const pasting = ref(false);
  const deleting = ref(false);
  const moving = ref(false);
  const addingDay = ref(false);
  const incrementModalOpen = ref(false);
  const incrementModalTitle = ref('');
  const incrementModalHint = ref('');
  const incrementModalExerciseNames = ref([]);
  const incrementModalPasteKind = ref('session');
  const incrementModalDefaultSessionLabel = ref('');
  const incrementModalDefaultSessionNotes = ref('');
  const pendingPasteAction = ref(null);

  const weekNumbers = computed(() =>
    Array.from({ length: Number(toValue(activeBlock)?.week_count ?? 0) }, (_, index) => index + 1),
  );

  const maxSessionsPerWeek = computed(() =>
    weekNumbers.value.reduce((max, weekNumber) => Math.max(max, sessionCountForWeek(weekNumber)), 0),
  );

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

  const isBusy = computed(
    () => pasting.value || moving.value || addingDay.value || deleting.value,
  );

  function sessionFor(weekNumber, weekday) {
    return toValue(activeBlock)?.sessions?.[`${weekNumber}-${weekday}`] ?? null;
  }

  function sessionCountForWeek(weekNumber) {
    return ALL_WEEKDAYS.filter((weekday) => sessionFor(weekNumber, weekday)).length;
  }

  function headingFor(weekNumber, weekday) {
    return columnHeading(toValue(activeBlock)?.date_start, weekNumber, weekday);
  }

  function hasSessionsInWeek(weekNumber) {
    return Boolean(weekSessionsToClipboard(toValue(activeBlock)?.sessions ?? {}, weekNumber));
  }

  function openIncrementModal(action) {
    pendingPasteAction.value = action;
    incrementModalTitle.value = action.title;
    incrementModalHint.value = action.hint;
    incrementModalExerciseNames.value = action.exerciseNames ?? [];
    incrementModalPasteKind.value = action.pasteKind ?? 'session';
    incrementModalDefaultSessionLabel.value = action.defaultSessionLabel ?? '';
    incrementModalDefaultSessionNotes.value = action.defaultSessionNotes ?? '';
    incrementModalOpen.value = true;
  }

  function closeIncrementModal() {
    incrementModalOpen.value = false;
    incrementModalTitle.value = '';
    incrementModalHint.value = '';
    incrementModalExerciseNames.value = [];
    incrementModalPasteKind.value = 'session';
    incrementModalDefaultSessionLabel.value = '';
    incrementModalDefaultSessionNotes.value = '';
    pendingPasteAction.value = null;
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
    if (!clipboardSession.value || !toValue(activeBlock)?.id || pasting.value) {
      return;
    }

    openIncrementModal({
      type: 'session',
      weekNumber,
      weekday,
      title: `Coller la séance sur ${weekdayShortLabel(weekday)}`,
      hint: 'Définis le titre, les notes, les incréments et les lignes concernées avant de coller.',
      pasteKind: 'session',
      defaultSessionLabel: clipboardSession.value.session_label ?? '',
      defaultSessionNotes: clipboardSession.value.notes ?? '',
      exerciseNames: collectClipboardExerciseNames(clipboardSession.value),
    });
  }

  function executePasteSession(weekNumber, weekday, options) {
    const payload = prepareClipboardSessionForPaste(clipboardSession.value, options);

    pasting.value = true;
    router.post(
      `/coach/program-blocks/${toValue(activeBlock).id}/sessions/bulk`,
      {
        operations: [clipboardSessionToOperation(payload, weekNumber, weekday)],
        builder_tab: builderTab,
      },
      programSessionVisitOptions({
        onFinish: () => {
          pasting.value = false;
        },
      }),
    );
  }

  function deleteSession({ weekNumber, weekday }) {
    if (!toValue(activeBlock)?.id || deleting.value) {
      return;
    }

    if (
      !window.confirm(
        `Supprimer la séance du ${weekdayShortLabel(weekday)} en semaine ${weekNumber} ? Toutes les données programmées pour ce jour seront effacées.`,
      )
    ) {
      return;
    }

    deleting.value = true;
    router.delete(
      `/coach/program-blocks/${toValue(activeBlock).id}/sessions`,
      programSessionVisitOptions({
        data: {
          week_number: weekNumber,
          weekday,
          builder_tab: builderTab,
        },
        onFinish: () => {
          deleting.value = false;
        },
      }),
    );
  }

  function addDay(weekNumber, weekday) {
    if (sessionFor(weekNumber, weekday) || !toValue(activeBlock)?.id || addingDay.value) {
      return;
    }

    addingDay.value = true;
    router.post(
      `/coach/program-blocks/${toValue(activeBlock).id}/sessions/bulk`,
      {
        operations: [
          {
            week_number: weekNumber,
            weekday,
            main_lift: 'squat',
            session_label: weekdayShortLabel(weekday),
            items: [],
            blocks: [],
          },
        ],
        builder_tab: builderTab,
      },
      programSessionVisitOptions({
        onFinish: () => {
          addingDay.value = false;
        },
      }),
    );
  }

  function moveSession(weekNumber, sourceWeekday, targetWeekday) {
    if (
      !toValue(activeBlock)?.id ||
      moving.value ||
      sourceWeekday === targetWeekday ||
      sourceWeekday == null ||
      targetWeekday == null
    ) {
      return;
    }

    const sourcePayload = sessionToClipboardPayload(sessionFor(weekNumber, sourceWeekday));
    if (!sourcePayload) {
      return;
    }

    const targetPayload = sessionToClipboardPayload(sessionFor(weekNumber, targetWeekday));

    moving.value = true;

    if (!targetPayload) {
      router.post(
        `/coach/program-blocks/${toValue(activeBlock).id}/sessions/bulk`,
        {
          operations: [clipboardSessionToOperation(sourcePayload, weekNumber, targetWeekday)],
          builder_tab: builderTab,
        },
        programSessionVisitOptions({
          only: [],
          onSuccess: () => {
            router.delete(
              `/coach/program-blocks/${toValue(activeBlock).id}/sessions`,
              programSessionVisitOptions({
                data: {
                  week_number: weekNumber,
                  weekday: sourceWeekday,
                  builder_tab: builderTab,
                },
                onFinish: () => {
                  moving.value = false;
                },
              }),
            );
          },
          onError: () => {
            moving.value = false;
          },
        }),
      );
      return;
    }

    router.post(
      `/coach/program-blocks/${toValue(activeBlock).id}/sessions/bulk`,
      {
        operations: [
          clipboardSessionToOperation(sourcePayload, weekNumber, targetWeekday),
          clipboardSessionToOperation(targetPayload, weekNumber, sourceWeekday),
        ],
        builder_tab: builderTab,
      },
      programSessionVisitOptions({
        onFinish: () => {
          moving.value = false;
        },
      }),
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
    if (moving.value) {
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

    if (moving.value || sourceWeek !== weekNumber || !sourceWeekday || sourceWeekday === targetWeekday) {
      return;
    }

    moveSession(weekNumber, sourceWeekday, targetWeekday);
  }

  function copyWeek(weekNumber) {
    const data = weekSessionsToClipboard(toValue(activeBlock)?.sessions ?? {}, weekNumber);

    if (!data) {
      return;
    }

    clipboardWeek.value = data;
    clipboardSession.value = null;
  }

  function pasteWeek(targetWeekNumber) {
    if (!clipboardWeek.value || !toValue(activeBlock)?.id || pasting.value) {
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
      hint: 'Définis les notes, les incréments et les lignes concernées pour toutes les séances collées.',
      pasteKind: 'week',
      defaultSessionNotes: '',
      exerciseNames: collectClipboardExerciseNames(clipboardWeek.value),
    });
  }

  function executePasteWeek(targetWeekNumber, options) {
    const payload = applyClipboardWeekIncrements(clipboardWeek.value, options);

    pasting.value = true;
    router.post(
      `/coach/program-blocks/${toValue(activeBlock).id}/sessions/bulk`,
      {
        operations: clipboardWeekToOperations(payload, targetWeekNumber),
        builder_tab: builderTab,
      },
      programSessionVisitOptions({
        onFinish: () => {
          pasting.value = false;
        },
      }),
    );
  }

  function confirmIncrementModal(options) {
    if (!pendingPasteAction.value) {
      return;
    }

    const action = pendingPasteAction.value;
    closeIncrementModal();

    if (action.type === 'session') {
      executePasteSession(action.weekNumber, action.weekday, options);
      return;
    }

    if (action.type === 'week') {
      executePasteWeek(action.targetWeekNumber, options);
    }
  }

  return {
    ALL_WEEKDAYS,
    weekNumbers,
    maxSessionsPerWeek,
    clipboardStatus,
    clipboardSession,
    clipboardWeek,
    pasting,
    isBusy,
    incrementModalOpen,
    incrementModalTitle,
    incrementModalHint,
    incrementModalExerciseNames,
    incrementModalPasteKind,
    incrementModalDefaultSessionLabel,
    incrementModalDefaultSessionNotes,
    sessionFor,
    headingFor,
    hasSessionsInWeek,
    copySession,
    pasteSession,
    deleteSession,
    addDay,
    copyWeek,
    pasteWeek,
    isDraggingSource,
    isDropTarget,
    onDragStart,
    onDragEnd,
    onDragOver,
    onDragLeave,
    onDrop,
    closeIncrementModal,
    confirmIncrementModal,
  };
}
