<template>
  <Teleport to="body">
    <Transition name="dialog">
      <div v-if="model" class="fixed inset-0 z-50 overflow-y-auto" @click.self="close">
        <div class="fixed inset-0 bg-black/50" @click="close" />
        <div
          class="fixed top-[50%] left-[50%] grid w-full max-w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 gap-4 rounded-lg border bg-background p-6 shadow-lg sm:max-w-lg"
        >
          <slot />
          <button
            v-if="showCloseButton"
            type="button"
            class="absolute top-4 right-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:ring-2 focus:ring-ring focus:outline-none"
            @click="close"
          >
            <X class="size-4" />
            <span class="sr-only">Close</span>
          </button>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { X } from 'lucide-vue-next'

withDefaults(defineProps<{ showCloseButton?: boolean }>(), { showCloseButton: true })

const model = defineModel<boolean>({ default: false })
const close = () => {
  model.value = false
}
</script>

<style scoped>
.dialog-enter-active,
.dialog-leave-active {
  transition: opacity 0.2s ease;
}
.dialog-enter-from,
.dialog-leave-to {
  opacity: 0;
}
</style>
