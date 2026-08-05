import { useEffect, useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import {
  Button,
  Popconfirm,
  Space,
  Tooltip,
  message,
  Modal,
  Form,
  Input,
  InputNumber,
  Upload,
  Empty,
  Carousel,
  Row,
  Col,
} from 'antd'
import { PlusOutlined, EditOutlined, DeleteOutlined, LoadingOutlined } from '@ant-design/icons'
import { api } from '../api/client'
import { getCsrfToken } from '../api/client'
import { ADMIN_BASE } from '../config'
import type { CustomConfigItem } from '../types/api'
import { usePageTitle } from '../contexts/PageTitleContext'

const CAROUSEL_CATEGORY = 'IndexCarousel'

export default function CarouselManage() {
  usePageTitle('轮播图片')
  const actionRef = useRef<ActionType>(null)
  const [items, setItems] = useState<CustomConfigItem[]>([])
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<CustomConfigItem | null>(null)
  const [saving, setSaving] = useState(false)
  const [uploading, setUploading] = useState(false)
  // 上传完成后递增，驱动 Upload 重挂载（清空内部 file 缓存，保证下次可重新上传）
  const [uploadNonce, setUploadNonce] = useState(0)
  const [form] = Form.useForm()

  useEffect(() => {
    if (modalOpen) {
      form.setFieldsValue(
        editing
          ? editing
          : { category: CAROUSEL_CATEGORY, data_type: 'image', priority: 0, name: '' },
      )
    }
  }, [modalOpen, editing, form])

  const openCreate = () => {
    setEditing(null)
    setModalOpen(true)
  }

  const openEdit = (row: CustomConfigItem) => {
    setEditing(row)
    setModalOpen(true)
  }

  const handleSave = async () => {
    try {
      const values = await form.validateFields()
      setSaving(true)
      // 轮播图固定为 image 类型
      const payload = { ...values, data_type: 'image', value: values.value || '' }
      if (editing) {
        await api.customConfigUpdate(editing.id, payload)
      } else {
        await api.customConfigSave(payload)
      }
      message.success(editing ? '轮播图已更新。' : '轮播图已创建。')
      setModalOpen(false)
      actionRef.current?.reload()
    } catch (e) {
      if (e instanceof Error) message.error(e.message)
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async (row: CustomConfigItem) => {
    try {
      await api.customConfigDelete(row.id)
      message.success('轮播图已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<CustomConfigItem>[] = [
    { title: 'ID', dataIndex: 'id', width: 60, search: false },
    {
      title: '图片',
      dataIndex: 'value',
      width: 160,
      search: false,
      render: (_, r) =>
        r.value ? (
          <img src={r.value} alt={r.name} style={{ width: 120, height: 60, objectFit: 'cover', borderRadius: 4 }} />
        ) : (
          <span style={{ color: '#bbb' }}>无</span>
        ),
    },
    { title: '名称', dataIndex: 'name', width: 160, ellipsis: true, search: false },
    {
      title: '优先级',
      dataIndex: 'priority',
      width: 90,
      search: false,
      render: (_, r) => (
        <Tooltip title="越大优先级越高">
          <span>{r.priority}</span>
        </Tooltip>
      ),
    },
    { title: '描述', dataIndex: 'description', ellipsis: true, search: false },
    {
      title: '操作',
      valueType: 'option',
      width: 110,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="编辑">
            <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
          </Tooltip>
          <Popconfirm title="确认删除该轮播图？" onConfirm={() => handleDelete(r)}>
            <Tooltip title="删除">
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space.Compact>
      ),
    },
  ]

  const request = async () => {
    const res = await api.customConfigs({ category: CAROUSEL_CATEGORY })
    setItems(res.items || [])
    return { data: res.items, total: res.total, success: true }
  }

  // 照片墙内容：有图显示预览，无图显示加号
  const value = Form.useWatch('value', form)
  const uploadField = value ? (
    <img
      src={value}
      alt="轮播图"
      style={{ width: '100%', height: '100%', objectFit: 'cover', borderRadius: 8 }}
    />
  ) : (
    <div>
      {uploading ? <LoadingOutlined /> : <PlusOutlined />}
      <div style={{ marginTop: 8 }}>上传</div>
    </div>
  )

  return (
    <div>
      <ProTable<CustomConfigItem>
        headerTitle="轮播图片列表"
        actionRef={actionRef}
        rowKey="id"
        columns={columns}
        request={request}
        search={false}
        pagination={false}
        columnsState={{
          persistenceKey: 'admin-carousel-list',
          defaultValue: {
            id: { show: false },
          },
        }}
        toolBarRender={() => [
          <Button key="create" type="primary" icon={<PlusOutlined />} onClick={openCreate}>
            上传轮播图
          </Button>,
        ]}
      />

      <div style={{ marginTop: 24 }}>
        <h3 style={{ marginBottom: 12 }}>轮播预览</h3>
        <Carousel autoplay>
          {items.length > 0 ? (
            items.map((item) => (
              <div key={item.id}>
                <div
                  style={{
                    height: 300,
                    background: '#f0f2f5',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    borderRadius: 8,
                    overflow: 'hidden',
                  }}
                >
                  {item.value ? (
                    <img
                      src={item.value}
                      alt={item.name}
                      style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                    />
                  ) : (
                    <Empty image={Empty.PRESENTED_IMAGE_SIMPLE} description="无图片" />
                  )}
                </div>
                {item.name && (
                  <div style={{ textAlign: 'center', marginTop: 8, color: 'rgba(0,0,0,0.65)' }}>
                    {item.name}
                  </div>
                )}
              </div>
            ))
          ) : (
            <div>
              <Empty
                image={Empty.PRESENTED_IMAGE_SIMPLE}
                description="暂无轮播图，请先上传"
                style={{ padding: 48 }}
              />
            </div>
          )}
        </Carousel>
      </div>

      <Modal
        title={editing ? '编辑轮播图' : '上传轮播图'}
        open={modalOpen}
        onOk={handleSave}
        confirmLoading={saving}
        onCancel={() => setModalOpen(false)}
        width={640}
      >
        <Form form={form} layout="vertical" initialValues={{ data_type: 'image', priority: 0 }}>
          <Form.Item name="category" label="分类" hidden>
            <Input />
          </Form.Item>
          <Form.Item name="value" hidden>
            <Input />
          </Form.Item>
          <Row gutter={24}>
            {/* 左侧：照片墙（方框 + 加号，已上传显示预览） */}
            <Col flex="120px">
              <Form.Item label="图片">
                <Upload
                  // key 变化强制重挂载，避免 antd 缓存同一 file 对象（重复选择同一文件时不重传、误弹旧消息）
                  key={`carousel-upload-${uploadNonce}`}
                  name="file"
                  accept="image/png, image/jpeg, image/gif, image/webp"
                  listType="picture-card"
                  showUploadList={false}
                  action={`${ADMIN_BASE}/upload/image`}
                  headers={{ 'X-CSRF-Token': getCsrfToken() || '' }}
                  onChange={(info) => {
                    if (info.file.status === 'uploading') {
                      setUploading(true)
                      return
                    }
                    setUploading(false)
                    if (info.file.status === 'done') {
                      const resp = info.file.response as { code?: number; data?: { url?: string }; msg?: string } | undefined
                      if (resp && resp.code === 0 && resp.data?.url) {
                        form.setFieldValue('value', resp.data.url)
                        message.success('图片上传成功。')
                        // 上传完成后递增 nonce 重置 Upload 内部状态，下次点击可重新上传
                        setUploadNonce((n) => n + 1)
                      } else {
                        message.error(resp?.msg || '图片上传失败。')
                        setUploadNonce((n) => n + 1)
                      }
                    } else if (info.file.status === 'error') {
                      message.error('图片上传失败。')
                      setUploadNonce((n) => n + 1)
                    }
                  }}
                >
                  {uploadField}
                </Upload>
              </Form.Item>
            </Col>
            {/* 右侧：表单字段 */}
            <Col flex="auto">
              <Form.Item name="key" label="键" rules={[{ required: true, message: '请输入键' }]}>
                <Input placeholder="如 slide-1（分类内唯一）" />
              </Form.Item>
              <Form.Item name="name" label="标题" rules={[{ required: true, message: '请输入标题' }]}>
                <Input placeholder="轮播图标题" />
              </Form.Item>
              <Space size="large">
                <Form.Item
                  name="priority"
                  label={
                    <Tooltip title="越大优先级越高">
                      <span>优先级</span>
                    </Tooltip>
                  }
                >
                  <InputNumber style={{ width: 120 }} />
                </Form.Item>
                <Form.Item name="description" label="描述">
                  <Input placeholder="简要描述" style={{ width: 220 }} />
                </Form.Item>
              </Space>
            </Col>
          </Row>
        </Form>
      </Modal>
    </div>
  )
}
