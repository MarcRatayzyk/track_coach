<script>
import AdminLayout from '../../Layouts/AdminLayout.vue';

export default {
  layout: AdminLayout,
};
</script>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  kpis: {
    type: Object,
    required: true,
  },
  recentCoaches: {
    type: Array,
    default: () => [],
  },
});

const kpiCards = [
  { key: 'coaches', label: 'Coachs' },
  { key: 'athletes', label: 'Athlètes' },
  { key: 'admins', label: 'Admins' },
  { key: 'active_subscriptions', label: 'Abonnements actifs' },
  { key: 'active_trials', label: 'Essais actifs' },
  { key: 'active_demos', label: 'Démos actives' },
  { key: 'signups_7d', label: 'Inscriptions 7j' },
  { key: 'signups_30d', label: 'Inscriptions 30j' },
  { key: 'disabled', label: 'Comptes désactivés' },
];
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Dashboard admin</h1>
        <p class="mt-1 text-sm text-[var(--app-muted)]">Vue d’ensemble de la plateforme Track Coach.</p>
      </div>
      <div class="flex gap-2">
        <Link href="/admin/users" class="rounded-lg border border-[var(--app-border)] px-3 py-2 text-sm hover:bg-[var(--app-hover)]">
          Utilisateurs
        </Link>
        <Link href="/admin/design" class="rounded-lg bg-[var(--app-accent)] px-3 py-2 text-sm font-medium text-white">
          Design stories
        </Link>
      </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
      <div
        v-for="card in kpiCards"
        :key="card.key"
        class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4"
      >
        <p class="text-xs uppercase tracking-wide text-[var(--app-muted)]">{{ card.label }}</p>
        <p class="mt-2 text-3xl font-semibold tabular-nums">{{ kpis[card.key] ?? 0 }}</p>
      </div>
    </div>

    <section class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]">
      <div class="border-b border-[var(--app-border)] px-4 py-3">
        <h2 class="text-sm font-semibold">Coachs récents</h2>
      </div>
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead class="text-xs uppercase text-[var(--app-muted)]">
            <tr>
              <th class="px-4 py-2 font-medium">Nom</th>
              <th class="px-4 py-2 font-medium">Email</th>
              <th class="px-4 py-2 font-medium">Athlètes</th>
              <th class="px-4 py-2 font-medium">Billing</th>
              <th class="px-4 py-2 font-medium">Statut</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="coach in recentCoaches"
              :key="coach.id"
              class="border-t border-[var(--app-border)]"
            >
              <td class="px-4 py-3 font-medium">{{ coach.name }}</td>
              <td class="px-4 py-3 text-[var(--app-muted)]">{{ coach.email }}</td>
              <td class="px-4 py-3 tabular-nums">{{ coach.active_athletes_count }}</td>
              <td class="px-4 py-3">{{ coach.billing_status }}</td>
              <td class="px-4 py-3">
                <span :class="coach.disabled ? 'text-rose-400' : 'text-emerald-400'">
                  {{ coach.disabled ? 'Désactivé' : 'Actif' }}
                </span>
              </td>
            </tr>
            <tr v-if="!recentCoaches.length">
              <td colspan="5" class="px-4 py-6 text-center text-[var(--app-muted)]">Aucun coach.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>
  </div>
</template>
