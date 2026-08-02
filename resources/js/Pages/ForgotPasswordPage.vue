<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    email: {
        type: String,
        default: '',
    },
});

const page = usePage();
const form = useForm({
    email: props.email,
});

const submitted = ref(false);

watch(
    () => page.props.flash?.success,
    (value) => {
        if (value) {
            submitted.value = true;
        }
    },
    { immediate: true },
);

const successMessage = computed(
    () => page.props.flash?.success
        ?? t('auth.forgotPassword.successDefault'),
);

function submit() {
    form.post('/forgot-password', {
        preserveScroll: true,
        onSuccess: () => {
            submitted.value = true;
        },
    });
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 px-4 py-12 text-slate-100">
        <Head :title="t('auth.forgotPassword.pageTitle')" />
        <div class="mx-auto w-full max-w-md rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
            <h1 class="text-2xl font-bold text-white">{{ t('auth.forgotPassword.title') }}</h1>

            <div
                v-if="$page.props.flash?.error"
                class="mt-6 rounded-xl border border-red-500/30 bg-red-950/40 px-4 py-3 text-sm text-red-200"
            >
                {{ $page.props.flash.error }}
            </div>

            <template v-if="submitted && $page.props.flash?.success">
                <div class="mt-6 rounded-xl border border-emerald-500/30 bg-emerald-950/40 px-4 py-3 text-sm text-emerald-200">
                    {{ successMessage }}
                </div>
                <p class="mt-4 text-slate-400">
                    {{ t('auth.forgotPassword.checkInbox') }}
                </p>
                <p class="mt-6 text-center text-sm text-slate-500">
                    <Link href="/login" class="text-blue-400 hover:text-blue-300">{{ t('auth.forgotPassword.backToLogin') }}</Link>
                </p>
            </template>

            <template v-else>
                <p class="mt-2 text-slate-400">
                    {{ t('auth.forgotPassword.subtitle') }}
                </p>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <label class="block text-sm font-medium text-slate-400">
                        {{ t('auth.forgotPassword.emailLabel') }}
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            class="mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                    </label>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                    >
                        {{ t('auth.forgotPassword.submit') }}
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-500">
                    <Link href="/login" class="text-blue-400 hover:text-blue-300">{{ t('auth.forgotPassword.backToLogin') }}</Link>
                </p>
            </template>
        </div>
    </div>
</template>
