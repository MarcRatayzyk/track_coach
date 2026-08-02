<script setup>
import { watch } from 'vue';
import { useI18n } from 'vue-i18n';
import UiIcon from './UiIcon.vue';
import { usePwaInstall } from '../composables/usePwaInstall';
import { track } from '../utils/analytics';

const { t } = useI18n();
const { showBanner, platform, install, dismiss } = usePwaInstall();

let bannerShownTracked = false;

watch(
    showBanner,
    (visible) => {
        if (visible && !bannerShownTracked) {
            bannerShownTracked = true;
            track('install_banner_shown', { install_platform: platform.value });
        }
    },
    { immediate: true },
);

async function acceptInstall() {
    track('install_banner_accepted', { install_platform: platform.value });
    await install();
}
</script>

<template>
    <div
        v-if="showBanner"
        class="fixed inset-x-0 bottom-[calc(4.5rem+env(safe-area-inset-bottom))] z-50 border-t border-blue-500/30 bg-slate-900/95 px-4 py-3 shadow-2xl shadow-blue-950/40 backdrop-blur-sm lg:bottom-0"
    >
        <div class="mx-auto flex max-w-lg items-start gap-3">
            <span
                class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-600/25 text-blue-300"
            >
                <UiIcon name="bolt" class="h-5 w-5" />
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-white">{{ t('modals.installBanner.title') }}</p>

                <p v-if="platform === 'android'" class="mt-1 text-xs leading-relaxed text-slate-300">
                    {{ t('modals.installBanner.androidBody') }}
                </p>

                <p v-else class="mt-1 text-xs leading-relaxed text-slate-300">
                    {{ t('modals.installBanner.iosBodyBefore') }}
                    <span class="inline-flex items-center gap-1 font-medium text-white">
                        {{ t('modals.installBanner.share') }}
                        <UiIcon name="share" class="h-3.5 w-3.5" />
                    </span>
                    {{ t('modals.installBanner.iosBodyMid') }}
                    <span class="font-medium text-white">{{ t('modals.installBanner.addToHome') }}</span>.
                </p>

                <button
                    v-if="platform === 'android'"
                    type="button"
                    class="mt-3 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-500"
                    @click="acceptInstall"
                >
                    {{ t('modals.installBanner.install') }}
                </button>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                :aria-label="t('modals.installBanner.close')"
                @click="dismiss"
            >
                <UiIcon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
