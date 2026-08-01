<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
    layout: AppLayout,
};
</script>

<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    return (
        {
            trial: 'Essai gratuit',
            subscribed: 'Abonné',
            trial_expired: 'Essai terminé',
            inactive: 'Inactif',
            demo: 'Compte démo',
            demo_expired: 'Démo expirée',
        }[status] ?? status
    );
});

function formatPrice(amount) {
    const n = Number(amount);
    if (Number.isNaN(n)) {
        return amount;
    }
    return n.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const planFeatures = [
    'Programmation SBD & builder',
    'Suivi PRs, readiness & adhérence',
    'Retours vidéo annotés',
    'Messagerie coach ↔ athlète',
    'Compétitions & match plans',
    'Dashboard & alertes',
];

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
        <Head title="Abonnement" />

        <header class="mb-8">
            <h1 class="text-2xl font-bold tracking-tight text-white">Abonnement</h1>
            <p class="mt-2 max-w-2xl text-sm text-slate-400">
                Choisis un plan selon le nombre d’athlètes actifs. Un seul essai 14 jours par compte :
                après expiration, l’accès est bloqué jusqu’au paiement.
            </p>
        </header>

        <div
            v-if="isDemo"
            class="mb-6 rounded-2xl border border-amber-500/40 bg-amber-950/30 p-5 text-sm text-amber-100"
        >
            <p class="font-semibold text-amber-200">Compte démo</p>
            <p class="mt-1 text-amber-100/80">
                La démo ne peut pas être convertie en abonnement. Crée un vrai compte coach pour
                démarrer l’essai de 14 jours.
            </p>
            <Link
                href="/register"
                class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-500"
            >
                Créer mon compte coach
            </Link>
        </div>

        <section
            v-else
            class="mb-8 rounded-2xl border border-slate-700/80 bg-slate-800/40 p-5 sm:p-6"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</p>
                    <p class="mt-1 text-lg font-semibold text-white">{{ statusLabel }}</p>
                    <p v-if="sharedBilling?.trialEndsAt" class="mt-1 text-sm text-slate-400">
                        Essai jusqu’au
                        {{ new Date(sharedBilling.trialEndsAt).toLocaleDateString('fr-FR') }}
                    </p>
                    <p
                        v-if="sharedBilling?.status === 'trial_expired'"
                        class="mt-1 text-sm text-amber-300/90"
                    >
                        Ton essai est terminé. Abonne-toi pour retrouver l’accès.
                    </p>
                    <p
                        v-else-if="sharedBilling?.status === 'inactive' && sharedBilling?.canStartTrial"
                        class="mt-1 text-sm text-slate-400"
                    >
                        Tu n’as pas encore utilisé ton essai gratuit.
                    </p>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ sharedBilling?.athleteCount ?? 0 }} athlète(s) actif(s)
                        <span v-if="sharedBilling?.seatLimit != null">
                            · plafond {{ sharedBilling.seatLimit }}
                        </span>
                        <span v-else-if="sharedBilling?.status === 'subscribed'"> · illimité</span>
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
                        Démarrer l’essai 14 jours
                    </button>
                    <button
                        v-if="sharedBilling?.status === 'subscribed' && stripeConfigured"
                        type="button"
                        class="rounded-xl border border-slate-600 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-slate-800"
                        :disabled="form.processing"
                        @click="openPortal"
                    >
                        Gérer mon abonnement
                    </button>
                </div>
            </div>
            <p v-if="!stripeConfigured" class="mt-4 text-sm text-amber-300">
                Stripe n’est pas encore configuré sur cet environnement (clés / price IDs manquants).
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
                    <span class="text-base font-medium text-slate-400">/ mois</span>
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
                    Recommandé pour ton roster actuel
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
                            ? 'Plan actuel'
                            : sharedBilling?.status === 'subscribed'
                              ? `Passer à ${formatPrice(plan.price_eur)} €/mois`
                              : `Payer ${formatPrice(plan.price_eur)} €/mois`
                    }}
                </button>
            </article>
        </div>

        <p
            v-if="sharedBilling?.hasAccess"
            class="mt-6 text-center text-sm text-slate-500"
        >
            <Link href="/dashboard" class="text-blue-400 hover:underline">Retour au dashboard</Link>
        </p>
    </div>
</template>
