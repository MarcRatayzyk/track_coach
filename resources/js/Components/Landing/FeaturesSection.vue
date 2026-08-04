<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';

const { t } = useI18n();

const features = computed(() => [
    {
        key: 'athletes',
        title: t('landing.features.items.athletes.title'),
        description: t('landing.features.items.athletes.description'),
        image: '/images/landing/roster.png',
        imagePos: 'object-top',
        path: 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
    },
    {
        key: 'programming',
        title: t('landing.features.items.programming.title'),
        description: t('landing.features.items.programming.description'),
        image: '/images/landing/program-editor.png',
        imagePos: 'object-left-top',
        path: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    },
    {
        key: 'sbd',
        title: t('landing.features.items.sbd.title'),
        description: t('landing.features.items.sbd.description'),
        image: '/images/landing/stats.png',
        imagePos: 'object-top',
        path: 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
    },
    {
        key: 'feedback',
        title: t('landing.features.items.feedback.title'),
        description: t('landing.features.items.feedback.description'),
        image: '/images/landing/session-feedback.png',
        imagePos: 'object-left-top',
        path: 'M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z',
    },
    {
        key: 'messaging',
        title: t('landing.features.items.messaging.title'),
        description: t('landing.features.items.messaging.description'),
        image: '/images/landing/messaging.png',
        imagePos: 'object-left-top',
        path: 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
    },
    {
        key: 'competitions',
        title: t('landing.features.items.competitions.title'),
        description: t('landing.features.items.competitions.description'),
        image: '/images/landing/calendar.png',
        imagePos: 'object-center',
        path: 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
    },
    {
        key: 'athleteApp',
        title: t('landing.features.items.athleteApp.title'),
        description: t('landing.features.items.athleteApp.description'),
        image: '/images/landing/session-feedback.png',
        imagePos: 'object-top',
        path: 'M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3',
    },
    {
        key: 'import',
        title: t('landing.features.items.import.title'),
        description: t('landing.features.items.import.description'),
        image: '/images/landing/program-editor.png',
        imagePos: 'object-right-top',
        path: 'M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5',
    },
    {
        key: 'stats',
        title: t('landing.features.items.stats.title'),
        description: t('landing.features.items.stats.description'),
        image: '/images/landing/stats.png',
        imagePos: 'object-center',
        path: 'M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941',
    },
]);
</script>

<template>
    <section
        id="fonctionnalites"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="features-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.features.eyebrow') }}</p>
                <h2
                    id="features-heading"
                    class="mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.features.heading') }}
                </h2>
                <p class="mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    {{ t('landing.features.subtitle') }}
                </p>
            </FadeIn>

            <div class="mt-14 grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-3">
                <FadeIn
                    v-for="(f, i) in features"
                    :key="f.key"
                    :delay="Math.min(i * 0.05, 0.35)"
                    class-name="group lp-glass lp-glass-hover flex flex-col overflow-hidden rounded-[16px] sm:rounded-[20px]"
                >
                    <div class="relative h-[88px] overflow-hidden border-b border-white/[0.06] bg-[#070d1f] sm:h-[140px]">
                        <img
                            :src="f.image"
                            :alt="f.title"
                            loading="lazy"
                            decoding="async"
                            class="h-full w-full scale-105 object-cover transition duration-500 group-hover:scale-110"
                            :class="f.imagePos"
                        />
                        <div
                            class="pointer-events-none absolute inset-0 bg-gradient-to-t from-[#050B1E]/80 via-transparent to-transparent"
                            aria-hidden="true"
                        />
                    </div>

                    <div class="flex flex-1 flex-col p-3.5 sm:p-6">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-500/15 text-blue-400 transition group-hover:bg-blue-500/25 sm:h-10 sm:w-10 sm:rounded-xl">
                            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="f.path" />
                            </svg>
                        </div>
                        <h3 class="mt-2.5 text-sm font-bold text-white sm:mt-4 sm:text-lg">{{ f.title }}</h3>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400 sm:mt-1.5 sm:text-[15px]">{{ f.description }}</p>
                    </div>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
