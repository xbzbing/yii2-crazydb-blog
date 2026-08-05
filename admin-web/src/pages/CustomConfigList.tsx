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
  Select,
  InputNumber,
  Tag,
  Empty,
  Tabs,
  Typography,
} from 'antd'
import { PlusOutlined, EditOutlined, DeleteOutlined, SearchOutlined, EyeOutlined } from '@ant-design/icons'
import { marked } from 'marked'
import { api } from '../api/client'
import type { CustomConfigCategory, CustomConfigItem } from '../types/api'
import { usePageTitle } from '../contexts/PageTitleContext'

const DATA_TYPES = ['text', 'markdown', 'html', 'image', 'url', 'base64', 'hex']

/** 按 data_type 渲染配置值 */
function ConfigValueView({ item }: { item: CustomConfigItem }) {
  const value = item.value || ''
  switch (item.data_type) {
    case 'image':
      return value ? (
        <img src={value} alt={item.name} style={{ maxWidth: '100%', maxHeight: 360, borderRadius: 6 }} />
      ) : (
        <Typography.Text type="secondary">（无图片）</Typography.Text>
      )
    case 'url':
      return value ? (
        <a href={value} target="_blank" rel="noopener noreferrer">
          {value}
        </a>
      ) : (
        <Typography.Text type="secondary">（空）</Typography.Text>
      )
    case 'html':
      return value ? (
        <div
          style={{ maxHeight: 400, overflow: 'auto', wordBreak: 'break-word' }}
          dangerouslySetInnerHTML={{ __html: value }}
        />
      ) : (
        <Typography.Text type="secondary">（空）</Typography.Text>
      )
    case 'markdown':
      return value ? (
        <div
          className="config-markdown-view"
          style={{ maxHeight: 400, overflow: 'auto', wordBreak: 'break-word', lineHeight: 1.8 }}
          dangerouslySetInnerHTML={{ __html: marked.parse(value) as string }}
        />
      ) : (
        <Typography.Text type="secondary">（空）</Typography.Text>
      )
    case 'base64':
      // 图片 base64（data:image/...）直接预览，否则按文本展示
      return value.startsWith('data:image/') ? (
        <img src={value} alt={item.name} style={{ maxWidth: '100%', maxHeight: 360, borderRadius: 6 }} />
      ) : (
        <Typography.Paragraph copyable={{ text: value }} style={{ wordBreak: 'break-all', marginBottom: 0 }}>
          {value || '（空）'}
        </Typography.Paragraph>
      )
    case 'hex':
      return (
        <Space>
          <span
            style={{
              display: 'inline-block',
              width: 24,
              height: 24,
              borderRadius: 4,
              border: '1px solid #ddd',
              background: /^#?[0-9a-fA-F]{3,8}$/.test(value.replace(/^#/, '')) ? value : '#fff',
              verticalAlign: 'middle',
            }}
          />
          <code>{value || '（空）'}</code>
        </Space>
      )
    default:
      // text
      return (
        <Typography.Paragraph copyable={{ text: value }} style={{ whiteSpace: 'pre-wrap', marginBottom: 0 }}>
          {value || '（空）'}
        </Typography.Paragraph>
      )
  }
}

