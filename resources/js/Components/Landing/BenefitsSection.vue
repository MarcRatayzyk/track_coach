<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FadeIn from './FadeIn.vue';

const { t } = useI18n();

const benefits = computed(() => [
    {
        key: 'time',
        big: t('landing.benefits.time.big'),
        title: t('landing.benefits.time.title'),
        description: t('landing.benefits.time.description'),
    },
    {
        key: 'precision',
        big: t('landing.benefits.precision.big'),
        title: t('landing.benefits.precision.title'),
        description: t('landing.benefits.precision.description'),
    },
    {
        key: 'central',
        big: t('landing.benefits.central.big'),
        label: t('landing.benefits.central.label'),
        title: t('landing.benefits.central.title'),
        description: t('landing.benefits.central.description'),
    },
]);
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="benefits-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <p class="text-sm font-semibold uppercase tracking-[0.18em] text-blue-400">{{ t('landing.benefits.eyebrow') }}</p>
                <h2
                    id="benefits-heading"
                    class="mt-3 max-w-xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl"
                >
                    {{ t('landing.benefits.heading') }}
                </h2>
            </FadeIn>

            <div class="mt-12 grid gap-5 lg:grid-cols-3">
                <FadeIn
                    v-for="(b, i) in benefits"
                    :key="b.key"
                    :delay="0.1 * i"
                    class-name="lp-glass lp-glass-hover rounded-[22px] p-8"
                >
                    <p class="text-4xl font-black tracking-tight text-blue-400 sm:text-5xl">{{ b.big }}</p>
                    <p v-if="b.label" class="mt-1 text-sm font-medium text-slate-500">{{ b.label }}</p>
                    <h3 class="mt-6 text-xl font-bold text-white">{{ b.title }}</h3>
                    <p class="mt-2 text-[15px] leading-relaxed text-slate-400">{{ b.description }}</p>
                </FadeIn>
            </div>
        </div>
    </section>
</template>
