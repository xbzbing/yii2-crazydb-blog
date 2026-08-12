import { useEffect, useState } from 'react'
import { Card, Form, Input, Select, Switch, Button, Space, message, Spin, Upload, Row, Col, Tooltip, Checkbox } from 'antd'
import { PlusOutlined, LoadingOutlined, FileDoneOutlined, SaveOutlined, RollbackOutlined } from '@ant-design/icons'
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
  const [format, setFormat] = useState<'markdown' | 'html'>('markdown')
  const [cover, setCover] = useState('')
  const [coverUploading, setCoverUploading] = useState(false)
  const [autoCover, setAutoCover] = useState(false)
  const [passwordEnabled, setPasswordEnabled] = useState(false)

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
          setFormat(p.format === 'html' ? 'html' : 'markdown')
          setPasswordEnabled(!!p.password)
          form.setFieldsValue({
            title: p.title,
            alias: p.alias,
            cid: p.cid || undefined,
            status: p.status,
            tags: p.tags,
            author_name: p.author_name,
            is_top: p.is_top === 1,
            password: p.password || '',
          })
        })
        .catch((e) => message.error(e instanceof Error ? e.message : String(e)))
        .finally(() => setLoading(false))
    } else {
      // 新建：状态默认「空」，由底部「发布/存为草稿」按钮决定
      form.setFieldsValue({ status: '', is_top: false, password: '' })
      setFormat('markdown')
      setPasswordEnabled(false)
      setLoading(false)
    }
  }, [id])

  const submitWithStatus = async (status: string) => {
    let values: {
      title: string
      alias?: string
      cid?: number
      tags?: string
      author_name?: string
      is_top?: boolean
      password?: string
    }
    try {
      values = await form.validateFields()
    } catch {
      return
    }
    setSubmitting(true)
    try {
      const payload = {
        title: values.title,
        alias: values.alias || '',
        cid: values.cid || 0,
        status,
        format,
        tags: values.tags || '',
        author_name: values.author_name || '',
        cover,
        auto_cover: autoCover ? 1 : 0,
        excerpt,
        content,
        is_top: values.is_top ? 1 : 0,
        password: passwordEnabled ? (values.password || '') : '',
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
      <Form form={form} layout="horizontal" onFinish={() => submitWithStatus('draft')} style={{ maxWidth: 960 }} labelCol={{ flex: '70px' }} wrapperCol={{ flex: 'auto' }}>
        {/* 第一行：标题（带 label，与下方输入框对齐）+ 置顶 */}
        <Row gutter={[16, 0]}>
          <Col flex="auto">
            <Form.Item name="title" label="标题" rules={[{ required: true, message: '请输入标题' }]}>
              <Input placeholder="文章标题" />
            </Form.Item>
          </Col>
          <Col>
            <Form.Item name="is_top" valuePropName="checked">
              <Switch checkedChildren="置顶" unCheckedChildren="置顶" />
            </Form.Item>
          </Col>
        </Row>

        {/* 第二行：别名、笔名、状态 */}
        <Row gutter={[16, 0]}>
          <Col span={8}>
            <Form.Item name="alias" label="别名">
              <Input placeholder="留空自动生成" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="author_name" label="笔名">
              <Input placeholder="作者笔名" />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item name="status" label="状态">
              <Select
                placeholder="状态"
                options={[
                  { value: 'published', label: '已发布' },
                  { value: 'draft', label: '草稿' },
                ]}
              />
            </Form.Item>
          </Col>
        </Row>

        {/* 第三行：分类、标签、密码 */}
        <Row gutter={[16, 0]}>
          <Col span={8}>
            <Form.Item name="cid" label="分类">
              <Select
                allowClear
                placeholder="选择分类"
                options={Object.entries(categories).map(([v, l]) => ({ value: Number(v), label: l }))}
              />
            </Form.Item>
          </Col>
          <Col span={8}>
            <Form.Item
              name="tags"
              label={
                <Tooltip title="多个标签用英文逗号分隔，最多 5 个">
                  <span>标签</span>
                </Tooltip>
              }
            >
              <Input placeholder="如 php, yii3, 博客" />
            </Form.Item>
          </Col>
          {/* 访问密码：开关 + 密码输入框组合（无 label，margin-left 对齐状态列） */}
          <Col span={8}>
            <Form.Item style={{ marginBottom: 0 }}>
              <Space.Compact style={{ marginLeft: 70 }}>
                <Button
                  type={passwordEnabled ? 'primary' : 'default'}
                  onClick={() => setPasswordEnabled(!passwordEnabled)}
                >
                  {passwordEnabled ? '加锁' : '未加锁'}
                </Button>
                {passwordEnabled ? (
                  <Form.Item
                    name="password"
                    noStyle
                    rules={[{ required: true, message: '请输入访问密码' }]}
                  >
                    <Input.Password placeholder="输入访问密码" autoComplete="new-password" />
                  </Form.Item>
                ) : (
                  <Input disabled placeholder="未加锁" />
                )}
              </Space.Compact>
            </Form.Item>
          </Col>
        </Row>

        <Form.Item label="封面图片">
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

        <Form.Item label="摘要" layout="vertical" labelCol={{ flex: 'none' }} style={{ marginBottom: 24 }}>
          {format === 'markdown' ? (
            <VditorEditor value={excerpt} onChange={setExcerpt} height={160} placeholder="摘要内容（可选）…" />
          ) : (
            <Input.TextArea rows={2} value={excerpt} onChange={(e) => setExcerpt(e.target.value)} placeholder="文章摘要（HTML 兼容旧文档）" />
          )}
        </Form.Item>

        <Form.Item label="正文" required layout="vertical" labelCol={{ flex: 'none' }} style={{ marginBottom: 24 }}>
          {format === 'markdown' ? (
            <VditorEditor value={content} onChange={setContent} height={480} />
          ) : (
            <Input.TextArea rows={16} value={content} onChange={(e) => setContent(e.target.value)} placeholder="正文内容（HTML 兼容旧文档）" />
          )}
          {format === 'html' && (
            <div style={{ marginTop: 8, color: '#999', fontSize: 13 }}>
              该文章为旧版 HTML 格式，仅兼容编辑；新文章请使用 Markdown。
            </div>
          )}
        </Form.Item>

        <Form.Item style={{ marginBottom: 0 }}>
          <Space style={{ marginLeft: 70 }}>
            <Button type="primary" icon={<FileDoneOutlined />} loading={submitting} onClick={() => submitWithStatus('published')}>
              发布
            </Button>
            <Button icon={<SaveOutlined />} loading={submitting} onClick={() => submitWithStatus('draft')}>
              存为草稿
            </Button>
            <Button icon={<RollbackOutlined />} onClick={() => navigate('/posts')}>
              返回列表
            </Button>
          </Space>
        </Form.Item>
      </Form>
    </Card>
  )
}
