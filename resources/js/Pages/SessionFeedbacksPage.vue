<script>
import AppLayout from '../Layouts/AppLayout.vue';

export default {
  layout: AppLayout,
};
</script>

<script setup>
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef, watch } from 'vue';
import { Capacitor } from '@capacitor/core';
import { FilePicker } from '@capawesome/capacitor-file-picker';
import { formatCalendarFr } from '../utils/formatDates';
import { cleanupSource, compressVideo, formatMb, resolveUploadBlob } from '../utils/compressVideo';
import VideoFeedbackSlider from '../Components/VideoFeedbackSlider.vue';
import SeriesComparisonCard from '../Components/SeriesComparisonCard.vue';
import SeriesPickerModal from '../Components/SeriesPickerModal.vue';
import VideoTrimModal from '../Components/VideoTrimModal.vue';
import { track } from '../utils/analytics';
import ReviewsWorkspace from '../Components/Feedbacks/ReviewsWorkspace.vue';
import AthleteReviewsWorkspace from '../Components/Feedbacks/AthleteReviewsWorkspace.vue';

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
  metrics: { type: Object, default: null },
});

const isCoach = computed(() => props.role === 'coach');
const isWeekly = computed(() => props.feedbackFrequency === 'weekly');
const usesDirectUpload = computed(() => props.uploadLimits?.driver === 's3');
const isNative = Capacitor.isNativePlatform();
const athleteTab = ref(props.activeFeedback ? 'history' : 'submit');
// selectedVideos : liste de VideoSource ({ name, size, type, file? | path? }).
const selectedVideos = ref([]);
const isCompressing = ref(false);
const compressionSummary = ref('');
const trimSummary = ref('');
const trimQueueIndex = ref(null);
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
  video_series: [],
});

// videoSeries[i] = id d'exercice choisi pour la vidéo i ('' = aucune série).
const videoSeries = ref([]);
const seriesPickerIndex = ref(null);

const selectedSession = computed(() =>
  props.eligibleSessions.find((s) => s.session_date === submitForm.session_date) ?? null,
);

const sessionExercises = computed(() => selectedSession.value?.exercises ?? []);

const selectedSessionLoggedNotes = computed(() => selectedSession.value?.logged_notes ?? []);

const seriesPickerOpen = computed({
  get: () => seriesPickerIndex.value !== null,
  set: (open) => {
    if (!open) {
      seriesPickerIndex.value = null;
    }
  },
});

const trimModalOpen = computed(
  () => trimQueueIndex.value !== null && Boolean(selectedVideos.value[trimQueueIndex.value]),
);

const trimModalSource = computed(() => {
  if (trimQueueIndex.value === null) {
    return null;
  }
  return selectedVideos.value[trimQueueIndex.value] ?? null;
});

const seriesPickerValue = computed({
  get: () => {
    if (seriesPickerIndex.value === null) {
      return '';
    }
    return videoSeries.value[seriesPickerIndex.value] ?? '';
  },
  set: (value) => {
    if (seriesPickerIndex.value === null) {
      return;
    }
    videoSeries.value[seriesPickerIndex.value] = value;
  },
});

function openSeriesPicker(index) {
  seriesPickerIndex.value = index;
}

function seriesLabelFor(index) {
  const id = videoSeries.value[index];
  if (id === '' || id === null || id === undefined) {
    return 'Aucune série';
  }
  const exercise = sessionExercises.value.find((item) => Number(item.id) === Number(id));
  if (!exercise) {
    return 'Aucune série';
  }
  return exercise.exercise_name || exercise.label;
}

function seriesSummaryFor(index) {
  const id = videoSeries.value[index];
  if (id === '' || id === null || id === undefined) {
    return 'Optionnel — rattacher à un exercice';
  }
  const exercise = sessionExercises.value.find((item) => Number(item.id) === Number(id));
  if (!exercise) {
    return '';
  }
  const parts = [exercise.section_label, exercise.summary].filter(Boolean);
  return parts.join(' · ');
}

