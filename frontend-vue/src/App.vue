<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Header -->
    <nav class="bg-blue-600 shadow-lg">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-white text-xl font-bold">
              <i class="fas fa-shopping-cart mr-2"></i>
              API Produtos & Pedidos
            </h1>
          </div>
          <div class="flex items-center space-x-4">
            <router-link 
              to="/produtos" 
              class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium"
            >
              <i class="fas fa-box mr-1"></i> Produtos
            </router-link>
            <router-link 
              to="/pedidos" 
              class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium"
            >
              <i class="fas fa-shopping-bag mr-1"></i> Pedidos
            </router-link>
            <button 
              v-if="isAuthenticated"
              @click="logout"
              class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium"
            >
              <i class="fas fa-sign-out-alt mr-1"></i> Sair
            </button>
            <router-link 
              v-else
              to="/login" 
              class="text-white hover:bg-blue-700 px-3 py-2 rounded-md text-sm font-medium"
            >
              <i class="fas fa-sign-in-alt mr-1"></i> Login
            </router-link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4 mt-8">
      <div class="max-w-7xl mx-auto px-4 text-center">
        <p>&copy; {{ currentYear }} API Produtos & Pedidos - Frontend Vue.js</p>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'

export default {
  name: 'App',
  setup() {
    const router = useRouter()
    const token = ref(localStorage.getItem('token'))
    
    const isAuthenticated = computed(() => !!token.value)
    const currentYear = new Date().getFullYear()
    
    const logout = () => {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      token.value = null
      router.push('/login')
    }
    
    return {
      isAuthenticated,
      logout,
      currentYear
    }
  }
}
</script>
