<script setup>
import { useI18n } from 'vue-i18n';
import { computed } from 'vue';

const { t } = useI18n();

const props = defineProps({
  file: {
    type: Object,
    required: true,
  },
  compact: {
    type: Boolean,
    default: false,
  },
});

const kind = computed(() => {
  const mime = props.file?.mime_type ?? '';
  const name = (props.file?.original_name ?? '').toLowerCase();
  if (props.file?.kind === 'audio' || mime.startsWith('audio/')) {
    return 'audio';
  }
  if (mime.startsWith('video/') || name.endsWith('.mp4') || name.endsWith('.webm')) {
    return 'video';
  }
  if (mime.includes('pdf') || name.endsWith('.pdf')) {
    return 'pdf';
  }
  if (mime.startsWith('image/')) {
    return 'image';
  }
  return 'file';
});

const label = computed(() => props.file?.original_name || t('app.messaging.file'));
</script>

<template>
  <div
    class="overflow-hidden rounded-[14px] border border-slate-700/80 bg-slate-950/50"
    :class="compact ? 'max-w-[12rem]' : 'max-w-sm'"
  >
    <audio v-if="kind === 'audio'" :src="file.url" controls class="w-full max-w-sm" />

    <a
      v-else-if="kind === 'video'"
      :href="file.url"
      target="_blank"
      rel="noopener"
      class="flex items-center gap-3 p-3 transition hover:bg-slate-900"
    >
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/20 text-blue-300">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z"
          />
        </svg>
      </div>
      <div class="min-w-0">
        <p class="truncate text-sm font-medium text-white">{{ label }}</p>
        <p class="text-xs text-slate-500">{{ t('app.messaging.videoPreview') }}</p>
      </div>
    </a>

    <a
      v-else-if="kind === 'pdf'"
      :href="file.url"
      target="_blank"
      rel="noopener"
      class="flex items-center gap-3 p-3 transition hover:bg-slate-900"
    >
      <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-800 text-slate-300">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"
          />
        </svg>
      </div>
      <div class="min-w-0">
        <p class="truncate text-sm font-medium text-white">{{ label }}</p>
        <p class="text-xs text-slate-500">{{ t('app.messaging.pdfDocument') }}</p>
      </div>
    </a>

    <a
      v-else-if="kind === 'image'"
      :href="file.url"
      target="_blank"
      rel="noopener"
      class="block"
    >
      <img :src="file.url" :alt="label" class="max-h-48 w-full object-cover" />
    </a>

    <a
      v-else
      :href="file.url"
      target="_blank"
      rel="noopener"
      class="flex items-center gap-3 p-3 text-sm text-slate-300 transition hover:bg-slate-900"
    >
      <span class="truncate">{{ label }}</span>
    </a>
  </div>
</template>
