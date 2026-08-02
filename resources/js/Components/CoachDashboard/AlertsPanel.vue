<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { motion } from 'motion-v';
import UiIcon from '../UiIcon.vue';
import SectionHeader from './SectionHeader.vue';
import { cardShell } from './dashboardUi';
import { useDismissedAlerts } from '../../composables/useDismissedAlerts';
const { t } = useI18n();

const props = defineProps({
  alerts: { type: Array, default: () => [] },
});

const { dismiss, filterActive } = useDismissedAlerts();

const severityOrder = { critical: 0, warning: 1, info: 2 };

const severityStyles = {
  critical: {
    border: 'border-red-500/40',
    bg: 'bg-red-950/30',
    hover: 'hover:border-red-500/55 hover:bg-red-950/40',
    icon: 'text-red-400',
    badge: 'border border-red-500/40 bg-red-500/20 text-red-200',
    label: 'Critique',
    bar: 'bg-red-500',
  },
  warning: {
    border: 'border-amber-500/35',
    bg: 'bg-amber-950/20',
    hover: 'hover:border-amber-500/50 hover:bg-amber-950/30',
    icon: 'text-amber-400',
    badge: 'border border-amber-500/50 bg-amber-500/20 text-amber-200',
    label: 'Attention',
    bar: 'bg-amber-400',
  },
  info: {
    border: 'border-blue-500/30',
    bg: 'bg-blue-950/15',
    hover: 'hover:border-blue-500/45 hover:bg-blue-950/25',
    icon: 'text-blue-400',
    badge: 'border border-blue-500/40 bg-blue-500/20 text-blue-200',
    label: 'Information',
    bar: 'bg-blue-400',
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

const items = computed(() =>
  filterActive(props.alerts).sort((a, b) => {
    const sa = severityOrder[a.severity] ?? 9;
    const sb = severityOrder[b.severity] ?? 9;
    if (sa !== sb) return sa - sb;
    return String(b.sort_date || '').localeCompare(String(a.sort_date || ''));
  }),
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

const canShare = computed(() => Boolean(selectedAlert.value?.share_payload));

const detailItems = computed(() => {
  const list = selectedAlert.value?.items;
  return Array.isArray(list) ? list : [];
});

const alertSubtitle = (alert) =>
  alert?.body?.trim() || alert?.athlete_name?.trim() || '—';

const sharePreview = computed(() => {
  const payload = selectedAlert.value?.share_payload;
  if (!payload) return null;
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
  if (!sharePreview.value) return;
  const shareText = `${sharePreview.value.socialText}\n${window.location.origin}${sharePreview.value.shareUrl}`;
  try {
    if (navigator.share) {
      await navigator.share({
        title: selectedAlert.value?.title ?? 'Power Roster',
        text: shareText,
        url: `${window.location.origin}${sharePreview.value.shareUrl}`,
      });
      shareFeedback.value = t('app.coachDash.sharedOk');
      return;
    }
    await navigator.clipboard.writeText(shareText);
    shareFeedback.value = t('app.coachDash.copiedOk');
  } catch {
    shareFeedback.value = t('app.coachDash.shareFailed');
  }
}
</script>

<template>
  <section
    id="dashboard-alerts"
    :class="[cardShell, 'flex h-full min-h-0 min-w-0 scroll-mt-24 flex-col overflow-hidden border-orange-500/20 p-4 sm:p-5']"
  >
    <SectionHeader
      eyebrow="Signaux"
      title="Alertes"
    >
      <template #actions>
        <span
          v-if="items.length"
          class="rounded-full bg-orange-950/50 px-2.5 py-1 text-xs font-medium text-orange-200"
        >
          {{ items.length }}
        </span>
      </template>
    </SectionHeader>

    <p
      v-if="!items.length"
      class="mt-6 rounded-xl border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
    >
      Aucune alerte — tout semble sous contrôle.
    </p>

    <ul
      v-else
      class="tc-scrollbar mt-5 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1 lg:max-h-[22rem]"
    >
      <motion.li
        v-for="(alert, index) in items"
        :key="alert.key"
        :initial="{ opacity: 0, y: 8 }"
        :animate="{ opacity: 1, y: 0 }"
        :transition="{ delay: index * 0.03, duration: 0.3 }"
      >
        <div
          class="flex w-full min-w-0 items-stretch overflow-hidden rounded-[1.05rem] border"
          :class="[stylesFor(alert).border, stylesFor(alert).bg]"
        >
          <button
            type="button"
            class="flex min-w-0 flex-1 items-stretch text-left transition duration-200"
            :class="stylesFor(alert).hover"
            @click="openAlert(alert)"
          >
            <span class="w-1 shrink-0 self-stretch" :class="stylesFor(alert).bar" />
            <span class="flex min-w-0 flex-1 items-start gap-3 px-3 py-3">
              <span
                class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-950/50"
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
                  {{ alertSubtitle(alert) }}
                </p>
              </div>
            </span>
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
          class="tc-scrollbar w-full max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-8 shadow-2xl"
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
              <p class="text-base font-semibold text-white">{{ sharePreview.headline }}</p>
              <p class="mt-1 text-sm text-slate-400">{{ sharePreview.subline }}</p>
              <p class="mt-2 text-sm font-medium text-blue-300">{{ sharePreview.athleteName }}</p>
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
              v-if="!detailItems.length"
              :href="selectedAlert.href"
              class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg hover:bg-blue-500"
              @click="closeModal"
            >
              Voir le détail
            </Link>
            <Link
              v-else
              :href="selectedAlert.href"
              class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg hover:bg-blue-500"
              @click="closeModal"
            >
              Voir tous les retours
            </Link>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
