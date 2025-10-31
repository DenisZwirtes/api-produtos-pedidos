<template>
  <div>
    <div class="mb-6">
      <h2 class="text-3xl font-bold text-gray-900 mb-4">
        <i class="fas fa-shopping-bag mr-2"></i> Meus Pedidos
      </h2>
      <button 
        @click="showCreateModal = true"
        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium"
      >
        <i class="fas fa-plus mr-1"></i> Novo Pedido
      </button>
    </div>

    <!-- Not Authenticated -->
    <div v-if="!isAuthenticated" class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      Você precisa fazer login para ver seus pedidos.
      <router-link to="/login" class="text-yellow-800 underline ml-1">Fazer login</router-link>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-8">
      <i class="fas fa-spinner fa-spin text-2xl text-blue-600"></i>
      <p class="mt-2 text-gray-600">Carregando pedidos...</p>
    </div>

    <!-- Error -->
    <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
      <i class="fas fa-exclamation-triangle mr-2"></i>
      {{ error }}
    </div>

    <!-- Orders List -->
    <div v-if="!loading && !error && isAuthenticated" class="space-y-4">
      <div v-if="pedidos.length === 0" class="text-center py-8 text-gray-500">
        <i class="fas fa-shopping-bag text-4xl mb-4"></i>
        <p>Você ainda não fez nenhum pedido.</p>
      </div>

      <div 
        v-for="pedido in pedidos" 
        :key="pedido.id"
        class="bg-white rounded-lg shadow-md p-6"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">
              Pedido #{{ pedido.id }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ formatDate(pedido.created_at) }}
            </p>
          </div>
          <span 
            :class="getStatusClass(pedido.status)"
            class="px-3 py-1 rounded-full text-sm font-medium"
          >
            {{ getStatusLabel(pedido.status) }}
          </span>
        </div>

        <div class="space-y-2 mb-4">
          <div 
            v-for="item in pedido.items" 
            :key="`${pedido.id}-${item.produto_id}`"
            class="flex justify-between items-center py-2 border-b border-gray-100"
          >
            <div>
              <p class="font-medium text-gray-900">{{ item.nome }}</p>
              <p class="text-sm text-gray-600">{{ item.categoria }}</p>
            </div>
            <div class="text-right">
              <p class="font-medium text-gray-900">
                {{ item.quantidade }}x R$ {{ item.preco_unitario.toFixed(2).replace('.', ',') }}
              </p>
              <p class="text-sm text-gray-600">
                Total: R$ {{ (item.quantidade * item.preco_unitario).toFixed(2).replace('.', ',') }}
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center">
          <div class="text-lg font-bold text-gray-900">
            Total: R$ {{ getTotalPedido(pedido).toFixed(2).replace('.', ',') }}
          </div>
          <div class="flex space-x-2">
            <button 
              v-if="pedido.status === 'pending'"
              @click="editPedido(pedido)"
              class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm font-medium"
            >
              <i class="fas fa-edit mr-1"></i> Editar
            </button>
            <button 
              v-if="pedido.status === 'pending'"
              @click="cancelPedido(pedido.id)"
              class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm font-medium"
            >
              <i class="fas fa-times mr-1"></i> Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- Pagination Controls -->
      <div v-if="lastPage > 1" class="pt-2 flex items-center justify-center space-x-2">
        <button
          class="px-3 py-1 rounded border text-sm disabled:opacity-50"
          :disabled="currentPage === 1"
          @click="goToPage(currentPage - 1)"
        >
          ‹ Anterior
        </button>
        <button
          v-for="p in pagesToShow"
          :key="p"
          class="px-3 py-1 rounded border text-sm"
          :class="p === currentPage ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700'"
          @click="goToPage(p)"
        >
          {{ p }}
        </button>
        <button
          class="px-3 py-1 rounded border text-sm disabled:opacity-50"
          :disabled="currentPage === lastPage"
          @click="goToPage(currentPage + 1)"
        >
          Próxima ›
        </button>
      </div>
    </div>

    <!-- Create/Edit Order Modal -->
    <div v-if="showCreateModal || showEditModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ showCreateModal ? 'Novo Pedido' : 'Editar Pedido' }}
          </h3>
          
          <form @submit.prevent="savePedido" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Itens do Pedido</label>
              <div class="space-y-3">
                <div 
                  v-for="(item, index) in form.items" 
                  :key="index"
                  class="flex space-x-3 items-end"
                >
                  <div class="flex-1">
                    <label class="block text-xs text-gray-600">Produto</label>
                    <select 
                      v-model="item.produto_id"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">Selecione um produto</option>
                      <option 
                        v-for="produto in getAvailableProducts(index)" 
                        :key="produto.id" 
                        :value="produto.id"
                      >
                        {{ produto.nome }} - R$ {{ produto.preco.toFixed(2).replace('.', ',') }} (Estoque: {{ produto.estoque }})
                      </option>
                    </select>
                  </div>
                  <div class="w-24">
                    <label class="block text-xs text-gray-600">Quantidade</label>
                    <input 
                      v-model.number="item.quantidade"
                      type="number" 
                      min="1"
                      required
                      class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                  </div>
                  <button 
                    type="button"
                    @click="removeItem(index)"
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
              <button 
                type="button"
                @click="addItem"
                class="mt-2 bg-gray-600 hover:bg-gray-700 text-white px-3 py-2 rounded text-sm"
              >
                <i class="fas fa-plus mr-1"></i> Adicionar Item
              </button>
            </div>

            <div class="flex space-x-3 pt-4">
              <button 
                type="submit"
                :disabled="saving || form.items.length === 0"
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
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

