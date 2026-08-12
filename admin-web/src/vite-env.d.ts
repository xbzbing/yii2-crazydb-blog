/// <reference types="vite/client" />

interface ImportMetaEnv {
  /** 后台 SPA 挂载根路径（如 /admin、/backend），构建期可配置 */
  readonly VITE_ADMIN_BASE?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
