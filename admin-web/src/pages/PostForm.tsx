import { useEffect, useState } from 'react'
import { Card, Form, Input, Select, Switch, Button, Space, message, Spin, Upload, Row, Col, Tooltip, Checkbox } from 'antd'
import { PlusOutlined, LoadingOutlined } from '@ant-design/icons'
import { useNavigate, useParams } from 'react-router-dom'
import { api, getCsrfToken } from '../api/client'
import VditorEditor from '../components/VditorEditor'

export default function PostForm() {
  const { id } = useParams()
  const isEdit = id !== undefined
  const navigate = useNavigate()
  const [form] = Form.useForm()
  const [categories, setCategories] = useState({})
  const [loading, setLoading] = useState(isEdit)
  const [submitting, setSubmitting] = useState(false)
  const [content, setContent] = useState('')
  const [excerpt, setExcerpt] = useState('')
  const [format, setFormat] = useState('html')
  const [cover, setCover] = useState('')
  const [coverUploading, setCoverUploading] = useState(false)
  const [autoCover, setAutoCover] = useState(false)

  useEffect(() => {
    // 拉分类下拉
    api
      .categories()
      .then((res) => {
        const map: Record<number, string> = {}
        ;(res.items || []).forEach((c) => (map[c.id] = c.name))
        setCategories(map)
      })
      .catch((e) => message.warning('分类加载失败：' + (e instanceof Error ? e.message : String(e))))

    if (isEdit) {
      api
        .post(Number(id))
        .then((data) => {
          const p = data.post
          setContent(p.content || '')
          setExcerpt(p.excerpt || '')
          setCover(p.cover || '')
          setFormat(p.format)
          form.setFieldsValue({
            title: p.title,
            alias: p.alias,
            cid: p.cid || undefined,
            status: p.status,
            format: p.format,
            tags: p.tags,
            author_name: p.author_name,
            is_top: p.is_top === 1,
            password: p.password || '',
          })
        })
        .catch((e) => message.error(e instanceof Error ? e.message : String(e)))
        .finally(() => setLoading(false))
    } else {
      form.setFieldsValue({ status: 'draft', format: 'html', is_top: false })
      setLoading(false)
    }
  }, [id])

  const onFinish = async (values: {
    title: string
    alias?: string
    cid?: number
    status: string
    format: string
    tags?: string
    author_name?: string
    is_top?: boolean
    password?: string
  }) => {
    setSubmitting(true)
    try {
      const payload = {
        title: values.title,
        alias: values.alias || '',
        cid: values.cid || 0,
        status: values.status,
        format: values.format,
        tags: values.tags || '',
        author_name: values.author_name || '',
        cover,
        auto_cover: autoCover ? 1 : 0,
        excerpt,
        content,
        is_top: values.is_top ? 1 : 0,
        password: values.password || '',
        // post_time 由后端处理：新建取当前时间，编辑保持原值（不传则后端兜底）
      }
      const data = isEdit ? await api.postUpdate(Number(id), payload) : await api.postSave(payload)
      if (data && data.ok === false) {
        message.error(Object.values(data.errors || {}).join('；') || '保存失败。')
        return
      }
      message.success(data?.message || '保存成功。')
      navigate('/posts')
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) return <Spin style={{ margin: 48 }} />

  return (
    <Card title={isEdit ? '编辑文章' : '新建文章'}>
      <Form form={form} layout="horizontal" onFinish={onFinish} style={{ maxWidth: 960 }}>
        <Row gutter={[16, 0]}>
          <Col span={24}>
            <Form.Item name="title" label="标题" labelCol={{ span: 2 }} wrapperCol={{ span: 22 }} rules={[{ required: true, message: '请输入标题' }]}>
              <Input placeholder="文章标题" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="alias" label="别名" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Input placeholder="URL 别名（留空自动生成）" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="author_name" label="笔名" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Input placeholder="作者笔名" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="is_top" label="置顶" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Switch />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="cid" label="分类" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Select
                allowClear
                placeholder="选择分类"
                options={Object.entries(categories).map(([v, l]) => ({ value: Number(v), label: l }))}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="status" label="状态" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Select
                options={[
                  { value: 'published', label: '已发布' },
                  { value: 'draft', label: '草稿' },
                  { value: 'hidden', label: '隐藏' },
                  { value: 'deleted', label: '已删除' },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="format" label="格式" labelCol={{ span: 7 }} wrapperCol={{ span: 17 }}>
              <Select
                onChange={setFormat}
                options={[
                  { value: 'html', label: 'HTML' },
                  { value: 'markdown', label: 'Markdown' },
                ]}
              />
            </Form.Item>
          </Col>
          <Col span={24}>
            <Form.Item
              name="tags"
              label={
                <Tooltip title="多个标签用英文逗号分隔，最多 5 个">
                  <span>标签</span>
                </Tooltip>
              }
              labelCol={{ span: 2 }}
              wrapperCol={{ span: 22 }}
            >
              <Input placeholder="如 php, yii3, 博客" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="password"
              label={
                <Tooltip title="设为「隐藏」状态并填写密码后，前台需输入密码才能查看全文">
                  <span>访问密码</span>
                </Tooltip>
              }
              labelCol={{ span: 7 }}
              wrapperCol={{ span: 17 }}
            >
              <Input.Password placeholder="留空表示无需密码" autoComplete="new-password" />
            </Form.Item>
          </Col>
        </Row>
        <Form.Item label="封面图片" labelCol={{ span: 2 }} wrapperCol={{ span: 22 }}>
          <Space align="start">
            <Upload
              name="file"
              accept="image/png, image/jpeg, image/gif, image/webp"
              listType="picture-card"
              showUploadList={false}
              action="/admin/upload/image"
              headers={{ 'X-CSRF-Token': getCsrfToken() || '' }}
              onChange={(info) => {
                if (info.file.status === 'uploading') {
                  setCoverUploading(true)
                  return
                }
                if (info.file.status === 'done') {
                  const resp = info.file.response
                  if (resp && resp.code === 0 && resp.data?.url) {
                    setCover(resp.data.url)
                    message.success('封面上传成功。')
                  } else {
                    message.error(resp?.msg || '封面上传失败。')
                  }
                  setCoverUploading(false)
                } else if (info.file.status === 'error') {
                  setCoverUploading(false)
                  message.error('封面上传失败。')
                }
              }}
            >
              {cover ? (
                <img
                  src={cover}
                  alt="封面"
                  style={{ width: 104, height: 104, objectFit: 'cover', borderRadius: 8 }}
                  onClick={(e) => e.stopPropagation()}
                />
              ) : (
                <div>
                  {coverUploading ? <LoadingOutlined /> : <PlusOutlined />}
                  <div style={{ marginTop: 8 }}>上传</div>
                </div>
              )}
            </Upload>
            <div style={{ minWidth: 300 }}>
              <Input value={cover} onChange={(e) => setCover(e.target.value)} placeholder="或直接填写图片地址" />
              <Space style={{ marginTop: 8 }} wrap>
                <Checkbox checked={autoCover} onChange={(e) => setAutoCover(e.target.checked)}>
                  自动生成封面
                </Checkbox>
                {cover && (
                  <>
                    <Button type="link" size="small" onClick={() => window.open(cover)}>
                      预览
                    </Button>
                    <Button type="link" size="small" danger onClick={() => setCover('')}>
                      移除
                    </Button>
                  </>
                )}
              </Space>
            </div>
          </Space>
        </Form.Item>

        <Form.Item label="摘要" labelCol={{ span: 2 }} wrapperCol={{ span: 22 }}>
          {format === 'markdown' ? (
            <VditorEditor value={excerpt} onChange={setExcerpt} height={160} placeholder="摘要内容（可选）…" />
          ) : (
            <Input.TextArea rows={2} value={excerpt} onChange={(e) => setExcerpt(e.target.value)} placeholder="文章摘要" />
          )}
        </Form.Item>

        <Form.Item label="正文" required labelCol={{ span: 2 }} wrapperCol={{ span: 22 }}>
          {format === 'markdown' ? (
            <VditorEditor value={content} onChange={setContent} height={480} />
          ) : (
            <Input.TextArea rows={16} value={content} onChange={(e) => setContent(e.target.value)} placeholder="正文内容（HTML）" />
          )}
        </Form.Item>

        <Space>
          <Button type="primary" htmlType="submit" loading={submitting}>
            保存
          </Button>
          <Button onClick={() => navigate('/posts')}>返回列表</Button>
        </Space>
      </Form>
    </Card>
  )
}
