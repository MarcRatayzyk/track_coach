<script>
import AdminLayout from '../../Layouts/AdminLayout.vue';

export default {
  layout: AdminLayout,
};
</script>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
  users: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ q: '', role: '', status: '' }),
  },
});

const form = useForm({
  q: props.filters.q ?? '',
  role: props.filters.role ?? '',
  status: props.filters.status ?? '',
});

let filterTimer = null;
let filtersReady = false;
watch(
  () => [form.q, form.role, form.status],
  () => {
    if (!filtersReady) {
      filtersReady = true;
      return;
    }
    window.clearTimeout(filterTimer);
    filterTimer = window.setTimeout(() => {
      form.get('/admin/users', {
        preserveState: true,
        replace: true,
      });
    }, 250);
  },
);

function toggleDisable(user) {
  router.patch(`/admin/users/${user.id}/disable`, {}, { preserveScroll: true });
}

function extendTrial(user) {
  router.patch(`/admin/users/${user.id}/extend-trial`, { days: 14 }, { preserveScroll: true });
}

function destroyUser(user) {
  if (!window.confirm(`Supprimer définitivement ${user.name} ?`)) {
    return;
  }
  router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString('fr-FR');
}
</script>

<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight">Utilisateurs</h1>
      <p class="mt-1 text-sm text-[var(--app-muted)]">Recherche, filtres et actions sur les comptes.</p>
    </div>

    <form class="grid gap-3 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4 sm:grid-cols-3" @submit.prevent>
      <label class="block text-sm">
        <span class="mb-1 block text-[var(--app-muted)]">Recherche</span>
        <input
          v-model="form.q"
          type="search"
          class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
          placeholder="Nom ou email"
        >
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-[var(--app-muted)]">Rôle</span>
        <select v-model="form.role" class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2">
          <option value="">Tous</option>
          <option value="coach">Coach</option>
          <option value="athlete">Athlète</option>
          <option value="admin">Admin</option>
        </select>
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-[var(--app-muted)]">Statut</span>
        <select v-model="form.status" class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2">
          <option value="">Tous</option>
          <option value="active">Actif</option>
          <option value="disabled">Désactivé</option>
        </select>
      </label>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)]">
      <table class="min-w-full text-left text-sm">
        <thead class="text-xs uppercase text-[var(--app-muted)]">
          <tr>
            <th class="px-4 py-3 font-medium">Compte</th>
            <th class="px-4 py-3 font-medium">Rôle</th>
            <th class="px-4 py-3 font-medium">Infos</th>
            <th class="px-4 py-3 font-medium">Créé</th>
            <th class="px-4 py-3 font-medium">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="user in users.data"
            :key="user.id"
            class="border-t border-[var(--app-border)] align-top"
          >
            <td class="px-4 py-3">
              <p class="font-medium">{{ user.name }}</p>
              <p class="text-xs text-[var(--app-muted)]">{{ user.email }}</p>
              <p v-if="user.disabled_at" class="mt-1 text-xs text-rose-400">Désactivé</p>
            </td>
            <td class="px-4 py-3">{{ user.role }}</td>
            <td class="px-4 py-3 text-xs text-[var(--app-muted)]">
              <template v-if="user.role === 'coach'">
                <p>Billing : {{ user.billing_status }}</p>
                <p>Athlètes : {{ user.athlete_count }}</p>
                <p v-if="user.trial_ends_at">Essai : {{ formatDate(user.trial_ends_at) }}</p>
                <p v-if="user.is_demo">Compte démo</p>
              </template>
              <span v-else>—</span>
            </td>
            <td class="px-4 py-3 text-xs">{{ formatDate(user.created_at) }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-col gap-2">
                <button
                  type="button"
                  class="rounded-lg border border-[var(--app-border)] px-2 py-1 text-xs hover:bg-[var(--app-hover)]"
                  @click="toggleDisable(user)"
                >
                  {{ user.disabled_at ? 'Réactiver' : 'Désactiver' }}
                </button>
                <button
                  v-if="user.role === 'coach'"
                  type="button"
                  class="rounded-lg border border-[var(--app-border)] px-2 py-1 text-xs hover:bg-[var(--app-hover)]"
                  @click="extendTrial(user)"
                >
                  +14j essai
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-rose-500/40 px-2 py-1 text-xs text-rose-400 hover:bg-rose-500/10"
                  @click="destroyUser(user)"
                >
                  Supprimer
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!users.data?.length">
            <td colspan="5" class="px-4 py-8 text-center text-[var(--app-muted)]">Aucun utilisateur.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="users.links?.length > 3" class="flex flex-wrap gap-2">
      <template v-for="(link, index) in users.links" :key="`${index}-${link.label}`">
        <Link
          v-if="link.url"
          :href="link.url"
          class="rounded-lg border border-[var(--app-border)] px-3 py-1 text-xs"
          :class="link.active ? 'bg-[var(--app-accent)] text-white' : ''"
          preserve-scroll
          v-html="link.label"
        />
        <span
          v-else
          class="rounded-lg border border-[var(--app-border)] px-3 py-1 text-xs opacity-40"
          v-html="link.label"
        />
      </template>
    </div>
  </div>
</template>
