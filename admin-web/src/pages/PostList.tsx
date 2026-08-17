import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import type { ProFormInstance } from '@ant-design/pro-form'
import { Button, Popconfirm, Tag, Space, Tooltip, message } from 'antd'
import { PlusOutlined, EditOutlined, DeleteOutlined, ExportOutlined } from '@ant-design/icons'
import { useNavigate, useSearchParams } from 'react-router-dom'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { PostItem } from '../types/api'

const STATUS_MAP = {
  published: { text: '已发布', color: 'green' },
  draft: { text: '草稿', color: 'default' },
  deleted: { text: '已删除', color: 'red' },
}

export default function PostList() {
  const navigate = useNavigate()
  const actionRef = useRef<ActionType>(null)
  const formRef = useRef<ProFormInstance>(undefined)
  const [searchParams] = useSearchParams()
  const urlTag = searchParams.get('tag') || ''

  // 从标签列表跳转带入的筛选：同步到搜索表单显示（onLoad 时表单已挂载）
  const syncedTagRef = useRef(false)
  const handleLoad = () => {
    if (urlTag && !syncedTagRef.current) {
      syncedTagRef.current = true
      formRef.current?.setFieldsValue({ tag: urlTag })
    }
  }

  const handleDelete = async (id: number) => {
    try {
      await api.postDelete(id)
      message.success('文章已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<PostItem>[] = [
    { title: 'ID', dataIndex: 'id', width: 55, search: false },
    {
      title: '标题',
      dataIndex: 'title',
      ellipsis: true,
      render: (_, record) => (
        <span style={{ whiteSpace: 'nowrap' }}>
          {record.is_top === 1 && <Tag color="red" style={{ marginRight: 4 }}>置顶</Tag>}
          {record.is_locked && <Tag color="orange" style={{ marginRight: 4 }}>加锁</Tag>}
          <a onClick={() => navigate(`/posts/${record.id}/edit`)}>{record.title}</a>
        </span>
      ),
    },
    {
      title: '分类',
      dataIndex: 'category_name',
      width: 100,
      search: false,
      render: (_, r) => r.category_name || <span style={{ color: '#bbb' }}>-</span>,
    },
    {
      title: '标签',
      dataIndex: 'tag',
      width: 160,
      ellipsis: true,
      render: (_, r) =>
        r.tags ? (
          <span style={{ whiteSpace: 'nowrap' }}>
            {r.tags
              .split(',')
              .map((t) => t.trim())
              .filter(Boolean)
              .map((t) => (
                <Tag key={t} color="blue" style={{ marginRight: 4 }}>
                  {t}
                </Tag>
              ))}
          </span>
        ) : (
          <span style={{ color: '#bbb' }}>-</span>
        ),
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 80,
      valueType: 'select',
      valueEnum: STATUS_MAP,
      render: (_, record) => {
        const s = STATUS_MAP[record.status as keyof typeof STATUS_MAP] || STATUS_MAP.draft
        return <Tag color={s.color}>{s.text}</Tag>
      },
    },
    { title: '评论', dataIndex: 'comment_count', width: 60, search: false },
    { title: '浏览', dataIndex: 'view_count', width: 60, search: false },
    {
      title: '发布时间',
      dataIndex: 'post_time',
      width: 150,
      search: false,
      render: (_, r) => (
        <span style={{ whiteSpace: 'nowrap' }}>{r.post_time ? dayjs.unix(r.post_time).format('YYYY-MM-DD HH:mm') : '-'}</span>
      ),
    },
    {
      title: '编辑时间',
      dataIndex: 'update_time',
      width: 150,
      search: false,
      render: (_, r) => (
        <span style={{ whiteSpace: 'nowrap' }}>{r.update_time ? dayjs.unix(r.update_time).format('YYYY-MM-DD HH:mm') : '-'}</span>
      ),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 96,
      fixed: 'right',
      render: (_, record) => {
        const frontUrl =
          record.status === 'published'
            ? record.alias
              ? `/archive/${record.alias}`
              : `/post/${record.id}`
            : null
        return (
          <Space.Compact>
            {frontUrl && (
              <Tooltip title="访问前台">
                <Button size="small" icon={<ExportOutlined />} onClick={() => window.open(frontUrl, '_blank')} />
              </Tooltip>
            )}
            <Tooltip title="编辑">
              <Button size="small" icon={<EditOutlined />} onClick={() => navigate(`/posts/${record.id}/edit`)} />
            </Tooltip>
            <Popconfirm
              title="确认删除该文章？"
              description="将同时删除关联评论与标签。"
              onConfirm={() => handleDelete(record.id)}
            >
              <Tooltip title="删除">
                <Button size="small" danger icon={<DeleteOutlined />} />
              </Tooltip>
            </Popconfirm>
          </Space.Compact>
        )
      },
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const page = params.current || 1
    const pageSize = params.pageSize || 20
    const status = (params.status as string) || ''
    const tag = (params.tag as string) || ''
    const res = await api.posts({ page, status, tag, pageSize })
    return {
      data: res.items,
      total: res.total,
      success: true,
    }
  }

  return (
    <ProTable
      headerTitle="文章列表"
      actionRef={actionRef}
      formRef={formRef}
      rowKey="id"
      columns={columns}
      request={request}
      params={{ tag: urlTag }}
      onLoad={handleLoad}
      search={{ labelWidth: 'auto' }}
      pagination={{ defaultPageSize: 20, showSizeChanger: false }}
      scroll={{ x: 'max-content' }}
      columnsState={{
        persistenceKey: 'admin-post-list',
        defaultValue: {
          id: { show: false },
          update_time: { show: false },
        },
      }}
      toolBarRender={() => [
        <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => navigate('/posts/create')}>
          新建文章
        </Button>,
      ]}
    />
  )
}
