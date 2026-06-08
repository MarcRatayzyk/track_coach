<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import SessionFeedbackVoiceRecorder from '../Components/SessionFeedbackVoiceRecorder.vue';
import { formatCalendarFr } from '../utils/formatDates';

const props = defineProps({
  role: { type: String, default: 'athlete' },
  filter: { type: String, default: 'all' },
  feedbacks: { type: Array, default: () => [] },
  activeFeedback: { type: Object, default: null },
  eligibleSessions: { type: Array, default: () => [] },
});

const isCoach = computed(() => props.role === 'coach');
const showSubmitForm = ref(false);
const voiceRecorderRef = ref(null);
const recordedAudioFiles = ref([]);

const submitForm = useForm({
  session_date: props.eligibleSessions[0]?.session_date ?? '',
  athlete_notes: '',
  videos: [],
});

const replyForm = useForm({
  body: '',
  audio_files: [],
});

const filterOptions = [
  { value: 'pending', label: 'En attente' },
  { value: 'all', label: 'Tous' },
];

function feedbackUrl(id) {
  return `/feedbacks?feedback=${id}${isCoach.value && props.filter === 'pending' ? '&filter=pending' : ''}`;
}

function filterUrl(value) {
  const params = new URLSearchParams();
  params.set('filter', value);
  if (props.activeFeedback?.id) {
    params.set('feedback', props.activeFeedback.id);
  }
  return `/feedbacks?${params.toString()}`;
}

function selectFeedback(id) {
  router.get(feedbackUrl(id), {}, { preserveState: true, preserveScroll: true });
}

function onVideoChange(event) {
  submitForm.videos = Array.from(event.target.files ?? []);
}

function onAudioImport(event) {
  const files = Array.from(event.target.files ?? []);
  recordedAudioFiles.value = [...recordedAudioFiles.value, ...files];
}

function onVoiceRecorded(file) {
  recordedAudioFiles.value = [...recordedAudioFiles.value, file];
}

function removeAudioFile(index) {
  recordedAudioFiles.value = recordedAudioFiles.value.filter((_, i) => i !== index);
}

function submitFeedback() {
  submitForm.post('/feedbacks', {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      submitForm.reset();
      showSubmitForm.value = false;
    },
  });
}

function submitReply() {
  if (!props.activeFeedback) {
    return;
  }
  replyForm
    .transform((data) => ({
      ...data,
      audio_files: recordedAudioFiles.value,
    }))
    .post(`/feedbacks/${props.activeFeedback.id}/reply`, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        replyForm.reset();
        recordedAudioFiles.value = [];
        voiceRecorderRef.value?.clear?.();
      },
    });
}

