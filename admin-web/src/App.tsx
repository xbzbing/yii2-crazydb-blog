import { lazy, Suspense, useEffect, useState } from 'react'
import { Routes, Route, useNavigate } from 'react-router-dom'
import { Spin } from 'antd'
import { fetchMe } from './api/client'
import type { MeData } from './types/api'
import AdminLayout from './layouts/AdminLayout'

// 页面路由懒加载：拆分为独立 chunk，降低构建峰值内存并加速首屏
const Dashboard = lazy(() => import('./pages/Dashboard'))
const PostList = lazy(() => import('./pages/PostList'))
const PostForm = lazy(() => import('./pages/PostForm'))
const CommentList = lazy(() => import('./pages/CommentList'))
const CategoryList = lazy(() => import('./pages/CategoryList'))
const CategoryForm = lazy(() => import('./pages/CategoryForm'))
const NavList = lazy(() => import('./pages/NavList'))
const NavForm = lazy(() => import('./pages/NavForm'))
const TagList = lazy(() => import('./pages/TagList'))
const UserList = lazy(() => import('./pages/UserList'))
const LogList = lazy(() => import('./pages/LogList'))
const CustomConfigList = lazy(() => import('./pages/CustomConfigList'))
const CarouselManage = lazy(() => import('./pages/CarouselManage'))
const BasicSettings = lazy(() => import('./pages/BasicSettings'))
const AuthSettings = lazy(() => import('./pages/AuthSettings'))
const SeoSettings = lazy(() => import('./pages/SeoSettings'))
const CachePage = lazy(() => import('./pages/CachePage'))
const EnvPage = lazy(() => import('./pages/EnvPage'))

function PageLoading() {
  return (
    <div style={{ display: 'flex', padding: 48, justifyContent: 'center' }}>
      <Spin />
    </div>
  )
}

type MeUser = MeData['user']

export default function App() {
  const [me, setMe] = useState<MeUser | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    // 401 事件 → 清态跳前台登录页（登录后 redirect 回后台）
    const onUnauthorized = () => {
      setMe(null)
      window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname + window.location.hash)}`
    }
    window.addEventListener('admin:unauthorized', onUnauthorized)
    fetchMe()
      .then((data) => setMe(data ? data.user : null))
      .finally(() => setLoading(false))
    return () => window.removeEventListener('admin:unauthorized', onUnauthorized)
  }, [])

  if (loading) {
    return (
      <div style={{ display: 'flex', height: '100vh', alignItems: 'center', justifyContent: 'center' }}>
        <Spin size="large" />
      </div>
    )
  }

  if (!me) {
    // 未登录：跳前台登录页（session 登录，登录成功 redirect 回后台）
    const target = `/login?redirect=${encodeURIComponent('/admin' + window.location.hash)}`
    if (window.location.pathname !== '/login') {
      window.location.replace(target)
      return null
    }
    return (
      <div style={{ display: 'flex', height: '100vh', alignItems: 'center', justifyContent: 'center' }}>
        <Spin size="large" />
      </div>
    )
  }

  return (
    <AdminLayout me={me} onLogout={() => setMe(null)}>
      <Suspense fallback={<PageLoading />}>
        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/posts" element={<PostList />} />
          <Route path="/posts/create" element={<PostForm />} />
          <Route path="/posts/:id/edit" element={<PostForm />} />
          <Route path="/comments" element={<CommentList />} />
          <Route path="/categories" element={<CategoryList />} />
          <Route path="/categories/create" element={<CategoryForm />} />
          <Route path="/categories/:id/edit" element={<CategoryForm />} />
          <Route path="/navs" element={<NavList />} />
          <Route path="/navs/create" element={<NavForm />} />
          <Route path="/navs/:id/edit" element={<NavForm />} />
          <Route path="/tags" element={<TagList />} />
          <Route path="/users" element={<UserList />} />
          <Route path="/logs" element={<LogList />} />
          <Route path="/customize/config" element={<CustomConfigList />} />
          <Route path="/customize/carousel" element={<CarouselManage />} />
          <Route path="/config" element={<NavigateToBasic />} />
          <Route path="/config/basic" element={<BasicSettings />} />
          <Route path="/config/auth" element={<AuthSettings />} />
          <Route path="/config/seo" element={<SeoSettings />} />
          <Route path="/config/cache" element={<CachePage />} />
          <Route path="/config/env" element={<EnvPage />} />
          <Route path="*" element={<div style={{ padding: 48 }}>页面不存在</div>} />
        </Routes>
      </Suspense>
    </AdminLayout>
  )
}

function NavigateToBasic() {
  const navigate = useNavigate()
  useEffect(() => {
    navigate('/config/basic', { replace: true })
  }, [])
  return null
}
