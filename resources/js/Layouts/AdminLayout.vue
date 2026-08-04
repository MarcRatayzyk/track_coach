<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogo from '../Components/AppLogo.vue';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import { useTheme } from '../composables/useTheme';
import { resetAnalytics } from '../utils/analytics';

const page = usePage();
const user = computed(() => page.props.auth?.user ?? null);
const flash = computed(() => page.props.flash ?? {});
const { isLight, toggleTheme } = useTheme();
const mobileOpen = ref(false);

const navItems = [
  { label: 'Dashboard', href: '/admin', pattern: '/admin$', exact: true },
  { label: 'Utilisateurs', href: '/admin/users', pattern: '/admin/users' },
  { label: 'Design stories', href: '/admin/design', pattern: '/admin/design' },
];

function navActive(item) {
  const url = page.url.split('?')[0];
  if (item.exact) {
    return url === '/admin' || url === '/admin/';
  }
  return url.startsWith(item.pattern);
}

function logout() {
  resetAnalytics();
  router.post('/logout');
}
</script>

<template>
  <div class="min-h-screen bg-[var(--app-bg)] text-[var(--app-text)]">
    <div class="mx-auto flex min-h-screen max-w-7xl">
      <aside class="hidden w-60 shrink-0 border-r border-[var(--app-border)] bg-[var(--app-surface)] p-4 lg:block">
        <Link href="/admin" class="flex items-center gap-2 px-2 py-1">
          <AppLogo class="h-8 w-8" />
          <div>
            <p class="text-sm font-semibold">Track Coach</p>
            <p class="text-xs text-[var(--app-muted)]">Admin</p>
          </div>
        </Link>

        <nav class="mt-8 space-y-1">
          <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            class="block rounded-lg px-3 py-2 text-sm font-medium transition"
            :class="navActive(item)
              ? 'bg-[var(--app-accent-soft)] text-[var(--app-accent)]'
              : 'text-[var(--app-muted)] hover:bg-[var(--app-hover)] hover:text-[var(--app-text)]'"
          >
            {{ item.label }}
          </Link>
        </nav>

        <div class="mt-8 space-y-3 border-t border-[var(--app-border)] pt-4">
          <p v-if="user" class="truncate px-2 text-xs text-[var(--app-muted)]">{{ user.email }}</p>
          <div class="flex items-center gap-2 px-1">
            <LanguageSwitcher />
            <button
              type="button"
              class="rounded-lg border border-[var(--app-border)] px-2 py-1 text-xs"
              @click="toggleTheme"
            >
              {{ isLight ? 'Sombre' : 'Clair' }}
            </button>
          </div>
          <button
            type="button"
            class="w-full rounded-lg border border-[var(--app-border)] px-3 py-2 text-left text-sm text-[var(--app-muted)] hover:bg-[var(--app-hover)]"
            @click="logout"
          >
            Déconnexion
          </button>
        </div>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between border-b border-[var(--app-border)] bg-[var(--app-surface)] px-4 py-3 lg:hidden">
          <Link href="/admin" class="flex items-center gap-2 font-semibold">
            <AppLogo class="h-7 w-7" />
            Admin
          </Link>
          <button
            type="button"
            class="rounded-lg border border-[var(--app-border)] px-3 py-1.5 text-sm"
            @click="mobileOpen = !mobileOpen"
          >
            Menu
          </button>
        </header>

        <nav
          v-if="mobileOpen"
          class="space-y-1 border-b border-[var(--app-border)] bg-[var(--app-surface)] p-3 lg:hidden"
        >
          <Link
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            class="block rounded-lg px-3 py-2 text-sm"
            :class="navActive(item) ? 'bg-[var(--app-accent-soft)] text-[var(--app-accent)]' : ''"
            @click="mobileOpen = false"
          >
            {{ item.label }}
          </Link>
          <button type="button" class="w-full rounded-lg px-3 py-2 text-left text-sm" @click="logout">
            Déconnexion
          </button>
        </nav>

        <main class="flex-1 p-4 sm:p-6">
          <div
            v-if="flash.success"
            class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300"
          >
            {{ flash.success }}
          </div>
          <div
            v-if="flash.error"
            class="mb-4 rounded-xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-300"
          >
            {{ flash.error }}
          </div>
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>
