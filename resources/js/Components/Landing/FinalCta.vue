<script setup>
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';
import LandingCta from './LandingCta.vue';

const { t } = useI18n();
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="final-cta-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <div
                    class="relative overflow-hidden rounded-[28px] border border-blue-400/25 bg-[#0f172a] px-8 py-16 text-center sm:px-12 sm:py-20 lg:px-16"
                >
                    <h2
                            id="final-cta-heading"
                            class="mx-auto max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl lg:text-5xl"
                        >
                            {{ t('landing.finalCta.heading') }}
                        </h2>
                        <p class="mx-auto mt-4 max-w-lg text-sm text-slate-300 sm:mt-5 sm:text-lg">
                            {{ t('landing.finalCta.subtitle') }}
                        </p>
                        <div class="mt-10">
                            <LandingCta cta-id="final_trial" align="center" size="large" />
                        </div>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
