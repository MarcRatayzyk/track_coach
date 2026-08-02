<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

const resolvedPlaceholder = computed(
  () => props.placeholder || t('app.messaging.searchConversation'),
);
</script>

<template>
  <label class="relative block">
    <span class="sr-only">{{ t('app.messaging.search') }}</span>
    <svg
      class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"
      fill="none"
      viewBox="0 0 24 24"
      stroke-width="1.5"
      stroke="currentColor"
      aria-hidden="true"
    >
      <path
        stroke-linecap="round"
        stroke-linejoin="round"
        d="m21 21-4.35-4.35m1.6-5.4a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"
      />
    </svg>
    <input
      type="search"
      :value="modelValue"
      :placeholder="resolvedPlaceholder"
      class="w-full rounded-[14px] border border-slate-800 bg-slate-950/80 py-2.5 pl-10 pr-3 text-sm text-white placeholder:text-slate-600 shadow-inner transition duration-200 focus:border-blue-500/50 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
      @input="emit('update:modelValue', $event.target.value)"
    />
  </label>
</template>
