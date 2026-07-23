<script setup>
import UiIcon from './UiIcon.vue';

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
                            Installer Power Roster
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

                <p class="mt-3 rounded-lg border border-amber-500/30 bg-amber-950/30 px-3 py-2 text-xs text-amber-200">
                    Un simple raccourci ouvre l’app dans le navigateur. Suis bien les étapes ci-dessous pour l’installer en plein écran.
                </p>

                <ol v-if="guideType === 'ios'" class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                        <span>
                            Utilise <strong class="text-white">Safari</strong> uniquement
                            (Chrome sur iPhone ne crée qu’un raccourci navigateur).
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
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
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                        <span>
                            Choisis <strong class="text-white">Sur l’écran d’accueil</strong>, puis confirme.
                        </span>
                    </li>
                </ol>

                <ol v-else-if="guideType === 'android'" class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                        <span>
                            Dans <strong class="text-white">Chrome</strong>, accepte la proposition
                            <strong class="text-white">Installer l’application</strong> si elle apparaît.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
                        <span>
                            Sinon : menu <strong class="text-white">⋮</strong> →
                            <strong class="text-white">Installer l’application</strong>
                            (pas seulement « Ajouter à l’écran d’accueil »).
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                        <span>
                            Supprime l’ancien raccourci si besoin, puis réinstalle pour ouvrir sans barre d’adresse.
                        </span>
                    </li>
                </ol>

                <ol v-else class="mt-4 space-y-3 text-sm text-slate-300">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">1</span>
                        <span>
                            Sur <strong class="text-white">Chrome</strong> ou <strong class="text-white">Edge</strong>,
                            clique sur l’icône <strong class="text-white">Installer</strong> dans la barre d’adresse
                            (à droite de l’URL).
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">2</span>
                        <span>
                            Sinon : menu <strong class="text-white">⋮</strong> →
                            <strong class="text-white">Installer Power Roster</strong>
                            ou <strong class="text-white">Applications disponibles</strong>.
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-xs font-bold text-blue-300">3</span>
                        <span>
                            L’app installée s’ouvre sans barre d’adresse, comme une application native.
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
