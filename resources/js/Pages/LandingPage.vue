<script setup>
import { Head } from '@inertiajs/vue3';
import LandingBackground from '../Components/Landing/LandingBackground.vue';
import LandingNav from '../Components/Landing/LandingNav.vue';
import LandingHero from '../Components/Landing/LandingHero.vue';
import PainPointsSection from '../Components/Landing/PainPointsSection.vue';
import FeaturesSection from '../Components/Landing/FeaturesSection.vue';
import DemoSection from '../Components/Landing/DemoSection.vue';
import BenefitsSection from '../Components/Landing/BenefitsSection.vue';
import StatsSection from '../Components/Landing/StatsSection.vue';
import ComparisonSection from '../Components/Landing/ComparisonSection.vue';
import FaqSection from '../Components/Landing/FaqSection.vue';
import PricingSection from '../Components/Landing/PricingSection.vue';
import FinalCta from '../Components/Landing/FinalCta.vue';
import LandingFooter from '../Components/Landing/LandingFooter.vue';
</script>

<template>
    <Head>
        <title>Power Roster — Coaching powerlifting structuré</title>
        <meta
            head-key="description"
            name="description"
            content="Power Roster réunit programmation, suivi SBD, vidéos annotées, messagerie et compétitions pour les coachs de powerlifting. Une seule plateforme."
        />
        <meta head-key="og:title" property="og:title" content="Power Roster — Structure ton coaching powerlifting" />
        <meta
            head-key="og:description"
            property="og:description"
            content="La plateforme de référence pour les coachs de powerlifting. Programmes, PRs, vidéos annotées et messages au même endroit."
        />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
    </Head>

    <div class="landing-page relative min-h-screen">
        <LandingBackground />
        <LandingNav />
        <main>
            <LandingHero />
            <PainPointsSection />
            <FeaturesSection />
            <DemoSection />
            <BenefitsSection />
            <StatsSection />
            <ComparisonSection />
            <PricingSection />
            <FaqSection />
            <FinalCta />
        </main>
        <LandingFooter />
    </div>
</template>
