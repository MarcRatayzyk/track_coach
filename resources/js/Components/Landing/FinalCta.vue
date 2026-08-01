<script setup>
import FadeIn from './FadeIn.vue';
import { track } from '../../utils/analytics';

function trackCta(ctaId) {
    track('cta_clicked', { cta_id: ctaId });
}
</script>

<template>
    <section class="relative z-10 px-5 py-20 sm:px-8 lg:px-10 lg:py-28" aria-labelledby="final-cta-heading">
        <div class="mx-auto w-full max-w-[1280px]">
            <FadeIn>
                <div
                    class="relative overflow-hidden rounded-[28px] border border-blue-400/25 px-8 py-16 text-center sm:px-12 sm:py-20 lg:px-16"
                    style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.35) 0%, rgba(5, 11, 30, 0.95) 45%, rgba(15, 23, 42, 0.9) 100%)"
                >
                    <div
                        class="pointer-events-none absolute -left-20 top-0 h-64 w-64 rounded-full bg-blue-500/30 blur-3xl"
                        aria-hidden="true"
                    />
                    <div
                        class="pointer-events-none absolute -right-16 bottom-0 h-56 w-56 rounded-full bg-blue-400/20 blur-3xl"
                        aria-hidden="true"
                    />

                    <div class="relative">
                        <h2
                            id="final-cta-heading"
                            class="mx-auto max-w-2xl text-3xl font-black tracking-[-0.03em] text-white sm:text-4xl lg:text-5xl"
                        >
                            Prêt à transformer ton coaching&nbsp;?
                        </h2>
                        <p class="mx-auto mt-5 max-w-lg text-base text-slate-300 sm:text-lg">
                            14 jours d’essai sans carte. Ou ouvre une démo avec ton e-mail pour tester
                            le produit immédiatement.
                        </p>
                        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                            <a
                                href="/register"
                                class="lp-btn-primary px-10 py-4 text-base leading-none sm:text-lg"
                                @click="trackCta('final_register')"
                            >
                                Essai 14 jours
                            </a>
                            <a
                                href="/demo"
                                class="lp-btn-secondary px-10 py-4 text-base leading-none sm:text-lg"
                                @click="trackCta('final_demo')"
                            >
                                Démo
                            </a>
                        </div>
                    </div>
                </div>
            </FadeIn>
        </div>
    </section>
</template>
