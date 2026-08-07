<template>
  <div class="pointer-events-none fixed inset-x-4 top-4 z-[9999] flex flex-col items-center gap-2 sm:inset-x-auto sm:right-5 sm:items-end">
    <TransitionGroup name="toast">
      <button
        v-for="toast in toastStore.toasts"
        :key="toast.id"
        type="button"
        class="glass pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-2xl p-4 text-left shadow-[var(--shadow-float)]"
        @click="toastStore.remove(toast.id)"
      >
        <span
          class="flex size-9 shrink-0 items-center justify-center rounded-xl"
          :class="{
            'bg-success/12 text-success': toast.type === 'success',
            'bg-destructive/12 text-destructive': toast.type === 'error',
            'bg-primary/12 text-primary': toast.type === 'info',
          }"
        >
          <CheckCircle v-if="toast.type === 'success'" class="size-[18px]" />
          <AlertCircle v-else-if="toast.type === 'error'" class="size-[18px]" />
          <Info v-else class="size-[18px]" />
        </span>
        <span class="flex-1 pt-1.5 text-sm font-medium leading-snug">{{ toast.message }}</span>
        <X class="mt-1.5 size-4 shrink-0 text-muted-foreground" />
      </button>
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { AlertCircle, CheckCircle, Info, X } from 'lucide-vue-next'
import { useToastStore } from '@/stores/toast'

const toastStore = useToastStore()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.25s ease, transform 0.3s var(--ease-out-expo);
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-12px) scale(0.96);
}
.toast-leave-active {
  position: absolute;
}
</style>
