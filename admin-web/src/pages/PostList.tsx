import { useEffect, useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns, type ProFormInstance } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, Space, Tooltip, Modal, Spin, message } from 'antd'
import { PlusOutlined, EyeOutlined, EditOutlined, DeleteOutlined, ExportOutlined } from '@ant-design/icons'
import { useNavigate, useSearchParams } from 'react-router-dom'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { PostItem, PostPreview } from '../types/api'
import { errMessage, runAction } from '../api/actions'
import { LIST_PAGINATION, LIST_SCROLL_X, toTableData } from '../components/table'

const STATUS_MAP = {
  published: { text: '已发布', color: 'green' },
  draft: { text: '草稿', color: 'default' },
  deleted: { text: '已删除', color: 'red' },
}

// ── 预览正文代码高亮：复用前台 PostShow 同一份 highlight.js 资源（vditor 自带） ──
const HLJS_BASE = '/static/vditor/dist/js/highlight.js'

interface HljsGlobal {
  highlightBlock(el: HTMLElement): void
}
declare global {
  interface Window {
    hljs?: HljsGlobal
  }
}

let hljsLoader: Promise<void> | null = null

/** 按需加载 highlight.js（CSS + JS，加载结果缓存，多次打开 Modal 只加载一次） */
function ensureHljs(): Promise<void> {
  if (window.hljs) return Promise.resolve()
  if (!hljsLoader) {
    hljsLoader = new Promise((resolve, reject) => {
      const link = document.createElement('link')
      link.rel = 'stylesheet'
      link.href = `${HLJS_BASE}/styles/github.css`
      document.head.appendChild(link)
      const script = document.createElement('script')
      script.src = `${HLJS_BASE}/highlight.pack.js`
      script.onload = () => resolve()
      script.onerror = () => {
        hljsLoader = null // 失败后允许下次重试
        reject(new Error('代码高亮脚本加载失败'))
      }
      document.head.appendChild(script)
    })
  }
  return hljsLoader
}

// UEditor 老格式 brush:xxx → highlight.js 语言名（与前台 PostShow 模板保持一致）
const BRUSH_MAP: Record<string, string> = {
  plain: 'plaintext', text: 'plaintext', bash: 'bash', shell: 'bash',
  php: 'php', python: 'python', java: 'java', c: 'c', cpp: 'cpp',
  js: 'javascript', javascript: 'javascript', sql: 'sql', xml: 'xml',
  html: 'xml', css: 'css', json: 'json', ruby: 'ruby', go: 'go',
}

/** 对预览容器内代码块执行语法高亮（新格式 pre>code + 老格式 pre[brush:]） */
function highlightPreview(root: HTMLElement): void {
  const hljs = window.hljs
  if (!hljs) return
  root.querySelectorAll('pre code').forEach((el) => {
    if (el.classList.contains('hljs')) return // 避免重复高亮
    hljs.highlightBlock(el as HTMLElement)
  })
  // UEditor 老格式：<pre class="brush:php;toolbar:false">（无 <code> 子元素）
  root.querySelectorAll('pre[class*="brush:"]').forEach((el) => {
    const pre = el as HTMLElement
    if (pre.querySelector('code')) return
    const m = /brush:\s*([\w-]+)/.exec(pre.className)
    const lang = m ? (BRUSH_MAP[m[1]] || m[1]) : ''
    const code = document.createElement('code')
    code.textContent = pre.textContent
    if (lang) code.className = 'language-' + lang
    pre.textContent = ''
    pre.appendChild(code)
    hljs.highlightBlock(code)
  })
}

