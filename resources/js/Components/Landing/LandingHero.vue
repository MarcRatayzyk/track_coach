<script setup>
import { motion } from 'motion-v';
import { useI18n } from 'vue-i18n';
import DashboardMockup from './DashboardMockup.vue';
import LandingCta from './LandingCta.vue';

const { t } = useI18n();
</script>

<template>
    <section class="relative z-10 px-5 pb-14 pt-24 sm:px-8 sm:pb-20 lg:px-10 lg:pb-28 lg:pt-36">
        <div class="mx-auto grid w-full max-w-[1280px] items-center gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-12 xl:gap-16">
            <div>
                <motion.p
                    :initial="{ opacity: 0, y: 16 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.5, delay: 0.05 }"
                    class="hidden items-center gap-2 rounded-full border border-blue-400/25 bg-blue-500/10 px-3.5 py-1.5 text-[13px] font-medium text-blue-300 sm:inline-flex"
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
                    class="max-w-xl text-[2.15rem] font-black leading-[1.1] tracking-[-0.04em] text-white sm:mt-7 sm:text-5xl xl:text-[3.5rem]"
                >
                    <span class="sm:hidden">
                        {{ t('landing.hero.titleMobile1') }}
                        <span class="block text-blue-400">{{ t('landing.hero.titleMobile2') }}</span>
                    </span>
                    <span class="hidden sm:contents">
                        {{ t('landing.hero.titleLine1') }}
                        <span class="block text-blue-400">{{ t('landing.hero.titleLine2') }}</span>
                        <span class="block text-slate-200">{{ t('landing.hero.titleLine3') }}</span>
                    </span>
                </motion.h1>

                <motion.p
                    :initial="{ opacity: 0, y: 20 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.22 }"
                    class="mt-4 max-w-lg text-[15px] leading-snug text-slate-400 sm:mt-6 sm:text-lg sm:leading-relaxed"
                >
                    <span class="sm:hidden">{{ t('landing.hero.subtitleMobile') }}</span>
                    <span class="hidden sm:inline">{{ t('landing.hero.subtitle') }}</span>
                </motion.p>

                <motion.div
                    :initial="{ opacity: 0, y: 20 }"
                    :animate="{ opacity: 1, y: 0 }"
                    :transition="{ duration: 0.6, delay: 0.32 }"
                    class="mt-7 sm:mt-9"
                >
                    <LandingCta cta-id="hero_trial" />
                </motion.div>

                <motion.p
                    :initial="{ opacity: 0 }"
                    :animate="{ opacity: 1 }"
                    :transition="{ duration: 0.5, delay: 0.5 }"
                    class="mt-4 text-xs text-slate-500 sm:mt-6 sm:text-sm"
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
