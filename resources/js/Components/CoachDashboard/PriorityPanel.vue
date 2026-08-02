<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import AnimatedCounter from './AnimatedCounter.vue';
import SectionHeader from './SectionHeader.vue';
import { cardHover, cardShell } from './dashboardUi';

const { t } = useI18n();

const props = defineProps({
  dailyPending: { type: Number, default: 0 },
  weeklyPending: { type: Number, default: 0 },
  alertsCount: { type: Number, default: 0 },
  criticalAlerts: { type: Number, default: 0 },
  unreadMessages: { type: Number, default: 0 },
});

function feedbackCard({
  key,
  title,
  pending,
  href,
  icon,
  accent,
}) {
  const remaining = Math.max(0, pending);

  return {
    key,
    title,
    value: remaining,
    unit: t('app.coachDash.toProcess'),
    emptyLabel: t('app.coachDash.allUpToDate'),
    detail: null,
    href,
    cta: remaining > 0 ? t('app.coachDash.treatNow') : t('app.coachDash.seeFeedbacks'),
    icon,
    accent,
  };
}

const cards = computed(() => [
  feedbackCard({
    key: 'daily',
    title: t('app.coachDash.dailyFeedback'),
    pending: props.dailyPending,
    href: '/feedbacks?filter=pending',
    icon: 'list',
    accent: 'border-amber-500/30 bg-gradient-to-br from-amber-500/10 to-transparent',
  }),
  feedbackCard({
    key: 'weekly',
    title: t('app.coachDash.weeklyFeedback'),
    pending: props.weeklyPending,
    href: '/feedbacks?filter=pending',
    icon: 'calendar',
    accent: 'border-indigo-500/30 bg-gradient-to-br from-indigo-500/10 to-transparent',
  }),
  {
    key: 'alerts',
    title: t('app.coachDash.alerts'),
    value: props.alertsCount,
    unit: t('app.coachDash.alertsUnit', props.alertsCount || 1),
    emptyLabel: t('app.coachDash.noAlerts'),
    detail:
      props.alertsCount === 0
        ? t('app.coachDash.nothingToWatch')
        : props.criticalAlerts > 0
          ? t('app.coachDash.criticalSummary', props.criticalAlerts, {
              critical: props.criticalAlerts,
              total: props.alertsCount,
            })
          : t('app.coachDash.alertsToReview', { count: props.alertsCount }),
    href: '#dashboard-alerts',
    cta: props.alertsCount > 0 ? t('app.coachDash.seeAlerts') : t('app.coachDash.allGood'),
    icon: 'alert',
    accent: 'border-rose-500/30 bg-gradient-to-br from-rose-500/10 to-transparent',
  },
  {
    key: 'messages',
    title: t('app.coachDash.messaging'),
    value: props.unreadMessages,
    unit: t('app.coachDash.unreadUnit', props.unreadMessages || 1),
    emptyLabel: t('app.coachDash.inboxUpToDate'),
    detail:
      props.unreadMessages > 0
        ? t('app.coachDash.conversationsWaiting', props.unreadMessages, {
            count: props.unreadMessages,
          })
        : t('app.coachDash.noPendingMessages'),
    href: '/messaging',
    cta: props.unreadMessages > 0 ? t('app.coachDash.openMessaging') : t('app.coachDash.open'),
    icon: 'chat',
    accent: 'border-blue-500/30 bg-gradient-to-br from-blue-500/10 to-transparent',
  },
]);
</script>

<template>
  <section>
    <SectionHeader
      :eyebrow="t('app.coachDash.priority')"
      :title="t('app.coachDash.priorityActions')"
    />

    <div
      class="-mx-1 mt-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-1 pb-2 [scrollbar-width:thin] lg:mx-0 lg:grid lg:snap-none lg:grid-cols-4 lg:overflow-visible lg:px-0 lg:pb-0"
    >
      <motion.div
        v-for="(card, index) in cards"
        :key="card.key"
        :initial="{ opacity: 0, y: 18 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: 0.05 * index, duration: 0.4, ease: [0.22, 1, 0.36, 1] }"
        class="w-[min(16.5rem,78vw)] shrink-0 snap-start lg:w-auto lg:min-w-0"
      >
        <component
          :is="card.href.startsWith('#') ? 'a' : Link"
          :href="card.href"
          :class="[cardShell, cardHover, card.accent, 'flex h-full flex-col']"
        >
          <div class="flex items-start">
            <span
              class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-950/50 text-white/90"
            >
              <UiIcon :name="card.icon" class="h-5 w-5" />
            </span>
          </div>

          <p class="mt-4 text-sm font-medium text-slate-400">{{ card.title }}</p>

          <div class="mt-2 flex items-baseline gap-2">
            <p class="text-4xl font-bold tracking-tight text-white tabular-nums leading-none">
              <AnimatedCounter :value="card.value" />
            </p>
            <p class="text-sm font-medium text-slate-400">{{ card.unit }}</p>
          </div>

          <div v-if="card.value === 0 || card.detail" class="mt-4 flex-1">
            <p class="text-xs leading-relaxed text-slate-500">
              {{ card.value > 0 ? card.detail : card.emptyLabel }}
            </p>
          </div>
          <div v-else class="mt-4 flex-1" />

          <p class="mt-4 text-sm font-semibold text-blue-300">
            {{ card.cta }} →
          </p>
        </component>
      </motion.div>
    </div>
  </section>
</template>