export default function CustomConfigList() {
  usePageTitle('自定义配置')
  const actionRef = useRef<ActionType>(null)
  const [activeTab, setActiveTab] = useState<string>('categories')
  const [categories, setCategories] = useState<CustomConfigCategory[]>([])
  // 列表 tab 的分类过滤（从分类 tab 点击跳转而来，空 = 全部）
  const [categoryFilter, setCategoryFilter] = useState('')
  const [search, setSearch] = useState('')
  const [modalOpen, setModalOpen] = useState(false)
  const [editing, setEditing] = useState<CustomConfigItem | null>(null)
  const [saving, setSaving] = useState(false)
  const [viewItem, setViewItem] = useState<CustomConfigItem | null>(null)
  const [form] = Form.useForm()

  const loadCategories = async () => {
    const res = await api.customConfigCategories()
    setCategories(res.items || [])
  }

  useEffect(() => {
    loadCategories()
  }, [])

  useEffect(() => {
    form.setFieldsValue({})
    if (editing) {
      form.setFieldsValue(editing)
    } else {
      form.setFieldsValue({ category: categoryFilter || '', data_type: 'text', priority: 0 })
    }
  }, [modalOpen, editing, categoryFilter, form])

  // 切到列表 tab 时刷新（首次进入或分类过滤变化）
  useEffect(() => {
    if (activeTab === 'list') {
      setTimeout(() => actionRef.current?.reload(), 0)
    }
  }, [activeTab, categoryFilter])

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
      const payload = { ...values, value: values.value || '' }
      if (editing) {
        await api.customConfigUpdate(editing.id, payload)
      } else {
        await api.customConfigSave(payload)
      }
      message.success(editing ? '配置已更新。' : '配置已创建。')
      setModalOpen(false)
      actionRef.current?.reload()
      if (!editing) loadCategories()
    } catch (e) {
      if (e instanceof Error) message.error(e.message)
    } finally {
      setSaving(false)
    }
  }

  const handleDelete = async (row: CustomConfigItem) => {
    try {
      await api.customConfigDelete(row.id)
      message.success('配置已删除。')
      actionRef.current?.reload()
      loadCategories()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const recordColumns: ProColumns<CustomConfigItem>[] = [
    { title: 'ID', dataIndex: 'id', width: 60, search: false },
    { title: '分类', dataIndex: 'category', width: 130, ellipsis: true, search: false },
    { title: '键', dataIndex: 'key', width: 150, ellipsis: true, search: false },
    { title: '名称', dataIndex: 'name', width: 150, ellipsis: true, search: false },
    {
      title: '类型',
      dataIndex: 'data_type',
      width: 90,
      search: false,
      render: (_, r) => <Tag color="blue">{r.data_type}</Tag>,
    },
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
      width: 150,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="查看">
            <Button size="small" icon={<EyeOutlined />} onClick={() => setViewItem(r)} />
          </Tooltip>
          <Tooltip title="编辑">
            <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
          </Tooltip>
          <Popconfirm title="确认删除该配置？" onConfirm={() => handleDelete(r)}>
            <Tooltip title="删除">
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space.Compact>
      ),
    },
  ]

  const listRequest = async () => {
    const res = await api.customConfigs({ category: categoryFilter, search })
    return { data: res.items, total: res.total, success: true }
  }

  // 分类 tab 内容
  const categoriesContent = (
    <ProTable<CustomConfigCategory>
      headerTitle="自定义配置分类"
      rowKey="category"
      search={false}
      pagination={false}
      dataSource={categories}
      toolBarRender={() => [
        <Button key="create" type="primary" icon={<PlusOutlined />} onClick={openCreate}>
          新建配置
        </Button>,
      ]}
      columns={[
        {
          title: '分类',
          dataIndex: 'category',
          render: (_, r) => (
            <a
              onClick={() => {
                setCategoryFilter(r.category)
                setActiveTab('list')
              }}
            >
              <Tag color="geekblue">{r.category}</Tag>
            </a>
          ),
        },
        { title: '配置数量', dataIndex: 'count', width: 120 },
      ]}
      locale={{ emptyText: <Empty description="暂无自定义配置" /> }}
    />
  )

  // 列表 tab 内容（默认全部，分类点击带 category 过滤）
  const listContent = (
    <div>
      {categoryFilter !== '' && (
        <Space style={{ marginBottom: 16 }}>
          <span style={{ fontWeight: 500 }}>
            分类：<Tag color="geekblue">{categoryFilter}</Tag>
          </span>
          <Button size="small" onClick={() => setCategoryFilter('')}>
            查看全部
          </Button>
        </Space>
      )}
      <ProTable<CustomConfigItem>
        headerTitle="自定义配置列表"
        actionRef={actionRef}
        rowKey="id"
        columns={recordColumns}
        request={listRequest}
        search={false}
        pagination={{ defaultPageSize: 20, showSizeChanger: false }}
        toolbar={{
          search: {
            placeholder: '搜索分类 / 名称 / 键',
            allowClear: true,
            onSearch: (v: string) => {
              setSearch(v || '')
              setTimeout(() => actionRef.current?.reload(), 0)
            },
          },
        }}
        toolBarRender={() => [
          <Button key="create" type="primary" icon={<PlusOutlined />} onClick={openCreate}>
            新建配置
          </Button>,
        ]}
      />
    </div>
  )

  return (
    <div>
      <Tabs
        activeKey={activeTab}
        onChange={setActiveTab}
        items={[
          { key: 'categories', label: '分类', children: categoriesContent },
          { key: 'list', label: '列表', children: listContent },
        ]}
      />

      <Modal
        title={editing ? '编辑配置' : '新建配置'}
        open={modalOpen}
        onOk={handleSave}
        confirmLoading={saving}
        onCancel={() => setModalOpen(false)}
        width={640}
      >
        <Form form={form} layout="vertical" initialValues={{ data_type: 'text', priority: 0 }}>
          <Form.Item name="category" label="分类" rules={[{ required: true, message: '请输入分类' }]}>
            <Input placeholder="如 ThemeDIY / IndexCarousel" />
          </Form.Item>
          <Form.Item name="key" label="键" rules={[{ required: true, message: '请输入键' }]}>
            <Input placeholder="如 aboutMe（分类内唯一）" />
          </Form.Item>
          <Form.Item name="name" label="名称" rules={[{ required: true, message: '请输入名称' }]}>
            <Input placeholder="配置名称" />
          </Form.Item>
          <Form.Item name="value" label="值" rules={[{ required: true, message: '请输入值' }]}>
            <Input.TextArea rows={5} placeholder="配置值（文本 / Markdown / 图片 URL 等，按类型解释）" />
          </Form.Item>
          <Space size="large">
            <Form.Item name="data_type" label="值类型">
              <Select style={{ width: 140 }} options={DATA_TYPES.map((t) => ({ value: t, label: t }))} />
            </Form.Item>
            <Form.Item
              name="priority"
              label={
                <Tooltip title="越大优先级越高">
                  <span>
                    优先级 <SearchOutlined style={{ fontSize: 12, color: 'rgba(0,0,0,0.45)' }} />
                  </span>
                </Tooltip>
              }
            >
              <InputNumber style={{ width: 140 }} />
            </Form.Item>
          </Space>
          <Form.Item name="description" label="描述">
            <Input placeholder="简要描述" />
          </Form.Item>
        </Form>
      </Modal>

      {/* 查看：按 data_type 渲染 */}
      <Modal
        title={viewItem ? `${viewItem.name}（${viewItem.data_type}）` : '查看配置'}
        open={viewItem !== null}
        onCancel={() => setViewItem(null)}
        footer={[
          <Button key="close" onClick={() => setViewItem(null)}>
            关闭
          </Button>,
        ]}
        width={560}
      >
        {viewItem && (
          <div>
            <Space direction="vertical" style={{ width: '100%' }} size="small">
              <div>
                <Typography.Text type="secondary">分类：</Typography.Text>
                <Tag color="geekblue">{viewItem.category}</Tag>
                <Typography.Text type="secondary" style={{ marginLeft: 16 }}>
                  键：
                </Typography.Text>
                <code>{viewItem.key}</code>
                <Typography.Text type="secondary" style={{ marginLeft: 16 }}>
                  优先级：
                </Typography.Text>
                {viewItem.priority}
              </div>
              {viewItem.description && (
                <div>
                  <Typography.Text type="secondary">描述：</Typography.Text>
                  {viewItem.description}
                </div>
              )}
              <div>
                <Typography.Text type="secondary">值：</Typography.Text>
                <div style={{ marginTop: 8 }}>
                  <ConfigValueView item={viewItem} />
                </div>
              </div>
            </Space>
          </div>
        )}
      </Modal>
    </div>
  )
}
