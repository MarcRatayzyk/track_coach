<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import UiIcon from './UiIcon.vue';

const content = defineModel({ type: String, default: '' });

const props = defineProps({
  placeholder: {
    type: String,
    default: 'Écrire un message…',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  processing: {
    type: Boolean,
    default: false,
  },
  audioFiles: {
    type: Array,
    default: () => [],
  },
  allowVoice: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['submit', 'recorded', 'remove-audio']);

const textareaRef = ref(null);
const isRecording = ref(false);
const recordError = ref('');
let mediaRecorder = null;
let chunks = [];

const canSend = computed(() => {
  const hasText = Boolean(content.value?.trim());
  const hasAudio = props.audioFiles.length > 0;
  return (hasText || hasAudio) && !props.processing && !props.disabled;
});

function resizeTextarea() {
  const el = textareaRef.value;
  if (!el) {
    return;
  }
  el.style.height = 'auto';
  el.style.height = `${Math.min(el.scrollHeight, 160)}px`;
}

function submit() {
  if (!canSend.value) {
    return;
  }
  emit('submit');
}

function onKeydown(event) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault();
    submit();
  }
}

async function toggleRecording() {
  if (isRecording.value) {
    stopRecording();
    return;
  }
  await startRecording();
}

async function startRecording() {
  recordError.value = '';
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
      const extension = blob.type.includes('ogg') ? 'ogg' : 'webm';
      const file = new File([blob], `vocal-${Date.now()}.${extension}`, { type: blob.type });
      emit('recorded', file);
    };
    mediaRecorder.start();
    isRecording.value = true;
  } catch {
    recordError.value = 'Microphone inaccessible.';
  }
}

function stopRecording() {
  if (mediaRecorder && isRecording.value) {
    mediaRecorder.stop();
    isRecording.value = false;
  }
}

watch(
  () => content.value,
  () => {
    nextTick(resizeTextarea);
  },
);

onMounted(() => {
  nextTick(resizeTextarea);
});

onBeforeUnmount(() => {
  stopRecording();
});
</script>

<template>
  <div class="space-y-2">
    <div
      class="flex items-end gap-2 rounded-xl border border-slate-700 bg-slate-950 px-3 py-2 shadow-inner focus-within:border-blue-500/60 focus-within:ring-1 focus-within:ring-blue-500/30"
    >
      <textarea
        ref="textareaRef"
        v-model="content"
        rows="1"
        :disabled="disabled || processing"
        :placeholder="placeholder"
        class="max-h-40 min-h-[2.75rem] flex-1 resize-none bg-transparent py-2 text-base leading-relaxed text-white placeholder:text-slate-600 focus:outline-none disabled:opacity-50"
        @input="resizeTextarea"
        @keydown="onKeydown"
      />

      <div class="flex shrink-0 items-center gap-1.5 pb-1">
        <button
          v-if="allowVoice"
          type="button"
          class="rounded-full p-2 transition"
          :class="
            isRecording
              ? 'bg-rose-600 text-white animate-pulse'
              : 'text-slate-400 hover:bg-slate-800 hover:text-white'
          "
          :aria-label="isRecording ? 'Arrêter l’enregistrement' : 'Enregistrer un message vocal'"
          :disabled="disabled || processing"
          @click="toggleRecording"
        >
          <UiIcon name="mic" class="h-5 w-5" />
        </button>

        <button
          type="button"
          class="rounded-full bg-blue-600 p-2.5 text-white transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-40"
          aria-label="Envoyer"
          :disabled="!canSend"
          @click="submit"
        >
          <UiIcon name="paper-plane" class="h-5 w-5" />
        </button>
      </div>
    </div>

    <p v-if="isRecording" class="text-xs font-medium text-rose-400">
      Enregistrement en cours… Appuie sur le micro pour arrêter.
    </p>
    <p v-if="recordError" class="text-xs text-red-400">{{ recordError }}</p>

    <ul v-if="audioFiles.length" class="flex flex-wrap gap-2">
      <li
        v-for="(file, index) in audioFiles"
        :key="`${file.name}-${index}`"
        class="inline-flex items-center gap-2 rounded-full border border-slate-700 bg-slate-900 px-3 py-1 text-xs text-slate-300"
      >
        <UiIcon name="mic" class="h-3.5 w-3.5 text-rose-400" />
        <span class="max-w-[10rem] truncate">{{ file.name }}</span>
        <button
          type="button"
          class="text-slate-500 hover:text-red-400"
          aria-label="Retirer l’audio"
          @click="emit('remove-audio', index)"
        >
          ×
        </button>
      </li>
    </ul>
  </div>
</template>
