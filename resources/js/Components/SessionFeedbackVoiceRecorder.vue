<script setup>
import { onBeforeUnmount, ref } from 'vue';

const emit = defineEmits(['recorded', 'removed']);

const isRecording = ref(false);
const recordedBlob = ref(null);
const recordedUrl = ref(null);
const errorMessage = ref('');
let mediaRecorder = null;
let chunks = [];

function cleanupUrl() {
  if (recordedUrl.value) {
    URL.revokeObjectURL(recordedUrl.value);
    recordedUrl.value = null;
  }
}

function removeRecording() {
  cleanupUrl();
  recordedBlob.value = null;
  errorMessage.value = '';
  emit('removed');
}

async function startRecording() {
  errorMessage.value = '';
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
    chunks = [];
    mediaRecorder = new MediaRecorder(stream);
    mediaRecorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        chunks.push(event.data);
      }
    };
    mediaRecorder.onstop = () => {
      stream.getTracks().forEach((track) => track.stop());
      const blob = new Blob(chunks, { type: mediaRecorder.mimeType || 'audio/webm' });
      cleanupUrl();
      recordedBlob.value = blob;
      recordedUrl.value = URL.createObjectURL(blob);
      const extension = blob.type.includes('ogg') ? 'ogg' : 'webm';
      const file = new File([blob], `vocal-${Date.now()}.${extension}`, { type: blob.type });
      emit('recorded', file);
    };
    mediaRecorder.start();
    isRecording.value = true;
  } catch {
    errorMessage.value = 'Microphone inaccessible. Utilisez l’import de fichier audio.';
  }
}

function stopRecording() {
  if (mediaRecorder && isRecording.value) {
    mediaRecorder.stop();
    isRecording.value = false;
  }
}

onBeforeUnmount(() => {
  stopRecording();
  cleanupUrl();
});

defineExpose({
  getBlob: () => recordedBlob.value,
  clear: removeRecording,
});
</script>

<template>
  <div class="rounded-xl border border-slate-700 bg-slate-950/50 p-4">
    <p class="text-sm font-medium text-white">Message vocal</p>
    <p class="mt-1 text-xs text-slate-500">Enregistrez ou importez un fichier audio.</p>

    <div class="mt-3 flex flex-wrap gap-2">
      <button
        v-if="!isRecording"
        type="button"
        class="rounded-lg bg-rose-600 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-500"
        @click="startRecording"
      >
        Enregistrer
      </button>
      <button
        v-else
        type="button"
        class="rounded-lg bg-slate-600 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-500"
        @click="stopRecording"
      >
        Arrêter
      </button>
      <button
        v-if="recordedUrl"
        type="button"
        class="rounded-lg border border-slate-600 px-3 py-2 text-sm text-slate-300 hover:bg-slate-800"
        @click="removeRecording"
      >
        Supprimer
      </button>
    </div>

    <audio v-if="recordedUrl" :src="recordedUrl" controls class="mt-3 w-full" />

    <p v-if="errorMessage" class="mt-2 text-sm text-red-400">{{ errorMessage }}</p>
  </div>
</template>
