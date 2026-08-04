<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';
import ScreenFrame from './ScreenFrame.vue';

const { t } = useI18n();

const highlights = computed(() => [
    t('landing.tracking.highlights.e1rm'),
    t('landing.tracking.highlights.volume'),
    t('landing.tracking.highlights.prs'),
]);
</script>

<template>
    <section
        id="suivi"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="tracking-heading"
    >
        <div class="mx-auto w-full max-w-[1280px]">
            <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-14 xl:gap-16">
                <FadeIn>
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">
                        {{ t('landing.tracking.eyebrow') }}
                    </p>
                    <h2
                        id="tracking-heading"
                        class="mt-3 max-w-xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl lg:text-[2.75rem] lg:leading-[1.1]"
                    >
                        {{ t('landing.tracking.heading') }}
                    </h2>
                    <p class="mt-5 max-w-lg text-base leading-relaxed text-slate-400 sm:text-lg">
                        {{ t('landing.tracking.body') }}
                    </p>
                    <ul class="mt-8 space-y-3">
                        <li
                            v-for="(item, i) in highlights"
                            :key="i"
                            class="flex items-start gap-3 text-[15px] leading-snug text-slate-300"
                        >
                            <span
                                class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-400"
                                aria-hidden="true"
                            />
                            {{ item }}
                        </li>
                    </ul>
                </FadeIn>

                <FadeIn :delay="0.12" class-name="relative">
                    <ScreenFrame
                        src="/images/landing/stats.png"
                        :alt="t('landing.tracking.mockupAlt')"
                        :label="t('landing.tracking.mockupLabel')"
                        loading="lazy"
                    />
                </FadeIn>
            </div>
        </div>
    </section>
</template>
