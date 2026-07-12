<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { COACH_SPECIALTY_OPTIONS } from '../config/ipfWeightCategories';

const props = defineProps({
  coach: {
    type: Object,
    required: true,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  editableProfile: {
    type: Object,
    default: null,
  },
  messagingThreadId: {
    type: Number,
    default: null,
  },
});

const profileForm = useForm({
  bio: props.editableProfile?.bio ?? '',
  specialties: props.editableProfile?.specialties ?? [],
  years_experience: props.editableProfile?.years_experience ?? null,
  certifications: props.editableProfile?.certifications ?? '',
  club_gym: props.editableProfile?.club_gym ?? '',
});

const rosterStats = computed(() => props.coach.roster_stats ?? null);

function toggleSpecialty(value) {
  const current = new Set(profileForm.specialties ?? []);
  if (current.has(value)) {
    current.delete(value);
  } else {
    current.add(value);
  }
  profileForm.specialties = [...current];
}

function submitProfile() {
  profileForm.patch('/coach/profile', {
    preserveScroll: true,
  });
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-4">
    <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg">
      <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-xs font-semibold uppercase tracking-widest text-blue-400">Profil coach</p>
          <h1 class="mt-1 text-2xl font-bold text-white">{{ coach.name }}</h1>
          <p v-if="coach.club_gym && !canEdit" class="mt-1 text-sm text-slate-400">{{ coach.club_gym }}</p>
        </div>
        <Link
          v-if="messagingThreadId"
          :href="`/messaging?thread=${messagingThreadId}`"
          class="rounded-xl border border-blue-500/40 bg-blue-950/30 px-4 py-2 text-sm font-semibold text-blue-200 hover:bg-blue-950/50"
        >
          Messagerie →
        </Link>
      </div>

      <div v-if="coach.specialty_labels?.length" class="mt-4 flex flex-wrap gap-2">
        <span
          v-for="label in coach.specialty_labels"
          :key="label"
          class="rounded-full border border-slate-700 bg-slate-950/60 px-3 py-1 text-xs font-medium text-slate-200"
        >
          {{ label }}
        </span>
      </div>

      <dl class="mt-4 grid gap-3 sm:grid-cols-2">
        <div
          v-if="coach.years_experience != null"
          class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5"
        >
          <dt class="text-xs text-slate-500">Expérience</dt>
          <dd class="mt-0.5 font-semibold text-white">{{ coach.years_experience }} an{{ coach.years_experience > 1 ? 's' : '' }}</dd>
        </div>
        <div
          v-if="coach.certifications"
          class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5 sm:col-span-2"
        >
          <dt class="text-xs text-slate-500">Certifications</dt>
          <dd class="mt-0.5 whitespace-pre-wrap text-sm text-white">{{ coach.certifications }}</dd>
        </div>
      </dl>

      <div
        v-if="coach.bio && !canEdit"
        class="mt-4 rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-300"
      >
        <p class="text-xs font-semibold text-slate-500">Bio</p>
        <p class="mt-1 whitespace-pre-wrap">{{ coach.bio }}</p>
      </div>
    </div>

    <form
      v-if="canEdit && editableProfile"
      class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg"
      @submit.prevent="submitProfile"
    >
      <h2 class="text-sm font-semibold text-white">Modifier mon profil</h2>
      <div class="mt-4 space-y-3">
        <label class="block text-xs text-slate-400">
          Bio
          <textarea
            v-model="profileForm.bio"
            rows="4"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <div>
          <p class="text-xs text-slate-400">Spécialités</p>
          <div class="mt-2 flex flex-wrap gap-2">
            <button
              v-for="option in COACH_SPECIALTY_OPTIONS"
              :key="option.value"
              type="button"
              class="rounded-full border px-3 py-1 text-xs font-medium transition"
              :class="
                profileForm.specialties.includes(option.value)
                  ? 'border-blue-500/60 bg-blue-600/20 text-blue-200'
                  : 'border-slate-700 text-slate-400 hover:border-slate-600'
              "
              @click="toggleSpecialty(option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>
        <label class="block text-xs text-slate-400">
          Années d'expérience
          <input
            v-model.number="profileForm.years_experience"
            type="number"
            min="0"
            max="60"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <label class="block text-xs text-slate-400">
          Certifications
          <textarea
            v-model="profileForm.certifications"
            rows="2"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <label class="block text-xs text-slate-400">
          Club / salle
          <input
            v-model="profileForm.club_gym"
            type="text"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-sm text-white"
          />
        </label>
        <button
          type="submit"
          class="w-full rounded-lg bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-500"
          :disabled="profileForm.processing"
        >
          Enregistrer
        </button>
      </div>
    </form>

    <section
      v-if="rosterStats"
      class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5 shadow-lg"
    >
      <h2 class="text-sm font-semibold text-white">Statistiques roster</h2>
      <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3 text-center">
          <p class="text-2xl font-bold tabular-nums text-white">{{ rosterStats.athlete_count }}</p>
          <p class="mt-1 text-xs text-slate-500">Athlètes actifs</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3 text-center">
          <p class="text-2xl font-bold tabular-nums text-white">
            {{ rosterStats.average_adherence_30d != null ? `${rosterStats.average_adherence_30d}%` : '—' }}
          </p>
          <p class="mt-1 text-xs text-slate-500">Adhérence moy. (30 j)</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3 text-center">
          <p class="text-2xl font-bold tabular-nums text-white">{{ rosterStats.active_blocks }}</p>
          <p class="mt-1 text-xs text-slate-500">Blocs actifs</p>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-950/50 px-3 py-3 text-center">
          <p class="text-2xl font-bold tabular-nums text-white">{{ rosterStats.upcoming_competitions }}</p>
          <p class="mt-1 text-xs text-slate-500">Compétitions à venir</p>
        </div>
      </div>
    </section>
  </div>
</template>
