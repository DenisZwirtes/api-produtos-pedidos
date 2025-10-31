<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
          <i class="fas fa-sign-in-alt mr-2"></i>
          Faça login na sua conta
        </h2>
        <p class="mt-2 text-center text-sm text-gray-600">
          Use as credenciais de teste: tester@example.com / password123
        </p>
      </div>
      
      <form class="mt-8 space-y-6" @submit.prevent="login">
        <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          <i class="fas fa-exclamation-triangle mr-2"></i>
          {{ error }}
        </div>

        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">Email</label>
            <input 
              id="email" 
              v-model="form.email"
              name="email" 
              type="email" 
              required 
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
              placeholder="Email"
            >
          </div>
          <div>
            <label for="password" class="sr-only">Senha</label>
            <input 
              id="password" 
              v-model="form.password"
              name="password" 
              type="password" 
              required 
              class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
              placeholder="Senha"
            >
          </div>
        </div>

        <div>
          <button 
            type="submit" 
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-blue-400"
          >
            <i v-if="loading" class="fas fa-spinner fa-spin mr-2"></i>
            {{ loading ? 'Entrando...' : 'Entrar' }}
          </button>
        </div>

        <div class="mt-6 p-4 bg-blue-50 rounded-md">
          <h3 class="text-sm font-medium text-blue-800 mb-2">Credenciais de Teste:</h3>
          <p class="text-sm text-blue-700">
            <strong>Email:</strong> tester@example.com<br>
            <strong>Senha:</strong> password123
          </p>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

export default {
  name: 'Login',
  setup() {
    const router = useRouter()
    const loading = ref(false)
    const error = ref('')
    
    const form = ref({
      email: 'tester@example.com',
      password: 'password123'
    })

    const login = async () => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await api.post('/login', form.value)
        const { token, user } = response.data
        
        localStorage.setItem('token', token)
        localStorage.setItem('user', JSON.stringify(user))
        
        router.push('/pedidos')
      } catch (err) {
        error.value = 'Erro ao fazer login: ' + (err.response?.data?.message || err.message)
      } finally {
        loading.value = false
      }
    }

    return {
      form,
      loading,
      error,
      login
    }
  }
}
</script>
