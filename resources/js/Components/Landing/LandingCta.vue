<script setup>
import { useI18n } from 'vue-i18n';
import { track } from '../../utils/analytics';

defineProps({
    ctaId: { type: String, default: 'landing_trial' },
    align: { type: String, default: 'left' },
    size: { type: String, default: 'default' },
});

const { t } = useI18n();

function trackClick(id) {
    track('cta_clicked', { cta_id: id });
}
</script>

<template>
    <div :class="align === 'center' ? 'text-center' : ''">
        <a
            href="/register"
            :class="[
                'lp-btn-primary inline-flex',
                size === 'large'
                    ? 'px-10 py-4 text-base leading-none sm:text-lg'
                    : 'px-7 py-3.5 text-[15px] leading-none sm:px-8 sm:py-4 sm:text-base',
            ]"
            @click="trackClick(ctaId)"
        >
            <span>{{ t('landing.cta.trial') }}</span>
            <svg
                v-if="size !== 'large'"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.5"
                aria-hidden="true"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
        </a>
        <p
            class="mt-3 text-xs text-slate-500 sm:text-sm"
            :class="align === 'center' ? 'mx-auto max-w-md' : ''"
        >
            {{ t('landing.cta.demoPrefix') }}
            <a
                href="/demo"
                class="font-semibold text-blue-400 hover:text-blue-300 hover:underline"
                @click="trackClick(`${ctaId}_demo`)"
            >
                {{ t('landing.cta.demo') }}
            </a>
        </p>
    </div>
</template>
