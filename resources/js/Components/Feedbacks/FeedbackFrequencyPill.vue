<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
  frequency: { type: String, default: null },
  compact: { type: Boolean, default: false },
});

const meta = computed(() => {
  if (props.frequency === 'daily') {
    return {
      label: props.compact ? t('app.feedbacks.dailyShort') : t('app.feedbacks.daily'),
      class: 'border-sky-500/35 bg-sky-500/10 text-sky-300',
    };
  }
  if (props.frequency === 'weekly') {
    return {
      label: props.compact ? t('app.feedbacks.weeklyShort') : t('app.feedbacks.weekly'),
      class: 'border-violet-500/35 bg-violet-500/10 text-violet-300',
    };
  }
  return null;
});
</script>

<template>
  <span
    v-if="meta"
    class="inline-flex shrink-0 rounded-full border px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
    :class="meta.class"
  >
    {{ meta.label }}
  </span>
</template>
