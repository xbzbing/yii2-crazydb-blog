import { useEffect, useState } from 'react'
import { Card, Form, Input, InputNumber, Select, Switch, Button, Space, message, Spin } from 'antd'
import { useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'

export default function NavForm() {
  const { id } = useParams()
  const isEdit = id !== undefined
  const navigate = useNavigate()
  const [form] = Form.useForm()
  const [parents, setParents] = useState<Array<{ id: number; name: string }>>([])
  const [loading, setLoading] = useState(isEdit)
  const [submitting, setSubmitting] = useState(false)
  const isRoute = Form.useWatch('route', form)

  useEffect(() => {
    const load = async () => {
      try {
        let data
        if (isEdit) {
          data = await api.nav(Number(id))
          const n = data.nav
          form.setFieldsValue({
            name: n.name,
            url: n.url,
            route: n.route === 1,
            pid: n.pid || 0,
            sort_order: n.sort_order,
          })
          setParents(data.parents || [])
        } else {
          setParents([])
          form.setFieldsValue({ route: false, pid: 0, sort_order: 0 })
        }
      } catch (e) {
        message.error(e instanceof Error ? e.message : String(e))
      } finally {
        setLoading(false)
      }
    }
    load()
  }, [id])

  const onFinish = async (values: {
    name: string
    url: string
    route: boolean
    pid?: number
    sort_order?: number
  }) => {
    setSubmitting(true)
    try {
      const payload = {
        name: values.name,
        url: values.url,
        route: values.route ? 1 : 0,
        pid: values.pid || 0,
        sort_order: values.sort_order || 0,
      }
      const data = isEdit ? await api.navUpdate(Number(id), payload) : await api.navSave(payload)
      if (data && data.ok === false) {
        message.error(Object.values(data.errors || {}).join('；') || '保存失败。')
        return
      }
      message.success(data?.message || '保存成功。')
      navigate('/navs')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <Spin style={{ margin: 48 }} />

  return (
    <Card title={isEdit ? '编辑导航' : '新建导航'}>
      <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 640 }}>
        <Form.Item name="name" label="导航名称" rules={[{ required: true, message: '请输入导航名称' }]}>
          <Input placeholder="如 首页" />
        </Form.Item>
        <Form.Item name="route" label="系统路由" valuePropName="checked" extra="开启后 URL 填写路由名（如 site/index、post/list）">
          <Switch />
        </Form.Item>
        <Form.Item
          name="url"
          label={isRoute ? '路由名' : 'URL'}
          rules={[{ required: true, message: '请输入 URL 或路由名' }]}
        >
          <Input placeholder={isRoute ? '如 site/index' : '如 https://example.com'} />
        </Form.Item>
        <Form.Item name="pid" label="父导航（仅支持两级）">
          <Select
            style={{ width: 220 }}
            options={[{ value: 0, label: '顶级导航' }, ...parents.map((p) => ({ value: p.id, label: p.name }))]}
          />
        </Form.Item>
        <Form.Item name="sort_order" label="排序（越大越靠前）">
          <InputNumber min={0} style={{ width: 160 }} />
        </Form.Item>
        <Space>
          <Button type="primary" htmlType="submit" loading={submitting}>
            保存
          </Button>
          <Button onClick={() => navigate('/navs')}>返回列表</Button>
        </Space>
      </Form>
    </Card>
  )
}
