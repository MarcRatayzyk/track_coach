<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  LEVEL_OPTIONS,
  SEX_OPTIONS,
  weightCategoriesForSex,
} from '../config/ipfWeightCategories';
import { track } from '../utils/analytics';

const { t } = useI18n();

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

const athleteSteps = computed(() => [
    { id: 'welcome', title: t('auth.accountSetup.steps.welcome') },
    { id: 'practice', title: t('auth.accountSetup.steps.practice') },
    { id: 'prs', title: t('auth.accountSetup.steps.prs') },
    { id: 'profile', title: t('auth.accountSetup.steps.profile') },
    { id: 'goals', title: t('auth.accountSetup.steps.goals') },
]);

const trainingYearOptions = computed(() => [
    { value: 0, label: t('auth.accountSetup.trainingYears.lt1') },
    { value: 1, label: t('auth.accountSetup.trainingYears.y1') },
    { value: 2, label: t('auth.accountSetup.trainingYears.y2') },
    { value: 3, label: t('auth.accountSetup.trainingYears.y3to5') },
    { value: 5, label: t('auth.accountSetup.trainingYears.y5to10') },
    { value: 10, label: t('auth.accountSetup.trainingYears.y10plus') },
]);

const sexOptions = computed(() =>
    SEX_OPTIONS.map((option) => ({
        ...option,
        label: t(`config.sex.${option.value}`),
    })),
);

const levelOptions = computed(() =>
    LEVEL_OPTIONS.map((option) => ({
        ...option,
        label: t(`config.level.${option.value}`),
    })),
);

