<script setup>
import { computed } from 'vue';
import ProgramTableDynamicCell from './ProgramTableDynamicCell.vue';
import { normalizeTableLayout, resolveVisibleColumns } from '../config/dayTableColumns';
import { sectionRowClass } from '../config/programTableSections';

const props = defineProps({
  row: {
    type: Object,
    required: true,
  },
  tableLayout: {
    type: Object,
    default: null,
  },
  defaultLift: {
    type: String,
    default: 'squat',
  },
  removable: {
    type: Boolean,
    default: true,
  },
  layoutVariant: {
    type: String,
    default: 'stacked',
    validator: (value) => ['stacked', 'spaced'].includes(value),
  },
  selectable: {
    type: Boolean,
    default: false,
  },
  selected: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update', 'remove', 'select']);

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));
const rowBackgroundClass = computed(() => sectionRowClass(props.row.section ?? 'accessory'));

function updateRow(value) {
  emit('update', value);
}

function onRowClick(event) {
  if (!props.selectable) {
    return;
  }

  if (event.target.closest('input, button, select, textarea, a, [role="button"]')) {
    return;
  }

  emit('select');
}
</script>

<template>
  <tr
    class="align-top transition-colors"
    :class="[
      rowBackgroundClass,
      selectable ? 'cursor-pointer' : '',
      selected ? 'ring-2 ring-inset ring-blue-400' : '',
    ]"
    @click="onRowClick"
  >
    <td
      v-for="(column, index) in visibleColumns"
      :key="column.id"
      class="border-b border-slate-800 px-1"
      :class="[
        index < visibleColumns.length - 1 ? 'border-r' : '',
        ['sets', 'reps', 'load', 'rest'].includes(column.id) ? 'py-0.5' : 'py-1',
        layoutVariant === 'spaced' && ['sets', 'reps', 'load', 'section'].includes(column.id)
          ? 'text-center'
          : '',
        layoutVariant === 'spaced' ? 'overflow-hidden' : '',
      ]"
    >
      <div v-if="column.id === 'exercise'" class="flex items-start gap-1.5">
        <div class="min-w-0 flex-1">
          <ProgramTableDynamicCell
            :column-id="column.id"
            :row="row"
            :default-lift="defaultLift"
            :default-load-mode="normalizedLayout.load_mode"
            @update="updateRow"
          />
        </div>
        <button
          v-if="removable"
          type="button"
          class="shrink-0 border-l border-slate-800 px-1.5 py-1 text-[10px] font-medium text-red-300 hover:bg-red-950/40"
          @click="$emit('remove')"
        >
          X
        </button>
      </div>
      <ProgramTableDynamicCell
        v-else
        :column-id="column.id"
        :row="row"
        :default-lift="defaultLift"
        :default-load-mode="normalizedLayout.load_mode"
        @update="updateRow"
      />
    </td>
  </tr>
</template>
