/**
 * 后台 SPA 挂载根路径。
 *
 * 与 nginx 托管目录、vite build.base、BrowserRouter basename 保持一致。
 * 换目录部署时只需修改这里（构建期由 VITE_ADMIN_BASE 环境变量覆盖）。
 */
export const ADMIN_BASE = (import.meta.env.VITE_ADMIN_BASE as string | undefined)?.replace(/\/+$/, '') || '/admin'

/** API 前缀 = 后台根路径 + /api */
export const ADMIN_API_BASE = `${ADMIN_BASE}/api`
