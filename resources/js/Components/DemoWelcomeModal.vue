<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();
const open = ref(false);

const welcome = computed(() => page.props.flash?.demo_welcome ?? null);

watch(
  welcome,
  (value) => {
    open.value = Boolean(value);
  },
  { immediate: true },
);

function close() {
  open.value = false;
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open && welcome"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      @click.self="close"
    >
      <div
        class="w-full max-w-md rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl lg:p-8"
        @click.stop
      >
        <div class="flex items-start justify-between gap-4">
          <h2 class="text-base font-semibold text-white">{{ t('modals.demoWelcome.title') }}</h2>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            :aria-label="t('modals.demoWelcome.close')"
            @click="close"
          >
            ✕
          </button>
        </div>

        <p class="mt-3 text-slate-400">
          <template v-if="welcome.expires_label">
            {{ t('modals.demoWelcome.body', { hours: welcome.hours, date: welcome.expires_label }) }}
          </template>
          <template v-else>
            {{ t('modals.demoWelcome.bodyNoDate', { hours: welcome.hours }) }}
          </template>
        </p>
        <ul class="mt-4 list-disc space-y-2 pl-5 text-sm text-slate-400">
          <li>{{ t('modals.demoWelcome.bullet1') }}</li>
          <li>{{ t('modals.demoWelcome.bullet2') }}</li>
          <li>{{ t('modals.demoWelcome.bullet3', { email: welcome.email }) }}</li>
        </ul>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            type="button"
            class="rounded-xl bg-blue-600 px-6 py-3 font-semibold text-white hover:bg-blue-500"
            @click="close"
          >
            {{ t('modals.demoWelcome.explore') }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
