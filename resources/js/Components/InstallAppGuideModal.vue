<script setup>
import { useI18n } from 'vue-i18n';
import UiIcon from './UiIcon.vue';
import { track } from '../utils/analytics';

const { t } = useI18n();

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    guideType: {
        type: String,
        default: 'desktop',
        validator: (value) => ['ios', 'android', 'desktop'].includes(value),
    },
});

const emit = defineEmits(['close']);

// Route Laravel : sert le fichier local (Render) ou bascule sur la release GitHub.
const APK_DOWNLOAD_URL = '/downloads/power-roster.apk';

function trackApkDownload() {
    track('apk_download_clicked', { source: 'install_guide' });
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[70] flex items-end justify-center bg-slate-950/70 p-4 sm:items-center"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
                role="dialog"
                aria-labelledby="install-guide-title"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/25 text-blue-300"
                        >
                            <UiIcon name="bolt" class="h-5 w-5" />
                        </span>
                        <h2 id="install-guide-title" class="text-base font-semibold text-white">
                            {{ t('modals.installGuide.title') }}
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                        :aria-label="t('modals.installGuide.close')"
                        @click="emit('close')"
                    >
                        <UiIcon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <p
                    v-if="guideType !== 'android'"
                    class="mt-3 rounded-lg border border-amber-500/30 bg-amber-950/30 px-3 py-2 text-xs text-amber-200"
                >
                    {{ t('modals.installGuide.shortcutWarning') }}
                </p>
                <p
                    v-else
                    class="mt-3 rounded-lg border border-emerald-500/30 bg-emerald-950/30 px-3 py-2 text-xs text-emerald-200"
                >
                    {{ t('modals.installGuide.androidHint') }}
                </p>

                <ol v-if="guideType === 'ios'" class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                        <span>{{ t('modals.installGuide.ios1') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
                        <span>
                            {{ t('modals.installGuide.ios2Before') }}
                            <span class="inline-flex items-center gap-1 font-medium text-white">
                                {{ t('modals.installGuide.ios2Share') }}
                                <UiIcon name="share" class="h-3.5 w-3.5" />
                            </span>
                            {{ t('modals.installGuide.ios2After') }}
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                        <span>
                            {{ t('modals.installGuide.ios3Before') }}
                            <strong class="text-white">{{ t('modals.installGuide.ios3Action') }}</strong>{{ t('modals.installGuide.ios3After') }}
                        </span>
                    </li>
                </ol>

                <div v-else-if="guideType === 'android'" class="mt-4 space-y-4">
                    <a
                        :href="APK_DOWNLOAD_URL"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-600/15 px-4 py-3 text-sm font-semibold text-emerald-200 transition hover:bg-emerald-600/25"
                        @click="trackApkDownload"
                    >
                        <UiIcon name="download" class="h-4 w-4" />
                        {{ t('modals.installGuide.downloadApk') }}
                    </a>
                    <p class="text-xs leading-relaxed text-slate-400">
                        {{ t('modals.installGuide.afterDownload') }}
                    </p>

                    <div class="border-t border-slate-700/80 pt-4">
                        <p class="mb-3 text-xs font-medium uppercase tracking-wide text-slate-500">
                            {{ t('modals.installGuide.orBrowser') }}
                        </p>
                        <ol class="space-y-3 text-sm text-slate-300">
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                                <span>{{ t('modals.installGuide.android1') }}</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
                                <span>{{ t('modals.installGuide.android2') }}</span>
                            </li>
                            <li class="flex gap-3">
                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                                <span>{{ t('modals.installGuide.android3') }}</span>
                            </li>
                        </ol>
                    </div>
                </div>

                <ol v-else class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                        <span>{{ t('modals.installGuide.desktop1') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
                        <span>{{ t('modals.installGuide.desktop2') }}</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                        <span>{{ t('modals.installGuide.desktop3') }}</span>
                    </li>
                </ol>

                <button
                    type="button"
                    class="mt-5 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                    @click="emit('close')"
                >
                    {{ t('modals.installGuide.understood') }}
                </button>
            </div>
        </div>
    </Teleport>
</template>
