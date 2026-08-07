<template>
  <div ref="rootEl" class="relative inline-block">
    <div @click="open = !open">
      <slot name="trigger" />
    </div>
    <Teleport to="body">
      <Transition name="dropdown">
        <div
          v-if="open"
          class="fixed z-50 w-48 min-w-[8rem] overflow-hidden rounded-md border bg-background p-1 text-foreground shadow-md"
          :style="positionStyle"
          @click.stop
        >
          <slot name="content" />
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const open = ref(false)
const rootEl = ref<HTMLElement | null>(null)

const positionStyle = computed(() => {
  if (!rootEl.value) return { top: '0px', left: '0px' }
  const rect = rootEl.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const top = spaceBelow > 200 ? rect.bottom + 4 : rect.top - 4
  return {
    top: `${top}px`,
    left: `${Math.min(rect.left, window.innerWidth - 200)}px`,
  }
})

const handleClickOutside = (event: MouseEvent) => {
  const target = event.target as Node
  if (rootEl.value && rootEl.value.contains(target)) return
  const content = (event.target as Element).closest('.fixed.z-50')
  if (content) return
  open.value = false
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))

defineExpose({ open })
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px) scale(0.96);
}
</style>
