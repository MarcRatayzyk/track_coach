<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';

const { t } = useI18n();

const sharedFeatures = computed(() => [
    t('landing.pricing.sharedFeatures.programming'),
    t('landing.pricing.sharedFeatures.tracking'),
    t('landing.pricing.sharedFeatures.feedback'),
    t('landing.pricing.sharedFeatures.messaging'),
    t('landing.pricing.sharedFeatures.competitions'),
    t('landing.pricing.sharedFeatures.dashboard'),
]);

const plans = computed(() => [
    {
        key: 'starter',
        name: t('landing.pricing.plans.starter.name'),
        price: '34,99',
        description: t('landing.pricing.plans.starter.description'),
        cta: t('landing.pricing.plans.starter.cta'),
        href: '/subscribe/starter',
        highlight: false,
        features: [...sharedFeatures.value, t('landing.pricing.plans.starter.limit')],
    },
    {
        key: 'growth',
        name: t('landing.pricing.plans.growth.name'),
        price: '49,99',
        description: t('landing.pricing.plans.growth.description'),
        cta: t('landing.pricing.plans.growth.cta'),
        href: '/subscribe/growth',
        highlight: true,
        features: [...sharedFeatures.value, t('landing.pricing.plans.growth.limit')],
    },
    {
        key: 'scale',
        name: t('landing.pricing.plans.scale.name'),
        price: '74,99',
        description: t('landing.pricing.plans.scale.description'),
        cta: t('landing.pricing.plans.scale.cta'),
        href: '/subscribe/scale',
        highlight: false,
        features: [...sharedFeatures.value, t('landing.pricing.plans.scale.limit')],
    },
]);
</script>

<template>
    <section
        id="pricing"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="pricing-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn class-name="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.pricing.eyebrow') }}</p>
                <h2
                    id="pricing-heading"
                    class="mx-auto mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.pricing.heading') }}
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    {{ t('landing.pricing.subtitle') }}
                </p>
            </FadeIn>

            <div class="mt-12 grid gap-4 md:grid-cols-3">
                <FadeIn
                    v-for="(plan, i) in plans"
                    :key="plan.key"
                    :delay="0.08 * i"
                    class-name="h-full"
                >
                    <article
                        class="flex h-full flex-col rounded-[22px] border p-6 sm:p-7"
                        :class="
                            plan.highlight
                                ? 'border-blue-400/40 bg-blue-500/10 shadow-[0_0_40px_-16px_rgba(59,130,246,0.55)]'
                                : 'lp-glass'
                        "
                    >
                        <p class="text-sm font-semibold uppercase tracking-wider text-blue-400">
                            {{ plan.name }}
                        </p>
                        <p class="mt-4 text-4xl font-black text-white">
                            {{ plan.price }} €
                            <span class="text-base font-medium text-slate-400">{{ t('common.perMonth') }}</span>
                        </p>
                        <p class="mt-3 text-[15px] text-slate-400">{{ plan.description }}</p>

                        <ul class="mt-6 flex-1 space-y-2.5">
                            <li
                                v-for="feature in plan.features"
                                :key="feature"
                                class="flex items-start gap-2.5 text-sm text-slate-300"
                            >
                                <svg
                                    class="mt-0.5 h-4 w-4 shrink-0 text-blue-400"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                                <span>{{ feature }}</span>
                            </li>
                        </ul>

                        <a
                            :href="plan.href"
                            class="lp-btn-primary mt-8 flex w-full items-center justify-center px-5 py-3.5 text-sm leading-none"
                        >
                            {{ plan.cta }}
                        </a>
                    </article>
                </FadeIn>
            </div>

            <FadeIn :delay="0.25" class-name="mt-10 text-center">
                <p class="text-sm text-slate-500">
                    {{ t('landing.pricing.footerNotReady') }}
                    <a href="/start-trial" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.trial14') }}</a>
                    · {{ t('landing.pricing.alreadyAccount') }}
                    <a href="/login" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.logIn') }}</a>
                    ·
                    <a href="/demo" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.demo') }}</a>
                </p>
            </FadeIn>
        </div>
    </section>
</template>
