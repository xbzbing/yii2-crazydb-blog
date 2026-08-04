import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// 后台 SPA 构建：产物输出到 ../public/admin（nginx 直接托管 /admin）
export default defineConfig({
  plugins: [react()],
  base: '/admin/',
  build: {
    outDir: '../public/admin',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1500,
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          antd: ['antd', '@ant-design/icons', '@ant-design/pro-components'],
        },
      },
    },
  },
  server: {
    port: 5173,
    // dev 时 API 代理到本地 nginx（同源 cookie + CSRF 一致）
    proxy: {
      '/admin/api': 'http://127.0.0.1',
      '/static': 'http://127.0.0.1',
    },
  },
})
