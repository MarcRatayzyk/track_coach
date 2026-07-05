<script setup>
import UiIcon from './UiIcon.vue';
import { usePwaInstall } from '../composables/usePwaInstall';

const { showBanner, platform, install, dismiss } = usePwaInstall();
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
                <p class="text-sm font-semibold text-white">Installer Track Coach</p>

                <p v-if="platform === 'android'" class="mt-1 text-xs leading-relaxed text-slate-300">
                    Ajoute l'app sur ton écran d'accueil pour un accès rapide, comme une application native.
                </p>

                <p v-else class="mt-1 text-xs leading-relaxed text-slate-300">
                    Appuie sur
                    <span class="inline-flex items-center gap-1 font-medium text-white">
                        Partager
                        <UiIcon name="share" class="h-3.5 w-3.5" />
                    </span>
                    puis sur
                    <span class="font-medium text-white">Sur l'écran d'accueil</span>.
                </p>

                <button
                    v-if="platform === 'android'"
                    type="button"
                    class="mt-3 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-500"
                    @click="install"
                >
                    Installer
                </button>
            </div>

            <button
                type="button"
                class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                aria-label="Fermer"
                @click="dismiss"
            >
                <UiIcon name="x-mark" class="h-4 w-4" />
            </button>
        </div>
    </div>
</template>
