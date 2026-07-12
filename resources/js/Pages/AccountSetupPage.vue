<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
  LEVEL_OPTIONS,
  SEX_OPTIONS,
  weightCategoriesForSex,
} from '../config/ipfWeightCategories';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    role: {
        type: String,
        required: true,
    },
    submitUrl: {
        type: String,
        required: true,
    },
});

const isCoach = computed(() => props.role === 'coach');

const stepIndex = ref(0);

const athleteSteps = [
    { id: 'welcome', title: 'Bienvenue' },
    { id: 'practice', title: 'Ta pratique' },
    { id: 'prs', title: 'Tes records' },
    { id: 'profile', title: 'Ton profil' },
    { id: 'goals', title: 'Tes objectifs' },
];

const trainingYearOptions = [
    { value: 0, label: "Moins d'1 an" },
    { value: 1, label: '1 an' },
    { value: 2, label: '2 ans' },
    { value: 3, label: '3–5 ans' },
    { value: 5, label: '5–10 ans' },
    { value: 10, label: '10 ans et +' },
];

const form = useForm({
    password: '',
    password_confirmation: '',
    years_training: null,
    squat: '',
    bench: '',
    deadlift: '',
    birth_date: '',
    height_cm: null,
    sex: '',
    weight_category: '',
    level: '',
    injuries_notes: '',
    profession: '',
    bio: '',
    specialties: [],
    years_experience: null,
    certifications: '',
    club_gym: '',
});

const categoryOptions = computed(() => weightCategoriesForSex(form.sex));

const currentStep = computed(() => athleteSteps[stepIndex.value] ?? athleteSteps[0]);
const isFirstStep = computed(() => stepIndex.value <= 0);
const isLastStep = computed(() => stepIndex.value >= athleteSteps.length - 1);

const canGoNext = computed(() => {
    if (currentStep.value.id === 'welcome') {
        return form.password.length >= 8 && form.password === form.password_confirmation;
    }
    return true;
});

function nextStep() {
    if (!canGoNext.value) {
        return;
    }
    if (isLastStep.value) {
        submit();
        return;
    }
    stepIndex.value += 1;
}

function prevStep() {
    if (!isFirstStep.value) {
        stepIndex.value -= 1;
    }
}

function submit() {
    form.post(props.submitUrl, {
        preserveScroll: true,
    });
}

