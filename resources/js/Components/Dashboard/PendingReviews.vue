<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SectionHeader from './SectionHeader.vue';
import ReviewCard from './ReviewCard.vue';

const props = defineProps({
  dailyTasks: { type: Array, default: () => [] },
  weeklyTasks: { type: Array, default: () => [] },
  today: { type: String, default: null },
});

const filter = ref('today');

const filters = [
  { key: 'today', label: "Aujourd'hui" },
  { key: 'overdue', label: 'En retard' },
  { key: 'week', label: 'Cette semaine' },
  { key: 'all', label: 'Tous' },
];

const merged = computed(() => {
  const daily = (props.dailyTasks ?? []).map((t) => ({ ...t, _type: 'daily' }));
  const weekly = (props.weeklyTasks ?? []).map((t) => ({ ...t, _type: 'weekly' }));
  return [...daily, ...weekly];
});

function isOverdue(task) {
  if (task.has_submission || task.feedback_status === 'coach_replied') {
    return false;
  }
  if (!task.due_at) {
    return false;
  }
  return Date.parse(task.due_at) < Date.now();
}

const filtered = computed(() => {
  let list = merged.value;
  if (filter.value === 'today') {
    list = list.filter(
      (t) =>
        t._type === 'daily' ||
        (t.session_date && props.today && t.session_date === props.today),
    );
  } else if (filter.value === 'overdue') {
    list = list.filter((t) => isOverdue(t));
  } else if (filter.value === 'week') {
    list = list.filter((t) => t._type === 'weekly' || t._type === 'daily');
  }

  return [...list].sort((a, b) => {
    const rank = (t) => {
      if (isOverdue(t)) return 0;
      if (t.has_submission && t.feedback_status !== 'coach_replied') return 1;
      if (!t.has_submission) return 2;
      return 3;
    };
    return rank(a) - rank(b);
  });
});
</script>

<template>
  <section>
    <SectionHeader
      eyebrow="À traiter"
      title="Retours à traiter"
    >
      <template #actions>
        <Link
          href="/feedbacks?filter=pending"
          class="rounded-[12px] border border-blue-500/40 bg-blue-950/30 px-3 py-1.5 text-xs font-semibold text-blue-200 transition hover:bg-blue-950/50"
        >
          Tous les retours
        </Link>
      </template>
    </SectionHeader>

    <div class="mt-4 flex flex-wrap gap-2">
      <button
        v-for="f in filters"
        :key="f.key"
        type="button"
        class="rounded-full border px-3 py-1.5 text-xs font-semibold transition duration-200"
        :class="
          filter === f.key
            ? 'border-blue-500/50 bg-blue-600/20 text-blue-100 shadow-[0_0_16px_rgba(59,130,246,0.2)]'
            : 'border-slate-700 bg-slate-950/40 text-slate-400 hover:border-slate-600 hover:text-slate-200'
        "
        @click="filter = f.key"
      >
        {{ f.label }}
      </button>
    </div>

    <p
      v-if="filtered.length === 0"
      class="mt-4 rounded-[18px] border border-dashed border-slate-700 bg-slate-950/40 px-4 py-10 text-center text-sm text-slate-500"
    >
      Aucun retour dans ce filtre — tu es à jour.
    </p>

    <div
      v-else
      class="-mx-1 mt-4 flex gap-3 overflow-x-auto px-1 pb-1 sm:grid sm:grid-cols-2 sm:overflow-visible xl:grid-cols-3"
    >
      <ReviewCard
        v-for="task in filtered"
        :key="`${task._type}-${task.id}`"
        :task="task"
        :type="task._type"
        class="flex-1"
      />
    </div>
  </section>
</template>