export default function PostList() {
  const navigate = useNavigate()
  const actionRef = useRef<ActionType>(null)
  const formRef = useRef<ProFormInstance>(undefined)
  const [searchParams] = useSearchParams()
  const urlTag = searchParams.get('tag') || ''
  const [preview, setPreview] = useState<PostPreview | null>(null)
  const [previewLoading, setPreviewLoading] = useState(false)
  const contentRef = useRef<HTMLDivElement>(null)

  // 预览内容挂载后执行语法高亮（hljs 按需异步加载；Modal 关闭则跳过）
  useEffect(() => {
    if (!preview) return
    let cancelled = false
    ensureHljs()
      .then(() => {
        if (!cancelled && contentRef.current) highlightPreview(contentRef.current)
      })
      .catch(() => {
        // 高亮脚本加载失败时静默降级：纯文本代码块（仍可读）
      })
    return () => {
      cancelled = true
    }
  }, [preview])

  const handlePreview = async (id: number) => {
    setPreviewLoading(true)
    try {
      setPreview(await api.postPreview(id))
    } catch (e) {
      message.error(errMessage(e))
    } finally {
      setPreviewLoading(false)
    }
  }

  // 从标签列表跳转带入的筛选：同步到搜索表单显示（onLoad 时表单已挂载）
  const syncedTagRef = useRef(false)
  const handleLoad = () => {
    if (urlTag && !syncedTagRef.current) {
      syncedTagRef.current = true
      formRef.current?.setFieldsValue({ tag: urlTag })
    }
  }

  const handleDelete = (id: number) =>
    runAction(() => api.postDelete(id), '文章已删除。', () => actionRef.current?.reload())

  const columns: ProColumns<PostItem>[] = [
    { title: 'ID', dataIndex: 'id', width: 55, search: false },
    {
      title: '标题',
      dataIndex: 'title',
      ellipsis: true,
      render: (_, record) => (
        <span style={{ whiteSpace: 'nowrap' }}>
          {record.is_top === 1 && <Tag color="red" style={{ marginRight: 4 }}>置顶</Tag>}
          {record.is_locked && <Tag color="orange" style={{ marginRight: 4 }}>加锁</Tag>}
          <a onClick={() => navigate(`/posts/${record.id}/edit`)}>{record.title}</a>
        </span>
      ),
    },
    {
      title: '分类',
      dataIndex: 'category_name',
      width: 100,
      search: false,
      render: (_, r) => r.category_name || <span style={{ color: '#bbb' }}>-</span>,
    },
    {
      title: '标签',
      dataIndex: 'tag',
      width: 160,
      ellipsis: true,
      render: (_, r) =>
        r.tags ? (
          <span style={{ whiteSpace: 'nowrap' }}>
            {r.tags
              .split(',')
              .map((t) => t.trim())
              .filter(Boolean)
              .map((t) => (
                <Tag key={t} color="blue" style={{ marginRight: 4 }}>
                  {t}
                </Tag>
              ))}
          </span>
        ) : (
          <span style={{ color: '#bbb' }}>-</span>
        ),
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 80,
      valueType: 'select',
      valueEnum: STATUS_MAP,
      render: (_, record) => {
        const s = STATUS_MAP[record.status as keyof typeof STATUS_MAP] || STATUS_MAP.draft
        return <Tag color={s.color}>{s.text}</Tag>
      },
    },
    { title: '评论', dataIndex: 'comment_count', width: 60, search: false },
    { title: '浏览', dataIndex: 'view_count', width: 60, search: false },
    {
      title: '访客',
      dataIndex: 'view_uv',
      width: 80,
      search: false,
      sorter: true,
    },
    {
      title: '发布时间',
      dataIndex: 'post_time',
      width: 150,
      search: false,
      render: (_, r) => (
        <span style={{ whiteSpace: 'nowrap' }}>{r.post_time ? dayjs.unix(r.post_time).format('YYYY-MM-DD HH:mm') : '-'}</span>
      ),
    },
    {
      title: '编辑时间',
      dataIndex: 'update_time',
      width: 150,
      search: false,
      render: (_, r) => (
        <span style={{ whiteSpace: 'nowrap' }}>{r.update_time ? dayjs.unix(r.update_time).format('YYYY-MM-DD HH:mm') : '-'}</span>
      ),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 160,
      fixed: 'right',
      render: (_, record) => {
        const frontUrl =
          record.status === 'published'
            ? record.alias
              ? `/archive/${encodeURIComponent(record.alias)}`
              : `/post/${record.id}`
            : null
        return (
          <Space.Compact>
            <Tooltip title="预览">
              <Button size="small" icon={<EyeOutlined />} onClick={() => handlePreview(record.id)} />
            </Tooltip>
            {frontUrl && (
              <Tooltip title="访问前台">
                <Button size="small" icon={<ExportOutlined />} onClick={() => window.open(frontUrl, '_blank')} />
              </Tooltip>
            )}
            <Tooltip title="编辑">
              <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/posts/${record.id}/edit`)} />
            </Tooltip>
            <Popconfirm
              title="确认删除该文章？"
              description="将同时删除关联评论与标签。"
              onConfirm={() => handleDelete(record.id)}
            >
              <Tooltip title="删除">
                <Button size="small" danger icon={<DeleteOutlined />} />
              </Tooltip>
            </Popconfirm>
          </Space.Compact>
        )
      },
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; sort?: Record<string, string>; [key: string]: unknown }) => {
    const page = params.current || 1
    const pageSize = params.pageSize || 20
    const status = (params.status as string) || ''
    const tag = (params.tag as string) || ''
    // ProTable 服务端排序：sorter 列点击后 sort 为 { field: 'ascend'|'descend' }
    const sortKeys = Object.keys(params.sort || {})
    const sortField = sortKeys[0] || ''
    const sortOrder = sortField ? (params.sort?.[sortField] === 'ascend' ? 'asc' : 'desc') : ''
    const res = await api.posts({
      page,
      status,
      tag,
      pageSize,
      ...(sortField ? { sort: sortField, order: sortOrder } : {}),
    })
    return toTableData(res)
  }

  return (
    <>
      <ProTable
        headerTitle="文章列表"
        actionRef={actionRef}
        formRef={formRef}
        rowKey="id"
        columns={columns}
        request={request}
        params={{ tag: urlTag }}
        onLoad={handleLoad}
        search={{ labelWidth: 'auto' }}
        pagination={LIST_PAGINATION}
        scroll={{ x: LIST_SCROLL_X }}
        columnsState={{
          persistenceKey: 'admin-post-list',
          defaultValue: {
            id: { show: false },
            update_time: { show: false },
          },
        }}
        toolBarRender={() => [
          <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => navigate('/posts/create')}>
            新建文章
          </Button>,
        ]}
      />

      <Modal
        title="文章预览"
        open={preview !== null}
        width={880}
        footer={null}
        onCancel={() => setPreview(null)}
        destroyOnClose
      >
        {preview && (
          <>
            <div className="post-preview-meta">
              {preview.post.is_top === 1 && <Tag color="red">置顶</Tag>}
              {preview.post.is_locked && <Tag color="orange">加锁</Tag>}
              {(() => {
                const s = STATUS_MAP[preview.post.status as keyof typeof STATUS_MAP] || STATUS_MAP.draft
                return <Tag color={s.color}>{s.text}</Tag>
              })()}
              <span className="post-preview-meta-title">{preview.post.title}</span>
            </div>
            <div className="post-preview-meta">
              {preview.post.category_name && <span>分类：{preview.post.category_name}</span>}
              <span>作者：{preview.post.author_name}</span>
              <span>格式：{preview.post.format === 'markdown' ? 'Markdown' : 'HTML'}</span>
              <span>浏览：{preview.post.view_count}</span>
              <span>评论：{preview.post.comment_count}</span>
              <span>发布：{dayjs.unix(preview.post.post_time).format('YYYY-MM-DD HH:mm')}</span>
              {preview.post.tags && (
                <span>
                  标签：
                  {preview.post.tags
                    .split(',')
                    .map((t) => t.trim())
                    .filter(Boolean)
                    .map((t) => (
                      <Tag key={t} color="blue" style={{ marginRight: 4 }}>
                        {t}
                      </Tag>
                    ))}
                </span>
              )}
            </div>
            <Spin spinning={previewLoading}>
              <div
                ref={contentRef}
                className="post-preview-content"
                dangerouslySetInnerHTML={{ __html: preview.html }}
              />
            </Spin>
          </>
        )}
      </Modal>
    </>
  )
}
