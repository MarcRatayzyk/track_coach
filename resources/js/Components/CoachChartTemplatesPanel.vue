<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  templates: {
    type: Array,
    default: () => [],
  },
  assignmentId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(['close', 'edit']);

function closePanel() {
  emit('close');
}

function addToDashboard(templateId) {
  router.post(
    '/coach/stats-dashboard-items',
    {
      template_id: templateId,
      assignment: props.assignmentId,
    },
    { preserveScroll: true },
  );
}

function deleteTemplate(template) {
  if (!window.confirm(`Supprimer le modèle « ${template.name} » ?`)) {
    return;
  }

  router.delete(`/coach/chart-templates/${template.id}`, {
    data: { assignment: props.assignmentId },
    preserveScroll: true,
  });
}

function editTemplate(template) {
  emit('edit', template);
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex justify-end bg-black/60 backdrop-blur-sm"
      @click.self="closePanel"
    >
      <aside
        class="flex h-full w-full max-w-md flex-col border-l border-slate-800 bg-slate-900 shadow-2xl"
        @click.stop
      >
        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4">
          <div>
            <h2 class="text-base font-semibold text-white">Mes modèles</h2>
            <p class="mt-0.5 text-xs text-slate-400">Réutilise ou ajoute tes graphiques au tableau de bord.</p>
          </div>
          <button
            type="button"
            class="rounded-lg p-2 text-slate-400 hover:bg-slate-800 hover:text-white"
            @click="closePanel"
          >
            ✕
          </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
          <p v-if="templates.length === 0" class="text-sm text-slate-500">
            Aucun modèle enregistré. Crée un graphique avec « Ajouter un graphique ».
          </p>

          <ul v-else class="space-y-3">
            <li
              v-for="template in templates"
              :key="template.id"
              class="rounded-xl border border-slate-800 bg-slate-950/50 p-4"
            >
              <p class="font-medium text-white">{{ template.name }}</p>
              <p class="mt-1 text-xs text-slate-500">
                {{ template.config?.chartType }} · {{ template.config?.metric }} ·
                {{ template.config?.groupBy }}
              </p>
              <div class="mt-3 flex flex-wrap gap-2">
                <button
                  type="button"
                  class="rounded-lg bg-blue-600/20 px-3 py-1.5 text-xs text-blue-200 hover:bg-blue-600/30"
                  @click="addToDashboard(template.id)"
                >
                  Ajouter au dashboard
                </button>
                <button
                  type="button"
                  class="rounded-lg border border-slate-700 px-3 py-1.5 text-xs text-slate-300 hover:bg-slate-800"
                  @click="editTemplate(template)"
                >
                  Modifier
                </button>
                <button
                  type="button"
                  class="rounded-lg px-3 py-1.5 text-xs text-red-400 hover:bg-red-950/30"
                  @click="deleteTemplate(template)"
                >
                  Supprimer
                </button>
              </div>
            </li>
          </ul>
        </div>
      </aside>
    </div>
  </Teleport>
</template>
