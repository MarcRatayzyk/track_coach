<script setup>
import SectionHeader from './SectionHeader.vue';
import KpiCard from './KpiCard.vue';

defineProps({
  items: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['select']);
</script>

<template>
  <section>
    <SectionHeader
      eyebrow="Vue d’ensemble"
      title="Indicateurs clés"
    />
    <div class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
      <KpiCard
        v-for="item in items"
        :key="item.key"
        :label="item.label"
        :value="item.value"
        :suffix="item.suffix"
        :delta="item.delta"
        :delta-positive="item.deltaPositive !== false"
        :icon="item.icon"
        :sparkline="item.sparkline"
        :color="item.color"
        :accent-class="item.accentClass"
        :icon-class="item.iconClass"
        :clickable="Boolean(item.clickable)"
        @click="$emit('select', item)"
      />
    </div>
  </section>
</template>
