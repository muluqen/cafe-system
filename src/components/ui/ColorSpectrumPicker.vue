<template>
  <div class="spectrum-picker" ref="containerRef">
    <div class="spectrum-picker__main">
      <div class="spectrum-picker__preview" :style="{ background: hexValue }" />
      <div class="spectrum-picker__sv-wrap">
        <canvas ref="svCanvas" class="spectrum-picker__sv" width="256" height="256" @pointerdown="onSvDown" />
        <div class="spectrum-picker__sv-cursor" :style="svCursorStyle" />
      </div>
    </div>
    <div class="spectrum-picker__hue-wrap">
      <canvas ref="hueCanvas" class="spectrum-picker__hue" width="256" height="16" @pointerdown="onHueDown" />
      <div class="spectrum-picker__hue-cursor" :style="hueCursorStyle" />
    </div>
    <div class="spectrum-picker__footer">
      <div class="spectrum-picker__hex-wrap">
        <span class="spectrum-picker__hash">#</span>
        <input
          class="spectrum-picker__hex"
          :value="hexDisplay"
          maxlength="6"
          placeholder="000000"
          @input="onHexInput"
          @blur="onHexBlur"
        />
      </div>
      <button class="spectrum-picker__clear" @click="$emit('update:modelValue', null)">Clear</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const containerRef = ref(null)
const svCanvas = ref(null)
const hueCanvas = ref(null)

const hue = ref(0)
const sat = ref(1)
const val = ref(1)
const isDraggingSv = ref(false)
const isDraggingHue = ref(false)

function hsvToRgb(h, s, v) {
  h = h / 360
  let r, g, b
  const i = Math.floor(h * 6)
  const f = h * 6 - i
  const p = v * (1 - s)
  const q = v * (1 - f * s)
  const t = v * (1 - (1 - f) * s)
  switch (i % 6) {
    case 0: r = v; g = t; b = p; break
    case 1: r = q; g = v; b = p; break
    case 2: r = p; g = v; b = t; break
    case 3: r = p; g = q; b = v; break
    case 4: r = t; g = p; b = v; break
    case 5: r = v; g = p; b = q; break
  }
  return [
    Math.round(r * 255),
    Math.round(g * 255),
    Math.round(b * 255),
  ]
}

function rgbToHex(r, g, b) {
  return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('')
}

function hexToRgb(hex) {
  if (!hex) return null
  hex = hex.replace('#', '')
  if (hex.length === 3) hex = hex.split('').map(c => c + c).join('')
  if (hex.length !== 6) return null
  const n = parseInt(hex, 16)
  if (isNaN(n)) return null
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255]
}

function rgbToHsv(r, g, b) {
  r /= 255; g /= 255; b /= 255
  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const d = max - min
  let h = 0
  const s = max === 0 ? 0 : d / max
  const v = max
  if (d !== 0) {
    switch (max) {
      case r: h = ((g - b) / d + (g < b ? 6 : 0)) / 6; break
      case g: h = ((b - r) / d + 2) / 6; break
      case b: h = ((r - g) / d + 4) / 6; break
    }
  }
  return [h * 360, s, v]
}

const hexValue = computed(() => {
  const [r, g, b] = hsvToRgb(hue.value, sat.value, val.value)
  return rgbToHex(r, g, b)
})

const hexDisplay = computed(() => {
  return hexValue.value ? hexValue.value.replace('#', '').toUpperCase() : ''
})

function parseHex(hex) {
  const rgb = hexToRgb(hex)
  if (!rgb) return false
  const [h, s, v] = rgbToHsv(...rgb)
  hue.value = h
  sat.value = s
  val.value = v
  return true
}

watch(() => props.modelValue, (v) => {
  if (v && typeof v === 'string' && v.startsWith('#')) {
    const rgb = hexToRgb(v)
    if (rgb) {
      const [h, s, sv] = rgbToHsv(...rgb)
      if (!isDraggingSv.value && !isDraggingHue.value) {
        hue.value = h
        sat.value = s
        val.value = sv
      }
    }
  }
}, { immediate: true })

watch(hexValue, (v) => {
  if (v && v !== props.modelValue) {
    emit('update:modelValue', v)
  }
})

function drawSv() {
  const canvas = svCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const w = canvas.width
  const h = canvas.height

  const baseColor = hsvToRgb(hue.value, 1, 1)
  const baseHex = rgbToHex(...baseColor)

  ctx.clearRect(0, 0, w, h)

  // White to hue gradient (horizontal)
  const gradH = ctx.createLinearGradient(0, 0, w, 0)
  gradH.addColorStop(0, '#FFFFFF')
  gradH.addColorStop(1, baseHex)
  ctx.fillStyle = gradH
  ctx.fillRect(0, 0, w, h)

  // Transparent to black gradient (vertical)
  const gradV = ctx.createLinearGradient(0, 0, 0, h)
  gradV.addColorStop(0, 'rgba(0,0,0,0)')
  gradV.addColorStop(1, 'rgba(0,0,0,1)')
  ctx.fillStyle = gradV
  ctx.fillRect(0, 0, w, h)
}

