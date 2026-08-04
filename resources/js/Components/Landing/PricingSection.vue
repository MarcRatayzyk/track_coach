<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';
import {
  DEFAULT_EUR_TO_USD_RATE,
  DEFAULT_PRICING,
  currencyFromLocale,
  discountedPrice,
  formatMoneyPlain,
  listPrice,
  usdToEur,
} from '../../utils/pricing';

const props = defineProps({
  pricing: {
    type: Object,
    default: null,
  },
});

const { t, locale } = useI18n();

const currency = computed(() => currencyFromLocale(locale.value));

const discountPercent = computed(
  () => Number(props.pricing?.launch_discount_percent ?? DEFAULT_PRICING.launch_discount_percent) || 0,
);

const fxRate = computed(
  () => Number(props.pricing?.eur_to_usd_rate ?? props.pricing?.plans?.[0]?.eur_to_usd_rate ?? DEFAULT_PRICING.eur_to_usd_rate) || DEFAULT_EUR_TO_USD_RATE,
);

const sharedFeatures = computed(() => [
  t('landing.pricing.sharedFeatures.programming'),
  t('landing.pricing.sharedFeatures.tracking'),
  t('landing.pricing.sharedFeatures.feedback'),
  t('landing.pricing.sharedFeatures.messaging'),
  t('landing.pricing.sharedFeatures.competitions'),
  t('landing.pricing.sharedFeatures.dashboard'),
]);

const plans = computed(() => {
  const source = props.pricing?.plans?.length ? props.pricing.plans : DEFAULT_PRICING.plans;
  const curr = currency.value;
  const rate = fxRate.value;
  return source.map((plan) => {
    const list = listPrice(plan, curr, rate);
    let sale = plan[`sale_price_${curr}`];
    if (sale == null) {
      const saleUsd = discountedPrice(plan.price_usd ?? list, discountPercent.value);
      sale = curr === 'usd' ? saleUsd : usdToEur(saleUsd, rate);
    }
    return {
      key: plan.key,
      name: t(`landing.pricing.plans.${plan.key}.name`),
      description: t(`landing.pricing.plans.${plan.key}.description`),
      cta: t('landing.pricing.plans.subscribe'),
      href: `/subscribe/${plan.key}`,
      highlight: plan.key === 'growth',
      listPrice: list,
      salePrice: Number(sale),
      features: [...sharedFeatures.value, t(`landing.pricing.plans.${plan.key}.limit`)],
    };
  });
});

function formatAmount(amount) {
  return formatMoneyPlain(amount, { locale: locale.value, currency: currency.value });
}
</script>

<template>
  <section
    id="pricing"
    class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
    aria-labelledby="pricing-heading"
  >
    <div class="mx-auto w-full max-w-[1280px]">
      <FadeIn class-name="text-center">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">
          {{ t('landing.pricing.eyebrow') }}
        </p>
        <h2
          id="pricing-heading"
          class="mx-auto mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
        >
          {{ t('landing.pricing.heading') }}
        </h2>
        <p class="mx-auto mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
          {{ t('landing.pricing.subtitle') }}
        </p>

        <div
          v-if="discountPercent > 0"
          class="mx-auto mt-5 inline-flex flex-col items-center gap-2"
        >
          <div
            class="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-3.5 py-1.5 text-sm font-semibold text-blue-200"
          >
            <span class="rounded-full bg-blue-500 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-white">
              {{ t('landing.pricing.launchBadge', { percent: discountPercent }) }}
            </span>
            <span>{{ t('landing.pricing.launchOffer') }}</span>
          </div>
          <p class="max-w-md text-center text-xs text-slate-500">
            {{ t('landing.pricing.launchDuration') }}
          </p>
        </div>
      </FadeIn>

      <div class="mt-12 grid gap-4 md:grid-cols-3">
        <FadeIn
          v-for="(plan, i) in plans"
          :key="plan.key"
          :delay="0.08 * i"
          class-name="h-full"
        >
          <article
            class="relative flex h-full flex-col rounded-[22px] border p-6 sm:p-7"
            :class="
              plan.highlight
                ? 'border-blue-400/40 bg-blue-500/10'
                : 'lp-glass'
            "
          >
            <div class="flex items-start justify-between gap-3">
              <p class="text-sm font-semibold uppercase tracking-wider text-blue-400">
                {{ plan.name }}
              </p>
              <span
                v-if="discountPercent > 0"
                class="shrink-0 rounded-md bg-blue-500/20 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-blue-200"
              >
                -{{ discountPercent }}%
              </span>
            </div>

            <div class="mt-4">
              <p
                v-if="discountPercent > 0"
                class="text-sm text-slate-500 line-through decoration-slate-500/80"
              >
                {{ formatAmount(plan.listPrice) }}
                <span class="text-xs font-medium">{{ t('common.perMonth') }}</span>
              </p>
              <p class="text-4xl font-black text-white">
                {{ formatAmount(plan.salePrice) }}
                <span class="text-base font-medium text-slate-400">{{ t('common.perMonth') }}</span>
              </p>
            </div>

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
          {{ t('landing.pricing.footerDemo') }}
          <a href="/demo" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.demo') }}</a>
          ·
          <a href="/register" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.trial14') }}</a>
          · {{ t('landing.pricing.alreadyAccount') }}
          <a href="/login" class="font-semibold text-blue-400 hover:underline">{{ t('landing.pricing.logIn') }}</a>
        </p>
      </FadeIn>
    </div>
  </section>
</template>
