<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';
import { motion } from 'motion-v';
import AnimatedCounter from './AnimatedCounter.vue';
import MiniSparkline from './MiniSparkline.vue';
import ProgressRing from './ProgressRing.vue';
import SectionHeader from './SectionHeader.vue';
import { cardShell } from './dashboardUi';
const { t } = useI18n();

const props = defineProps({
  performance: {
    type: Object,
    default: () => ({}),
  },
});

const p = computed(() => props.performance ?? {});

const feedbackValues = computed(() =>
  (p.value.feedback_series ?? []).map((d) => d.value ?? 0),
);

const maxFeedback = computed(() => Math.max(...feedbackValues.value, 1));

const adherenceBars = computed(() => p.value.adherence_series ?? []);

const heatmap = computed(() => p.value.heatmap ?? []);

const maxHeat = computed(() => Math.max(...heatmap.value.map((h) => h.value || 0), 1));

function heatClass(value) {
  const ratio = (value || 0) / maxHeat.value;
  if (ratio === 0) return 'bg-slate-800/60';
  if (ratio < 0.34) return 'bg-blue-900/70';
  if (ratio < 0.67) return 'bg-blue-600/70';
  return 'bg-blue-400/80';
}
</script>

<template>
  <section :class="[cardShell, 'p-5']">
    <SectionHeader
      eyebrow="Performance"
      title="Performance globale"
    />

    <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4">
        <p class="text-xs text-slate-400">Volume moyen</p>
        <p class="mt-2 text-2xl font-bold text-white">
          <AnimatedCounter :value="p.volume_proxy ?? 0" />
        </p>
        <p class="mt-1 text-[11px] text-slate-500">Proxy retours / 7 j</p>
      </div>
      <div class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4">
        <p class="text-xs text-slate-400">PR cette semaine</p>
        <p class="mt-2 text-2xl font-bold text-emerald-300">
          <AnimatedCounter :value="p.prs_this_week ?? 0" />
        </p>
        <p class="mt-1 text-[11px] text-slate-500">Nouveaux records</p>
      </div>
      <div class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4">
        <p class="text-xs text-slate-400">{{ t('app.coachDash.adherenceRate') }}</p>
        <p class="mt-2 text-2xl font-bold text-white">
          <template v-if="p.average_adherence != null">
            <AnimatedCounter :value="p.average_adherence" />%
          </template>
          <template v-else>—</template>
        </p>
        <p class="mt-1 text-[11px] text-slate-500">Moyenne 30 jours</p>
      </div>
      <div class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4">
        <p class="text-xs text-slate-400">Retours remplis</p>
        <p class="mt-2 text-2xl font-bold text-amber-200">
          <AnimatedCounter :value="p.feedback_fill_rate ?? 0" />%
        </p>
        <p class="mt-1 text-[11px] text-slate-500">Aujourd’hui</p>
      </div>
      <div class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4 sm:col-span-2 xl:col-span-1">
        <p class="text-xs text-slate-400">{{ t('app.coachDash.activeAthletes') }}</p>
        <p class="mt-2 text-2xl font-bold text-sky-200">
          <AnimatedCounter :value="p.active_athletes_7d ?? 0" />
        </p>
        <p class="mt-1 text-[11px] text-slate-500">Sur 7 jours</p>
      </div>
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
      <motion.div
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4 lg:col-span-1"
      >
        <p class="text-sm font-semibold text-white">Retours (7 jours)</p>
        <div class="mt-4 flex h-28 items-end gap-1.5">
          <div
            v-for="(point, i) in p.feedback_series ?? []"
            :key="point.date || i"
            class="group relative flex flex-1 flex-col items-center justify-end"
          >
            <div
              class="w-full rounded-t-md bg-gradient-to-t from-blue-600 to-sky-400 transition duration-200 group-hover:opacity-90"
              :style="{ height: `${Math.max(8, ((point.value || 0) / maxFeedback) * 100)}%` }"
            />
            <span class="mt-2 text-[10px] uppercase text-slate-500">{{ point.label }}</span>
          </div>
        </div>
      </motion.div>

      <motion.div
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        :transition="{ delay: 0.05 }"
        class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4"
      >
        <div class="flex items-center justify-between gap-3">
          <p class="text-sm font-semibold text-white">{{ t('app.coachDash.adherence') }}</p>
          <ProgressRing
            :value="p.average_adherence ?? 0"
            :total="100"
            :size="52"
            :stroke="5"
            color="#818cf8"
          />
        </div>
        <div class="mt-4">
          <MiniSparkline
            :values="adherenceBars.map((b) => b.value)"
            color="#818cf8"
            :height="48"
          />
        </div>
        <div class="mt-3 flex justify-between text-[10px] text-slate-500">
          <span v-for="bar in adherenceBars" :key="bar.label">{{ bar.label }}</span>
        </div>
      </motion.div>

      <motion.div
        :initial="{ opacity: 0, y: 10 }"
        :whileInView="{ opacity: 1, y: 0 }"
        :viewport="{ once: true }"
        :transition="{ delay: 0.1 }"
        class="rounded-[1.15rem] border border-slate-800/80 bg-slate-950/40 p-4"
      >
        <p class="text-sm font-semibold text-white">Heatmap retours</p>
        <p class="mt-1 text-xs text-slate-500">28 derniers jours</p>
        <div class="mt-4 grid grid-cols-7 gap-1.5">
          <div
            v-for="cell in heatmap"
            :key="cell.date"
            class="aspect-square rounded-md transition duration-200 hover:scale-110"
            :class="heatClass(cell.value)"
            :title="`${cell.date} · ${cell.value}`"
          />
        </div>
      </motion.div>
    </div>
  </section>
</template>
