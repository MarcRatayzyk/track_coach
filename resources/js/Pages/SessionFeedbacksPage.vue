<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef, watch } from 'vue';
import { Capacitor } from '@capacitor/core';
import { FilePicker } from '@capawesome/capacitor-file-picker';
import { formatCalendarFr } from '../utils/formatDates';
import { cleanupSource, compressVideo, formatMb, resolveUploadBlob } from '../utils/compressVideo';
import VideoAnnotator from '../Components/VideoAnnotator.vue';

const props = defineProps({
  role: { type: String, default: 'athlete' },
  filter: { type: String, default: 'all' },
  feedbacks: { type: Array, default: () => [] },
  activeFeedback: { type: Object, default: null },
  eligibleSessions: { type: Array, default: () => [] },
  feedbackFrequency: { type: String, default: 'weekly' },
  uploadLimits: {
    type: Object,
    default: () => ({ maxFiles: 3, maxFileBytes: 100 * 1024 * 1024, driver: 'local' }),
  },
});

const isCoach = computed(() => props.role === 'coach');
const isWeekly = computed(() => props.feedbackFrequency === 'weekly');
const usesDirectUpload = computed(() => props.uploadLimits?.driver === 's3');
const isNative = Capacitor.isNativePlatform();
const showSubmitForm = ref(false);
// selectedVideos : liste de VideoSource ({ name, size, type, file? | path? }).
const selectedVideos = ref([]);
const isCompressing = ref(false);
const compressionSummary = ref('');
const videoInputRef = useTemplateRef('videoInput');
// Barre de progression unifiée (compression + upload) sur 0..100.
const pipelineProgress = ref(0);
const uploadStatus = ref('');
const isUploading = ref(false);
const MAX_VIDEOS = computed(() => props.uploadLimits?.maxFiles ?? 3);
const MAX_VIDEO_BYTES = computed(() => props.uploadLimits?.maxFileBytes ?? 100 * 1024 * 1024);
const ALLOWED_VIDEO_MIME_TYPES = new Set([
  'video/mp4',
  'video/webm',
  'video/quicktime',
  'video/x-msvideo',
  'video/3gpp',
  'video/3gpp2',
  'video/x-matroska',
  'video/x-m4v',
]);

const submitForm = useForm({
  session_date: props.eligibleSessions[0]?.session_date ?? '',
  athlete_notes: '',
  videos: [],
  video_upload_ids: [],
});

const filterOptions = [
  { value: 'pending', label: 'En attente' },
  { value: 'all', label: 'Tous' },
];

const athleteDescription = computed(() => {
  if (isWeekly.value) {
    return 'Envoyez un message et, si besoin, une vidéo pour votre retour hebdomadaire.';
  }
  return 'Envoyez un message et, si besoin, une vidéo pour chaque séance programme réalisée.';
});

const maxVideoMbLabel = computed(() =>
  Math.max(1, Math.floor(MAX_VIDEO_BYTES.value / (1024 * 1024))),
);

const progressPercent = computed(() => pipelineProgress.value);

const showProgressBar = computed(
  () =>
    isCompressing.value ||
    isUploading.value ||
    progressPercent.value > 0 ||
    Boolean(uploadStatus.value && (submitForm.processing || isUploading.value || isCompressing.value)),
);

const statusLine = computed(() => {
  if (isCompressing.value) {
    return uploadStatus.value || 'Compression en cours…';
  }
  return uploadStatus.value;
});

const submitBusy = computed(
  () => submitForm.processing || isUploading.value || isCompressing.value,
);

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

function messagingReplyUrl(feedback) {
  const threadId = feedback.coach_thread_id;
  if (threadId) {
    return `/messaging?thread=${threadId}&feedback=${feedback.id}`;
  }
  return `/messaging?athlete=${feedback.athlete_id}&feedback=${feedback.id}`;
}

