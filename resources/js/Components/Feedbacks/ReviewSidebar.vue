<script setup>
import { computed } from 'vue';
import ReviewFilters from './ReviewFilters.vue';
import ReviewCard from './ReviewCard.vue';
import {
  dateKey,
  feedbackUrgency,
  isFeedbackOverdue,
  todayKey,
} from '../../utils/feedbackUrgency';

const props = defineProps({
  feedbacks: { type: Array, default: () => [] },
  activeId: { type: Number, default: null },
  filter: { type: String, default: 'all' },
  search: { type: String, default: '' },
  mode: { type: String, default: 'coach' },
});

const emit = defineEmits(['update:filter', 'update:search', 'select']);

function urgencyFor(item) {
  if (props.mode === 'athlete') {
    return item.status === 'coach_replied' ? 'done' : 'normal';
  }
  return feedbackUrgency(item);
}

const counts = computed(() => {
  const today = todayKey();
  const result = { all: props.feedbacks.length, today: 0, overdue: 0, done: 0, pending: 0 };
  for (const item of props.feedbacks) {
    if (item.status === 'coach_replied') {
      result.done += 1;
      continue;
    }
    result.pending += 1;
    if (props.mode === 'coach') {
      const u = feedbackUrgency(item);
      if (u === 'overdue') result.overdue += 1;
      else if (dateKey(item.session_date) === today) result.today += 1;
    }
  }
  return result;
});

const filtered = computed(() => {
  const today = todayKey();
  const q = props.search.trim().toLowerCase();

  let items = [...props.feedbacks];

  if (props.filter === 'today') {
    items = items.filter(
      (item) => item.status !== 'coach_replied' && dateKey(item.session_date) === today,
    );
  } else if (props.filter === 'overdue') {
    items = items.filter((item) => isFeedbackOverdue(item, today));
  } else if (props.filter === 'pending') {
    items = items.filter((item) => item.status !== 'coach_replied');
  } else if (props.filter === 'done') {
    items = items.filter((item) => item.status === 'coach_replied');
  }

  if (q) {
    items = items.filter((item) => {
      const hay = `${item.athlete_name || ''} ${item.session_label || ''} ${item.athlete_notes || ''}`.toLowerCase();
      return hay.includes(q);
    });
  }

  const rank = (item) => {
    if (props.mode === 'athlete') {
      return item.status === 'coach_replied' ? 1 : 0;
    }
    const u = urgencyFor(item);
    if (u === 'overdue') return 0;
    if (u === 'today') return 1;
    if (item.status !== 'coach_replied') return 2;
    return 3;
  };

  return items.sort((a, b) => {
    const ra = rank(a);
    const rb = rank(b);
    if (ra !== rb) return ra - rb;
    return String(b.submitted_at || '').localeCompare(String(a.submitted_at || ''));
  });
});
</script>

<template>
  <aside class="flex h-full min-h-0 w-full flex-col lg:w-[14rem] xl:w-[15rem]">
    <ReviewFilters
      :model-value="filter"
      :search="search"
      :counts="counts"
      :mode="mode"
      @update:model-value="emit('update:filter', $event)"
      @update:search="emit('update:search', $event)"
    />

    <div class="tc-scrollbar mt-3 min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
      <p
        v-if="!filtered.length"
        class="rounded-[18px] border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
      >
        Aucun retour dans ce filtre.
      </p>
      <ReviewCard
        v-for="item in filtered"
        :key="item.id"
        :item="item"
        :selected="item.id === activeId"
        :urgency="urgencyFor(item)"
        :mode="mode"
        @select="emit('select', $event)"
      />
    </div>
  </aside>
</template>
