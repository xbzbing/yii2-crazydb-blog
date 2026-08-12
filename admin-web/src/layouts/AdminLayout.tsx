import { ProLayout, PageContainer } from '@ant-design/pro-components'
import {
  DashboardOutlined,
  FileTextOutlined,
  CommentOutlined,
  FolderOutlined,
  CompassOutlined,
  TagsOutlined,
  TeamOutlined,
  HistoryOutlined,
  SettingOutlined,
  SlidersOutlined,
  LogoutOutlined,
} from '@ant-design/icons'
import { useState, useEffect } from 'react'
import { useNavigate, useLocation, Link } from 'react-router-dom'
import { Dropdown } from 'antd'
import { getCsrfToken } from '../api/client'
import type { MeData } from '../types/api'
import { PageTitleContext } from '../contexts/PageTitleContext'

interface AdminLayoutProps {
  me: MeData['user']
  onLogout: () => void
  children: React.ReactNode
}

interface MenuItem {
  key: string
  path: string
  name: string
  icon?: React.ReactNode
  routes?: MenuItem[]
}

const menuItems: MenuItem[] = [
  { key: '/', path: '/', name: '仪表盘', icon: <DashboardOutlined /> },
  { key: '/posts', path: '/posts', name: '文章管理', icon: <FileTextOutlined /> },
  { key: '/comments', path: '/comments', name: '评论管理', icon: <CommentOutlined /> },
  { key: '/categories', path: '/categories', name: '分类管理', icon: <FolderOutlined /> },
  { key: '/navs', path: '/navs', name: '导航管理', icon: <CompassOutlined /> },
  { key: '/tags', path: '/tags', name: '标签管理', icon: <TagsOutlined /> },
  { key: '/users', path: '/users', name: '用户管理', icon: <TeamOutlined /> },
  { key: '/logs', path: '/logs', name: '日志管理', icon: <HistoryOutlined /> },
  {
    key: '/customize',
    name: '个性化设置',
    icon: <SlidersOutlined />,
    path: '/customize',
    routes: [
      { key: '/customize/config', path: '/customize/config', name: '自定义配置' },
      { key: '/customize/carousel', path: '/customize/carousel', name: '轮播图片' },
    ],
  },
  {
    key: '/config',
    name: '站点配置',
    icon: <SettingOutlined />,
    path: '/config',
    routes: [
      { key: '/config/basic', path: '/config/basic', name: '基本设置' },
      { key: '/config/seo', path: '/config/seo', name: 'SEO 设置' },
      { key: '/config/cache', path: '/config/cache', name: '缓存管理' },
      { key: '/config/env', path: '/config/env', name: '环境信息' },
    ],
  },
]

