import { ProLayout } from '@ant-design/pro-components'
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
  LogoutOutlined,
} from '@ant-design/icons'
import { useNavigate, useLocation } from 'react-router-dom'
import { Dropdown } from 'antd'
import { getCsrfToken } from '../api/client'
import type { MeData } from '../types/api'

interface AdminLayoutProps {
  me: MeData['user']
  onLogout: () => void
  children: React.ReactNode
}

const menuItems = [
  { path: '/', name: '仪表盘', icon: <DashboardOutlined /> },
  { path: '/posts', name: '文章管理', icon: <FileTextOutlined /> },
  { path: '/comments', name: '评论管理', icon: <CommentOutlined /> },
  { path: '/categories', name: '分类管理', icon: <FolderOutlined /> },
  { path: '/navs', name: '导航管理', icon: <CompassOutlined /> },
  { path: '/tags', name: '标签管理', icon: <TagsOutlined /> },
  { path: '/users', name: '用户管理', icon: <TeamOutlined /> },
  { path: '/logs', name: '日志管理', icon: <HistoryOutlined /> },
  {
    name: '站点配置',
    icon: <SettingOutlined />,
    path: '/config',
    children: [
      { path: '/config/basic', name: '基本设置' },
      { path: '/config/seo', name: 'SEO 设置' },
      { path: '/config/cache', name: '缓存管理' },
      { path: '/config/env', name: '环境信息' },
    ],
  },
]

export default function AdminLayout({ me, onLogout, children }: AdminLayoutProps) {
  const navigate = useNavigate()
  const location = useLocation()

  // 受控高亮：始终选中当前 path；站点配置子页时保持父级展开
  const pathname = location.pathname
  const selectedKeys = [pathname]
  const openKeys = pathname.startsWith('/config') ? ['/config'] : []

  const userMenu = {
    items: [
      { key: 'logout', icon: <LogoutOutlined />, label: '退出登录' },
    ],
    onClick: async ({ key }: { key: string }) => {
      if (key === 'logout') {
        try {
          // /site/logout 是 POST 路由，走 fetch + CSRF，成功后回登录页
          await fetch('/site/logout', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': getCsrfToken() || '' },
          })
        } catch {
          /* 即使失败也回登录页 */
        }
        onLogout()
        window.location.replace('/admin/login')
      }
    },
  }

  return (
    <ProLayout
      title="Crazydb-Blog"
      logo="/favicon.ico"
      layout="mix"
      route={{
        path: '/',
        routes: [
          {
            path: '/posts',
            name: '内容管理',
            routes: [
              { path: '/posts', name: '文章管理', icon: <FileTextOutlined /> },
              { path: '/comments', name: '评论管理', icon: <CommentOutlined /> },
              { path: '/categories', name: '分类管理', icon: <FolderOutlined /> },
              { path: '/navs', name: '导航管理', icon: <CompassOutlined /> },
              { path: '/tags', name: '标签管理', icon: <TagsOutlined /> },
            ],
          },
          { path: '/users', name: '用户管理', icon: <TeamOutlined /> },
          { path: '/logs', name: '日志管理', icon: <HistoryOutlined /> },
          { path: '/config', name: '站点配置', icon: <SettingOutlined /> },
        ],
      }}
      location={{ pathname: location.pathname }}
      selectedKeys={selectedKeys}
      openKeys={openKeys}
      onOpenChange={(keys) => {
        // 展开状态交由路径驱动：站点配置子页保持展开，其他收起
        void keys
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
      menuDataRender={() => menuItems}
      onMenuHeaderClick={() => navigate('/')}
    >
      <div style={{ padding: 16 }}>{children}</div>
    </ProLayout>
  )
}
