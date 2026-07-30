<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import FadeIn from './FadeIn.vue';

const stats = [
    { value: '24/7', label: 'Accès coach & athlètes' },
    { value: '100%', label: 'Focus powerlifting' },
    { value: '1', label: 'Plateforme unique' },
    { value: '0', label: 'Feuille Excel' },
];

const visible = ref(false);
const el = ref(null);
let observer;

onMounted(() => {
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) visible.value = true;
        },
        { threshold: 0.3 },
    );
    if (el.value) observer.observe(el.value);
});

onUnmounted(() => observer?.disconnect());
</script>

<template>
    <section class="relative z-10 px-5 py-16 sm:px-8 lg:px-10 lg:py-20" aria-label="Chiffres clés">
        <div ref="el" class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <div
                    class="grid grid-cols-2 gap-px overflow-hidden rounded-[24px] border border-white/[0.08] bg-white/[0.06] sm:grid-cols-4"
                    :class="visible ? 'opacity-100' : 'opacity-90'"
                >
                    <div
                        v-for="(s, i) in stats"
                        :key="s.label"
                        class="bg-[#050B1E]/90 px-6 py-10 text-center transition duration-700"
                        :style="{ transitionDelay: `${i * 80}ms`, transform: visible ? 'translateY(0)' : 'translateY(12px)' }"
                    >
                        <p class="text-4xl font-black tracking-tight text-white sm:text-5xl">{{ s.value }}</p>
                        <p class="mt-2 text-sm text-slate-400">{{ s.label }}</p>
                    </div>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
