import { createContext, useContext, useEffect } from 'react'

/**
 * 页面标题上下文：二级/更深入页面用它注册具体标题（如「编辑文章」「新建文章」），
 * 供 AdminLayout 渲染为面包屑最后一级。
 */
export const PageTitleContext = createContext<{
  setPageTitle: (title: string | null) => void
}>({
  setPageTitle: () => {},
})

export function usePageTitle(title: string | null) {
  const { setPageTitle } = useContext(PageTitleContext)
  useEffect(() => {
    setPageTitle(title)
    return () => setPageTitle(null)
  }, [title, setPageTitle])
}
