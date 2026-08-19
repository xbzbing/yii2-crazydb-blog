import { useEffect, useState } from 'react'
import { Card, Divider, Form, Input, Select, Button, message, Spin, Row, Col } from 'antd'
import { api } from '../api/client'
import type { ConfigValues } from '../types/api'
import { usePageTitle } from '../contexts/PageTitleContext'

/**
 * 站点配置 - 基本设置。
 * 页头已有面包屑「站点配置 / 基本设置」，此处 Card 不再重复标题；
 * 访问统计分类关键词独立成区块（Divider 分隔），其余样式与之前保持一致。
 */
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
    <Card>
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

        <Divider style={{ margin: '16px 0' }} />

        {/* 访问统计分类关键词：独立区块，与站点基础配置区分 */}
        <div style={{ marginBottom: 16 }}>
          <div
            style={{
              fontSize: 15,
              fontWeight: 600,
              color: 'rgba(0,0,0,0.88)',
              marginBottom: 4,
            }}
          >
            访问统计分类关键词
          </div>
          <div style={{ fontSize: 13, color: 'rgba(0,0,0,0.45)', marginBottom: 16 }}>
            前台访问按 UA 关键词判定为 爬虫 / 脚本 / 正常 三类（英文逗号分隔；留空使用默认值）
          </div>
          <Row gutter={32}>
            <Col xs={24} sm={12}>
              <Form.Item
                name="visit_bot_keywords"
                label="爬虫访问关键词"
                extra="UA 命中任一关键词判定为爬虫访问"
              >
                <Input placeholder="spider,bingbot,bot.html" />
              </Form.Item>
            </Col>
            <Col xs={24} sm={12}>
              <Form.Item
                name="visit_script_keywords"
                label="脚本访问关键词"
                extra="UA 命中任一关键词判定为脚本访问"
              >
                <Input placeholder="python-,curl,wget,axios,java-http-client,java/,headless" />
              </Form.Item>
            </Col>
          </Row>
        </div>

        <div style={{ textAlign: 'center', paddingTop: 8 }}>
          <Button type="primary" htmlType="submit" loading={submitting}>
            保存配置
          </Button>
        </div>
      </Form>
    </Card>
  )
}
