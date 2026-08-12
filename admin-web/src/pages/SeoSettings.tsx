import { useEffect, useState } from 'react'
import { Card, Form, Input, Button, Space, message, Spin, Typography } from 'antd'
import { api } from '../api/client'
import type { ConfigValues } from '../types/api'

const { TextArea } = Input

/** 站点配置 - SEO 设置 */
export default function SeoSettings() {
  const [form] = Form.useForm()
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)

  useEffect(() => {
    api
      .config()
      .then((data) => {
        form.setFieldsValue({
          seo_title: data?.values?.seo_title,
          seo_keywords: data?.values?.seo_keywords,
          seo_description: data?.values?.seo_description,
        })
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
    <Card title="SEO 设置">
      <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 720 }}>
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
    </Card>
  )
}
