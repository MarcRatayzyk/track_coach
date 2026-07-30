<script setup>
import ScreenFrame from './ScreenFrame.vue';
</script>

<template>
    <div class="lp-mockup-stage relative mx-auto w-full max-w-[620px] lg:max-w-none">
        <div class="lp-mockup-glow" />

        <!-- Floating roster strip -->
        <div
            class="lp-float absolute -left-3 top-6 z-20 hidden w-[44%] max-w-[230px] overflow-hidden rounded-2xl border border-white/10 bg-[#0b1228]/95 shadow-2xl shadow-blue-950/50 backdrop-blur-xl sm:block lg:-left-10 lg:top-10"
        >
            <div class="border-b border-white/[0.06] px-2.5 py-1.5">
                <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Roster</p>
            </div>
            <div class="max-h-[110px] overflow-hidden">
                <img
                    src="/images/landing/roster.png"
                    alt="Tableau roster athlètes Power Roster"
                    loading="eager"
                    decoding="async"
                    class="h-auto w-full scale-110 object-cover object-top"
                />
            </div>
        </div>

        <!-- Floating program peek -->
        <div
            class="lp-float-delayed absolute -right-2 top-14 z-20 hidden w-[40%] max-w-[210px] overflow-hidden rounded-2xl border border-white/10 bg-[#0b1228]/95 shadow-2xl backdrop-blur-xl sm:block lg:-right-8"
        >
            <div class="border-b border-white/[0.06] px-2.5 py-1.5">
                <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Programmation</p>
            </div>
            <div class="max-h-[120px] overflow-hidden">
                <img
                    src="/images/landing/program-editor.png"
                    alt="Éditeur de programme SBD"
                    loading="eager"
                    decoding="async"
                    class="h-auto w-full scale-105 object-cover object-left-top"
                />
            </div>
        </div>

        <!-- Floating stats card bottom -->
        <div
            class="lp-float-slow absolute -bottom-3 left-[8%] z-20 hidden w-[50%] max-w-[270px] overflow-hidden rounded-2xl border border-white/10 bg-[#0b1228]/95 shadow-2xl md:block lg:-bottom-6 lg:left-0"
        >
            <div class="border-b border-white/[0.06] px-2.5 py-1.5">
                <p class="text-[9px] font-semibold uppercase tracking-wider text-slate-500">Stats SBD</p>
            </div>
            <div class="max-h-[100px] overflow-hidden">
                <img
                    src="/images/landing/stats.png"
                    alt="Statistiques volume et topset"
                    loading="lazy"
                    decoding="async"
                    class="h-auto w-full scale-110 object-cover object-top"
                />
            </div>
        </div>

        <!-- Main dashboard frame -->
        <div class="lp-mockup-frame relative z-10">
            <ScreenFrame
                src="/images/landing/actions.png"
                alt="Dashboard coach Power Roster — actions prioritaires et retours à traiter"
                label="app.powerroster.fr / dashboard"
                loading="eager"
            />
        </div>
    </div>
</template>
