<script>
import AdminLayout from '../../Layouts/AdminLayout.vue';

export default {
  layout: AdminLayout,
};
</script>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import CoachRosterAwardsModal from '../../Components/CoachRosterAwardsModal.vue';
import WrappedStoryModal from '../../Components/WrappedStoryModal.vue';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  demo: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  wrapped_athlete_theme: { ...props.settings.wrapped_athlete_theme },
  roster_awards_theme: { ...props.settings.roster_awards_theme },
  roster_awards_copy: JSON.parse(JSON.stringify(props.settings.roster_awards_copy)),
  wrapped_copy: { ...props.settings.wrapped_copy },
});

const previewKind = ref('weekly');
const wrappedPreviewOpen = ref(false);
const awardsPreviewOpen = ref(false);

const previewWrapped = computed(() => {
  if (previewKind.value === 'monthly') {
    return props.demo.wrapped.monthly;
  }
  return props.demo.wrapped.weekly;
});

const liveAwards = computed(() => {
  const base = JSON.parse(JSON.stringify(props.demo.awards));
  const copy = form.roster_awards_copy;
  const nameByKey = { steps: 'Camille', kcal: 'Jordan', sommeil: 'Sam' };

  base.screens = (base.screens || []).map((screen) => {
    const key = screen.award_key;
    const entry = copy[key] || {};
    return {
      ...screen,
      eyebrow: entry.eyebrow ?? screen.eyebrow,
      title: entry.title ?? screen.title,
      punchline: String(entry.punchline ?? screen.punchline).replace('{name}', nameByKey[key] || screen.athlete_name),
    };
  });

  return base;
});

watch(
  () => props.settings,
  (next) => {
    form.wrapped_athlete_theme = { ...next.wrapped_athlete_theme };
    form.roster_awards_theme = { ...next.roster_awards_theme };
    form.roster_awards_copy = JSON.parse(JSON.stringify(next.roster_awards_copy));
    form.wrapped_copy = { ...next.wrapped_copy };
  },
  { deep: true },
);

function save() {
  form.put('/admin/design', { preserveScroll: true });
}
</script>

<template>
  <div class="space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Design stories</h1>
        <p class="mt-1 text-sm text-[var(--app-muted)]">
          Couleurs et textes des Wrapped athlète et Monthly Rewards coach.
        </p>
      </div>
      <button
        type="button"
        class="rounded-lg bg-[var(--app-accent)] px-4 py-2 text-sm font-medium text-white disabled:opacity-50"
        :disabled="form.processing"
        @click="save"
      >
        Enregistrer
      </button>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
      <section class="space-y-4 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4">
        <h2 class="text-sm font-semibold">Thème Wrapped athlète</h2>
        <div class="grid gap-3 sm:grid-cols-2">
          <label
            v-for="key in ['default_accent', 'squat', 'bench', 'deadlift']"
            :key="key"
            class="block text-sm"
          >
            <span class="mb-1 block capitalize text-[var(--app-muted)]">{{ key.replace('_', ' ') }}</span>
            <input
              v-model="form.wrapped_athlete_theme[key]"
              type="color"
              class="h-10 w-full cursor-pointer rounded-lg border border-[var(--app-border)] bg-transparent"
            >
          </label>
        </div>
        <div class="grid gap-3">
          <label class="block text-sm">
            <span class="mb-1 block text-[var(--app-muted)]">Brand label (optionnel)</span>
            <input
              v-model="form.wrapped_copy.brand_label"
              type="text"
              class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
              placeholder="Track Coach Wrapped"
            >
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-[var(--app-muted)]">Texte outro (optionnel)</span>
            <input
              v-model="form.wrapped_copy.keep_going"
              type="text"
              class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
              placeholder="Continue comme ça"
            >
          </label>
        </div>
        <div class="flex flex-wrap gap-2 pt-2">
          <button
            type="button"
            class="rounded-lg border border-[var(--app-border)] px-3 py-1.5 text-xs"
            :class="previewKind === 'weekly' ? 'bg-[var(--app-accent-soft)]' : ''"
            @click="previewKind = 'weekly'"
          >
            Weekly
          </button>
          <button
            type="button"
            class="rounded-lg border border-[var(--app-border)] px-3 py-1.5 text-xs"
            :class="previewKind === 'monthly' ? 'bg-[var(--app-accent-soft)]' : ''"
            @click="previewKind = 'monthly'"
          >
            Monthly
          </button>
          <button
            type="button"
            class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium"
            @click="wrappedPreviewOpen = true"
          >
            Prévisualiser Wrapped
          </button>
        </div>
      </section>

      <section class="space-y-4 rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface)] p-4">
        <h2 class="text-sm font-semibold">Thème Monthly Rewards coach</h2>
        <div class="grid gap-3 sm:grid-cols-2">
          <label
            v-for="key in ['default_accent', 'steps', 'kcal', 'sommeil']"
            :key="key"
            class="block text-sm"
          >
            <span class="mb-1 block capitalize text-[var(--app-muted)]">{{ key.replace('_', ' ') }}</span>
            <input
              v-model="form.roster_awards_theme[key]"
              type="color"
              class="h-10 w-full cursor-pointer rounded-lg border border-[var(--app-border)] bg-transparent"
            >
          </label>
        </div>
        <div
          v-for="awardKey in ['steps', 'kcal', 'sommeil']"
          :key="awardKey"
          class="space-y-2 rounded-xl border border-[var(--app-border)] p-3"
        >
          <p class="text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)]">{{ awardKey }}</p>
          <input
            v-model="form.roster_awards_copy[awardKey].eyebrow"
            type="text"
            class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2 text-sm"
            placeholder="Eyebrow"
          >
          <input
            v-model="form.roster_awards_copy[awardKey].title"
            type="text"
            class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2 text-sm"
            placeholder="Titre"
          >
          <input
            v-model="form.roster_awards_copy[awardKey].punchline"
            type="text"
            class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2 text-sm"
            placeholder="Punchline ({name})"
          >
        </div>
        <div class="grid gap-3">
          <label class="block text-sm">
            <span class="mb-1 block text-[var(--app-muted)]">Hint intro (optionnel)</span>
            <input
              v-model="form.roster_awards_copy.intro_hint"
              type="text"
              class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
            >
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-[var(--app-muted)]">Titre outro (optionnel)</span>
            <input
              v-model="form.roster_awards_copy.outro_title"
              type="text"
              class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
            >
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-[var(--app-muted)]">Sous-titre outro (optionnel)</span>
            <input
              v-model="form.roster_awards_copy.outro_subtitle"
              type="text"
              class="w-full rounded-lg border border-[var(--app-border)] bg-transparent px-3 py-2"
            >
          </label>
        </div>
        <button
          type="button"
          class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium"
          @click="awardsPreviewOpen = true"
        >
          Prévisualiser Awards
        </button>
      </section>
    </div>

    <WrappedStoryModal
      :open="wrappedPreviewOpen"
      :wrapped="previewWrapped"
      :theme="form.wrapped_athlete_theme"
      :copy="form.wrapped_copy"
      @close="wrappedPreviewOpen = false"
    />

    <CoachRosterAwardsModal
      :open="awardsPreviewOpen"
      :awards="liveAwards"
      :theme="form.roster_awards_theme"
      :copy="form.roster_awards_copy"
      @close="awardsPreviewOpen = false"
    />
  </div>
</template>
