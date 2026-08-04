<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';
import ScreenFrame from './ScreenFrame.vue';

const { t } = useI18n();

const pillars = computed(() => [
    {
        key: 'tables',
        title: t('landing.modularity.pillars.tables.title'),
        description: t('landing.modularity.pillars.tables.description'),
    },
    {
        key: 'charts',
        title: t('landing.modularity.pillars.charts.title'),
        description: t('landing.modularity.pillars.charts.description'),
    },
    {
        key: 'checkins',
        title: t('landing.modularity.pillars.checkins.title'),
        description: t('landing.modularity.pillars.checkins.description'),
    },
]);

const showcases = computed(() => [
    {
        key: 'dayTable',
        title: t('landing.modularity.showcases.dayTable.title'),
        description: t('landing.modularity.showcases.dayTable.description'),
        src: '/images/landing/day-table-layout.png',
        alt: t('landing.modularity.showcases.dayTable.alt'),
        label: t('landing.modularity.showcases.dayTable.label'),
    },
    {
        key: 'chartBuilder',
        title: t('landing.modularity.showcases.chartBuilder.title'),
        description: t('landing.modularity.showcases.chartBuilder.description'),
        src: '/images/landing/chart-builder.png',
        alt: t('landing.modularity.showcases.chartBuilder.alt'),
        label: t('landing.modularity.showcases.chartBuilder.label'),
    },
]);
</script>

<template>
    <section
        id="modularite"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="modularity-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.modularity.eyebrow') }}</p>
                <h2
                    id="modularity-heading"
                    class="mt-3 max-w-3xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.modularity.heading') }}
                </h2>
                <p class="mt-4 max-w-2xl text-base text-slate-400 sm:text-lg">
                    {{ t('landing.modularity.subtitle') }}
                </p>
            </FadeIn>

            <div class="mt-12 grid gap-4 sm:grid-cols-3">
                <FadeIn
                    v-for="(pillar, i) in pillars"
                    :key="pillar.key"
                    :delay="0.06 * i"
                    class-name="rounded-[18px] border border-white/[0.06] bg-white/[0.02] px-5 py-5"
                >
                    <h3 class="text-base font-bold text-white">{{ pillar.title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-400">{{ pillar.description }}</p>
                </FadeIn>
            </div>

            <div class="mt-14 grid gap-10 lg:grid-cols-2 lg:gap-8">
                <FadeIn
                    v-for="(item, i) in showcases"
                    :key="item.key"
                    :delay="0.08 + i * 0.1"
                    class-name="flex flex-col"
                >
                    <div class="mb-5 max-w-md">
                        <h3 class="text-xl font-bold text-white sm:text-2xl">{{ item.title }}</h3>
                        <p class="mt-2 text-[15px] leading-relaxed text-slate-400">{{ item.description }}</p>
                    </div>
                    <div class="relative flex-1">
                        <ScreenFrame
                            :src="item.src"
                            :alt="item.alt"
                            :label="item.label"
                            loading="lazy"
                        />
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
