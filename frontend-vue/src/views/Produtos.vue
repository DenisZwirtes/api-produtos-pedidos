<template>
  <div>
    <div class="mb-6">
      <h2 class="text-3xl font-bold text-gray-900 mb-4">
        <i class="fas fa-box mr-2"></i> Produtos
      </h2>
      <button 
        @click="showCreateModal = true"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium"
      >
        <i class="fas fa-plus mr-1"></i> Novo Produto
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      <p class="mt-2 text-gray-600">Carregando produtos...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      {{ error }}
    </div>

    <!-- Products Grid -->
    <div v-if="!loading && !error" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div 
        v-for="produto in produtos" 
        :key="produto.id"
        class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-semibold text-gray-900">{{ produto.nome }}</h3>
          <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">
            {{ produto.categoria }}
          </span>
        </div>
        
        <div class="space-y-2 mb-4">
          <p class="text-2xl font-bold text-green-600">
            R$ {{ produto.preco.toFixed(2).replace('.', ',') }}
          </p>
          <p class="text-sm text-gray-600">
            <i class="fas fa-warehouse mr-1"></i>
            Estoque: {{ produto.estoque }} unidades
          </p>
        </div>

        <div class="flex space-x-2">
          <button 
            @click="editProduto(produto)"
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium"
          >
            <i class="fas fa-edit mr-1"></i> Editar
          </button>
          <button 
            @click="deleteProduto(produto.id)"
            class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium"
          >
            <i class="fas fa-trash mr-1"></i> Excluir
          </button>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showCreateModal ? 'Novo Produto' : 'Editar Produto' }}
          </h3>
          
          <form @submit.prevent="saveProduto" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nome</label>
              <input 
                v-model="form.nome"
                type="text" 
                required
                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Preço</label>
              <input 
                v-model="form.preco"
                type="number" 
                step="0.01"
                min="0"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Estoque</label>
              <input 
                v-model="form.estoque"
                type="number" 
                min="0"
                required
                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Categoria</label>
              <input 
                v-model="form.categoria"
                type="text" 
                required
                class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
              >
            </div>

            <div class="flex space-x-3 pt-4">
              <button 
                type="submit"
                :disabled="saving"
                class="flex-1 bg-blue-600 hover:bg-blue-700 disabled:bg-blue-400 text-white px-4 py-2 rounded font-medium"
              >
                <i v-if="saving" class="fas fa-spinner fa-spin mr-1"></i>
                {{ saving ? 'Salvando...' : 'Salvar' }}
              </button>
              <button 
                type="button"
                @click="closeModal"
                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-medium"
              >
                Cancelar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import api from '../services/api'

export default {
  name: 'Produtos',
  setup() {
    const produtos = ref([])
    const loading = ref(false)
    const error = ref('')
    const saving = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const editingProduto = ref(null)
    
    const form = ref({
      nome: '',
      preco: '',
      estoque: '',
      categoria: ''
    })

    const loadProdutos = async () => {
      loading.value = true
      error.value = ''
      try {
        const response = await api.get('/produtos')
        produtos.value = response.data.data
      } catch (err) {
        error.value = 'Erro ao carregar produtos: ' + (err.response?.data?.message || err.message)
      } finally {
        loading.value = false
      }
    }

    const saveProduto = async () => {
      saving.value = true
      try {
        const data = {
          nome: form.value.nome,
          preco: parseFloat(form.value.preco),
          estoque: parseInt(form.value.estoque),
          categoria: form.value.categoria
        }

        if (editingProduto.value) {
          await api.put(`/produtos/${editingProduto.value.id}`, data)
        } else {
          await api.post('/produtos', data)
        }

        await loadProdutos()
        closeModal()
      } catch (err) {
        error.value = 'Erro ao salvar produto: ' + (err.response?.data?.message || err.message)
      } finally {
        saving.value = false
      }
    }

    const editProduto = (produto) => {
      editingProduto.value = produto
      form.value = {
        nome: produto.nome,
        preco: produto.preco,
        estoque: produto.estoque,
        categoria: produto.categoria
      }
      showEditModal.value = true
    }

    const deleteProduto = async (id) => {
      if (!confirm('Tem certeza que deseja excluir este produto?')) return
      
      try {
        await api.delete(`/produtos/${id}`)
        await loadProdutos()
      } catch (err) {
        error.value = 'Erro ao excluir produto: ' + (err.response?.data?.message || err.message)
      }
    }

    const closeModal = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingProduto.value = null
      form.value = {
        nome: '',
        preco: '',
        estoque: '',
        categoria: ''
      }
    }

    onMounted(() => {
      loadProdutos()
    })

    return {
      produtos,
      loading,
      error,
      saving,
      showCreateModal,
      showEditModal,
      form,
      loadProdutos,
      saveProduto,
      editProduto,
      deleteProduto,
      closeModal
    }
  }
}
</script>
