<script setup>
const props = defineProps({
  columnHeading: {
    type: String,
    required: true,
  },
  isDropTarget: {
    type: Boolean,
    default: false,
  },
  hasSessionClipboard: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  layoutVariant: {
    type: String,
    default: 'stacked',
    validator: (value) => ['stacked', 'spaced'].includes(value),
  },
  collapsed: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['add-session', 'paste-session']);

function onClick() {
  if (props.disabled) {
    return;
  }

  if (props.hasSessionClipboard) {
    emit('paste-session');
    return;
  }

  emit('add-session');
}
</script>

<template>
  <button
    type="button"
    :disabled="disabled"
    :title="columnHeading"
    class="flex w-full rounded-lg border border-dashed transition"
    :class="[
      collapsed
        ? 'items-center justify-between gap-3 px-4 py-2 text-left'
        : 'h-full flex-col items-center justify-center text-center',
      !collapsed && layoutVariant === 'spaced' ? 'min-h-[8rem] gap-3 px-4 py-5' : '',
      !collapsed && layoutVariant !== 'spaced' ? 'min-h-[14rem] gap-5 px-1 py-4' : '',
      isDropTarget
        ? 'z-10 border-blue-400 bg-blue-950/30 ring-2 ring-blue-400 ring-offset-1 ring-offset-slate-950'
        : 'border-slate-700/60 bg-slate-950/30 text-slate-500 hover:border-slate-500 hover:bg-slate-900/50 hover:text-slate-300',
      disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
    ]"
    @click="onClick"
  >
    <template v-if="collapsed">
      <span class="text-sm font-semibold uppercase tracking-wide text-slate-400">
        {{ columnHeading }}
      </span>
      <span class="flex shrink-0 items-center gap-2 text-xs">
        <span class="text-slate-500">Repos</span>
        <span
          :class="hasSessionClipboard && !disabled ? 'text-blue-300' : 'text-slate-500'"
        >
          {{ hasSessionClipboard && !disabled ? 'Coller' : '+ Ajouter' }}
        </span>
      </span>
    </template>
    <template v-else-if="layoutVariant === 'spaced'">
      <span class="text-xs font-medium text-slate-500">Jour de repos</span>
      <span
        class="text-[11px]"
        :class="hasSessionClipboard && !disabled ? 'text-blue-300' : 'text-slate-500'"
      >
        {{ hasSessionClipboard && !disabled ? 'Coller une séance' : 'Ajouter une séance' }}
      </span>
    </template>
    <template v-else>
      <span
        class="text-[10px] font-medium leading-tight text-slate-500"
        style="writing-mode: vertical-rl; text-orientation: mixed"
      >
        Jour de repos
      </span>
      <span
        class="text-[9px] leading-tight"
        :class="hasSessionClipboard && !disabled ? 'text-blue-300' : 'text-slate-500'"
        style="writing-mode: vertical-rl; text-orientation: mixed"
      >
        {{ hasSessionClipboard && !disabled ? 'Coller une séance' : 'Ajouter une séance' }}
      </span>
    </template>
  </button>
</template>
