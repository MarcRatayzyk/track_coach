<script setup>
import UiIcon from './UiIcon.vue';
import InstallAppIosGuide from './InstallAppIosGuide.vue';
import { usePwaInstall } from '../composables/usePwaInstall';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
        validator: (value) => ['default', 'menu', 'compact'].includes(value),
    },
});

const emit = defineEmits(['interacted']);

const {
    showInstallAction,
    installOrGuide,
    showIosGuide,
    closeIosGuide,
} = usePwaInstall();

async function handleClick() {
    await installOrGuide();
    emit('interacted');
}

const buttonClasses = {
    default:
        'inline-flex items-center gap-2 rounded-xl border border-blue-500/40 bg-blue-600/15 px-3 py-2 text-sm font-medium text-blue-200 transition hover:bg-blue-600/25',
    menu: 'flex w-full items-center gap-2.5 rounded-xl border border-transparent px-3 py-2.5 text-left text-sm font-medium text-slate-200 transition hover:bg-slate-800/60',
    compact:
        'inline-flex items-center gap-1.5 rounded-lg border border-slate-700 bg-slate-800/50 px-2.5 py-1.5 text-xs font-medium text-slate-200 transition hover:bg-slate-800',
};
</script>

<template>
    <template v-if="showInstallAction">
        <button
            type="button"
            :class="buttonClasses[props.variant]"
            @click="handleClick"
        >
            <UiIcon name="bolt" class="h-4 w-4 shrink-0 text-blue-300" />
            <span>Installer l'app</span>
        </button>

        <InstallAppIosGuide :open="showIosGuide" @close="closeIosGuide" />
    </template>
</template>
