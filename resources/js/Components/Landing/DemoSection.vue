<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';
import ScreenFrame from './ScreenFrame.vue';

const { t } = useI18n();

const screens = computed(() => [
    {
        id: 'dashboard',
        label: t('landing.demo.screens.dashboard'),
        src: '/images/landing/actions.png',
    },
    {
        id: 'feedback',
        label: t('landing.demo.screens.feedback'),
        src: '/images/landing/session-feedback.png',
    },
    {
        id: 'messaging',
        label: t('landing.demo.screens.messaging'),
        src: '/images/landing/messaging.png',
    },
    {
        id: 'roster',
        label: t('landing.demo.screens.roster'),
        src: '/images/landing/roster.png',
    },
    {
        id: 'program',
        label: t('landing.demo.screens.programs'),
        src: '/images/landing/program-editor.png',
    },
    {
        id: 'athlete-app',
        label: t('landing.demo.screens.athleteApp'),
        src: '/images/landing/session-feedback.png',
    },
    {
        id: 'import',
        label: t('landing.demo.screens.import'),
        src: '/images/landing/program-editor.png',
    },
    {
        id: 'stats',
        label: t('landing.demo.screens.stats'),
        src: '/images/landing/stats.png',
    },
]);

const active = ref(0);

function select(i) {
    active.value = i;
}
</script>

<template>
    <section
        id="demo"
        class="relative z-10 scroll-mt-24 px-5 py-20 sm:px-8 lg:px-10 lg:py-28"
        aria-labelledby="demo-heading"
    >
        <div class="mx-auto w-full max-w-[1100px]">
            <FadeIn class-name="text-center">
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.demo.eyebrow') }}</p>
                <h2
                    id="demo-heading"
                    class="mx-auto mt-3 max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.demo.heading') }}
                </h2>
                <p class="mx-auto mt-4 max-w-xl text-base text-slate-400 sm:text-lg">
                    {{ t('landing.demo.subtitle') }}
                </p>
            </FadeIn>

            <FadeIn :delay="0.1" class-name="mt-10">
                <div
                    class="flex flex-wrap items-center justify-center gap-2"
                    role="tablist"
                    :aria-label="t('landing.demo.tabsAria')"
                >
                    <button
                        v-for="(screen, i) in screens"
                        :key="screen.id"
                        type="button"
                        role="tab"
                        :aria-selected="active === i"
                        class="rounded-xl border px-3.5 py-2 text-sm font-semibold transition"
                        :class="
                            active === i
                                ? 'border-blue-400/40 bg-blue-500/15 text-blue-300 shadow-[0_0_24px_-8px_rgba(59,130,246,0.5)]'
                                : 'border-white/10 bg-white/[0.02] text-slate-400 hover:border-white/20 hover:text-white'
                        "
                        @click="select(i)"
                    >
                        {{ screen.label }}
                    </button>
                </div>
            </FadeIn>

            <FadeIn :delay="0.15" class-name="mt-8">
                <div class="relative mx-auto w-full max-w-[720px]">
                    <div
                        class="pointer-events-none absolute -inset-4 rounded-[28px] bg-blue-500/10 blur-2xl"
                        aria-hidden="true"
                    />
                    <div
                        v-for="(screen, i) in screens"
                        :key="screen.id"
                        v-show="active === i"
                        role="tabpanel"
                    >
                        <ScreenFrame
                            :src="screen.src"
                            :alt="screen.label"
                            :label="`Power Roster · ${screen.label}`"
                            loading="lazy"
                        />
                    </div>
                    <div class="mt-6 text-center">
                        <a
                            href="/demo"
                            class="lp-btn-primary inline-flex px-6 py-3 text-sm leading-none"
                        >
                            {{ t('landing.demo.openDemo') }}
                        </a>
                    </div>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
