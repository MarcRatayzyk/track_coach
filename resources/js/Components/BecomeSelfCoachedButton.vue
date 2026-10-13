<script setup>
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';

defineProps({
  variant: {
    type: String,
    default: 'card',
  },
});

const { t } = useI18n();
const confirming = ref(false);
const processing = ref(false);

function ask() {
  confirming.value = true;
}

function cancel() {
  confirming.value = false;
}

function confirm() {
  if (processing.value) {
    return;
  }

  processing.value = true;
  router.post('/athlete/self-coaching', {}, {
    onFinish: () => {
      processing.value = false;
    },
  });
}
</script>

<template>
  <section
    v-if="variant === 'card'"
    class="rounded-2xl border border-emerald-500/30 bg-emerald-950/20 p-4"
  >
    <p class="text-[10px] font-semibold uppercase tracking-widest text-emerald-300/90">
      {{ t('app.athleteDash.selfCoachEyebrow') }}
    </p>
    <p class="mt-1 text-sm font-semibold text-white">{{ t('app.athleteDash.selfCoachTitle') }}</p>
    <p class="mt-1 text-xs leading-relaxed text-slate-400">{{ t('app.athleteDash.selfCoachBody') }}</p>

    <div v-if="!confirming" class="mt-3">
      <button
        type="button"
        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500"
        @click="ask"
      >
        {{ t('app.athleteDash.selfCoachAction') }}
      </button>
    </div>
    <div v-else class="mt-3 space-y-2">
      <p class="text-xs text-slate-300">{{ t('app.athleteDash.selfCoachConfirmHint') }}</p>
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-60"
          :disabled="processing"
          @click="confirm"
        >
          {{ t('app.athleteDash.selfCoachConfirm') }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200 hover:bg-slate-800"
          :disabled="processing"
          @click="cancel"
        >
          {{ t('app.athleteDash.selfCoachCancel') }}
        </button>
      </div>
    </div>
  </section>

  <div v-else class="space-y-2">
    <button
      v-if="!confirming"
      type="button"
      class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-emerald-200 transition hover:bg-slate-800/60"
      :class="variant === 'sidebar' ? 'justify-center border border-emerald-500/30 bg-emerald-950/30 px-3 py-2 text-center hover:border-emerald-400/50 hover:bg-emerald-950/50' : ''"
      @click="ask"
    >
      {{ t('app.athleteDash.selfCoachAction') }}
    </button>
    <div
      v-else
      class="rounded-xl border border-emerald-500/30 bg-emerald-950/30 px-3 py-2.5"
    >
      <p class="text-xs leading-relaxed text-slate-300">{{ t('app.athleteDash.selfCoachConfirmHint') }}</p>
      <div class="mt-2 flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-500 disabled:opacity-60"
          :disabled="processing"
          @click="confirm"
        >
          {{ t('app.athleteDash.selfCoachConfirm') }}
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-600 px-2.5 py-1.5 text-xs font-semibold text-slate-200"
          :disabled="processing"
          @click="cancel"
        >
          {{ t('app.athleteDash.selfCoachCancel') }}
        </button>
      </div>
    </div>
  </div>
</template>
