<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <!-- Top Bar -->
      <div class="bg-detik-red text-white py-1">
        <div class="container mx-auto px-4">
          <div class="flex justify-between items-center text-sm">
            <div class="flex space-x-4">
              <span>{{ new Date().toLocaleDateString('id-ID', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
              }) }}</span>
            </div>
            <div class="flex space-x-4">
              <a href="#" class="hover:underline">RSS</a>
              <a href="#" class="hover:underline">Kontak</a>
              <a href="#" class="hover:underline">Karir</a>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Header -->
      <div class="container mx-auto px-4 py-4">
        <div class="flex items-center justify-between">
          <!-- Logo -->
          <div class="flex-shrink-0">
            <Link href="/" class="text-3xl font-bold text-detik-red">
              DetikClone
            </Link>
          </div>

          <!-- Search Bar -->
          <div class="flex-1 max-w-xl mx-8">
            <form @submit.prevent="search" class="relative">
              <input
                v-model="searchQuery"
                type="search"
                placeholder="Cari berita..."
                class="w-full px-4 py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-detik-red focus:border-transparent"
              >
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
              </div>
            </form>
          </div>

          <!-- Social Media -->
          <div class="flex space-x-3">
            <a href="#" class="text-gray-600 hover:text-detik-red">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
              </svg>
            </a>
            <a href="#" class="text-gray-600 hover:text-detik-red">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
              </svg>
            </a>
          </div>
        </div>
      </div>

      <!-- Navigation Menu -->
      <nav class="bg-detik-dark text-white">
        <div class="container mx-auto px-4">
          <div class="flex space-x-8 overflow-x-auto">
            <Link
              href="/"
              class="py-3 px-2 text-sm font-medium hover:text-detik-red border-b-2 border-transparent hover:border-detik-red whitespace-nowrap"
              :class="{ 'border-detik-red text-detik-red': $page.component === 'Home' }"
            >
              Beranda
            </Link>
            <Link
              v-for="category in categories"
              :key="category.id"
              :href="`/kategori/${category.slug}`"
              class="py-3 px-2 text-sm font-medium hover:text-detik-red border-b-2 border-transparent hover:border-detik-red whitespace-nowrap"
            >
              {{ category.name }}
            </Link>
          </div>
        </div>
      </nav>
    </header>

    <!-- Main Content -->
    <main>
      <slot />
    </main>

    <!-- Footer -->
    <footer class="bg-detik-dark text-white mt-16">
      <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
          <!-- Company Info -->
          <div class="col-span-1 md:col-span-2">
            <h3 class="text-xl font-bold text-detik-red mb-4">DetikClone</h3>
            <p class="text-gray-300 mb-4">
              Portal berita terdepan Indonesia yang menyajikan informasi terkini, akurat, dan terpercaya.
            </p>
            <div class="flex space-x-4">
              <a href="#" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                </svg>
              </a>
              <a href="#" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M22.56 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="font-semibold mb-4">Navigasi</h4>
            <ul class="space-y-2">
              <li><Link href="/" class="text-gray-300 hover:text-white">Beranda</Link></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Tentang Kami</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Kontak</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Karir</a></li>
            </ul>
          </div>

          <!-- Legal -->
          <div>
            <h4 class="font-semibold mb-4">Legal</h4>
            <ul class="space-y-2">
              <li><a href="#" class="text-gray-300 hover:text-white">Kebijakan Privasi</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Syarat & Ketentuan</a></li>
              <li><a href="#" class="text-gray-300 hover:text-white">Disclaimer</a></li>
            </ul>
          </div>
        </div>

        <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
          <p>&copy; {{ new Date().getFullYear() }} DetikClone. All rights reserved.</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'

interface Category {
  id: number
  name: string
  slug: string
}

withDefaults(defineProps<{
  categories?: Category[]
}>(), {
  categories: () => []
})

const searchQuery = ref('')

const search = () => {
  if (searchQuery.value.trim()) {
    router.get('/cari', { q: searchQuery.value.trim() })
  }
}
</script>