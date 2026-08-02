<script setup>
import { motion } from 'motion-v';
import { useI18n } from 'vue-i18n';
import DashboardMockup from './DashboardMockup.vue';
import { track } from '../../utils/analytics';

const { t } = useI18n();

function trackCta(ctaId) {
    track('cta_clicked', { cta_id: ctaId });
}
</script>

<template>
    <section class="relative z-10 px-5 pb-20 pt-28 sm:px-8 lg:px-10 lg:pb-28 lg:pt-36">
        <div class="mx-auto grid w-full max-w-[1280px] items-center gap-14 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12 xl:gap-16">
            <div>
                <motion.p
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.5, delay: 0.05 }"
                    class="inline-flex items-center gap-2 rounded-full border border-blue-400/25 bg-blue-500/10 px-3.5 py-1.5 text-[13px] font-medium text-blue-300 shadow-[0_0_24px_-8px_rgba(59,130,246,0.5)]"
                >
                    <span class="relative flex h-1.5 w-1.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-60" />
                        <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-blue-400" />
                    </span>
                    {{ t('landing.hero.badge') }}
                </motion.p>

                <motion.h1
                    :initial="{ opacity: 0, y: 24 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.65, delay: 0.12, ease: [0.22, 1, 0.36, 1] }"
                    class="mt-7 max-w-xl text-[2.35rem] font-black leading-[1.08] tracking-[-0.04em] text-white sm:text-5xl xl:text-[3.5rem]"
                >
                    {{ t('landing.hero.titleLine1') }}
                    <span class="block text-blue-400">{{ t('landing.hero.titleLine2') }}</span>
                    <span class="block text-slate-200">{{ t('landing.hero.titleLine3') }}</span>
                </motion.h1>

                <motion.p
                    :initial="{ opacity: 0, y: 20 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.22 }"
                    class="mt-6 max-w-lg text-base leading-relaxed text-slate-400 sm:text-lg"
                >
                    {{ t('landing.hero.subtitle') }}
                </motion.p>

                <motion.div
                    :initial="{ opacity: 0, y: 20 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.32 }"
                    class="mt-9 flex flex-wrap items-center gap-3 sm:gap-4"
                >
                    <a
                        href="#pricing"
                        class="lp-btn-primary px-7 py-3.5 text-[15px] leading-none sm:px-8 sm:py-4 sm:text-base"
                        @click="trackCta('hero_commencer')"
                    >
                        <span>{{ t('landing.hero.ctaStart') }}</span>
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <a
                        href="/start-trial"
                        class="lp-btn-secondary px-7 py-3.5 text-[15px] leading-none sm:px-8 sm:py-4 sm:text-base"
                        @click="trackCta('hero_trial')"
                    >
                        {{ t('landing.hero.ctaTrial') }}
                    </a>
                    <a
                        href="/demo"
                        class="lp-btn-secondary px-7 py-3.5 text-[15px] leading-none sm:px-8 sm:py-4 sm:text-base"
                        @click="trackCta('hero_demo')"
                    >
                        {{ t('landing.hero.ctaDemo') }}
                    </a>
                </motion.div>

                <motion.p
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :transition="{ duration: 0.5, delay: 0.5 }"
                    class="mt-6 text-sm text-slate-500"
                >
                    {{ t('landing.hero.trustLine') }}
                </motion.p>
            </div>

            <motion.div
                :initial="{ opacity: 0, x: 40, scale: 0.96 }"
                :animate="{ opacity: 1, x: 0, scale: 1 }"
                :transition="{ duration: 0.85, delay: 0.25, ease: [0.22, 1, 0.36, 1] }"
                class="relative"
            >
                <DashboardMockup />
            </motion.div>
        </div>
    </section>
</template>
