import { useEffect, useState } from 'react'
import { Card, Tabs, Form, Input, Select, Button, Space, message, Spin, Typography } from 'antd'
import { useLocation, useNavigate } from 'react-router-dom'
import { api } from '../api/client'
import type { ConfigValues } from '../types/api'

const { TextArea } = Input

/**
 * 站点配置：子菜单（基本设置 / SEO 设置 / 缓存管理 / 环境信息）。
 * 保持仪表盘页面不变，仅重构配置页为分组导航。
 */
export default function ConfigPage() {
  const [form] = Form.useForm()
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [themeOptions, setThemeOptions] = useState({})
  const navigate = useNavigate()
  const location = useLocation()

  // activeKey 由受控 Tab 状态驱动，子路由（cache/env）也能映射到对应 Tab
  const [tabKey, setTabKey] = useState(
    location.pathname === '/config/cache'
      ? 'cache'
      : location.pathname === '/config/env'
        ? 'env'
        : 'basic',
  )

  const handleTabChange = (key: string) => {
    setTabKey(key)
    if (key === 'cache') navigate('/config/cache')
    else if (key === 'env') navigate('/config/env')
    else navigate('/config')
  }

  // 直接访问子路由时同步 Tab
  useEffect(() => {
    const k = location.pathname === '/config/cache'
      ? 'cache'
      : location.pathname === '/config/env'
        ? 'env'
        : 'basic'
    setTabKey(k)
  }, [location.pathname])

  useEffect(() => {
    api
      .config()
      .then((data) => {
        setThemeOptions(data.themeOptions || {})
        form.setFieldsValue(data.values || {})
      })
      .catch((e) => message.error(e.message))
      .finally(() => setLoading(false))
  }, [])

  const onFinish = async (values: ConfigValues) => {
    setSubmitting(true)
    try {
      // 两个 Tab 共用同一 form 存储：合并保留的字段，避免卸载端字段被置空
      const merged = { ...form.getFieldsValue(), ...values }
      const data = await api.configSave(merged)
      if (data && data.ok === false) {
        message.error(data.message || '部分配置保存失败。')
        return
      }
      message.success(data?.message || '配置已保存。')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  const tabItems = [
    {
      key: 'basic',
      label: '基本设置',
      children: (
        <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 720 }}>
          <Typography.Text strong>站点信息</Typography.Text>
          <Form.Item name="site_name" label="站点名称">
            <Input placeholder="站点名称" />
          </Form.Item>
          <Form.Item name="admin_email" label="管理员邮箱">
            <Input placeholder="admin@example.com" />
          </Form.Item>
          <Form.Item name="theme" label="前台主题">
            <Select options={Object.entries(themeOptions).map(([v, l]) => ({ value: v, label: l }))} />
          </Form.Item>
          <Form.Item name="allow_comment" label="允许评论">
            <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
          </Form.Item>
          <Form.Item name="allow_register" label="允许注册">
            <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
          </Form.Item>
          <Form.Item name="need_approve" label="评论需审核">
            <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
          </Form.Item>
          <Space>
            <Button type="primary" htmlType="submit" loading={submitting}>
              保存配置
            </Button>
          </Space>
        </Form>
      ),
    },
    {
      key: 'seo',
      label: 'SEO 设置',
      children: (
        <Form
          form={form}
          layout="vertical"
          onFinish={onFinish}
          style={{ maxWidth: 720 }}
        >
          <Typography.Text strong>SEO 设置</Typography.Text>
          <Form.Item name="seo_title" label="SEO 标题">
            <Input placeholder="SEO 标题" />
          </Form.Item>
          <Form.Item name="seo_keywords" label="SEO 关键词">
            <Input placeholder="逗号分隔的关键词" />
          </Form.Item>
          <Form.Item name="seo_description" label="SEO 描述">
            <TextArea rows={3} placeholder="SEO 描述" />
          </Form.Item>
          <Space>
            <Button type="primary" htmlType="submit" loading={submitting}>
              保存配置
            </Button>
          </Space>
        </Form>
      ),
    },
  ]

  if (loading) return <Spin style={{ margin: 48 }} />

  return (
    <Card>
      <Tabs
        activeKey={tabKey}
        onChange={handleTabChange}
        items={tabItems}
      />
    </Card>
  )
}
