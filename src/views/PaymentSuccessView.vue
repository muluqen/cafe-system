<script setup>
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { verifyPayment } from '../services/payment.js'

const route = useRoute()
const router = useRouter()
const status = ref('verifying')
const message = ref('Verifying your payment...')

onMounted(async () => {
  const txRef = route.query.tx_ref
  if (!txRef) {
    status.value = 'error'
    message.value = 'Invalid payment reference.'
    return
  }
  try {
    const res = await verifyPayment(txRef)
    if (res.data.status === 'success') {
      status.value = 'success'
      message.value = 'Payment successful! Your order is confirmed.'
    } else {
      status.value = 'failed'
      message.value = 'Payment was not completed.'
    }
  } catch {
    status.value = 'error'
    message.value = 'Could not verify payment. Please contact support.'
  }
})
</script>

<template>
  <div class="payment-result">
    <div v-if="status === 'verifying'" class="verifying">
      <p>⏳ {{ message }}</p>
    </div>
    <div v-else-if="status === 'success'" class="success">
      <h2>✅ {{ message }}</h2>
      <button @click="$router.push('/')">Back to Home</button>
    </div>
    <div v-else class="failed">
      <h2>❌ {{ message }}</h2>
      <button @click="$router.push('/')">Back to Home</button>
    </div>
  </div>
</template>

<style scoped>
.payment-result {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  text-align: center;
  flex-direction: column;
  gap: 16px;
}
.success h2 { color: green; }
.failed h2 { color: red; }
button {
  margin-top: 16px;
  padding: 10px 24px;
  cursor: pointer;
}
</style>