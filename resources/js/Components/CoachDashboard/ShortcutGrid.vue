<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';
import { cardHover, cardShell } from './dashboardUi';
const { t } = useI18n();

defineProps({
  onAddAthlete: { type: Function, default: null },
});

const shortcuts = [
  { key: 'program', label: t('app.coachDash.createProgram'), href: '/program-builder', icon: 'bolt' },
  { key: 'athlete', label: t('app.coachDash.addAthlete'), action: 'add-athlete', icon: 'users' },
  { key: 'message', label: 'Envoyer un message', href: '/messaging', icon: 'chat' },
  { key: 'comp', label: t('app.coachDash.createCompetition'), href: '/competitions', icon: 'calendar' },
  { key: 'pr', label: 'Voir tous les PR', href: '/athletes', icon: 'trophy' },
];
</script>

<template>
  <section :class="[cardShell, 'p-5']">
    <SectionHeader
      :eyebrow="t('app.coachDash.quickAccess')"
      title="Raccourcis"
    />

    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <motion.div
        v-for="(item, index) in shortcuts"
        :key="item.key"
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        :transition="{ delay: index * 0.04, duration: 0.3 }"
      >
        <Link
          v-if="item.href"
          :href="item.href"
          :class="[
            cardShell,
            cardHover,
            'flex h-full flex-col items-center gap-3 p-4 text-center',
          ]"
        >
          <span
            class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/15 text-blue-300"
          >
            <UiIcon :name="item.icon" class="h-5 w-5" />
          </span>
          <span class="text-xs font-semibold text-slate-200">{{ item.label }}</span>
        </Link>
        <button
          v-else
          type="button"
          :class="[
            cardShell,
            cardHover,
            'flex h-full w-full flex-col items-center gap-3 p-4 text-center',
          ]"
          @click="onAddAthlete?.()"
        >
          <span
            class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-500/15 text-blue-300"
          >
            <UiIcon :name="item.icon" class="h-5 w-5" />
          </span>
          <span class="text-xs font-semibold text-slate-200">{{ item.label }}</span>
        </button>
      </motion.div>
    </div>
  </section>
</template>
