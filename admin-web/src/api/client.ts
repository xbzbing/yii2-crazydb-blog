/**
 * 后台 JSON API 客户端。
 *
 * 约定：
 * - 所有写请求（POST）自动携带 X-CSRF-Token header（从 <ADMIN_BASE>/api/me 获取）
 * - 响应包体统一 {ok, data, error}
 * - 401 时清空登录态并跳转登录页
 */
import type {
  ApiEnvelope,
  CacheStatus,
  Category,
  CommentItem,
  ConfigData,
  ConfigValues,
  CustomConfigCategory,
  CustomConfigItem,
  DashboardData,
  EnvData,
  LogItem,
  MeData,
  Nav,
  Pagination,
  PostDetail,
  PostItem,
  Tag,
  User,
  ValidationResult,
} from '../types/api'
import { ADMIN_API_BASE } from '../config'

const BASE = ADMIN_API_BASE

let csrfToken: string | null = null

/** 供编辑器等需要手动带 CSRF 的场景取用 */
export function getCsrfToken(): string | null {
  return csrfToken
}

/** 获取当前登录管理员（未登录返回 null，但会回写 401 响应里的 csrf） */
export async function fetchMe(): Promise<MeData | null> {
  const res = await fetch(`${BASE}/me`, { credentials: 'same-origin' })
  const json = (await res.json().catch(() => null)) as ApiEnvelope<MeData> | null
  if (!json) return null
  if (typeof json.data?.csrf === 'string') csrfToken = json.data.csrf
  if (typeof json.csrf === 'string') csrfToken = json.csrf
  if (json.ok) return json.data
  return null
}

interface RequestOptions {
  method?: string
  body?: unknown
  headers?: Record<string, string>
}

async function request<T = unknown>(path: string, options: RequestOptions = {}): Promise<T> {
  const { method = 'GET', body, headers = {} } = options
  const finalHeaders: Record<string, string> = { ...headers }
  const opts: RequestInit = {
    method,
    credentials: 'same-origin',
    headers: finalHeaders,
  }
  if (method !== 'GET' && method !== 'HEAD') {
    if (csrfToken) finalHeaders['X-CSRF-Token'] = csrfToken
    if (body !== undefined) finalHeaders['Content-Type'] = 'application/json'
  }
  if (body !== undefined) opts.body = typeof body === 'string' ? body : JSON.stringify(body)

  const res = await fetch(`${BASE}${path}`, opts)
  let json: ApiEnvelope<T> | null = null
  try {
    json = (await res.json()) as ApiEnvelope<T>
  } catch {
    /* 非 JSON 响应 */
  }

  if (res.status === 401) {
    // 会话过期 → 清空本地态（由 Router 跳转登录页）
    window.dispatchEvent(new CustomEvent('admin:unauthorized'))
    throw new Error(json?.error || '登录已过期，请重新登录。')
  }
  if (res.status === 422) {
    throw new Error(json?.error || 'CSRF 校验失败，请刷新页面后重试。')
  }
  if (json && json.ok === false) {
    const err = new Error(json.error || '请求失败。') as Error & { payload?: ApiEnvelope<T> }
    err.payload = json
    throw err
  }
  if (json && json.ok === true) return json.data as T
  // 非标准包体兜底
  if (res.ok) return json as unknown as T
  throw new Error(`请求失败（HTTP ${res.status}）。`)
}

interface ListData<T> extends Pagination {
  items: T[]
  status?: string
  types?: string[]
}

