/** Vditor 全局对象声明（vendored 脚本挂到 window） */
interface VditorOptions {
  cdn?: string
  mode?: string
  height?: number
  lang?: string
  placeholder?: string
  value?: string
  cache?: { enable: boolean }
  upload?: {
    url?: string
    fieldName?: string
    max?: number
    accept?: string
    withCredentials?: boolean
    headers?: Record<string, string>
  }
  input?: (value: string) => void
  after?: () => void
}

interface VditorInstance {
  setValue: (value: string) => void
  getValue: () => string
}

interface VditorConstructor {
  new (el: HTMLElement | string, options: VditorOptions): VditorInstance
}

interface Window {
  Vditor?: VditorConstructor
}
