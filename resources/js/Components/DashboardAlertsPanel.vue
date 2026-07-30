<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { motion } from 'motion-v';
import UiIcon from './UiIcon.vue';
import SectionHeader from './Dashboard/SectionHeader.vue';
import { useDismissedAlerts } from '../composables/useDismissedAlerts';

const props = defineProps({
  alerts: {
    type: Array,
    default: () => [],
  },
});

const { dismiss, filterActive } = useDismissedAlerts();

const severityStyles = {
  critical: {
    border: 'border-red-500/40',
    bg: 'bg-red-950/30',
    hover: 'hover:border-red-500/55 hover:bg-red-950/40',
    icon: 'text-red-400',
    badge: 'border border-red-500/40 bg-red-500/20 text-red-200',
    bar: 'bg-red-500',
    label: 'Critique',
  },
  warning: {
    border: 'border-amber-500/35',
    bg: 'bg-amber-950/20',
    hover: 'hover:border-amber-500/50 hover:bg-amber-950/30',
    icon: 'text-amber-400',
    badge: 'border border-amber-500/50 bg-amber-500/20 text-amber-200',
    bar: 'bg-amber-500',
    label: 'Attention',
  },
  info: {
    border: 'border-blue-500/30',
    bg: 'bg-blue-950/15',
    hover: 'hover:border-blue-500/45 hover:bg-blue-950/25',
    icon: 'text-blue-400',
    badge: 'border border-blue-500/40 bg-blue-500/20 text-blue-200',
    bar: 'bg-blue-500',
    label: 'Information',
  },
};

const typeIcons = {
  block_ending: 'bolt',
  competition_soon: 'calendar',
  adherence_drop: 'list',
  adherence_low: 'list',
  no_program: 'clipboard',
  inactive_athlete: 'users',
  feedback_pending_reply: 'video',
  feedback_not_sent: 'list',
  feedback_overdue: 'alert',
  unread_message: 'chat',
  adherence_high: 'trophy',
  pr_celebration: 'trophy',
};

const severityRank = { critical: 0, warning: 1, info: 2 };

const items = computed(() =>
  filterActive(props.alerts).sort(
    (a, b) => (severityRank[a.severity] ?? 9) - (severityRank[b.severity] ?? 9),
  ),
);

const selectedAlert = ref(null);
const shareFeedback = ref('');
const modalOpen = computed(() => selectedAlert.value !== null);

function stylesFor(alert) {
  return severityStyles[alert.severity] ?? severityStyles.info;
}

function iconFor(alert) {
  return typeIcons[alert.type] ?? 'alert';
}

function openAlert(alert) {
  selectedAlert.value = alert;
}

function closeModal() {
  selectedAlert.value = null;
  shareFeedback.value = '';
}

function dismissAlert(alert) {
  if (!alert?.key) return;
  dismiss(alert.key);
  if (selectedAlert.value?.key === alert.key) {
    closeModal();
  }
}

function athleteLabel(alert) {
  return alert.body?.trim() || alert.athlete_name?.trim() || '—';
}

const canShare = computed(() => Boolean(selectedAlert.value?.share_payload));

const detailItems = computed(() => {
  const list = selectedAlert.value?.items;
  return Array.isArray(list) ? list : [];
});

const sharePreview = computed(() => {
  const payload = selectedAlert.value?.share_payload;
  if (!payload) {
    return null;
  }
  return {
    headline: payload.headline ?? selectedAlert.value?.title ?? '',
    subline: payload.subline ?? selectedAlert.value?.body ?? '',
    athleteName: payload.athlete_name ?? selectedAlert.value?.athlete_name ?? '',
    socialText: payload.social_text ?? '',
    shareUrl: payload.share_url ?? selectedAlert.value?.href ?? '/dashboard',
    templates: Array.isArray(payload.templates) ? payload.templates : [],
  };
});

