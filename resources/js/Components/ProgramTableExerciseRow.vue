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
});

const emit = defineEmits(['update', 'remove']);

const normalizedLayout = computed(() => normalizeTableLayout(props.tableLayout));
const visibleColumns = computed(() => resolveVisibleColumns(normalizedLayout.value));
const rowBackgroundClass = computed(() => sectionRowClass(props.row.section ?? 'accessory'));

function updateRow(value) {
  emit('update', value);
}
</script>

<template>
  <tr class="align-top transition-colors" :class="rowBackgroundClass">
    <td
      v-for="(column, index) in visibleColumns"
      :key="column.id"
      class="border-b border-slate-800 px-1 py-1"
      :class="[
        index < visibleColumns.length - 1 ? 'border-r' : '',
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