function formatSubmitted(iso) {
  if (!iso) {
    return '';
  }
  try {
    return new Date(iso).toLocaleString('fr-FR', {
      day: 'numeric',
      month: 'short',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return iso;
  }
}

watch(
  () => props.eligibleSessions,
  (sessions) => {
    if (sessions.length && !submitForm.session_date) {
      submitForm.session_date = sessions[0].session_date;
    }
  },
  { immediate: true },
);
</script>

<template>
  <div>
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-white">Retours de séance</h1>
        <p class="mt-2 max-w-2xl text-slate-400">
          <template v-if="isCoach">
            Consultez les vidéos et commentaires des athlètes, puis répondez par texte et messages vocaux.
          </template>
          <template v-else>
            Envoyez une vidéo et un commentaire sur la séance programme que vous venez de réaliser.
          </template>
        </p>
      </div>
      <div v-if="isCoach" class="flex gap-2">
        <Link
          v-for="opt in filterOptions"
          :key="opt.value"
          :href="filterUrl(opt.value)"
          preserve-state
          class="rounded-lg border px-3 py-1.5 text-sm font-medium transition"
          :class="
            filter === opt.value
              ? 'border-blue-500/60 bg-blue-600/20 text-white'
              : 'border-slate-700 text-slate-300 hover:bg-slate-800'
          "
        >
          {{ opt.label }}
        </Link>
      </div>
      <button
        v-else-if="eligibleSessions.length"
        type="button"
        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500"
        @click="showSubmitForm = !showSubmitForm"
      >
        {{ showSubmitForm ? 'Annuler' : 'Nouveau retour' }}
      </button>
    </div>

    <div
      v-if="!isCoach && showSubmitForm"
      class="mt-6 rounded-2xl border border-blue-500/30 bg-slate-900/60 p-6 shadow-xl"
    >
      <h2 class="text-base font-semibold text-white">Envoyer un retour</h2>
      <form class="mt-4 space-y-4" @submit.prevent="submitFeedback">
        <div>
          <label class="block text-sm font-medium text-slate-300">Séance</label>
          <select
            v-model="submitForm.session_date"
            required
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
          >
            <option value="" disabled>Choisir une séance</option>
            <option
              v-for="s in eligibleSessions"
              :key="s.session_date"
              :value="s.session_date"
            >
              {{ formatCalendarFr(s.session_date) }} — {{ s.session_label }}
            </option>
          </select>
          <p v-if="submitForm.errors.session_date" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.session_date }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Commentaire</label>
          <textarea
            v-model="submitForm.athlete_notes"
            rows="4"
            placeholder="Comment s’est passée la séance ?"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white placeholder:text-slate-600"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Vidéos (1 à 3)</label>
          <input
            type="file"
            accept="video/*"
            multiple
            required
            class="mt-1 w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-white"
            @change="onVideoChange"
          />
          <p v-if="submitForm.errors.videos" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.videos }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="submitForm.processing"
          class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        >
          Envoyer au coach
        </button>
      </form>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-12">
      <aside class="lg:col-span-4">
        <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-xl">
          <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">
            {{ isCoach ? 'Retours reçus' : 'Historique' }}
          </h2>
          <ul class="mt-3 max-h-[28rem] space-y-2 overflow-y-auto">
            <li v-for="item in feedbacks" :key="item.id">
              <button
                type="button"
                class="w-full rounded-xl border px-3 py-3 text-left transition"
                :class="
                  activeFeedback?.id === item.id
                    ? 'border-blue-500/60 bg-blue-600/20'
                    : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'
                "
                @click="selectFeedback(item.id)"
              >
                <p class="text-sm font-semibold text-white">
                  <template v-if="isCoach">{{ item.athlete_name }}</template>
                  <template v-else>{{ item.session_label }}</template>
                </p>
                <p class="mt-0.5 text-xs text-slate-500">
                  {{ formatCalendarFr(item.session_date) }}
                  · {{ item.video_count }} vidéo(s)
                </p>
                <span
                  class="mt-2 inline-block rounded-full px-2 py-0.5 text-[10px] font-medium"
                  :class="
                    item.status === 'coach_replied'
                      ? 'bg-emerald-950/60 text-emerald-300'
                      : 'bg-amber-950/60 text-amber-300'
                  "
                >
                  {{ item.status === 'coach_replied' ? 'Répondu' : 'En attente' }}
                </span>
              </button>
            </li>
          </ul>
          <p v-if="!feedbacks.length" class="mt-4 text-center text-sm text-slate-500">
            Aucun retour pour le moment.
          </p>
        </div>
      </aside>

      <section
        class="min-h-[24rem] rounded-2xl border border-slate-800 bg-slate-900/50 p-6 shadow-xl lg:col-span-8"
      >
        <template v-if="activeFeedback">
          <div class="border-b border-slate-800 pb-4">
            <h2 class="text-lg font-semibold text-white">
              <template v-if="isCoach">{{ activeFeedback.athlete_name }}</template>
              <template v-else>{{ activeFeedback.session_label }}</template>
            </h2>
            <p class="mt-1 text-sm text-slate-500">
              Séance du {{ formatCalendarFr(activeFeedback.session_date) }}
              · envoyé {{ formatSubmitted(activeFeedback.submitted_at) }}
            </p>
          </div>

          <div v-if="activeFeedback.athlete_notes" class="mt-4">
            <h3 class="text-sm font-medium text-slate-400">Commentaire athlète</h3>
            <p class="mt-2 whitespace-pre-wrap rounded-lg bg-slate-950/60 p-3 text-slate-200">
              {{ activeFeedback.athlete_notes }}
            </p>
          </div>

          <div v-if="activeFeedback.videos?.length" class="mt-6 space-y-4">
            <h3 class="text-sm font-medium text-slate-400">Vidéos</h3>
            <video
              v-for="video in activeFeedback.videos"
              :key="video.id"
              :src="video.url"
              controls
              class="w-full rounded-xl border border-slate-800 bg-black"
            />
          </div>

          <div v-if="activeFeedback.reply" class="mt-8 border-t border-slate-800 pt-6">
            <h3 class="text-sm font-semibold text-emerald-400">Réponse du coach</h3>
            <p
              v-if="activeFeedback.reply.body"
              class="mt-2 whitespace-pre-wrap text-slate-200"
            >
              {{ activeFeedback.reply.body }}
            </p>
            <div v-if="activeFeedback.reply.audio_files?.length" class="mt-4 space-y-3">
              <audio
                v-for="audio in activeFeedback.reply.audio_files"
                :key="audio.id"
                :src="audio.url"
                controls
                class="w-full"
              />
            </div>
            <p class="mt-2 text-xs text-slate-500">
              {{ formatSubmitted(activeFeedback.reply.created_at) }}
            </p>
          </div>

          <form
            v-else-if="isCoach"
            class="mt-8 border-t border-slate-800 pt-6"
            @submit.prevent="submitReply"
          >
            <h3 class="text-sm font-semibold text-white">Votre réponse</h3>
            <textarea
              v-model="replyForm.body"
              rows="4"
              placeholder="Commentaire pour l’athlète…"
              class="mt-3 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white"
            />
            <SessionFeedbackVoiceRecorder
              ref="voiceRecorderRef"
              class="mt-4"
              @recorded="onVoiceRecorded"
              @removed="() => {}"
            />
            <div class="mt-4">
              <label class="block text-sm text-slate-400">Importer des fichiers audio</label>
              <input
                type="file"
                accept="audio/*"
                multiple
                class="mt-1 w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-700 file:px-3 file:py-2 file:text-white"
                @change="onAudioImport"
              />
            </div>
            <ul v-if="recordedAudioFiles.length" class="mt-3 space-y-1 text-sm text-slate-400">
              <li
                v-for="(file, index) in recordedAudioFiles"
                :key="`${file.name}-${index}`"
                class="flex items-center justify-between gap-2"
              >
                <span class="truncate">{{ file.name }}</span>
                <button
                  type="button"
                  class="text-red-400 hover:text-red-300"
                  @click="removeAudioFile(index)"
                >
                  Retirer
                </button>
              </li>
            </ul>
            <p v-if="replyForm.errors.body" class="mt-2 text-sm text-red-400">
              {{ replyForm.errors.body }}
            </p>
            <button
              type="submit"
              :disabled="replyForm.processing"
              class="mt-4 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-50"
            >
              Envoyer la réponse
            </button>
          </form>
        </template>
        <div v-else class="flex h-full min-h-[20rem] items-center justify-center text-slate-500">
          Sélectionnez un retour dans la liste.
        </div>
      </section>
    </div>
  </div>
</template>
