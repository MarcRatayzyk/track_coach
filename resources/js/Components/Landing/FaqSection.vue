<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';

const { t } = useI18n();

const openIndex = ref(0);

const faqs = computed(() => [
    {
        key: 'access',
        q: t('landing.faq.items.access.q'),
        a: t('landing.faq.items.access.a'),
    },
    {
        key: 'migrate',
        q: t('landing.faq.items.migrate.q'),
        a: t('landing.faq.items.migrate.a'),
    },
    {
        key: 'athletesPay',
        q: t('landing.faq.items.athletesPay.q'),
        a: t('landing.faq.items.athletesPay.a'),
    },
    {
        key: 'timeToStart',
        q: t('landing.faq.items.timeToStart.q'),
        a: t('landing.faq.items.timeToStart.a'),
    },
]);

function toggle(i) {
    openIndex.value = openIndex.value === i ? -1 : i;
}
</script>

<template>
    <section
        id="faq"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="faq-heading"
    >
        <div class="mx-auto w-full max-w-[800px]">
            <FadeIn class-name="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.faq.eyebrow') }}</p>
                <h2
                    id="faq-heading"
                    class="mt-3 text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.faq.heading') }}
                </h2>
            </FadeIn>

            <div class="mt-10 space-y-3">
                <FadeIn
                    v-for="(item, i) in faqs"
                    :key="item.key"
                    :delay="0.04 * i"
                >
                    <div class="lp-glass overflow-hidden rounded-[18px]">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition hover:bg-white/[0.02] sm:px-6"
                            :aria-expanded="openIndex === i"
                            @click="toggle(i)"
                        >
                            <span class="text-[15px] font-semibold text-white sm:text-base">{{ item.q }}</span>
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-white/10 text-slate-400 transition"
                                :class="openIndex === i ? 'rotate-45 bg-blue-500/15 text-blue-300' : ''"
                                aria-hidden="true"
                            >
                                +
                            </span>
                        </button>
                        <div class="lp-faq-content" :class="{ 'is-open': openIndex === i }">
                            <div>
                                <p class="px-5 pb-5 text-[15px] leading-relaxed text-slate-400 sm:px-6">
                                    {{ item.a }}
                                </p>
                            </div>
                        </div>
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
