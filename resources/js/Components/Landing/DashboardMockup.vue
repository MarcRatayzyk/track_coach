<script setup>
import ScreenFrame from './ScreenFrame.vue';
</script>

<template>
    <div class="lp-mockup-stage relative mx-auto w-full max-w-[420px] sm:max-w-[460px] lg:max-w-[480px]">
        <div class="lp-mockup-glow" />

        <div class="lp-mockup-frame relative z-10">
            <ScreenFrame
                src="/images/landing/actions.png"
                alt="Dashboard coach Power Roster : actions prioritaires et retours à traiter"
                label="app.powerroster.fr / dashboard"
                loading="eager"
            />
        </div>
    </div>
</template>