function selectFeedback(id) {
  router.get(feedbackUrl(id), {}, { preserveState: true, preserveScroll: true });
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function isAllowedVideo(source) {
  if (!source.type) {
    return /\.(mp4|mov|webm|m4v|3gp|3gpp|mkv|avi)$/i.test(source.name || '');
  }
  return ALLOWED_VIDEO_MIME_TYPES.has(source.type);
}

function applySelectedVideos(sources) {
  const errors = [];
  if (sources.length > MAX_VIDEOS.value) {
    errors.push(`Vous pouvez envoyer au maximum ${MAX_VIDEOS.value} vidéos.`);
  }
  if (sources.some((s) => !isAllowedVideo(s))) {
    errors.push('Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).');
  }
  if (sources.some((s) => (s.size ?? 0) > MAX_VIDEO_BYTES.value)) {
    errors.push(`Chaque vidéo ne doit pas dépasser ${maxVideoMbLabel.value} Mo.`);
  }

  if (errors.length) {
    submitForm.setError('videos', errors[0]);
    resetSelectionState();
    return;
  }

  submitForm.clearErrors('videos');
  submitForm.clearErrors('video_upload_ids');
  selectedVideos.value = sources;
  compressionSummary.value = '';
  pipelineProgress.value = 0;
  uploadStatus.value = '';
}

// Web / PWA : input HTML classique -> on transporte le File tel quel.
function onVideoChange(event) {
  const sources = Array.from(event.target.files ?? []).map((f) => ({
    name: f.name,
    size: f.size,
    type: f.type,
    file: f,
  }));
  applySelectedVideos(sources);
}

// Natif : picker qui renvoie un chemin de fichier (aucun chargement mémoire).
async function pickNativeVideos() {
  try {
    const result = await FilePicker.pickVideos({ readData: false });
    const sources = (result?.files ?? []).map((f) => ({
      name: f.name || 'video.mp4',
      size: f.size ?? 0,
      type: f.mimeType || 'video/mp4',
      path: f.path,
    }));
    if (sources.length) {
      applySelectedVideos(sources);
    }
  } catch (error) {
    const message = error?.message || '';
    // Annulation par l'utilisateur -> on ignore silencieusement.
    if (!/cancel/i.test(message)) {
      submitForm.setError('videos', message || 'Sélection vidéo impossible.');
    }
  }
}

function resetSelectionState() {
  selectedVideos.value = [];
  compressionSummary.value = '';
  pipelineProgress.value = 0;
  uploadStatus.value = '';
  if (videoInputRef.value) {
    videoInputRef.value.value = '';
  }
}

function clearSelectedVideos() {
  resetSelectionState();
  isCompressing.value = false;
  isUploading.value = false;
  submitForm.clearErrors('videos');
  submitForm.clearErrors('video_upload_ids');
}

async function jsonRequest(url, method, body = null) {
  const response = await fetch(url, {
    method,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
    body: body ? JSON.stringify(body) : null,
  });

  const data = await response.json().catch(() => ({}));
  if (!response.ok) {
    const message =
      data?.message ||
      data?.errors?.video?.[0] ||
      data?.errors?.mime_type?.[0] ||
      data?.errors?.video_upload_ids?.[0] ||
      'Erreur lors de l’envoi de la vidéo.';
    throw new Error(message);
  }

  return data;
}

function putFileToSignedUrl(url, blob, contentType, onProgress) {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('PUT', url, true);

    // Uniquement Content-Type : d’autres headers déclenchent un preflight CORS
    // que R2 refuse souvent si AllowedHeaders vaut "*".
    xhr.setRequestHeader('Content-Type', contentType || blob.type || 'application/octet-stream');

    xhr.upload.onprogress = (event) => {
      if (event.lengthComputable && typeof onProgress === 'function') {
        onProgress(event.loaded / event.total);
      }
    };

    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) {
        resolve();
        return;
      }
      reject(
        new Error(
          xhr.status === 0
            ? 'Échec CORS ou réseau vers le stockage. Vérifiez la config CORS du bucket R2.'
            : `Échec de l’upload vers le stockage (HTTP ${xhr.status}).`,
        ),
      );
    };

    xhr.onerror = () => {
      reject(new Error('Échec CORS ou réseau vers le stockage. Vérifiez la config CORS du bucket R2.'));
    };

    xhr.send(blob);
  });
}

