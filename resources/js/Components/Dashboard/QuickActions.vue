<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';

const { t } = useI18n();

defineProps({
  compact: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['add-athlete', 'open-competition']);

const actions = computed(() => [
  {
    key: 'program',
    label: t('app.coachDash.createProgram'),
    href: '/program-builder',
    icon: 'clipboard',
    class: 'border-blue-500/40 bg-blue-600 text-white hover:bg-blue-500 shadow-blue-900/40',
  },
  {
    key: 'athlete',
    label: t('app.coachDash.addAthlete'),
    icon: 'users',
    class: 'border-blue-500/40 bg-blue-950/40 text-blue-100 hover:bg-blue-950/60',
  },
  {
    key: 'message',
    label: t('app.coachDash.sendMessage'),
    href: '/messaging',
    icon: 'chat',
    class: 'border-slate-700 bg-slate-950/50 text-slate-200 hover:border-slate-600',
  },
  {
    key: 'competition',
    label: t('app.coachDash.createCompetition'),
    icon: 'trophy',
    class: 'border-slate-700 bg-slate-950/50 text-slate-200 hover:border-slate-600',
  },
]);
</script>

<template>
  <motion.div
    :initial="{ opacity: 0 }"
    :animate="{ opacity: 1 }"
    class="flex flex-wrap gap-2"
  >
    <component
      :is="action.href ? Link : 'button'"
      v-for="action in actions"
      :key="action.key"
      :href="action.href"
      type="button"
      class="inline-flex items-center gap-2 rounded-[14px] border px-3.5 py-2 text-sm font-semibold shadow-lg transition duration-200 hover:shadow-[0_0_18px_rgba(59,130,246,0.2)]"
      :class="[action.class, compact ? 'text-xs' : '']"
      @click="
        action.key === 'athlete'
          ? $emit('add-athlete')
          : action.key === 'competition'
            ? $emit('open-competition')
            : null
      "
    >
      <UiIcon :name="action.icon" class="h-4 w-4" />
      {{ action.label }}
    </component>
  </motion.div>
</template>
