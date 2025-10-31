import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import Produtos from './views/Produtos.vue'
import Pedidos from './views/Pedidos.vue'
import Login from './views/Login.vue'

const routes = [
  { path: '/', component: Produtos },
  { path: '/produtos', component: Produtos },
  { path: '/pedidos', component: Pedidos },
  { path: '/login', component: Login }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

const app = createApp(App)
app.use(router)
app.mount('#app')