// Position de la barre unifiée : chaque vidéo occupe une tranche de 1/total,
// répartie 50 % compression + 50 % upload.
function setPipelineProgress(index, total, fractionWithinFile) {
  pipelineProgress.value = Math.min(
    100,
    Math.round(((index + fractionWithinFile) / total) * 100),
  );
}

// Pipeline : pour chaque vidéo on compresse PUIS on l'uploade immédiatement,
// au lieu de tout compresser puis tout uploader.
async function uploadVideosDirectly(sources) {
  const ids = [];
  const total = sources.length;
  const summaries = [];

  for (let index = 0; index < total; index += 1) {
    const original = sources[index];

    isCompressing.value = true;
    isUploading.value = false;
    uploadStatus.value = `Compression ${index + 1}/${total}…`;
    const result = await compressVideo(original, {
      onProgress: (ratio) => setPipelineProgress(index, total, ratio * 0.5),
    });
    if (result.compressed) {
      summaries.push(`${formatMb(result.originalBytes)} → ${formatMb(result.outputBytes)}`);
    }
    const prepared = result.source;

    isCompressing.value = false;
    isUploading.value = true;
    uploadStatus.value = `Préparation ${index + 1}/${total}…`;
    const presign = await jsonRequest('/feedbacks/video-uploads', 'POST', {
      filename: prepared.name,
      mime_type: prepared.type || 'video/mp4',
      size_bytes: prepared.size,
    });

    const blob = await resolveUploadBlob(prepared);
    uploadStatus.value = `Envoi ${index + 1}/${total}…`;
    await putFileToSignedUrl(
      presign.upload_url,
      blob,
      prepared.type || 'video/mp4',
      (ratio) => setPipelineProgress(index, total, 0.5 + ratio * 0.5),
    );

    uploadStatus.value = `Finalisation ${index + 1}/${total}…`;
    await jsonRequest(`/feedbacks/video-uploads/${presign.id}/complete`, 'POST');
    ids.push(presign.id);

    await cleanupSource(prepared);
  }

  compressionSummary.value = summaries.length ? `Compressé : ${summaries.join(' · ')}` : '';
  pipelineProgress.value = 100;
  uploadStatus.value = 'Vidéos envoyées.';
  return ids;
}

// Mode local (pas de R2) : on prépare des File réels pour l'upload multipart Inertia.
async function prepareLocalFiles(sources) {
  const files = [];
  const total = sources.length;
  const summaries = [];

  for (let index = 0; index < total; index += 1) {
    isCompressing.value = true;
    uploadStatus.value = `Compression ${index + 1}/${total}…`;
    const result = await compressVideo(sources[index], {
      onProgress: (ratio) => setPipelineProgress(index, total, ratio),
    });
    if (result.compressed) {
      summaries.push(`${formatMb(result.originalBytes)} → ${formatMb(result.outputBytes)}`);
    }
    const prepared = result.source;
    const blob = await resolveUploadBlob(prepared);
    files.push(new File([blob], prepared.name, { type: prepared.type || 'video/mp4' }));
    await cleanupSource(prepared);
  }

  isCompressing.value = false;
  compressionSummary.value = summaries.length ? `Compressé : ${summaries.join(' · ')}` : '';
  return files;
}

