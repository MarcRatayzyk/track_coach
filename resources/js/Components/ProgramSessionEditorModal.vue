<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import SessionEditorPanel from './SessionEditorPanel.vue';
import { cellKey } from '../utils/programBuilder';

const { t } = useI18n();

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  programBlock: {
    type: Object,
    default: null,
  },
  selectedCell: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['close', 'saved', 'cleared']);

const session = computed(() => {
  if (!props.programBlock || !props.selectedCell) {
    return null;
  }
  const key = cellKey(props.selectedCell.weekNumber, props.selectedCell.weekday);
  return props.programBlock.sessions?.[key] ?? null;
});
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && programBlock && selectedCell"
      class="fixed inset-0 z-50 flex items-end justify-center p-0 sm:items-center sm:p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="program-session-editor-title"
    >
      <button
        type="button"
        class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm"
        :aria-label="t('common.close')"
        @click="emit('close')"
      />

      <div
        class="relative z-10 flex max-h-[92vh] w-full max-w-2xl flex-col overflow-y-auto rounded-t-2xl border border-slate-800 bg-slate-900 p-4 shadow-2xl sm:rounded-2xl sm:p-5"
      >
        <SessionEditorPanel
          :active-block="programBlock"
          :selected-cell="selectedCell"
          :session="session"
          @close="emit('close')"
          @saved="emit('saved')"
          @cleared="emit('cleared')"
        />
      </div>
    </div>
  </Teleport>
</template>
