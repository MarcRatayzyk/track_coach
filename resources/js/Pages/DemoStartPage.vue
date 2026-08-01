<script>
export default {
    layout: null,
};
</script>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLogo from '../Components/AppLogo.vue';

defineProps({
    demoHours: { type: Number, default: 48 },
});

const form = useForm({
    email: '',
    name: '',
});

function submit() {
    form.post('/demo');
}
</script>

<template>
    <Head title="Essayer la démo" />

    <div class="min-h-screen bg-slate-950 text-slate-200">
        <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col justify-center px-5 py-12">
            <Link href="/" class="mb-10 inline-flex items-center self-start">
                <AppLogo
                    mark-class="h-10 w-10"
                    wordmark-class="text-xl font-bold tracking-tight text-white"
                />
            </Link>

            <h1 class="text-3xl font-black tracking-tight text-white">Démo</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-400">
                Entre ton e-mail pour ouvrir un espace coach jetable rempli de données réalistes.
                Chaque adresse ne peut lancer qu’une seule démo ({{ demoHours }} h).
            </p>

            <form class="mt-8 space-y-4" @submit.prevent="submit">
                <div>
                    <label for="demo-email" class="block text-sm font-medium text-slate-300">E-mail</label>
                    <input
                        id="demo-email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                        class="mt-1 block w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label for="demo-name" class="block text-sm font-medium text-slate-300">
                        Prénom / pseudo <span class="text-slate-500">(optionnel)</span>
                    </label>
                    <input
                        id="demo-name"
                        v-model="form.name"
                        type="text"
                        autocomplete="name"
                        class="mt-1 block w-full rounded-xl border border-slate-700 bg-slate-900 px-3 py-2.5 text-sm text-white focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-400">{{ form.errors.name }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-60"
                >
                    {{ form.processing ? 'Préparation…' : 'Lancer la démo' }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                Déjà un compte ?
                <Link href="/login" class="text-blue-400 hover:underline">Se connecter</Link>
                ·
                <Link href="/start-trial" class="text-blue-400 hover:underline">Essai 14 jours</Link>
            </p>
        </div>
    </div>
</template>
