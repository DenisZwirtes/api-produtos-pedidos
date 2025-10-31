import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  server: {
    port: 3000,
    host: true, // Permite acesso de fora do container
    watch: {
      // Configuração de polling para Docker (detecta mudanças mais rápido)
      usePolling: true,
      interval: 50, // Verifica mudanças a cada 50ms (mais agressivo)
      binaryInterval: 100 // Para arquivos binários, verifica a cada 100ms
    },
    hmr: {
      // HMR configurado para funcionar em Docker
      // clientPort: porta onde o browser acessa (não a porta interna do container)
      clientPort: 3000,
      host: 'localhost', // Host onde o browser está acessando
      protocol: 'ws', // WebSocket protocol
      overlay: true // Mostra erros em overlay
    },
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  }
})
