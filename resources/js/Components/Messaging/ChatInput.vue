<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import UiIcon from '../UiIcon.vue';

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
    default: false,
  },
});

const emit = defineEmits(['submit', 'recorded', 'remove-audio']);

const textareaRef = ref(null);
const showEmoji = ref(false);

const emojis = ['👍', '🔥', '💪', '✅', '🙌', '😂', '❤️', '👀', '🎯', '⚡'];

const canSend = computed(() => {
  const hasText = Boolean(content.value?.trim());
  return hasText && !props.processing && !props.disabled;
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

function insertEmoji(emoji) {
  content.value = `${content.value ?? ''}${emoji}`;
  showEmoji.value = false;
  nextTick(resizeTextarea);
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
</script>

<template>
  <div class="space-y-2">
    <div
      class="rounded-[18px] border border-slate-700/80 bg-slate-950/70 p-2 shadow-inner backdrop-blur-md transition duration-200 focus-within:border-blue-500/50 focus-within:shadow-[0_0_0_3px_rgba(59,130,246,0.12)]"
    >
      <div class="flex items-end gap-2">
        <div class="relative flex shrink-0 items-center pb-1">
          <button
            type="button"
            class="rounded-full p-2 text-slate-400 transition duration-200 hover:bg-slate-800 hover:text-white"
            aria-label="Emoji"
            @click="showEmoji = !showEmoji"
          >
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75h.008v.008H9.75V9.75Zm4.5 0h.008v.008h-.008V9.75Z"
              />
            </svg>
          </button>

          <div
            v-if="showEmoji"
            class="absolute bottom-11 left-0 z-20 flex flex-wrap gap-1 rounded-[14px] border border-slate-700 bg-slate-900 p-2 shadow-xl"
          >
            <button
              v-for="emoji in emojis"
              :key="emoji"
              type="button"
              class="rounded-lg px-1.5 py-1 text-base transition hover:bg-slate-800"
              @click="insertEmoji(emoji)"
            >
              {{ emoji }}
            </button>
          </div>
        </div>

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

        <div class="flex shrink-0 items-center pb-1">
          <button
            type="button"
            class="rounded-full bg-blue-600 p-2.5 text-white shadow-lg shadow-blue-900/50 transition duration-200 hover:bg-blue-500 hover:shadow-[0_0_20px_rgba(59,130,246,0.45)] disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none"
            aria-label="Envoyer"
            :disabled="!canSend"
            @click="submit"
          >
            <UiIcon name="paper-plane" class="h-5 w-5" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
