<script setup>
import UiIcon from './UiIcon.vue';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-950/70 p-4 sm:items-center"
            @click.self="emit('close')"
        >
            <div
                class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900 p-5 shadow-2xl"
                role="dialog"
                aria-labelledby="ios-install-title"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/25 text-blue-300"
                        >
                            <UiIcon name="bolt" class="h-5 w-5" />
                        </span>
                        <h2 id="ios-install-title" class="text-base font-semibold text-white">
                            Installer Track Coach
                        </h2>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-800 hover:text-white"
                        aria-label="Fermer"
                        @click="emit('close')"
                    >
                        <UiIcon name="x-mark" class="h-4 w-4" />
                    </button>
                </div>

                <ol class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300"
                        >1</span>
                        <span>Ouvre Track Coach dans <strong class="text-white">Safari</strong>.</span>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300"
                        >2</span>
                        <span>
                            Appuie sur
                            <span class="inline-flex items-center gap-1 font-medium text-white">
                                Partager
                                <UiIcon name="share" class="h-3.5 w-3.5" />
                            </span>
                            en bas de l’écran.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300"
                        >3</span>
                        <span>
                            Choisis
                            <strong class="text-white">Sur l’écran d’accueil</strong>,
                            puis confirme.
                        </span>
                    </li>
                </ol>

                <button
                    type="button"
                    class="mt-5 w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500"
                    @click="emit('close')"
                >
                    Compris
                </button>
            </div>
        </div>
    </Teleport>
</template>
