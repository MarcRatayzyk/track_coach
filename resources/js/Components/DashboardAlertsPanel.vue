<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import UiIcon from './UiIcon.vue';

const props = defineProps({
  alerts: {
    type: Array,
    default: () => [],
  },
});

const severityStyles = {
  critical: {
    border: 'border-red-500/35',
    bg: 'bg-red-950/25',
    hover: 'hover:border-red-500/50 hover:bg-red-950/35',
    icon: 'text-red-400',
    badge: 'bg-red-500/20 text-red-200',
  },
  warning: {
    border: 'border-amber-500/35',
    bg: 'bg-amber-950/20',
    hover: 'hover:border-amber-500/50 hover:bg-amber-950/30',
    icon: 'text-amber-400',
    badge: 'bg-amber-500/20 text-amber-200',
  },
  info: {
    border: 'border-blue-500/30',
    bg: 'bg-blue-950/15',
    hover: 'hover:border-blue-500/45 hover:bg-blue-950/25',
    icon: 'text-blue-400',
    badge: 'bg-blue-500/20 text-blue-200',
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
  unread_message: 'chat',
  adherence_high: 'trophy',
  pr_celebration: 'trophy',
};

const severityLabels = {
  critical: 'Urgent',
  warning: 'Attention',
  info: 'Info',
};

const items = computed(() => props.alerts ?? []);
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

function athleteLabel(alert) {
  return alert.athlete_name?.trim() || '—';
}

const canShare = computed(() => Boolean(selectedAlert.value?.share_payload));

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
    title: selectedAlert.value?.title ?? 'Track Coach',
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
</script>

<template>
  <section class="flex h-full min-h-0 flex-col rounded-xl border border-orange-500/25 bg-slate-900/50 p-4 shadow-lg">
    <div class="flex shrink-0 flex-wrap items-start justify-between gap-3">
      <div class="flex items-center gap-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-orange-500/15 text-orange-400">
          <UiIcon name="alert" class="h-5 w-5" />
        </span>
        <div>
          <h2 class="text-base font-semibold text-white">Alertes</h2>
        </div>
      </div>
      <span
        v-if="items.length"
        class="rounded-full bg-orange-950/50 px-2.5 py-1 text-xs font-medium text-orange-200"
      >
        {{ items.length }} alerte{{ items.length > 1 ? 's' : '' }}
      </span>
    </div>

    <p
      v-if="!items.length"
      class="mt-4 rounded-lg border border-dashed border-slate-700 bg-slate-950/40 px-4 py-8 text-center text-sm text-slate-500"
    >
      Aucune alerte pour le moment — tout semble sous contrôle.
    </p>

    <ul
      v-else
      class="tc-scrollbar tc-scrollbar-alerts mt-4 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1.5 lg:max-h-[17.5rem]"
    >
      <li v-for="alert in items" :key="alert.key">
        <button
          type="button"
          class="flex w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left transition"
          :class="[stylesFor(alert).border, stylesFor(alert).bg, stylesFor(alert).hover]"
          @click="openAlert(alert)"
        >
          <span
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-950/50"
            :class="stylesFor(alert).icon"
          >
            <UiIcon :name="iconFor(alert)" class="h-4 w-4" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-white">{{ alert.title }}</p>
            <p class="mt-0.5 truncate text-xs text-slate-400">{{ athleteLabel(alert) }}</p>
          </div>
          <span
            class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
            :class="stylesFor(alert).badge"
          >
            {{ severityLabels[alert.severity] ?? alert.severity }}
          </span>
        </button>
      </li>
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
                  {{ severityLabels[selectedAlert.severity] ?? selectedAlert.severity }}
                </span>
              </div>
              <p v-if="selectedAlert.athlete_name" class="mt-2 text-base text-blue-400">
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
              Voir le détail
            </Link>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
