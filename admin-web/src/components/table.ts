/** 列表页统一的分页与横向滚动配置 */
export const LIST_PAGINATION = { defaultPageSize: 20, showSizeChanger: false } as const

export const LIST_SCROLL_X = 'max-content'

/** ProTable request 适配：后端 { items, total } → ProTable 要求的 { data, total, success } */
export function toTableData<T>(res: { items: T[]; total: number }) {
  return { data: res.items, total: res.total, success: true as const }
}
