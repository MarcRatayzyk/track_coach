<script setup>
import { useI18n } from 'vue-i18n';
import { Link } from '@inertiajs/vue3';

const { t } = useI18n();

defineProps({
  isCoach: {
    type: Boolean,
    default: true,
  },
});
</script>

<template>
  <div
    class="flex h-full min-h-0 flex-1 flex-col items-center justify-center overflow-hidden rounded-[18px] border border-slate-800 bg-slate-900/50 px-6 py-12 text-center shadow-xl backdrop-blur-md"
  >
    <div class="relative mb-8 h-40 w-40">
      <div class="absolute inset-0 rounded-full bg-gradient-to-br from-blue-600/20 via-slate-800/40 to-transparent blur-2xl" />
      <div class="absolute inset-6 rounded-[28px] border border-blue-500/20 bg-gradient-to-br from-slate-800/80 to-slate-950/90 shadow-2xl shadow-blue-950/40" />
      <div class="absolute inset-10 rounded-[22px] border border-slate-700/60 bg-slate-900/80" />
      <div class="absolute left-1/2 top-1/2 flex h-14 w-14 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-2xl bg-blue-600/20 ring-1 ring-blue-500/30">
        <svg class="h-7 w-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.065 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155"
          />
        </svg>
      </div>
      <div class="absolute -right-1 top-8 h-3 w-3 animate-pulse rounded-full bg-blue-500 shadow-[0_0_12px_rgba(59,130,246,0.8)]" />
      <div class="absolute bottom-6 left-2 h-2 w-2 rounded-full bg-slate-600" />
    </div>

    <h2 class="text-xl font-bold text-white">{{ t('app.messaging.selectConversation') }}</h2>
    <p class="mt-3 max-w-sm text-sm leading-relaxed text-slate-400">
      <template v-if="isCoach">
        {{ t('app.messaging.emptyCoachHint') }}
      </template>
      <template v-else>
        {{ t('app.messaging.emptyAthleteHint') }}
      </template>
    </p>

    <Link
      v-if="isCoach"
      href="/athletes"
      class="mt-6 inline-flex items-center gap-2 rounded-[14px] border border-blue-500/30 bg-blue-600/10 px-4 py-2.5 text-sm font-semibold text-blue-300 transition duration-200 hover:bg-blue-600/20 hover:text-blue-200"
    >
      {{ t('app.messaging.openProfileShort') }}
    </Link>
  </div>
</template>
