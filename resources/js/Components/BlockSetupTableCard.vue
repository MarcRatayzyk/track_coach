<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { router, useForm } from '@inertiajs/vue3';
import { track } from '../utils/analytics';

const { t } = useI18n();

const props = defineProps({
  athletes: {
    type: Array,
    default: () => [],
  },
  existingBlocks: {
    type: Array,
    default: () => [],
  },
  dayTableLayouts: {
    type: Array,
    default: () => [],
  },
  defaultDayTableLayoutId: {
    type: Number,
    default: null,
  },
});

const today = new Date().toISOString().slice(0, 10);

const form = useForm({
  athlete_id: props.athletes[0]?.id ?? '',
  name: '',
  week_count: 4,
  days_per_week: 4,
  date_start: today,
  day_table_layout_id: props.defaultDayTableLayoutId ?? props.dayTableLayouts[0]?.id ?? '',
  builder_tab: 'table_v2',
});

const blockSearch = ref('');

const filteredBlocks = computed(() => {
  const term = blockSearch.value.trim().toLowerCase();
  if (!term) {
    return props.existingBlocks;
  }

  return props.existingBlocks.filter((block) => {
    const haystack = [block.name, block.athlete_name, block.date_start, block.date_end]
      .filter(Boolean)
      .join(' ')
      .toLowerCase();
    return haystack.includes(term);
  });
});

function submit() {
  form.post('/coach/program-blocks', {
    preserveScroll: true,
    onSuccess: () => {
      track('program_created', { source: 'table_v2' });
    },
  });
}

const deletingId = ref(null);

function openBlock(assignmentId) {
  router.get('/program-builder', { assignment: assignmentId, tab: 'table_v2' }, { preserveState: false });
}

function deleteBlock(block) {
  if (
    !window.confirm(
      t('programBuilder.shared.deleteBlockConfirm', { name: block.name }),
    )
  ) {
    return;
  }

  deletingId.value = block.id;
  router.delete(`/coach/program-blocks/${block.id}`, {
    preserveScroll: true,
    onFinish: () => {
      deletingId.value = null;
    },
  });
}

const fieldClass =
  'mt-0.5 w-full rounded-md border border-slate-700 bg-slate-950 px-2 py-1 text-sm text-white';
</script>

<template>
  <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-3 shadow-lg sm:p-4">
    <h2 class="text-base font-semibold text-white">{{ t('programBuilder.blockSetupTable.title') }}</h2>
    <p class="mt-0.5 text-xs leading-relaxed text-slate-400">
      {{ t('programBuilder.blockSetupTable.subtitle') }}
    </p>

    <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="submit">
      <label class="min-w-[9rem] flex-1 text-[11px] text-slate-400">
        {{ t('common.athlete') }}
        <select v-model="form.athlete_id" required :class="fieldClass">
          <option v-for="athlete in athletes" :key="athlete.id" :value="athlete.id">
            {{ athlete.name }}
          </option>
        </select>
      </label>

      <label class="min-w-[9rem] flex-1 text-[11px] text-slate-400">
        {{ t('programBuilder.blockSetupTable.tableStructure') }}
        <select v-model="form.day_table_layout_id" :class="fieldClass">
          <option v-for="layout in dayTableLayouts" :key="layout.id" :value="layout.id">
            {{ layout.name }}{{ layout.is_default ? t('programBuilder.shared.defaultSuffix') : '' }}
          </option>
        </select>
      </label>

      <label class="min-w-[10rem] flex-[1.2] text-[11px] text-slate-400">
        {{ t('programBuilder.blockSetupTable.blockName') }}
        <input
          v-model="form.name"
          type="text"
          required
          :placeholder="t('programBuilder.blockSetupTable.blockNamePlaceholder')"
          :class="fieldClass"
        />
      </label>

      <label class="w-[4.25rem] shrink-0 text-[11px] text-slate-400">
        {{ t('programBuilder.blockSetupTable.weekCount') }}
        <input
          v-model.number="form.week_count"
          type="number"
          min="1"
          max="16"
          required
          :class="fieldClass"
        />
      </label>

      <label class="w-[4.25rem] shrink-0 text-[11px] text-slate-400">
        {{ t('programBuilder.blockSetupTable.daysPerWeek') }}
        <input
          v-model.number="form.days_per_week"
          type="number"
          min="1"
          max="7"
          required
          :class="fieldClass"
        />
      </label>

      <label class="w-[9.5rem] shrink-0 text-[11px] text-slate-400">
        {{ t('programBuilder.blockSetupTable.dateStart') }}
        <input v-model="form.date_start" type="date" required :class="fieldClass" />
      </label>

      <button
        type="submit"
        :disabled="form.processing || !athletes.length"
        class="shrink-0 rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white shadow hover:bg-blue-500 disabled:opacity-50"
      >
        {{ t('programBuilder.blockSetupTable.create') }}
      </button>

      <p v-if="Object.keys(form.errors).length" class="w-full text-sm text-red-400">
        {{ Object.values(form.errors).flat().join(' ') }}
      </p>
    </form>

    <div v-if="existingBlocks.length" class="mt-4 border-t border-slate-800 pt-3">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-white">{{ t('programBuilder.blockSetupTable.resume') }}</h3>
        <input
          v-model="blockSearch"
          type="search"
          :placeholder="t('programBuilder.blockSetupTable.searchPlaceholder')"
          class="w-full max-w-xs rounded-md border border-slate-700 bg-slate-950 px-2.5 py-1.5 text-sm text-white placeholder:text-slate-500 sm:w-56"
        />
      </div>
      <ul class="mt-2 space-y-1.5">
        <li
          v-for="block in filteredBlocks"
          :key="block.id"
          class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-800 bg-slate-950/50 px-2.5 py-2"
        >
          <div class="min-w-0">
            <p class="text-sm font-medium text-white">{{ block.name }}</p>
            <p class="mt-0.5 text-[11px] text-slate-400">
              {{ t('programBuilder.shared.blockMeta', { athlete: block.athlete_name, weeks: block.week_count, start: block.date_start }) }}
              <span v-if="block.date_end">{{ t('programBuilder.shared.blockMetaTo', { end: block.date_end }) }}</span>
              <span
                v-if="block.status === 'draft'"
                class="ml-1 rounded bg-amber-500/15 px-1.5 py-0.5 text-[10px] text-amber-300"
              >
                {{ t('programBuilder.shared.draft') }}
              </span>
            </p>
          </div>
          <div class="flex shrink-0 flex-wrap gap-1.5">
            <button
              type="button"
              class="rounded-md border border-slate-700 px-2.5 py-1 text-xs font-medium text-blue-300 hover:bg-slate-800"
              @click="openBlock(block.id)"
            >
              {{ t('programBuilder.blockSetupTable.openV2') }}
            </button>
            <button
              type="button"
              class="rounded-md border border-red-500/40 px-2.5 py-1 text-xs font-medium text-red-300 hover:bg-red-950/40 disabled:opacity-50"
              :disabled="deletingId === block.id"
              @click="deleteBlock(block)"
            >
              {{ deletingId === block.id ? t('common.deleting') : t('common.delete') }}
            </button>
          </div>
        </li>
        <li v-if="!filteredBlocks.length" class="rounded-lg border border-dashed border-slate-800 px-3 py-4 text-center text-xs text-slate-500">
          {{ t('programBuilder.blockSetupTable.searchEmpty') }}
        </li>
      </ul>
    </div>
  </section>
</template>
