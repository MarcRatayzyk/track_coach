<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLogo from '../Components/AppLogo.vue';
</script>

<template>
  <div class="min-h-screen bg-slate-950 text-slate-200">
    <Head title="Politique de confidentialité — Power Roster" />

    <header class="border-b border-slate-800/80">
      <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 sm:px-6">
        <Link href="/" class="flex items-center gap-2">
          <AppLogo mark-class="h-8 w-8" wordmark-class="text-sm font-bold text-white" />
        </Link>
        <Link href="/" class="text-sm font-medium text-slate-400 transition hover:text-white">
          Retour à l'accueil
        </Link>
      </div>
    </header>

    <main class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
      <h1 class="text-3xl font-bold tracking-tight text-white">Politique de confidentialité</h1>
      <p class="mt-2 text-sm text-slate-500">Dernière mise à jour : {{ new Date().getFullYear() }}</p>

      <div class="mt-8 space-y-8 text-sm leading-relaxed text-slate-300">
        <section>
          <h2 class="text-lg font-semibold text-white">1. Responsable du traitement</h2>
          <p class="mt-2">
            Power Roster est une plateforme de coaching de force (powerlifting) permettant aux coachs
            de gérer leurs athlètes, programmes, retours vidéo et communications. Le responsable du
            traitement des données est l'éditeur de Power Roster.
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">2. Données collectées</h2>
          <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Données de compte : nom, adresse e-mail, rôle (coach ou athlète).</li>
            <li>Données de profil : date de naissance, taille, sexe, catégorie de poids, historique de blessures, biographie.</li>
            <li>Données d'entraînement : séances, records personnels, compétitions, indicateurs de forme (readiness), poids corporel.</li>
            <li>Contenus : retours vidéo, annotations, messages texte et audio.</li>
            <li>Données techniques : journaux de connexion nécessaires à la sécurité.</li>
            <li>Données d'usage anonymisées ou associées à ton compte : pages vues, actions dans l'app (via PostHog UE).</li>
          </ul>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">3. Finalités et base légale</h2>
          <p class="mt-2">
            Les données sont traitées pour fournir le service de coaching (exécution du contrat),
            assurer la sécurité de la plateforme (intérêt légitime) et, le cas échéant, sur la base de
            ton consentement. Les données de santé (blessures) ne sont traitées que dans le cadre du
            suivi sportif que tu confies à ton coach.
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">4. Partage des données</h2>
          <p class="mt-2">
            Les données d'un athlète sont accessibles au(x) coach(s) auxquels il est rattaché. Nous ne
            vendons aucune donnée. Certains sous-traitants techniques hébergent les données pour notre
            compte (hébergement, stockage vidéo, envoi d'e-mails) dans le respect du RGPD.
          </p>
          <p class="mt-2">
            Nous utilisons PostHog (hébergé dans l'Union européenne) pour l'analytics produit :
            pages consultées, actions dans l'application et, le cas échéant, enregistrements de
            session avec masquage des champs saisis. Ces données servent uniquement à améliorer le
            service. Elles sont conservées selon la politique de rétention de PostHog.
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">5. Durée de conservation</h2>
          <p class="mt-2">
            Les données sont conservées tant que ton compte est actif. Tu peux supprimer ton compte à
            tout moment, ce qui entraîne l'effacement définitif de tes données et de tes médias.
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">6. Tes droits</h2>
          <p class="mt-2">
            Conformément au RGPD, tu disposes d'un droit d'accès, de rectification, d'effacement, de
            limitation et de portabilité. Depuis ton espace
            <Link href="/account/privacy" class="text-blue-400 hover:underline">Confidentialité et données</Link>,
            tu peux exporter tes données au format JSON et supprimer ton compte. Pour toute autre
            demande, contacte le support.
          </p>
        </section>

        <section>
          <h2 class="text-lg font-semibold text-white">7. Sécurité</h2>
          <p class="mt-2">
            Les mots de passe sont chiffrés, les accès sont cloisonnés par rôle et par relation
            coach-athlète, et des sauvegardes régulières sont réalisées.
          </p>
        </section>
      </div>
    </main>

    <footer class="border-t border-slate-800/80">
      <div class="mx-auto max-w-4xl px-4 py-6 text-sm text-slate-500 sm:px-6">
        Power Roster — Coaching powerlifting.
      </div>
    </footer>
  </div>
</template>
