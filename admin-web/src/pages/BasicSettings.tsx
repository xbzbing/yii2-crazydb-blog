import { useEffect, useState } from 'react'
import { Card, Form, Input, Select, Button, Space, message, Spin } from 'antd'
import { api } from '../api/client'
import type { ConfigValues } from '../types/api'
import { usePageTitle } from '../contexts/PageTitleContext'

/** 站点配置 - 基本设置 */
export default function BasicSettings() {
  usePageTitle('基本设置')
  const [form] = Form.useForm()
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [themeOptions, setThemeOptions] = useState({})

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
      // 只提交本页字段，其余字段保持原值
      const all = await api.config().catch(() => ({ values: {} }))
      const merged = { ...(all?.values || {}), ...values }
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

  if (loading) return <Spin style={{ margin: 48 }} />

  return (
    <Card title="基本设置">
      <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 720 }}>
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
    </Card>
  )
}
