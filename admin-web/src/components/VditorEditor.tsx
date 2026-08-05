import { useEffect, useRef, useState } from 'react'
import { Spin } from 'antd'
import { getCsrfToken } from '../api/client'
import { ADMIN_BASE } from '../config'

interface VditorEditorProps {
  value: string
  onChange?: (value: string) => void
  height?: number
  placeholder?: string
}

/**
 * Markdown 编辑器（Vditor，即时渲染模式，自带分屏预览与图片上传）。
 *
 * Vditor 资源从既有 /static/vditor（vendored，HTML 后台同源）动态加载：
 * 不打包进 SPA 产物，保持构建体积与版本单源。
 */
export default function VditorEditor({
  value,
  onChange,
  height = 420,
  placeholder = '请输入 Markdown 内容…',
}: VditorEditorProps) {
  const containerRef = useRef<HTMLDivElement>(null)
  const inputRef = useRef<string | null>(null)
  const [ready, setReady] = useState(false)
  const [loaded, setLoaded] = useState(false)
  const initializedRef = useRef(false)
  const vditorRef = useRef<{ destroy: () => void } | null>(null)

  // 注入 Vditor 静态资源（只注入一次；若已全局存在则直接标记就绪）
  useEffect(() => {
    if (window.Vditor) {
      setLoaded(true)
      return
    }
    if (loaded) return
    const link = document.createElement('link')
    link.rel = 'stylesheet'
    link.href = '/static/vditor/dist/index.css'
    document.head.appendChild(link)
    const script = document.createElement('script')
    script.src = '/static/vditor/dist/index.min.js'
    script.onload = () => setLoaded(true)
    document.head.appendChild(script)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [])

  const init = () => {
    if (initializedRef.current || !window.Vditor || !containerRef.current) return
    initializedRef.current = true
    const vditor = new window.Vditor(containerRef.current, {
      cdn: '/static/vditor',
      mode: 'ir',
      height,
      lang: 'zh_CN',
      placeholder,
      value: value || '',
      cache: { enable: false },
      upload: {
        url: `${ADMIN_BASE}/upload/image`,
        fieldName: 'file',
        max: 2 * 1024 * 1024,
        accept: 'image/png, image/jpeg, image/gif, image/webp',
        withCredentials: false,
        headers: {
          'X-CSRF-Token': getCsrfToken() || '',
        },
      },
      input: (v: string) => {
        inputRef.current = v
        onChange?.(v)
      },
      after: () => setReady(true),
    })
    vditorRef.current = vditor
  }

  useEffect(() => {
    if (loaded && window.Vditor) {
      init()
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [loaded])

  // 外部值变化时同步回编辑器（仅当与编辑器当前内容不一致，避免光标跳动）
  useEffect(() => {
    if (ready && window.Vditor && inputRef.current !== value && containerRef.current) {
      const el = containerRef.current.querySelector('.vditor-content') as HTMLElement & {
        _vditor?: { setValue: (v: string) => void }
      } | null
      if (el?._vditor) el._vditor.setValue(value || '')
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [value, ready])

  // 卸载时销毁 Vditor 实例（格式切换 html↔markdown 时组件重挂载）
  useEffect(() => {
    return () => {
      vditorRef.current?.destroy()
      vditorRef.current = null
      initializedRef.current = false
    }
  }, [])

  return (
    <div style={{ position: 'relative' }}>
      <div ref={containerRef} style={{ minHeight: height }} />
      {!ready && (
        <div
          style={{
            position: 'absolute',
            inset: 0,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            background: '#fff',
            zIndex: 1,
          }}
        >
          <Spin tip="编辑器加载中…" />
        </div>
      )}
    </div>
  )
}
