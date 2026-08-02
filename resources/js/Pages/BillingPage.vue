<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { localeTag } from '../i18n';

const { t, locale } = useI18n();

const props = defineProps({
    plans: { type: Array, default: () => [] },
    stripeConfigured: { type: Boolean, default: false },
    isDemo: { type: Boolean, default: false },
    billing: { type: Object, default: null },
});

const page = usePage();
const sharedBilling = computed(() => props.billing ?? page.props.billing ?? null);

const form = useForm({
    plan: sharedBilling.value?.requiredPlan ?? 'starter',
});

const statusLabel = computed(() => {
    const status = sharedBilling.value?.status;
    const map = {
        trial: 'app.billing.statuses.trial',
        subscribed: 'app.billing.statuses.subscribed',
        trial_expired: 'app.billing.statuses.trialExpired',
        inactive: 'app.billing.statuses.inactive',
        demo: 'app.billing.statuses.demo',
        demo_expired: 'app.billing.statuses.demoExpired',
    };
    return map[status] ? t(map[status]) : status;
});

function formatPrice(amount) {
    const n = Number(amount);
    if (Number.isNaN(n)) {
        return amount;
    }
    return n.toLocaleString(localeTag(locale.value), {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

const planFeatures = computed(() => [
    t('app.billing.features.programming'),
    t('app.billing.features.tracking'),
    t('app.billing.features.feedback'),
    t('app.billing.features.messaging'),
    t('app.billing.features.competitions'),
    t('app.billing.features.dashboard'),
]);

function checkout(planKey) {
    form.plan = planKey;
    form.post('/billing/checkout');
}

function startTrial() {
    form.post('/billing/start-trial');
}

function openPortal() {
    form.post('/billing/portal');
}
</script>

<template>
    <div class="mx-auto w-full max-w-5xl px-4 py-8 sm:px-6">
        <Head :title="t('app.billing.title')" />

        <header class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-white">{{ t('app.billing.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                {{ t('app.billing.subtitle') }}
            </p>
        </header>

        <div
            v-if="isDemo"
            class="mb-6 rounded-2xl border border-amber-500/40 bg-amber-950/30 p-5 text-sm text-amber-100"
        >
            <p class="font-semibold text-amber-200">{{ t('app.billing.demoTitle') }}</p>
            <p class="mt-1 text-amber-100/80">
                {{ t('app.billing.demoBody') }}
            </p>
            <Link
                href="/register"
                class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500"
            >
                {{ t('app.billing.createCoachAccount') }}
            </Link>
        </div>

        <section
            v-else
            class="mb-8 rounded-2xl border border-slate-700/80 bg-slate-800/40 p-5 sm:p-6"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ t('app.billing.status') }}</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ statusLabel }}</p>
                    <p v-if="sharedBilling?.trialEndsAt" class="mt-1 text-sm text-slate-400">
                        {{
                            t('app.billing.trialUntil', {
                                date: new Date(sharedBilling.trialEndsAt).toLocaleDateString(
                                    localeTag(locale),
                                ),
                            })
                        }}
                    </p>
                    <p
                        v-if="sharedBilling?.status === 'trial_expired'"
                        class="mt-1 text-sm text-amber-300/90"
                    >
                        {{ t('app.billing.trialEnded') }}
                    </p>
                    <p
                        v-else-if="sharedBilling?.status === 'inactive' && sharedBilling?.canStartTrial"
                        class="mt-1 text-sm text-slate-400"
                    >
                        {{ t('app.billing.trialNotUsed') }}
                    </p>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ t('app.billing.activeAthletes', { count: sharedBilling?.athleteCount ?? 0 }) }}
                        <span v-if="sharedBilling?.seatLimit != null">
                            · {{ t('app.billing.seatLimit', { limit: sharedBilling.seatLimit }) }}
                        </span>
                        <span v-else-if="sharedBilling?.status === 'subscribed'">
                            · {{ t('app.billing.unlimited') }}
                        </span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-if="sharedBilling?.canStartTrial"
                        type="button"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                        :disabled="form.processing"
                        @click="startTrial"
                    >
                        {{ t('app.billing.startTrial') }}
                    </button>
                    <button
                        v-if="sharedBilling?.status === 'subscribed' && stripeConfigured"
                        type="button"
                        class="rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                        :disabled="form.processing"
                        @click="openPortal"
                    >
                        {{ t('app.billing.manageSubscription') }}
                    </button>
                </div>
            </div>
            <p v-if="!stripeConfigured" class="mt-4 text-sm text-amber-300">
                {{ t('app.billing.stripeMissing') }}
            </p>
        </section>

        <div class="grid gap-4 md:grid-cols-3">
            <article
                v-for="plan in plans"
                :key="plan.key"
                class="flex flex-col rounded-2xl border border-slate-700/80 bg-slate-800/40 p-5"
                :class="
                    sharedBilling?.requiredPlan === plan.key
                        ? 'ring-1 ring-blue-500/50'
                        : ''
                "
            >
                <p class="text-sm font-semibold uppercase tracking-wider text-blue-400">
                    {{ plan.name }}
                </p>
                <p class="mt-3 text-3xl font-black text-white">
                    {{ formatPrice(plan.price_eur) }} €
                    <span class="text-base font-medium text-slate-400">{{ t('common.perMonth') }}</span>
                </p>
                <p class="mt-2 text-sm text-slate-400">{{ plan.description }}</p>
                <ul class="mt-4 flex-1 space-y-2">
                    <li
                        v-for="feature in planFeatures"
                        :key="feature"
                        class="flex items-start gap-2 text-sm text-slate-300"
                    >
                        <span class="mt-0.5 text-blue-400" aria-hidden="true">✓</span>
                        <span>{{ feature }}</span>
                    </li>
                    <li class="flex items-start gap-2 text-sm text-slate-300">
                        <span class="mt-0.5 text-blue-400" aria-hidden="true">✓</span>
                        <span>{{ plan.description }}</span>
                    </li>
                </ul>
                <p
                    v-if="sharedBilling?.requiredPlan === plan.key"
                    class="mt-4 text-xs font-medium text-blue-300"
                >
                    {{ t('app.billing.recommended') }}
                </p>
                <button
                    v-if="!isDemo"
                    type="button"
                    class="mt-5 flex h-11 w-full shrink-0 items-center justify-center rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-lg shadow-blue-900/30 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="
                        form.processing ||
                        !stripeConfigured ||
                        (sharedBilling?.status === 'subscribed' && sharedBilling?.plan === plan.key)
                    "
                    @click="checkout(plan.key)"
                >
                    {{
                        sharedBilling?.status === 'subscribed' && sharedBilling?.plan === plan.key
                            ? t('app.billing.currentPlan')
                            : sharedBilling?.status === 'subscribed'
                              ? t('app.billing.switchTo', { price: formatPrice(plan.price_eur) })
                              : t('app.billing.pay', { price: formatPrice(plan.price_eur) })
                    }}
                </button>
            </article>
        </div>

        <p
            v-if="sharedBilling?.hasAccess"
            class="mt-6 text-center text-sm text-slate-500"
        >
            <Link href="/dashboard" class="text-blue-400 hover:underline">{{ t('app.billing.backDashboard') }}</Link>
        </p>
    </div>
</template>
