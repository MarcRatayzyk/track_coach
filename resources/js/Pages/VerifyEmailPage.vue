<script>
export default {
  layout: null,
};
</script>

<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineProps({
  status: {
    type: String,
    default: null,
  },
  mailError: {
    type: String,
    default: null,
  },
  trialDays: {
    type: Number,
    default: 14,
  },
});

const form = useForm({});

function resend() {
  form.post('/email/verification-notification');
}
</script>

<template>
  <div class="min-h-screen bg-slate-950 px-4 py-12 text-slate-100">
    <Head :title="t('auth.verifyEmail.pageTitle')" />
    <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
      <h1 class="text-2xl font-bold text-white">{{ t('auth.verifyEmail.title') }}</h1>
      <p class="mt-3 text-slate-400">
        {{ t('auth.verifyEmail.subtitle') }}
      </p>
      <p class="mt-3 text-sm text-slate-500">
        {{ t('auth.verifyEmail.trialActive', { days: trialDays }) }}
      </p>

      <div
        v-if="$page.props.flash?.success"
        class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200"
      >
        {{ $page.props.flash.success }}
      </div>

      <p
        v-if="status === 'verification-link-sent'"
        class="mt-4 rounded-xl border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200"
      >
        {{ t('auth.verifyEmail.linkSent') }}
      </p>

      <p
        v-if="mailError || $page.props.flash?.error"
        class="mt-4 rounded-xl border border-rose-500/30 bg-rose-950/40 px-4 py-3 text-sm text-rose-200"
      >
        {{ mailError || $page.props.flash?.error }}
      </p>

      <button
        type="button"
        :disabled="form.processing"
        class="mt-8 w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        @click="resend"
      >
        {{ t('auth.verifyEmail.resend') }}
      </button>

      <button
        type="button"
        class="mt-4 w-full text-sm text-slate-500 hover:text-slate-300"
        @click="router.post('/logout')"
      >
        {{ t('auth.verifyEmail.logOut') }}
      </button>

      <p class="mt-6 text-center text-sm text-slate-500">
        <Link href="/" class="text-blue-400 hover:text-blue-300">{{ t('auth.verifyEmail.backHome') }}</Link>
      </p>
    </div>
  </div>
</template>