const replyForm = useForm({
  content: '',
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
  () =>
    submitForm.processing ||
    isUploading.value ||
    isCompressing.value ||
    trimQueueIndex.value !== null,
);

function feedbackUrl(id) {
  return `/feedbacks?feedback=${id}`;
}

function selectFeedback(id) {
  replyForm.reset();
  replyForm.clearErrors();
  router.get(feedbackUrl(id), {}, { preserveState: true, preserveScroll: true });
}

function sendReply() {
  if (!props.activeFeedback?.id) {
    return;
  }
  const content = replyForm.content?.trim() ?? '';
  if (!content) {
    replyForm.setError('content', 'Écrivez votre retour avant de l’envoyer.');
    return;
  }

  replyForm
    .transform(() => ({ content }))
    .post(`/feedbacks/${props.activeFeedback.id}/reply`, {
      preserveScroll: true,
      onSuccess: () => {
        track('feedback_replied', { feedback_id: props.activeFeedback.id });
        replyForm.reset();
      },
    });
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
  videoSeries.value = sources.map(() => '');
  compressionSummary.value = '';
  trimSummary.value = '';
  pipelineProgress.value = 0;
  uploadStatus.value = '';
  startTrimQueue();
}

function startTrimQueue() {
  if (!selectedVideos.value.length) {
    trimQueueIndex.value = null;
    return;
  }
  trimQueueIndex.value = 0;
}

function advanceTrimQueue() {
  if (trimQueueIndex.value === null) {
    return;
  }
  const next = trimQueueIndex.value + 1;
  if (next >= selectedVideos.value.length) {
    trimQueueIndex.value = null;
    return;
  }
  trimQueueIndex.value = next;
}

function onTrimConfirm(result) {
  const index = trimQueueIndex.value;
  if (index === null) {
    return;
  }
  if (result?.trimmed && result.source) {
    selectedVideos.value[index] = result.source;
    const parts = trimSummary.value ? trimSummary.value.split(' · ').filter(Boolean) : [];
    parts.push(`${formatMb(result.originalBytes)} → ${formatMb(result.outputBytes)} (rogné)`);
    trimSummary.value = parts.join(' · ');
  }
  advanceTrimQueue();
}

function onTrimUseFull() {
  advanceTrimQueue();
}

function onTrimCancel() {
  trimQueueIndex.value = null;
  clearSelectedVideos();
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
  videoSeries.value = [];
  compressionSummary.value = '';
  trimSummary.value = '';
  trimQueueIndex.value = null;
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
    submitForm.video_series = seriesPayload();
    submitForm.post('/feedbacks', {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        track('feedback_sent', {
          has_video: filesToSend.length > 0,
          upload_driver: 'multipart',
        });
        submitForm.reset();
        clearSelectedVideos();
        athleteTab.value = 'history';
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
    submitForm.video_series = seriesPayload();

    await new Promise((resolve, reject) => {
      submitForm.post('/feedbacks', {
        preserveScroll: true,
        onSuccess: () => {
          track('feedback_sent', {
            has_video: videoUploadIds.length > 0,
            upload_driver: 'direct',
          });
          submitForm.reset();
          clearSelectedVideos();
          athleteTab.value = 'history';
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
  () => props.activeFeedback?.id,
  () => {
    replyForm.reset();
    replyForm.clearErrors();
  },
);

watch(
  () => props.eligibleSessions,
  (sessions) => {
    if (sessions.length && !submitForm.session_date) {
      submitForm.session_date = sessions[0].session_date;
    }
  },
  { immediate: true },
);

// Les exercices dépendent de la séance : on réinitialise les séries choisies quand elle change.
watch(
  () => submitForm.session_date,
  () => {
    videoSeries.value = selectedVideos.value.map(() => '');
  },
);

function seriesPayload() {
  return videoSeries.value.map((id) => (id === '' || id === null ? null : Number(id)));
}
</script>

<template>
  <ReviewsWorkspace
    v-if="isCoach"
    :feedbacks="feedbacks"
    :active-feedback="activeFeedback"
    :metrics="metrics"
  />

  <AthleteReviewsWorkspace
    v-else
    v-model:tab="athleteTab"
    :feedbacks="feedbacks"
    :active-feedback="activeFeedback"
    :can-submit="eligibleSessions.length > 0"
  >
    <template #submit-form>
      <div
        class="rounded-[18px] border border-blue-500/30 bg-slate-900/60 p-4 shadow-xl lg:p-6"
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
            <div
              v-if="selectedSessionLoggedNotes.length"
              class="mt-3 rounded-xl border border-slate-800 bg-slate-950/50 p-3"
            >
              <p class="text-xs font-medium text-slate-400">Notes prises pendant la séance</p>
              <ul class="mt-2 space-y-1.5">
                <li
                  v-for="(entry, index) in selectedSessionLoggedNotes"
                  :key="`${entry.exercise_name}-${index}`"
                  class="text-sm text-slate-200"
                >
                  <span class="font-medium text-slate-100">{{ entry.exercise_name }}</span>
                  <span class="text-slate-500"> — </span>
                  <span class="whitespace-pre-wrap text-slate-300">{{ entry.note }}</span>
                </li>
              </ul>
            </div>
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
            <div
              v-if="selectedVideos.length && sessionExercises.length"
              class="mt-3 space-y-2 rounded-xl border border-slate-800 bg-slate-950/40 p-3"
            >
              <p class="text-xs font-medium text-slate-400">Associez chaque vidéo à une série (optionnel)</p>
              <div v-for="(video, index) in selectedVideos" :key="index" class="flex flex-col gap-1.5">
                <span class="min-w-0 truncate text-xs text-slate-300">{{ video.name }}</span>
                <button
                  type="button"
                  class="flex w-full items-center justify-between gap-3 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2.5 text-left transition hover:border-slate-600 hover:bg-slate-900"
                  @click="openSeriesPicker(index)"
                >
                  <span class="min-w-0">
                    <span class="block truncate text-sm font-medium text-white">{{ seriesLabelFor(index) }}</span>
                    <span class="mt-0.5 block truncate text-xs text-slate-500">{{ seriesSummaryFor(index) }}</span>
                  </span>
                  <span class="shrink-0 text-xs font-medium text-blue-300">Changer</span>
                </button>
              </div>
            </div>
            <p v-if="trimSummary" class="mt-1 text-xs text-sky-400/90">Rogné : {{ trimSummary }}</p>
            <p v-if="compressionSummary && !isCompressing" class="mt-1 text-xs text-emerald-400/90">{{ compressionSummary }}</p>
            <div v-if="showProgressBar" class="mt-3">
              <div class="h-2 overflow-hidden rounded-full bg-slate-800">
                <div class="h-full rounded-full bg-blue-500 transition-all duration-200" :style="{ width: `${progressPercent}%` }" />
              </div>
              <p v-if="statusLine" class="mt-1 text-xs text-slate-400">{{ statusLine }}</p>
            </div>
            <p v-if="submitForm.errors.videos" class="mt-1 text-sm text-red-400">{{ submitForm.errors.videos }}</p>
            <p v-else-if="submitForm.errors.video_upload_ids" class="mt-1 text-sm text-red-400">{{ submitForm.errors.video_upload_ids }}</p>
          </div>

          <button
            type="submit"
            :disabled="submitBusy"
            class="rounded-xl bg-gradient-to-r from-blue-600 to-blue-500 px-6 py-3 text-sm font-semibold text-white shadow-[0_0_24px_rgba(59,130,246,0.25)] hover:from-blue-500 hover:to-blue-400 disabled:opacity-50"
          >
            {{ isCompressing ? 'Compression…' : isUploading || submitForm.processing ? 'Envoi en cours…' : 'Envoyer au coach' }}
          </button>
        </form>
      </div>
    </template>
  </AthleteReviewsWorkspace>

  <SeriesPickerModal
    v-if="!isCoach"
    v-model="seriesPickerValue"
    v-model:open="seriesPickerOpen"
    :exercises="sessionExercises"
    title="Choisir une série"
  />
  <VideoTrimModal
    v-if="!isCoach"
    :open="trimModalOpen"
    :source="trimModalSource"
    :index="trimQueueIndex ?? 0"
    :total="selectedVideos.length"
    @confirm="onTrimConfirm"
    @use-full="onTrimUseFull"
    @cancel="onTrimCancel"
  />
</template>
