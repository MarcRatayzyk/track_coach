<script setup>
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import LandingBackground from '../Components/Landing/LandingBackground.vue';
import LandingNav from '../Components/Landing/LandingNav.vue';
import LandingHero from '../Components/Landing/LandingHero.vue';
import PainPointsSection from '../Components/Landing/PainPointsSection.vue';
import FeaturesSection from '../Components/Landing/FeaturesSection.vue';
import TrackingSection from '../Components/Landing/TrackingSection.vue';
import ModularitySection from '../Components/Landing/ModularitySection.vue';
import AthleteAppSection from '../Components/Landing/AthleteAppSection.vue';
import DemoSection from '../Components/Landing/DemoSection.vue';
import BenefitsSection from '../Components/Landing/BenefitsSection.vue';
import StatsSection from '../Components/Landing/StatsSection.vue';
import ComparisonSection from '../Components/Landing/ComparisonSection.vue';
import FaqSection from '../Components/Landing/FaqSection.vue';
import PricingSection from '../Components/Landing/PricingSection.vue';
import FinalCta from '../Components/Landing/FinalCta.vue';
import LandingFooter from '../Components/Landing/LandingFooter.vue';

const { t } = useI18n();
</script>

<template>
    <Head>
        <title>{{ t('landing.meta.title') }}</title>
        <meta
            head-key="description"
            name="description"
            :content="t('landing.meta.description')"
        />
        <meta head-key="og:title" property="og:title" :content="t('landing.meta.ogTitle')" />
        <meta
            head-key="og:description"
            property="og:description"
            :content="t('landing.meta.ogDescription')"
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
            <TrackingSection />
            <ModularitySection />
            <AthleteAppSection />
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