export default {
  name: 'Pedidos',
  setup() {
    const router = useRouter()
    const pedidos = ref([])
    const produtos = ref([])
    const loading = ref(false)
    const error = ref('')
    const saving = ref(false)
    const showCreateModal = ref(false)
    const showEditModal = ref(false)
    const editingPedido = ref(null)
    
    // pagination state
    const currentPage = ref(1)
    const lastPage = ref(1)
    const perPage = ref(15)
    const total = ref(0)

    const pagesToShow = computed(() => {
      const pages = []
      const start = Math.max(1, currentPage.value - 2)
      const end = Math.min(lastPage.value, start + 4)
      for (let p = start; p <= end; p++) pages.push(p)
      return pages
    })

    const form = ref({
      items: [{ produto_id: '', quantidade: 1 }]
    })

    const isAuthenticated = computed(() => !!localStorage.getItem('token'))

    const loadPedidos = async (page = currentPage.value) => {
      if (!isAuthenticated.value) return
      
      loading.value = true
      error.value = ''
      try {
        const response = await api.get('/pedidos', { params: { page, per_page: perPage.value } })
        pedidos.value = response.data.data
        const meta = response.data.meta || {}
        currentPage.value = meta.current_page || page
        lastPage.value = meta.last_page || 1
        perPage.value = meta.per_page || perPage.value
        total.value = meta.total || pedidos.value.length
      } catch (err) {
        error.value = 'Erro ao carregar pedidos: ' + (err.response?.data?.message || err.message)
      } finally {
        loading.value = false
      }
    }

    const loadProdutos = async () => {
      try {
        const response = await api.get('/produtos')
        produtos.value = response.data.data
      } catch (err) {
        console.error('Erro ao carregar produtos:', err)
      }
    }

    const savePedido = async () => {
      saving.value = true
      try {
        const data = {
          items: form.value.items.filter(item => item.produto_id && item.quantidade > 0)
        }

        if (editingPedido.value) {
          await api.put(`/pedidos/${editingPedido.value.id}`, data)
        } else {
          await api.post('/pedidos', data)
        }

        await loadPedidos(currentPage.value)
        closeModal()
      } catch (err) {
        error.value = 'Erro ao salvar pedido: ' + (err.response?.data?.message || err.message)
      } finally {
        saving.value = false
      }
    }

    const editPedido = (pedido) => {
      editingPedido.value = pedido
      form.value = {
        items: pedido.items.map(item => ({
          produto_id: item.produto_id,
          quantidade: item.quantidade
        }))
      }
      showEditModal.value = true
    }

    const cancelPedido = async (id) => {
      if (!confirm('Tem certeza que deseja cancelar este pedido?')) return
      
      try {
        await api.get(`/pedidos/${id}/cancel`)
        await loadPedidos(currentPage.value)
      } catch (err) {
        error.value = 'Erro ao cancelar pedido: ' + (err.response?.data?.message || err.message)
      }
    }

    const addItem = () => {
      form.value.items.push({ produto_id: '', quantidade: 1 })
    }

    const removeItem = (index) => {
      if (form.value.items.length > 1) {
        form.value.items.splice(index, 1)
      }
    }

    const closeModal = () => {
      showCreateModal.value = false
      showEditModal.value = false
      editingPedido.value = null
      form.value = {
        items: [{ produto_id: '', quantidade: 1 }]
      }
    }

    const getTotalPedido = (pedido) => {
      return pedido.items.reduce((total, item) => {
        return total + (item.quantidade * item.preco_unitario)
      }, 0)
    }

    const getStatusClass = (status) => {
      const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
      }
      return classes[status] || 'bg-gray-100 text-gray-800'
    }

    const getStatusLabel = (status) => {
      const labels = {
        pending: 'Pendente',
        completed: 'Concluído',
        cancelled: 'Cancelado'
      }
      return labels[status] || status
    }

    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getAvailableProducts = (currentIndex) => {
      const selectedIds = new Set(
        form.value.items
          .map((it, idx) => (idx === currentIndex ? null : it.produto_id))
          .filter(Boolean)
      )
      return produtos.value.filter(p => !selectedIds.has(p.id))
    }

    const goToPage = (p) => {
      if (p < 1 || p > lastPage.value || p === currentPage.value) return
      loadPedidos(p)
    }

    onMounted(() => {
      loadPedidos()
      loadProdutos()
    })

    return {
      pedidos,
      produtos,
      loading,
      error,
      saving,
      showCreateModal,
      showEditModal,
      form,
      isAuthenticated,
      loadPedidos,
      savePedido,
      editPedido,
      cancelPedido,
      addItem,
      removeItem,
      closeModal,
      getTotalPedido,
      getStatusClass,
      getStatusLabel,
      formatDate,
      getAvailableProducts,
      // pagination
      currentPage,
      lastPage,
      perPage,
      total,
      pagesToShow,
      goToPage
    }
  }
}
</script>
