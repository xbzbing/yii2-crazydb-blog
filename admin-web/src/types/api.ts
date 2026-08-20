/** 后台 JSON API 统一响应包体 */
export interface ApiEnvelope<T = unknown> {
  ok: boolean
  data: T | null
  error: string | null
  csrf?: string
}

/** 分页元数据 */
export interface Pagination {
  total: number
  page: number
  pageSize: number
  pageCount: number
}

export interface User {
  id: number
  username: string
  nickname: string
  email: string
  website: string | null
  avatar: string | null
  role: number
  status: number
  register_time: number
  update_time: number
  active_time: number
  info: string | null
  is_webmaster?: boolean
}

export interface MeData {
  user: {
    id: number
    username: string
    nickname: string
    avatar: string
    email: string
    role: number
  }
  csrf: string
}

export interface PostItem {
  id: number
  cid: number
  category_name?: string
  author_id: number
  author_name: string
  type: string
  title: string
  alias: string
  format: string
  status: string
  tags: string
  comment_count: number
  view_count: number
  view_uv: number
  is_top: number
  is_locked?: boolean
  post_time: number
  create_time: number
  update_time: number
}

export interface PostDetail {
  id: number
  cid: number
  author_id: number
  author_name: string
  type: string
  title: string
  alias: string
  excerpt: string
  content: string
  format: string
  status: string
  tags: string
  is_locked: boolean
  cover: string
  is_top: number
  post_time: number
}

export interface CommentItem {
  id: number
  pid: number
  uid: number | null
  nickname: string
  email: string
  url: string | null
  ip: string
  user_agent: string
  reply_to: number | null
  content: string
  status: string
  create_time: number
  update_time: number
  post_id?: number | null
  post_title?: string | null
  post_url?: string | null
}

export interface Category {
  id: number
  pid: number
  name: string
  alias: string
  desc: string | null
  display: string
  sort_order: number
  keywords: string
  update_time: number
}

export interface Nav {
  id: number
  pid: number
  name: string
  url: string
  route: number
  sort_order: number
  create_time: number
  update_time: number
}

export interface Tag {
  name: string
  totalCount: number | string
}

export interface LogItem {
  id: number
  uid: number
  nickname: string
  type: string
  action: string
  result: string
  detail: string
  ip?: string
  user_agent?: string
  create_time: number
}

export interface CustomConfigCategory {
  category: string
  count: number
}

export interface CustomConfigItem {
  id: number
  category: string
  key: string
  name: string
  value: string
  data_type: string
  priority: number
  description: string
  create_time: number
  update_time: number
}

export interface VisitTrendItem {
  date: string
  pv: number
  uv: number
  pv_crawler: number
  pv_script: number
  pv_normal: number
}

export interface HourlyData {
  time: string // 'YYYY-MM-DD HH:00'
  pv: number
  uv: number
  ip: number
}

export interface DashboardData {
  postTotal: number
  commentTotal: number
  pendingComments: number
  userTotal: number
  optionTotal: number
  todayPv: number
  todayUv: number
  todayIp: number
  todayCrawler: number
  todayScript: number
  todayNormal: number
  visitTrend: VisitTrendItem[]
  visitHourly: HourlyData[]
}

export interface ConfigValues {
  site_name?: string
  admin_email?: string
  allow_comment?: string
  allow_register?: string
  need_approve?: string
  theme?: string
  site_status?: string
  maintenance_message?: string
  site_analyzer?: string
  visit_bot_keywords?: string
  visit_script_keywords?: string
  seo_title?: string
  seo_keywords?: string
  seo_description?: string
  [key: string]: string | undefined
}

export interface ConfigData {
  values: ConfigValues
  fields: Record<string, { label: string; type: string }>
  themeOptions: Record<string, string>
}

export interface CacheStatus {
  connected: boolean
  error?: string
  redisVersion?: string
  uptime?: number
  usedMemory?: number
  usedMemoryPeak?: number
  maxMemory?: number
  totalKeys?: number
  hits?: number
  misses?: number
  connectedClients?: number
  db?: string
}

export interface EnvData {
  system: {
    os: string
    serverSoftware: string
    serverName: string
    hostname: string
  }
  php: {
    version: string
    sapi: string
    memoryLimit: string
    maxExecutionTime: string
    uploadAllowed: boolean
    uploadMaxSize: string
    postMaxSize: string
    extensions: string
    currentMemoryUsage: string
    peakMemoryUsage: string
  }
  database: {
    version: string
    size: string
    tableCount: number
    error?: string
  }
  vps: {
    load: { '1min': number; '5min': number; '15min': number } | null
    cpuCores: number | null
    memory: {
      total: string
      used: string
      free: string
      usagePercent: number
      swapTotal: string
      swapFree: string
    } | null
    disk: { total: string; free: string; used: string; usagePercent: number } | null
    uptime: string | null
    processes: number | null
    phpProcesses: number | null
    uname: string | null
  }
}

/** 校验失败的响应（ok 包体内 data.ok=false + errors） */
export interface ValidationResult {
  ok: boolean
  message?: string
  errors?: Record<string, string>
  failed?: string[]
  values?: ConfigValues
}

/** OTP 状态 */
export interface OtpStatus {
  otp_enabled: boolean
}

/** OTP setup 响应 */
export interface OtpSetupData {
  uri: string
  secret: string
}