export const api = {
  me: fetchMe,
  dashboard: (days = 14) => request<DashboardData>(`/dashboard?days=${days}`),

  posts: (params: Record<string, string | number> = {}) =>
    request<ListData<PostItem>>(`/posts?${new URLSearchParams(params as Record<string, string>)}`),
  post: (id: number) =>
    request<{ post: PostDetail; categories: Record<string, string> }>(`/post/${id}`),
  postSave: (data: Record<string, unknown>) => request<ValidationResult>('/post/save', { method: 'POST', body: data }),
  postUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/post/update/${id}`, { method: 'POST', body: data }),
  postDelete: (id: number) => request<{ message: string }>(`/post/delete/${id}`, { method: 'POST' }),

  comments: (params: Record<string, string | number> = {}) =>
    request<ListData<CommentItem>>(`/comments?${new URLSearchParams(params as Record<string, string>)}`),
  commentAction: (action: string, id: number) =>
    request<{ message: string }>(`/comment/${action}/${id}`, { method: 'POST' }),
  comment: (id: number) => request<{ comment: CommentItem }>(`/comment/${id}`),
  commentUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/comment/update/${id}`, { method: 'POST', body: data }),

  categories: () => request<ListData<Category>>('/categories'),
  category: (id: number) => request<{ category: Category }>(`/category/${id}`),
  categorySave: (data: Record<string, unknown>) =>
    request<ValidationResult>('/category/save', { method: 'POST', body: data }),
  categoryUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/category/update/${id}`, { method: 'POST', body: data }),
  categoryDelete: (id: number) => request<{ message: string }>(`/category/delete/${id}`, { method: 'POST' }),

  navs: () => request<ListData<Nav>>('/navs'),
  nav: (id: number) =>
    request<{ nav: Nav; parents: Array<{ id: number; name: string }> }>(`/nav/${id}`),
  navSave: (data: Record<string, unknown>) =>
    request<ValidationResult>('/nav/save', { method: 'POST', body: data }),
  navUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/nav/update/${id}`, { method: 'POST', body: data }),
  navDelete: (id: number) => request<{ message: string }>(`/nav/delete/${id}`, { method: 'POST' }),

  tags: () => request<ListData<Tag>>('/tags'),
  tagDelete: (name: string) =>
    request<{ message: string }>(`/tag/delete/${encodeURIComponent(name)}`, { method: 'POST' }),

  users: (params: Record<string, string | number> = {}) =>
    request<ListData<User>>(`/users?${new URLSearchParams(params as Record<string, string>)}`),
  userAction: (action: string, id: number) =>
    request<{ message: string }>(`/user/${action}/${id}`, { method: 'POST' }),
  userUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/user/update/${id}`, { method: 'POST', body: data }),

  logs: (params: Record<string, string | number> = {}) =>
    request<ListData<LogItem> & { types?: string[]; type?: string }>(
      `/logs?${new URLSearchParams(params as Record<string, string>)}`,
    ),
  logClear: () => request<{ message: string }>('/logs/clear', { method: 'POST' }),

  customConfigCategories: () =>
    request<{ items: CustomConfigCategory[] }>('/custom-configs/categories'),
  customConfigs: (params: Record<string, string | number> = {}) =>
    request<ListData<CustomConfigItem>>(
      `/custom-configs?${new URLSearchParams(params as Record<string, string>)}`,
    ),
  customConfig: (id: number) =>
    request<{ config: CustomConfigItem }>(`/custom-config/${id}`),
  customConfigSave: (data: Record<string, unknown>) =>
    request<ValidationResult>('/custom-config/save', { method: 'POST', body: data }),
  customConfigUpdate: (id: number, data: Record<string, unknown>) =>
    request<ValidationResult>(`/custom-config/update/${id}`, { method: 'POST', body: data }),
  customConfigDelete: (id: number) =>
    request<{ message: string }>(`/custom-config/delete/${id}`, { method: 'POST' }),

  config: () => request<ConfigData>('/config'),
  configSave: (data: ConfigValues) => request<ValidationResult>('/config/save', { method: 'POST', body: data }),

  cacheStatus: () => request<CacheStatus>('/cache'),
  cacheClear: () => request<{ message: string }>('/cache/clear', { method: 'POST' }),

  env: () => request<EnvData>('/env'),
}