async function shareAlert() {
  if (!sharePreview.value) {
    return;
  }

  const shareText = `${sharePreview.value.socialText}\n${window.location.origin}${sharePreview.value.shareUrl}`;
  const shareData = {
    title: selectedAlert.value?.title ?? 'Power Roster',
    text: shareText,
    url: `${window.location.origin}${sharePreview.value.shareUrl}`,
  };

  try {
    if (navigator.share) {
      await navigator.share(shareData);
      shareFeedback.value = 'Partagé avec succès.';
      return;
    }

    await navigator.clipboard.writeText(shareText);
    shareFeedback.value = 'Texte copié. Tu peux le coller où tu veux.';
  } catch (_error) {
    shareFeedback.value = 'Partage annulé ou impossible sur cet appareil.';
  }
}

const counts = computed(() => ({
  critical: items.value.filter((a) => a.severity === 'critical').length,
  warning: items.value.filter((a) => a.severity === 'warning').length,
  info: items.value.filter((a) => a.severity === 'info').length,
}));
</script>

<template>
  <section
    id="dashboard-alerts"
    class="flex h-full min-h-0 flex-col rounded-[20px] border border-orange-500/25 bg-slate-900/50 p-4 shadow-lg backdrop-blur-sm sm:p-5"
  >
    <SectionHeader
      eyebrow="Surveillance"
      title="Alertes"
    >
      <template #actions>
        <div class="flex flex-wrap gap-1.5">
          <span
            v-if="counts.critical"
            class="rounded-full border border-red-500/40 bg-red-500/15 px-2 py-0.5 text-[10px] font-semibold text-red-200"
          >
            {{ counts.critical }} critique{{ counts.critical > 1 ? 's' : '' }}
          </span>
          <span
            v-if="counts.warning"
            class="rounded-full border border-amber-500/40 bg-amber-500/15 px-2 py-0.5 text-[10px] font-semibold text-amber-200"
          >
            {{ counts.warning }} attention
          </span>
          <span
            v-if="counts.info"
            class="rounded-full border border-blue-500/40 bg-blue-500/15 px-2 py-0.5 text-[10px] font-semibold text-blue-200"
          >
            {{ counts.info }} info
          </span>
        </div>
      </template>
    </SectionHeader>

    <p
      v-if="!items.length"
      class="mt-4 rounded-[16px] border border-dashed border-slate-700 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500"
    >
      Aucune alerte pour le moment — tout semble sous contrôle.
    </p>

    <ul
      v-else
      class="tc-scrollbar tc-scrollbar-alerts mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1.5 lg:max-h-[22rem]"
    >
      <motion.li
        v-for="(alert, index) in items"
        :key="alert.key"
        :initial="{ opacity: 0, y: 8 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: index * 0.04, duration: 0.25 }"
      >
        <div
          class="relative flex w-full min-w-0 items-stretch overflow-hidden rounded-[16px] border"
          :class="[stylesFor(alert).border, stylesFor(alert).bg]"
        >
          <button
            type="button"
            class="relative flex min-w-0 flex-1 items-start gap-3 px-3 py-3 text-left transition duration-200 hover:-translate-y-0.5 hover:shadow-[0_0_18px_rgba(59,130,246,0.1)]"
            :class="stylesFor(alert).hover"
            @click="openAlert(alert)"
          >
            <div class="absolute inset-y-0 left-0 w-0.5" :class="stylesFor(alert).bar" />
            <span
              class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-[12px] bg-slate-950/50"
              :class="stylesFor(alert).icon"
            >
              <UiIcon :name="iconFor(alert)" class="h-4 w-4" />
            </span>
            <div class="min-w-0 flex-1 overflow-hidden">
              <div class="flex min-w-0 items-center gap-2">
                <p class="min-w-0 truncate text-sm font-semibold text-white">
                  {{ alert.title }}
                </p>
                <span
                  class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                  :class="stylesFor(alert).badge"
                >
                  {{ stylesFor(alert).label }}
                </span>
              </div>
              <p class="mt-0.5 line-clamp-2 break-words text-xs leading-snug text-slate-400">
                {{ athleteLabel(alert) }}
              </p>
            </div>
          </button>
          <button
            type="button"
            class="shrink-0 self-stretch border-l border-slate-700/60 px-3 text-xs font-semibold text-slate-300 transition hover:bg-slate-900/60 hover:text-white"
            title="Écarter cette alerte"
            @click.stop="dismissAlert(alert)"
          >
            OK
          </button>
        </div>
      </motion.li>
    </ul>

    <Teleport to="body">
      <div
        v-if="modalOpen && selectedAlert"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        @click.self="closeModal"
      >
        <div
          class="tc-scrollbar tc-scrollbar-alerts w-full max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-8 shadow-2xl"
          @click.stop
        >
          <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h3 class="text-xl font-semibold text-white">{{ selectedAlert.title }}</h3>
                <span
                  class="rounded-full px-2.5 py-1 text-xs font-semibold uppercase tracking-wide"
                  :class="stylesFor(selectedAlert).badge"
                >
                  {{ stylesFor(selectedAlert).label }}
                </span>
              </div>
              <p v-if="selectedAlert.athlete_name && !detailItems.length" class="mt-2 text-base text-blue-400">
                {{ selectedAlert.athlete_name }}
              </p>
            </div>
            <button
              type="button"
              class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
              aria-label="Fermer"
              @click="closeModal"
            >
              ✕
            </button>
          </div>

          <p class="mt-6 text-base leading-relaxed text-slate-300">{{ selectedAlert.body }}</p>

          <ul
            v-if="detailItems.length"
            class="tc-scrollbar mt-5 max-h-72 space-y-2 overflow-y-auto pr-1"
          >
            <li
              v-for="(item, idx) in detailItems"
              :key="`${item.athlete_id}-${item.label}-${idx}`"
            >
              <Link
                :href="item.href || selectedAlert.href"
                class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-950/50 px-3 py-3 transition hover:border-blue-500/40 hover:bg-slate-950"
                @click="closeModal"
              >
                <span
                  class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-950/70 text-xs font-semibold text-blue-200"
                >
                  {{ (item.athlete_name || '?').slice(0, 2).toUpperCase() }}
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-semibold text-white">
                    {{ item.athlete_name }}
                  </span>
                  <span class="mt-0.5 block truncate text-xs text-slate-400">
                    {{ item.label }}
                  </span>
                </span>
                <span class="shrink-0 text-xs font-medium text-blue-300">Voir →</span>
              </Link>
            </li>
          </ul>

          <div
            v-if="canShare && sharePreview"
            class="mt-6 rounded-xl border border-blue-500/30 bg-slate-950/70 p-5"
          >
            <p class="text-[10px] font-semibold uppercase tracking-widest text-blue-300/90">
              Aperçu partage
            </p>
            <div class="mt-2 rounded-xl border border-slate-700 bg-slate-900 p-4">
              <p class="animate-pulse text-base font-semibold text-white">{{ sharePreview.headline }}</p>
              <p class="mt-1 text-sm text-slate-400">{{ sharePreview.subline }}</p>
              <p class="mt-2 text-sm font-medium text-blue-300">{{ sharePreview.athleteName }}</p>
            </div>
            <div v-if="sharePreview.templates.length" class="mt-3 flex flex-wrap gap-2">
              <span
                v-for="template in sharePreview.templates"
                :key="template.id"
                class="rounded-full border border-slate-700 bg-slate-900 px-2.5 py-1 text-[10px] font-medium text-slate-300"
              >
                {{ template.label }}
              </span>
            </div>
            <p v-if="shareFeedback" class="mt-3 text-xs text-emerald-300">{{ shareFeedback }}</p>
          </div>

          <div class="mt-8 flex flex-wrap justify-end gap-3">
            <button
              type="button"
              class="rounded-xl border border-slate-600 px-5 py-2.5 text-sm font-medium text-slate-300 hover:bg-slate-800"
              @click="closeModal"
            >
              Fermer
            </button>
            <button
              type="button"
              class="rounded-xl border border-emerald-500/40 bg-emerald-600/15 px-5 py-2.5 text-sm font-semibold text-emerald-100 hover:bg-emerald-600/25"
              @click="dismissAlert(selectedAlert)"
            >
              OK, écarter
            </button>
            <button
              v-if="canShare"
              type="button"
              class="rounded-xl border border-blue-500/40 bg-blue-600/20 px-5 py-2.5 text-sm font-semibold text-blue-100 hover:bg-blue-600/30"
              @click="shareAlert"
            >
              Partager
            </button>
            <Link
              :href="selectedAlert.href"
              class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg hover:bg-blue-500"
              @click="closeModal"
            >
              {{ detailItems.length ? 'Voir tous les retours' : 'Voir le détail' }}
            </Link>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
