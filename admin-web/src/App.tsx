import { useEffect, useState } from 'react'
import { Routes, Route, useNavigate } from 'react-router-dom'
import { Spin } from 'antd'
import { fetchMe } from './api/client'
import type { MeData } from './types/api'
import AdminLayout from './layouts/AdminLayout'
import LoginPage from './pages/Login'
import Dashboard from './pages/Dashboard'
import PostList from './pages/PostList'
import PostForm from './pages/PostForm'
import CommentList from './pages/CommentList'
import CategoryList from './pages/CategoryList'
import CategoryForm from './pages/CategoryForm'
import NavList from './pages/NavList'
import NavForm from './pages/NavForm'
import TagList from './pages/TagList'
import UserList from './pages/UserList'
import LogList from './pages/LogList'
import BasicSettings from './pages/BasicSettings'
import SeoSettings from './pages/SeoSettings'
import CachePage from './pages/CachePage'
import EnvPage from './pages/EnvPage'

type MeUser = MeData['user']

export default function App() {
  const [me, setMe] = useState<MeUser | null>(null)
  const [loading, setLoading] = useState(true)
  const navigate = useNavigate()

  useEffect(() => {
    // 401 事件 → 清态跳登录
    const onUnauthorized = () => {
      setMe(null)
      navigate('/login', { replace: true })
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
    return (
      <Routes>
        <Route path="/login" element={<LoginPage onLoggedIn={(user) => setMe(user)} />} />
        <Route path="*" element={<NavigateToLogin />} />
      </Routes>
    )
  }

  return (
    <AdminLayout me={me} onLogout={() => setMe(null)}>
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
        <Route path="/config" element={<NavigateToBasic />} />
        <Route path="/config/basic" element={<BasicSettings />} />
        <Route path="/config/seo" element={<SeoSettings />} />
        <Route path="/config/cache" element={<CachePage />} />
        <Route path="/config/env" element={<EnvPage />} />
        <Route path="*" element={<div style={{ padding: 48 }}>页面不存在</div>} />
      </Routes>
    </AdminLayout>
  )
}

function NavigateToLogin() {
  const navigate = useNavigate()
  useEffect(() => {
    navigate('/login', { replace: true })
  }, [])
  return null
}

function NavigateToBasic() {
  const navigate = useNavigate()
  useEffect(() => {
    navigate('/config/basic', { replace: true })
  }, [])
  return null
}
