import { useEffect, useState } from 'react'
import { Card, Form, Input, InputNumber, Select, Button, Space, message, Spin } from 'antd'
import { useNavigate, useParams } from 'react-router-dom'
import { api } from '../api/client'

const { TextArea } = Input

export default function CategoryForm() {
  const { id } = useParams()
  const isEdit = id !== undefined
  const navigate = useNavigate()
  const [form] = Form.useForm()
  const [loading, setLoading] = useState(isEdit)
  const [submitting, setSubmitting] = useState(false)
  const [parents, setParents] = useState<{ value: number; label: string }[]>([])

  useEffect(() => {
    const load = async () => {
      try {
        const res = await api.categories()
        // 顶级分类（pid=0）可作父级；编辑时排除自身
        const list = (res.items || [])
          .filter((c) => c.pid === 0 && (!isEdit || c.id !== Number(id)))
          .map((c) => ({ value: c.id, label: c.name }))
        setParents(list)
      } catch (e) {
        message.warning('父分类加载失败：' + (e instanceof Error ? e.message : String(e)))
      }
    }
    load()

    if (isEdit) {
      api
        .category(Number(id))
        .then((data) => {
          const c = data.category
          form.setFieldsValue({
            name: c.name,
            alias: c.alias,
            desc: c.desc,
            keywords: c.keywords,
            sort_order: c.sort_order,
            pid: c.pid || 0,
          })
        })
        .catch((e) => message.error(e instanceof Error ? e.message : String(e)))
        .finally(() => setLoading(false))
    } else {
      setLoading(false)
    }
  }, [id])

  const onFinish = async (values: {
    name: string
    alias?: string
    desc?: string
    keywords?: string
    sort_order?: number
    pid?: number
  }) => {
    setSubmitting(true)
    try {
      const payload = {
        name: values.name,
        alias: values.alias || '',
        desc: values.desc || '',
        keywords: values.keywords || '',
        sort_order: values.sort_order || 0,
        pid: values.pid || 0,
      }
      const data = isEdit ? await api.categoryUpdate(Number(id), payload) : await api.categorySave(payload)
      if (data && data.ok === false) {
        message.error(Object.values(data.errors || {}).join('；') || '保存失败。')
        return
      }
      message.success(data?.message || '保存成功。')
      navigate('/categories')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <Spin style={{ margin: 48 }} />

  return (
    <Card title={isEdit ? '编辑分类' : '新建分类'}>
      <Form form={form} layout="vertical" onFinish={onFinish} style={{ maxWidth: 640 }}>
        <Form.Item name="name" label="分类名称" rules={[{ required: true, message: '请输入分类名称' }]}>
          <Input placeholder="如 PHP" />
        </Form.Item>
        <Form.Item name="alias" label="别名（留空自动生成）">
          <Input placeholder="URL 别名" />
        </Form.Item>
        <Form.Item name="pid" label="父分类（仅支持两级）">
          <Select
            style={{ width: 220 }}
            options={[{ value: 0, label: '顶级分类' }, ...parents]}
          />
        </Form.Item>
        <Form.Item name="keywords" label="关键词">
          <Input placeholder="SEO 关键词" />
        </Form.Item>
        <Form.Item name="desc" label="描述">
          <TextArea rows={3} placeholder="分类描述" />
        </Form.Item>
        <Form.Item name="sort_order" label="排序（越大越靠前）">
          <InputNumber min={0} style={{ width: 160 }} />
        </Form.Item>
        <Space>
          <Button type="primary" htmlType="submit" loading={submitting}>
            保存
          </Button>
          <Button onClick={() => navigate('/categories')}>返回列表</Button>
        </Space>
      </Form>
    </Card>
  )
}