async function submitFeedback() {
  const notes = submitForm.athlete_notes?.trim() ?? '';
  if (!submitForm.session_date) {
    submitForm.setError('session_date', 'Choisissez une séance.');
    return;
  }
  if (!notes && selectedVideos.value.length === 0) {
    submitForm.setError('athlete_notes', 'Ajoutez un message ou au moins une vidéo.');
    return;
  }

  submitForm.clearErrors();

  const hasVideos = selectedVideos.value.length > 0;

  if (!usesDirectUpload.value) {
    let filesToSend = [];
    try {
      isUploading.value = true;
      pipelineProgress.value = 0;
      if (hasVideos) {
        filesToSend = await prepareLocalFiles(selectedVideos.value);
      }
    } catch (error) {
      isUploading.value = false;
      isCompressing.value = false;
      submitForm.setError('videos', error?.message || 'Échec de la préparation des vidéos.');
      return;
    }

    uploadStatus.value = filesToSend.length
      ? 'Envoi en cours (cela peut prendre une minute)…'
      : 'Envoi en cours…';
    submitForm.videos = filesToSend;
    submitForm.video_upload_ids = [];
    submitForm.post('/feedbacks', {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        submitForm.reset();
        clearSelectedVideos();
        showSubmitForm.value = false;
        uploadStatus.value = '';
      },
      onError: (errors) => {
        const first =
          errors?.videos ||
          errors?.athlete_notes ||
          errors?.session_date ||
          Object.values(errors || {})[0];
        if (first && !errors?.videos) {
          submitForm.setError('videos', Array.isArray(first) ? first[0] : String(first));
        }
        uploadStatus.value = '';
      },
      onFinish: () => {
        isUploading.value = false;
        if (uploadStatus.value.startsWith('Envoi')) {
          uploadStatus.value = '';
        }
      },
    });
    return;
  }

  isUploading.value = true;
  pipelineProgress.value = 0;

  try {
    let videoUploadIds = [];
    if (hasVideos) {
      videoUploadIds = await uploadVideosDirectly(selectedVideos.value);
    }

    submitForm.videos = [];
    submitForm.video_upload_ids = videoUploadIds;

    await new Promise((resolve, reject) => {
      submitForm.post('/feedbacks', {
        preserveScroll: true,
        onSuccess: () => {
          submitForm.reset();
          clearSelectedVideos();
          showSubmitForm.value = false;
          resolve();
        },
        onError: (errors) => {
          const first =
            errors?.video_upload_ids ||
            errors?.videos ||
            errors?.athlete_notes ||
            errors?.session_date ||
            Object.values(errors || {})[0];
          if (first) {
            submitForm.setError(
              'videos',
              Array.isArray(first) ? first[0] : String(first),
            );
          }
          reject(new Error('validation'));
        },
        onFinish: () => {
          isUploading.value = false;
        },
      });
    });
  } catch (error) {
    if (error?.message && error.message !== 'validation') {
      submitForm.setError('videos', error.message);
    }
    isUploading.value = false;
    isCompressing.value = false;
  }
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
            Consultez les vidéos et commentaires des athlètes, puis répondez via la messagerie.
          </template>
          <template v-else>
            {{ athleteDescription }}
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
        {{ showSubmitForm ? 'Annuler' : isWeekly ? 'Nouveau retour hebdo' : 'Nouveau retour' }}
      </button>
    </div>

    <div
      v-if="!isCoach && showSubmitForm"
      class="mt-3 rounded-2xl border border-blue-500/30 bg-slate-900/60 p-4 shadow-xl lg:mt-4 lg:p-6"
    >
      <h2 class="text-base font-semibold text-white">
        {{ isWeekly ? 'Envoyer votre retour hebdomadaire' : 'Envoyer un retour' }}
      </h2>
      <form class="mt-4 space-y-4" @submit.prevent="submitFeedback">
        <div>
          <label class="block text-sm font-medium text-slate-300">
            {{ isWeekly ? 'Semaine / séance' : 'Séance' }}
          </label>
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
              {{ isWeekly ? s.session_label : `${formatCalendarFr(s.session_date)} — ${s.session_label}` }}
            </option>
          </select>
          <p v-if="submitForm.errors.session_date" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.session_date }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">Message</label>
          <textarea
            v-model="submitForm.athlete_notes"
            rows="4"
            placeholder="Comment s’est passée la séance ?"
            class="mt-1 w-full rounded-xl border border-slate-700 bg-slate-950 px-4 py-3 text-white placeholder:text-slate-600"
          />
          <p v-if="submitForm.errors.athlete_notes" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.athlete_notes }}
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-300">
            Vidéos (optionnel, 1 à {{ MAX_VIDEOS }}, max {{ maxVideoMbLabel }} Mo)
          </label>
          <button
            v-if="isNative"
            type="button"
            :disabled="submitBusy"
            class="mt-1 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
            @click="pickNativeVideos"
          >
            {{ selectedVideos.length ? 'Changer les vidéos' : 'Choisir des vidéos' }}
          </button>
          <input
            v-else
            ref="videoInput"
            type="file"
            accept="video/*"
            multiple
            :disabled="submitBusy"
            class="mt-1 w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-white disabled:opacity-50"
            @change="onVideoChange"
          />
          <p v-if="selectedVideos.length" class="mt-2 text-xs text-slate-500">
            {{ selectedVideos.length }} fichier{{ selectedVideos.length > 1 ? 's' : '' }} sélectionné{{ selectedVideos.length > 1 ? 's' : '' }}
          </p>
          <p v-if="compressionSummary && !isCompressing" class="mt-1 text-xs text-emerald-400/90">
            {{ compressionSummary }}
          </p>
          <p class="mt-2 text-xs text-slate-500">
            <template v-if="isNative">
              Les vidéos sont compressées automatiquement (~720p) pour un envoi plus rapide.
            </template>
            <template v-else>
              La vidéo est envoyée telle quelle : privilégiez un format déjà léger pour un envoi plus rapide.
            </template>
          </p>
          <div v-if="showProgressBar" class="mt-3">
            <div class="h-2 overflow-hidden rounded-full bg-slate-800">
              <div
                class="h-full rounded-full bg-blue-500 transition-all duration-200"
                :style="{ width: `${progressPercent}%` }"
              />
            </div>
            <p v-if="statusLine" class="mt-1 text-xs text-slate-400">{{ statusLine }}</p>
          </div>
          <p v-if="!usesDirectUpload" class="mt-2 text-xs text-amber-400/90">
            Mode local : limite PHP ~{{ maxVideoMbLabel }} Mo. Configure R2 (clés AWS_*) pour aller jusqu’à 200 Mo.
          </p>
          <p v-if="submitForm.errors.videos" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.videos }}
          </p>
          <p v-else-if="submitForm.errors.video_upload_ids" class="mt-1 text-sm text-red-400">
            {{ submitForm.errors.video_upload_ids }}
          </p>
        </div>

        <button
          type="submit"
          :disabled="submitBusy"
          class="rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-500 disabled:opacity-50"
        >
          {{
            isCompressing
              ? 'Compression…'
              : isUploading || submitForm.processing
                ? 'Envoi en cours…'
                : 'Envoyer au coach'
          }}
        </button>
      </form>
    </div>

    <div class="mt-3 grid gap-4 lg:mt-6 lg:grid-cols-12 lg:gap-6">
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
                      ? 'bg-emerald-500/20 text-emerald-300'
                      : 'bg-amber-500/20 text-amber-300'
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
        class="min-h-[20rem] rounded-2xl border border-slate-800 bg-slate-900/50 p-4 shadow-xl lg:col-span-8 lg:min-h-[24rem] lg:p-6"
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
            <h3 class="text-sm font-medium text-slate-400">Message athlète</h3>
            <p class="mt-2 whitespace-pre-wrap rounded-lg bg-slate-950/60 p-3 text-slate-200">
              {{ activeFeedback.athlete_notes }}
            </p>
          </div>

          <div v-if="activeFeedback.videos?.length" class="mt-6 space-y-4">
            <h3 class="text-sm font-medium text-slate-400">Vidéos</h3>
            <VideoAnnotator
              v-for="video in activeFeedback.videos"
              :key="video.id"
              :video="video"
              :readonly="!isCoach"
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

          <div
            v-else-if="isCoach && activeFeedback.status === 'submitted'"
            class="mt-8 border-t border-slate-800 pt-6"
          >
            <Link
              :href="messagingReplyUrl(activeFeedback)"
              class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500"
            >
              Répondre dans la messagerie →
            </Link>
          </div>
        </template>
        <div v-else class="flex h-full min-h-[20rem] items-center justify-center text-slate-500">
          Sélectionnez un retour dans la liste.
        </div>
      </section>
    </div>
  </div>
</template>
