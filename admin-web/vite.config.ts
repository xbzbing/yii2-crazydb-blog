import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// 后台 SPA 构建：产物输出到 ../public/admin（nginx 直接托管 /admin）。
// 换部署目录时用 VITE_ADMIN_BASE 环境变量（与 src/config.ts 保持一致）。
const adminBase = process.env.VITE_ADMIN_BASE || '/admin/'

export default defineConfig({
  plugins: [react()],
  base: adminBase,
  build: {
    outDir: '../public/admin',
    emptyOutDir: true,
    chunkSizeWarningLimit: 1500,
    rollupOptions: {
      maxParallelFileOps: 2,
      output: {
        // 只拆分 react 与图表引擎。antd 走默认 tree-shaking（ESM 按需），
        // 不强制整包拆分，避免单 chunk 过大导致低内存机器构建 OOM。
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          charts: ['@ant-design/plots'],
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