function drawHue() {
  const canvas = hueCanvas.value
  if (!canvas) return
  const ctx = canvas.getContext('2d')
  const w = canvas.width
  const h = canvas.height

  const grad = ctx.createLinearGradient(0, 0, w, 0)
  const stops = [0, 60, 120, 180, 240, 300, 360]
  const colors = ['#ff0000', '#ffff00', '#00ff00', '#00ffff', '#0000ff', '#ff00ff', '#ff0000']
  stops.forEach((s, i) => grad.addColorStop(s / 360, colors[i]))

  ctx.clearRect(0, 0, w, h)
  ctx.fillStyle = grad
  ctx.beginPath()
  ctx.roundRect(0, 0, w, h, 8)
  ctx.fill()
}

const svCursorStyle = computed(() => ({
  left: `${sat.value * 100}%`,
  top: `${(1 - val.value) * 100}%`,
}))

const hueCursorStyle = computed(() => ({
  left: `${(hue.value / 360) * 100}%`,
}))

function onSvDown(e) {
  isDraggingSv.value = true
  updateSv(e)
  window.addEventListener('pointermove', onSvMove)
  window.addEventListener('pointerup', onSvUp)
}

function onSvMove(e) {
  if (isDraggingSv.value) updateSv(e)
}

function onSvUp() {
  isDraggingSv.value = false
  window.removeEventListener('pointermove', onSvMove)
  window.removeEventListener('pointerup', onSvUp)
}

function updateSv(e) {
  const canvas = svCanvas.value
  if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  const y = Math.max(0, Math.min(1, (e.clientY - rect.top) / rect.height))
  sat.value = x
  val.value = 1 - y
}

function onHueDown(e) {
  isDraggingHue.value = true
  updateHue(e)
  window.addEventListener('pointermove', onHueMove)
  window.addEventListener('pointerup', onHueUp)
}

function onHueMove(e) {
  if (isDraggingHue.value) updateHue(e)
}

function onHueUp() {
  isDraggingHue.value = false
  window.removeEventListener('pointermove', onHueMove)
  window.removeEventListener('pointerup', onHueUp)
}

function updateHue(e) {
  const canvas = hueCanvas.value
  if (!canvas) return
  const rect = canvas.getBoundingClientRect()
  const x = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width))
  hue.value = x * 360
}

function onHexInput(e) {
  const raw = e.target.value.replace(/[^0-9a-fA-F]/g, '').slice(0, 6)
  if (raw.length === 6) {
    parseHex(raw)
  }
}

function onHexBlur(e) {
  const raw = e.target.value.replace(/[^0-9a-fA-F]/g, '')
  if (raw.length === 6) {
    parseHex(raw)
  } else if (raw.length === 3) {
    parseHex(raw.split('').map(c => c + c).join(''))
  }
}

onMounted(async () => {
  await nextTick()
  drawHue()
  drawSv()
  if (props.modelValue) parseHex(props.modelValue)
})

watch(hue, () => drawSv())
</script>

<style scoped>
.spectrum-picker {
  background: #18181B;
  border: 1px solid #27272A;
  border-radius: 0.75rem;
  padding: 0.75rem;
  width: 100%;
  max-width: 320px;
}

.spectrum-picker__main {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.spectrum-picker__preview {
  width: 48px;
  height: 48px;
  border-radius: 0.5rem;
  border: 2px solid #3F3F46;
  flex-shrink: 0;
}

.spectrum-picker__sv-wrap {
  position: relative;
  flex: 1;
  aspect-ratio: 1;
  border-radius: 0.5rem;
  overflow: hidden;
  cursor: crosshair;
}

.spectrum-picker__sv {
  width: 100%;
  height: 100%;
  display: block;
}

.spectrum-picker__sv-cursor {
  position: absolute;
  width: 14px;
  height: 14px;
  border: 2px solid white;
  border-radius: 50%;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.3), inset 0 0 0 1px rgba(0,0,0,0.3);
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.spectrum-picker__hue-wrap {
  position: relative;
  height: 16px;
  margin-bottom: 0.75rem;
  cursor: pointer;
}

.spectrum-picker__hue {
  width: 100%;
  height: 100%;
  display: block;
  border-radius: 8px;
}

.spectrum-picker__hue-cursor {
  position: absolute;
  top: 50%;
  width: 18px;
  height: 18px;
  border: 2.5px solid white;
  border-radius: 50%;
  box-shadow: 0 0 0 1px rgba(0,0,0,0.3);
  transform: translate(-50%, -50%);
  pointer-events: none;
}

.spectrum-picker__footer {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.spectrum-picker__hex-wrap {
  display: flex;
  align-items: center;
  background: #09090B;
  border: 1px solid #27272A;
  border-radius: 0.375rem;
  padding: 0.375rem 0.5rem;
  flex: 1;
}

.spectrum-picker__hash {
  color: #52525B;
  font-size: 0.8rem;
  font-weight: 600;
  margin-right: 0.125rem;
}

.spectrum-picker__hex {
  background: transparent;
  border: none;
  outline: none;
  color: #FAFAFA;
  font-size: 0.8rem;
  font-family: monospace;
  font-weight: 600;
  width: 100%;
  text-transform: uppercase;
}

.spectrum-picker__hex::placeholder {
  color: #3F3F46;
}

.spectrum-picker__clear {
  padding: 0.375rem 0.75rem;
  background: transparent;
  border: 1px solid #27272A;
  border-radius: 0.375rem;
  color: #71717A;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 150ms ease;
}

.spectrum-picker__clear:hover {
  background: #1C1C1F;
  color: #FAFAFA;
  border-color: #3F3F46;
}
</style>