export default function AdminLayout({ me, onLogout, children }: AdminLayoutProps) {
  const navigate = useNavigate()
  const location = useLocation()

  // 高亮当前路径对应的菜单：进入二级/三级页面（如 /posts/:id/edit、/config/basic）时，
  // 子菜单选中高亮，一级分类（父级）由 antd 自动保持高亮并展开。
  // 路径匹配按最长前缀：/posts/2493/edit → /posts；/config/basic → /config/basic（父级 /config 展开）。
  const pathname = location.pathname
  const matchMenuItem = (items: MenuItem[], path: string): { item: MenuItem; parent?: MenuItem } | null => {
    for (const item of items) {
      // 优先匹配子项（更深层），保证 /config/basic 命中基本设置而非站点配置
      const child = (item.routes || []).find((c) => c.path === path || path.startsWith(`${c.path}/`))
      if (child) return { item: child, parent: item }
      if (item.path === path || (item.path !== '/' && path.startsWith(`${item.path}/`))) {
        return { item }
      }
    }
    return null
  }
  const matched = matchMenuItem(menuItems, pathname)
  const selectedKeys = matched ? [matched.item.key] : []
  const [openKeys, setOpenKeys] = useState<string[]>(matched?.parent ? [matched.parent.key] : [])
  // 二级/更深入页面自身注册的具体标题（如「编辑文章」「新建文章」），作为面包屑最后一级
  const [pageTitle, setPageTitle] = useState<string | null>(null)

  // 路由变化时同步展开父级（如从 /posts 切到 /config/basic）
  useEffect(() => {
    if (matched?.parent) {
      setOpenKeys((prev) => (prev.includes(matched.parent!.key) ? prev : [...prev, matched.parent!.key]))
    }
  }, [pathname])

  // 面包屑：仅二级/更深入页面显示，只展示实际菜单层级（无根节点）。
  // 顶级菜单页（路径即菜单路径，如 /posts 文章管理）不显示。
  // 示例：/posts/create、/posts/2493/edit → 文章管理 / 编辑文章；
  //       /config/basic → 站点配置 / 基本设置（页面标题与菜单项同名时去重）。
  // path 为 router 相对路径，由 Link 客户端导航（自动补 basename），无需写死 /admin。
  const buildBreadcrumb = (path: string): { title: string; path?: string }[] => {
    const m = matchMenuItem(menuItems, path)
    // 顶级菜单页不显示面包屑（路径精确命中顶级菜单项）
    if (!m || (!m.parent && path === m.item.path)) {
      return []
    }
    const crumbs: { title: string; path?: string }[] = []
    if (m.parent) {
      crumbs.push({ title: m.parent.name, path: m.parent.path })
    }
    // 页面标题与菜单项同名（如 站点配置/基本设置）时不再追加，避免重复
    if (pageTitle && pageTitle !== m.item.name) {
      crumbs.push({ title: m.item.name, path: m.item.path })
      crumbs.push({ title: pageTitle })
    } else {
      crumbs.push({ title: m.item.name })
    }
    return crumbs
  }
  const breadcrumb = buildBreadcrumb(pathname)

  // 面包屑渲染：非末级用 Link 客户端导航（避免整页刷新），末级为纯文本
  const breadcrumbItemRender = (
    route: { title?: React.ReactNode; path?: string },
    _params: unknown,
    routes: Array<{ title?: React.ReactNode; path?: string }>,
  ) => {
    const isLast = routes.indexOf(route) === routes.length - 1
    return route.path && !isLast ? (
      <Link to={route.path}>{route.title}</Link>
    ) : (
      <span>{route.title}</span>
    )
  }

  const userMenu = {
    items: [
      { key: 'logout', icon: <LogoutOutlined />, label: '退出登录' },
    ],
    onClick: async ({ key }: { key: string }) => {
      if (key === 'logout') {
        try {
          // /logout 是 POST 路由，走 fetch + CSRF，成功后跳前台登录页
          await fetch('/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': getCsrfToken() || '' },
          })
        } catch {
          /* 即使失败也跳前台登录页 */
        }
        onLogout()
        window.location.href = `/login?redirect=${encodeURIComponent('/admin')}`
      }
    },
  }

  // 点击子菜单：收起父级以外的其他展开项（站点配置是唯一带子菜单的项，点击即收起）
  return (
    <ProLayout
      title="Crazydb-Blog"
      logo="/favicon.ico"
      layout="mix"
      route={{
        path: '/',
        routes: menuItems,
      }}
      location={{ pathname: location.pathname }}
      selectedKeys={selectedKeys}
      openKeys={openKeys}
      onOpenChange={(keys) => {
        // 站点配置路径驱动展开，其余按用户点击
        setOpenKeys(keys === false ? [] : keys)
      }}
      menuItemRender={(item, dom) => (
        <a onClick={() => navigate(item.path || '/')}>{dom}</a>
      )}
      avatarProps={{
        src: me?.avatar || undefined,
        title: me?.nickname || me?.username || '管理员',
        render: (_, dom) => (
          <Dropdown menu={userMenu}>
            <a
              onClick={(e) => e.preventDefault()}
              style={{ display: 'flex', alignItems: 'center', color: 'inherit' }}
            >
              {dom}
            </a>
          </Dropdown>
        ),
      }}
      actionsRender={() => [
        <a
          key="front"
          href="/"
          target="_blank"
          rel="noopener noreferrer"
          onClick={(e) => {
            e.preventDefault()
            window.open('/', '_blank')
          }}
        >
          返回前台
        </a>,
      ]}
      onMenuHeaderClick={() => navigate('/')}
    >
      {/* 面包屑导航：仅二级/更深入页面显示，顶级菜单页不渲染页头；并收紧面包屑与顶部间距。
          itemRender 用 Link 客户端导航，避免整页刷新。 */}
      <PageTitleContext.Provider value={{ setPageTitle }}>
        <PageContainer
          title={false}
          header={breadcrumb.length > 0 ? { style: { padding: '8px 24px 8px' } } : undefined}
          breadcrumb={breadcrumb.length > 0 ? { items: breadcrumb, itemRender: breadcrumbItemRender } : undefined}
        >
          {children}
        </PageContainer>
      </PageTitleContext.Provider>
    </ProLayout>
  )
}