const inputClass =
    'mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white placeholder:text-slate-600';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-blue-950/30 px-4 py-10 text-slate-100">
        <Head :title="isCoach ? 'Activer mon compte coach' : 'Bienvenue sur Track Coach'" />

        <div class="mx-auto w-full max-w-lg">
            <template v-if="isCoach">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
                    <h1 class="text-2xl font-bold text-white">Active ton compte coach</h1>
                    <p class="mt-2 text-slate-400">
                        Bonjour <span class="font-medium text-slate-200">{{ user.name }}</span>,
                        choisis un mot de passe pour accéder à ton espace coach.
                    </p>
                    <p class="mt-3 text-sm text-slate-500">E-mail : {{ user.email }}</p>

                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <label class="block text-sm font-medium text-slate-400">
                            Mot de passe
                            <input v-model="form.password" type="password" required autocomplete="new-password" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            Confirmation
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                :class="inputClass"
                            />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            Bio
                            <textarea v-model="form.bio" rows="3" :class="inputClass" placeholder="Présentation courte" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            Années d'expérience
                            <input v-model.number="form.years_experience" type="number" min="0" max="60" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            Certifications
                            <textarea v-model="form.certifications" rows="2" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            Club / salle
                            <input v-model="form.club_gym" type="text" :class="inputClass" />
                        </label>
                        <p v-if="form.errors.password" class="text-sm text-red-400">{{ form.errors.password }}</p>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                        >
                            Activer mon compte
                        </button>
                    </form>
                </div>
            </template>

            <template v-else>
                <div class="mb-6 flex items-center justify-between gap-3">
                    <div class="flex gap-1.5">
                        <span
                            v-for="(step, index) in athleteSteps"
                            :key="step.id"
                            class="h-1.5 rounded-full transition-all"
                            :class="index === stepIndex ? 'w-8 bg-blue-500' : index < stepIndex ? 'w-4 bg-blue-500/50' : 'w-4 bg-slate-700'"
                        />
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        {{ stepIndex + 1 }}/{{ athleteSteps.length }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-900/85 p-6 shadow-xl sm:p-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-400">Onboarding</p>
                    <h1 class="mt-2 text-2xl font-bold text-white">{{ currentStep.title }}</h1>
                    <p class="mt-2 text-sm text-slate-400">
                        Salut <span class="font-medium text-slate-200">{{ user.name }}</span>
                        <template v-if="currentStep.id === 'welcome'">
                            — créons ton profil athlète en quelques étapes.
                        </template>
                    </p>

                    <div class="mt-6 space-y-4">
                        <template v-if="currentStep.id === 'welcome'">
                            <p class="text-sm text-slate-500">E-mail de connexion : {{ user.email }}</p>
                            <label class="block text-sm font-medium text-slate-400">
                                Mot de passe
                                <input v-model="form.password" type="password" required autocomplete="new-password" :class="inputClass" />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Confirmation
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    required
                                    autocomplete="new-password"
                                    :class="inputClass"
                                />
                            </label>
                            <p v-if="form.errors.password" class="text-sm text-red-400">{{ form.errors.password }}</p>
                        </template>

                        <template v-else-if="currentStep.id === 'practice'">
                            <p class="text-sm text-slate-400">Depuis combien de temps tu pratiques la force / powerlifting ?</p>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="option in trainingYearOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-2.5 text-sm font-medium transition"
                                    :class="
                                        form.years_training === option.value
                                            ? 'border-blue-500 bg-blue-600 text-white'
                                            : 'border-slate-700 text-slate-300 hover:border-slate-600'
                                    "
                                    @click="form.years_training = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </template>

                        <template v-else-if="currentStep.id === 'prs'">
                            <p class="text-sm text-slate-400">
                                Renseigne tes meilleurs lifts actuels (compétition ou estimés au gym).
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="block text-xs font-medium text-slate-400">
                                    Squat (kg)
                                    <input v-model="form.squat" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                                <label class="block text-xs font-medium text-slate-400">
                                    Bench (kg)
                                    <input v-model="form.bench" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                                <label class="block text-xs font-medium text-slate-400">
                                    Terre (kg)
                                    <input v-model="form.deadlift" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                            </div>
                        </template>

                        <template v-else-if="currentStep.id === 'profile'">
                            <label class="block text-sm font-medium text-slate-400">
                                Date de naissance
                                <input v-model="form.birth_date" type="date" :class="inputClass" />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Taille (cm)
                                <input
                                    v-model.number="form.height_cm"
                                    type="number"
                                    min="100"
                                    max="250"
                                    :class="inputClass"
                                />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Sexe
                                <select v-model="form.sex" :class="inputClass">
                                    <option value="">—</option>
                                    <option v-for="option in SEX_OPTIONS" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Profession
                                <input
                                    v-model="form.profession"
                                    type="text"
                                    :class="inputClass"
                                    placeholder="Ex. Étudiant, ingénieur…"
                                />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Catégorie de poids IPF
                                <select v-model="form.weight_category" :class="inputClass">
                                    <option value="">—</option>
                                    <option
                                        v-for="option in categoryOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Niveau
                                <select v-model="form.level" :class="inputClass">
                                    <option value="">—</option>
                                    <option v-for="option in LEVEL_OPTIONS" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                Blessures / gênes récentes
                                <textarea
                                    v-model="form.injuries_notes"
                                    rows="2"
                                    :class="inputClass"
                                    placeholder="Optionnel"
                                />
                            </label>
                        </template>

                        <template v-else-if="currentStep.id === 'goals'">
                            <label class="block text-sm font-medium text-slate-400">
                                Objectifs & contexte pour ton coach
                                <textarea
                                    v-model="form.bio"
                                    rows="4"
                                    :class="inputClass"
                                    placeholder="Ex. viser un total de 500 kg, préparer une compétition en novembre…"
                                />
                            </label>
                            <p class="text-xs text-slate-500">
                                Tu pourras modifier ces infos plus tard depuis l’icône profil.
                            </p>
                        </template>

                        <p v-if="Object.keys(form.errors).length" class="text-sm text-red-400">
                            {{ Object.values(form.errors).flat().join(' ') }}
                        </p>
                    </div>

                    <div class="mt-8 flex items-center justify-between gap-3">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm text-slate-300 hover:bg-slate-800 disabled:opacity-30"
                            :disabled="isFirstStep"
                            @click="prevStep"
                        >
                            Retour
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                            :disabled="form.processing || !canGoNext"
                            @click="nextStep"
                        >
                            {{ isLastStep ? (form.processing ? 'Activation…' : 'Terminer') : 'Suivant' }}
                        </button>
                    </div>
                </div>
            </template>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="/login" class="text-blue-400 hover:text-blue-300">Retour à la connexion</a>
            </p>
        </div>
    </div>
</template>
