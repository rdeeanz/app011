<template>
  <div class="min-h-screen bg-gray-100 flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
      <div class="text-center">
        <h1 class="text-6xl font-bold text-red-600 mb-4">{{ status }}</h1>
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">
          {{ title }}
        </h2>
        <p class="text-gray-600 mb-6">
          {{ description }}
        </p>
        <Link
          href="/"
          class="inline-block bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors"
        >
          Kembali ke Beranda
        </Link>
      </div>
      
      <!-- Debug Information (development only) -->
      <div v-if="isDevelopment" class="mt-8 p-4 bg-gray-50 rounded border text-sm">
        <h3 class="font-semibold mb-2">Debug Information:</h3>
        <div class="space-y-1 text-gray-600">
          <div><strong>Status:</strong> {{ status }}</div>
          <div><strong>URL:</strong> {{ currentUrl }}</div>
          <div v-if="error"><strong>Error:</strong> {{ error }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: Number,
    default: 500
  }
})

const isDevelopment = computed(() => {
  return import.meta.env.DEV || window.location.hostname === 'localhost'
})

const currentUrl = computed(() => {
  return window.location.href
})

const title = computed(() => {
  const titles = {
    404: 'Halaman Tidak Ditemukan',
    403: 'Akses Ditolak',
    500: 'Terjadi Kesalahan Server',
    503: 'Server Tidak Tersedia'
  }
  return titles[props.status] || 'Terjadi Kesalahan'
})

const description = computed(() => {
  const descriptions = {
    404: 'Halaman yang Anda cari tidak dapat ditemukan. Mungkin sudah dipindahkan atau dihapus.',
    403: 'Anda tidak memiliki izin untuk mengakses halaman ini.',
    500: 'Terjadi kesalahan pada server. Tim kami sedang bekerja untuk memperbaikinya.',
    503: 'Server sedang dalam pemeliharaan. Silakan coba lagi nanti.'
  }
  return descriptions[props.status] || 'Terjadi kesalahan yang tidak diketahui.'
})

const error = computed(() => {
  // Get error from URL params if available
  const urlParams = new URLSearchParams(window.location.search)
  return urlParams.get('error') || null
})
</script>

<style scoped>
/* Add any additional styling if needed */
</style>