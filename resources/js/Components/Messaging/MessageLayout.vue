<script setup>
defineProps({
  showSidebar: {
    type: Boolean,
    default: true,
  },
  showContext: {
    type: Boolean,
    default: false,
  },
  mobilePane: {
    type: String,
    default: 'list',
  },
});
</script>

<template>
  <div
    class="tc-message-layout flex min-h-0 gap-3 lg:min-h-[32rem] lg:gap-4"
  >
    <!-- Colonne 1 : liste -->
    <div
      v-if="showSidebar"
      class="min-h-0 min-w-0"
      :class="
        mobilePane === 'list'
          ? 'flex w-full lg:flex lg:w-auto'
          : 'hidden lg:flex'
      "
    >
      <slot name="sidebar" />
    </div>

    <!-- Colonne 2 : conversation -->
    <div
      class="min-h-0 min-w-0 flex-1"
      :class="
        !showSidebar || mobilePane === 'chat'
          ? 'flex'
          : 'hidden lg:flex'
      "
    >
      <slot name="chat" />
    </div>

    <!-- Colonne 3 : contexte (desktop / overlay tablet) -->
    <div
      v-if="showContext"
      class="min-h-0"
      :class="
        mobilePane === 'context'
          ? 'fixed inset-0 z-40 flex bg-slate-950/80 p-3 backdrop-blur-sm xl:static xl:inset-auto xl:z-auto xl:bg-transparent xl:p-0 xl:backdrop-blur-none'
          : 'hidden xl:flex'
      "
    >
      <slot name="context" />
    </div>
  </div>
</template>
