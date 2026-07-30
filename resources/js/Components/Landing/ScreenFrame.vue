<script setup>
defineProps({
    src: { type: String, required: true },
    alt: { type: String, required: true },
    label: { type: String, default: '' },
    loading: { type: String, default: 'lazy' },
});
</script>

<template>
    <div class="overflow-hidden rounded-[18px] border border-white/[0.1] bg-[#070d1f] shadow-[0_28px_60px_-24px_rgba(0,0,0,0.75),0_0_0_1px_rgba(59,130,246,0.1)]">
        <div v-if="label" class="flex items-center gap-2 border-b border-white/[0.06] px-3.5 py-2.5">
            <span class="h-2 w-2 rounded-full bg-red-400/80" />
            <span class="h-2 w-2 rounded-full bg-amber-400/80" />
            <span class="h-2 w-2 rounded-full bg-emerald-400/80" />
            <span class="ml-2 truncate text-[10px] font-medium text-slate-500 sm:text-[11px]">{{ label }}</span>
        </div>
        <img
            :src="src"
            :alt="alt"
            :loading="loading"
            decoding="async"
            class="block h-auto w-full"
        />
    </div>
</template>