const form = useForm({
    email: props.user.email ?? '',
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

const needsEmail = computed(() => props.role === 'athlete' && (props.user.needs_email || !props.user.email));

const categoryOptions = computed(() => weightCategoriesForSex(form.sex));

watch(
  () => form.sex,
  (sex, previousSex) => {
    if (sex === previousSex) {
      return;
    }
    const allowed = weightCategoriesForSex(sex).map((item) => item.value);
    if (form.weight_category && !allowed.includes(form.weight_category)) {
      form.weight_category = '';
    }
  },
);

const currentStep = computed(() => athleteSteps.value[stepIndex.value] ?? athleteSteps.value[0]);
const isFirstStep = computed(() => stepIndex.value <= 0);
const isLastStep = computed(() => stepIndex.value >= athleteSteps.value.length - 1);

const choiceBtnClass = (selected) =>
  selected
    ? 'border-blue-500 bg-blue-600 text-white shadow-[0_0_12px_rgba(59,130,246,0.25)]'
    : 'border-slate-700 bg-slate-950/60 text-slate-300 hover:border-slate-500 hover:text-white';

const canGoNext = computed(() => {
    if (currentStep.value.id === 'welcome') {
        const emailOk = !needsEmail.value || form.email.trim().length > 3;
        return emailOk && form.password.length >= 8 && form.password === form.password_confirmation;
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
        onSuccess: () => {
            track('account_setup_completed', { role: props.role });
        },
    });
}

const inputClass =
    'mt-2 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white placeholder:text-slate-600';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-950 via-slate-950 to-blue-950/30 px-4 py-4 text-slate-100 sm:py-8 tc-native-safe-top">
        <Head :title="isCoach ? t('auth.accountSetup.coachPageTitle') : t('auth.accountSetup.athletePageTitle')" />

        <div class="mx-auto w-full max-w-lg">
            <template v-if="isCoach">
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-8 shadow-xl">
                    <h1 class="text-2xl font-bold text-white">{{ t('auth.accountSetup.coachTitle') }}</h1>
                    <p class="mt-2 text-slate-400">
                        {{ t('auth.accountSetup.coachHello', { name: user.name }) }}
                    </p>
                    <p class="mt-3 text-sm text-slate-500">{{ t('auth.accountSetup.emailLabel', { email: user.email }) }}</p>

                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.password') }}
                            <input v-model="form.password" type="password" required autocomplete="new-password" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.confirmation') }}
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                required
                                autocomplete="new-password"
                                :class="inputClass"
                            />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.bio') }}
                            <textarea v-model="form.bio" rows="3" :class="inputClass" :placeholder="t('auth.accountSetup.bioPlaceholder')" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.yearsExperience') }}
                            <input v-model.number="form.years_experience" type="number" min="0" max="60" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.certifications') }}
                            <textarea v-model="form.certifications" rows="2" :class="inputClass" />
                        </label>
                        <label class="block text-sm font-medium text-slate-400">
                            {{ t('auth.accountSetup.clubGym') }}
                            <input v-model="form.club_gym" type="text" :class="inputClass" />
                        </label>
                        <p v-if="form.errors.password" class="text-sm text-red-400">{{ form.errors.password }}</p>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-blue-600 py-3 font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                        >
                            {{ t('auth.accountSetup.activateAccount') }}
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
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-400">{{ t('auth.accountSetup.onboarding') }}</p>
                    <h1 class="mt-2 text-2xl font-bold text-white">{{ currentStep.title }}</h1>
                    <p class="mt-2 text-sm text-slate-400">
                        {{ t('auth.accountSetup.hello', { name: user.name }) }}
                        <template v-if="currentStep.id === 'welcome'">
                            {{ t('auth.accountSetup.welcomeHint') }}
                        </template>
                    </p>

                    <div class="mt-6 space-y-4">
                        <template v-if="currentStep.id === 'welcome'">
                            <label v-if="needsEmail" class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.emailLoginLabel') }}
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    autocomplete="email"
                                    :class="inputClass"
                                    :placeholder="t('auth.accountSetup.emailPlaceholder')"
                                />
                            </label>
                            <p v-else class="text-sm text-slate-500">{{ t('auth.accountSetup.emailLoginHint', { email: user.email }) }}</p>
                            <p v-if="form.errors.email" class="text-sm text-red-400">{{ form.errors.email }}</p>
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.password') }}
                                <input v-model="form.password" type="password" required autocomplete="new-password" :class="inputClass" />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.confirmation') }}
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
                            <p class="text-sm text-slate-400">{{ t('auth.accountSetup.practiceQuestion') }}</p>
                            <div class="grid grid-cols-2 gap-2.5">
                                <button
                                    v-for="option in trainingYearOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                                    :class="choiceBtnClass(form.years_training === option.value)"
                                    @click="form.years_training = option.value"
                                >
                                    {{ option.label }}
                                </button>
                            </div>
                        </template>

                        <template v-else-if="currentStep.id === 'prs'">
                            <p class="text-sm text-slate-400">
                                {{ t('auth.accountSetup.prsHint') }}
                            </p>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="block text-xs font-medium text-slate-400">
                                    {{ t('auth.accountSetup.squatKg') }}
                                    <input v-model="form.squat" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                                <label class="block text-xs font-medium text-slate-400">
                                    {{ t('auth.accountSetup.benchKg') }}
                                    <input v-model="form.bench" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                                <label class="block text-xs font-medium text-slate-400">
                                    {{ t('auth.accountSetup.deadliftKg') }}
                                    <input v-model="form.deadlift" type="number" min="0" max="999" inputmode="numeric" :class="inputClass" />
                                </label>
                            </div>
                        </template>

                        <template v-else-if="currentStep.id === 'profile'">
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.birthDate') }}
                                <input v-model="form.birth_date" type="date" :class="inputClass" />
                            </label>
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.heightCm') }}
                                <input
                                    v-model.number="form.height_cm"
                                    type="number"
                                    min="100"
                                    max="250"
                                    :class="inputClass"
                                />
                            </label>
                            <div>
                                <p class="text-sm font-medium text-slate-400">{{ t('auth.accountSetup.sex') }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-2.5">
                                    <button
                                        v-for="option in sexOptions"
                                        :key="option.value"
                                        type="button"
                                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                                        :class="choiceBtnClass(form.sex === option.value)"
                                        @click="form.sex = option.value"
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                            </div>
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.profession') }}
                                <input
                                    v-model="form.profession"
                                    type="text"
                                    :class="inputClass"
                                    :placeholder="t('auth.accountSetup.professionPlaceholder')"
                                />
                            </label>
                            <div>
                                <p class="text-sm font-medium text-slate-400">{{ t('auth.accountSetup.weightCategory') }}</p>
                                <p v-if="!form.sex" class="mt-1 text-xs text-amber-300/90">
                                    {{ t('auth.accountSetup.chooseSexFirst') }}
                                </p>
                                <div
                                    v-else
                                    class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3"
                                >
                                    <button
                                        v-for="option in categoryOptions"
                                        :key="option.value"
                                        type="button"
                                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                                        :class="choiceBtnClass(form.weight_category === option.value)"
                                        @click="form.weight_category = option.value"
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-slate-400">{{ t('auth.accountSetup.level') }}</p>
                                <div class="mt-2 grid grid-cols-2 gap-2.5">
                                    <button
                                        v-for="option in levelOptions"
                                        :key="option.value"
                                        type="button"
                                        class="rounded-xl border px-3 py-3 text-sm font-semibold transition"
                                        :class="choiceBtnClass(form.level === option.value)"
                                        @click="form.level = option.value"
                                    >
                                        {{ option.label }}
                                    </button>
                                </div>
                            </div>
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.injuries') }}
                                <textarea
                                    v-model="form.injuries_notes"
                                    rows="2"
                                    :class="inputClass"
                                    :placeholder="t('auth.accountSetup.injuriesPlaceholder')"
                                />
                            </label>
                        </template>

                        <template v-else-if="currentStep.id === 'goals'">
                            <label class="block text-sm font-medium text-slate-400">
                                {{ t('auth.accountSetup.goalsLabel') }}
                                <textarea
                                    v-model="form.bio"
                                    rows="4"
                                    :class="inputClass"
                                    :placeholder="t('auth.accountSetup.goalsPlaceholder')"
                                />
                            </label>
                            <p class="text-xs text-slate-500">
                                {{ t('auth.accountSetup.goalsHint') }}
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
                            {{ t('auth.accountSetup.back') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
                            :disabled="form.processing || !canGoNext"
                            @click="nextStep"
                        >
                            {{ isLastStep ? (form.processing ? t('auth.accountSetup.activating') : t('auth.accountSetup.finish')) : t('auth.accountSetup.next') }}
                        </button>
                    </div>
                </div>
            </template>

            <p class="mt-6 text-center text-sm text-slate-500">
                <a href="/login" class="text-blue-400 hover:text-blue-300">{{ t('auth.accountSetup.backToLogin') }}</a>
            </p>
        </div>
    </div>
</template>
