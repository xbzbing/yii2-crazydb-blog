import { useEffect, useState } from 'react'
import { Card, Form, Input, Select, Button, message, Spin, Row, Col } from 'antd'
import { api } from '../api/client'
import type { ConfigValues } from '../types/api'
import { usePageTitle } from '../contexts/PageTitleContext'

/** 站点配置 - 基本设置（左右两列，小屏自动切换单列） */
export default function BasicSettings() {
  usePageTitle('基本设置')
  const [form] = Form.useForm()
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [themeOptions, setThemeOptions] = useState({})
  const siteStatus = Form.useWatch('site_status', form) ?? 'running'

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
      <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 860 }}>
        <Row gutter={32}>
          {/* 左列：站点基础 */}
          <Col xs={24} sm={12}>
            <Form.Item name="site_name" label="站点名称">
              <Input placeholder="站点名称" />
            </Form.Item>
            <Form.Item name="admin_email" label="管理员邮箱">
              <Input placeholder="admin@example.com" />
            </Form.Item>
            <Form.Item name="theme" label="前台主题">
              <Select options={Object.entries(themeOptions).map(([v, l]) => ({ value: v, label: l }))} />
            </Form.Item>
            <Form.Item name="site_status" label="站点状态">
              <Select
                options={[
                  { value: 'running', label: '运行中' },
                  { value: 'maintenance', label: '维护中' },
                ]}
              />
            </Form.Item>
            {siteStatus === 'maintenance' && (
              <Form.Item name="maintenance_message" label="维护文案" extra="站点处于维护中时，前台首页会显示该文案">
                <Input placeholder="系统升级中" />
              </Form.Item>
            )}
          </Col>
          {/* 右列：权限控制 */}
          <Col xs={24} sm={12}>
            <Form.Item name="allow_register" label="允许注册">
              <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
            </Form.Item>
            <Form.Item name="allow_comment" label="允许评论">
              <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
            </Form.Item>
            <Form.Item name="need_approve" label="评论需审核">
              <Select options={[{ value: 'open', label: '开启' }, { value: 'close', label: '关闭' }]} />
            </Form.Item>
          </Col>
        </Row>
        <div style={{ textAlign: 'center', paddingTop: 8 }}>
          <Button type="primary" htmlType="submit" loading={submitting}>
            保存配置
          </Button>
        </div>
      </Form>
    </Card>
  )
}
