import { computed, inject, provide, reactive } from 'vue';

export const TABLE_ROW_EDITOR_KEY = Symbol('tableRowEditor');

export function provideTableRowEditor() {
  const state = reactive({
    weekNumber: null,
    weekday: null,
    rowIndex: null,
    row: null,
    sessionHeading: '',
    defaultLift: 'squat',
    onUpdate: null,
  });

  function selectRow({
    weekNumber,
    weekday,
    rowIndex,
    row,
    sessionHeading,
    defaultLift,
    onUpdate,
  }) {
    state.weekNumber = weekNumber;
    state.weekday = weekday;
    state.rowIndex = rowIndex;
    state.row = row;
    state.sessionHeading = sessionHeading;
    state.defaultLift = defaultLift;
    state.onUpdate = onUpdate;
  }

  function clearSelection() {
    state.weekNumber = null;
    state.weekday = null;
    state.rowIndex = null;
    state.row = null;
    state.sessionHeading = '';
    state.defaultLift = 'squat';
    state.onUpdate = null;
  }

  const hasSelection = computed(() => state.row != null);

  const context = { state, selectRow, clearSelection, hasSelection };

  provide(TABLE_ROW_EDITOR_KEY, context);

  return context;
}

export function useTableRowEditor() {
  return inject(TABLE_ROW_EDITOR_KEY, null);
}
