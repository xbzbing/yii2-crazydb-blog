import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, message } from 'antd'
import { PlusOutlined } from '@ant-design/icons'
import { useNavigate } from 'react-router-dom'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { PostItem } from '../types/api'

const STATUS_MAP = {
  published: { text: '已发布', color: 'green' },
  hidden: { text: '隐藏', color: 'orange' },
  draft: { text: '草稿', color: 'default' },
  deleted: { text: '已删除', color: 'red' },
}

export default function PostList() {
  const navigate = useNavigate()
  const actionRef = useRef<ActionType>(null)

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
    { title: 'ID', dataIndex: 'id', width: 70, search: false },
    {
      title: '标题',
      dataIndex: 'title',
      ellipsis: true,
      render: (_, record) => <a onClick={() => navigate(`/posts/${record.id}/edit`)}>{record.title}</a>,
    },
    { title: '别名', dataIndex: 'alias', width: 200, ellipsis: true, search: false },
    {
      title: '状态',
      dataIndex: 'status',
      width: 110,
      valueType: 'select',
      valueEnum: STATUS_MAP,
      render: (_, record) => {
        const s = STATUS_MAP[record.status as keyof typeof STATUS_MAP] || STATUS_MAP.draft
        return <Tag color={s.color}>{s.text}</Tag>
      },
    },
    { title: '格式', dataIndex: 'format', width: 90, search: false, render: (_, r) => (r.format === 'markdown' ? 'Markdown' : 'HTML') },
    { title: '评论', dataIndex: 'comment_count', width: 70, search: false },
    { title: '浏览', dataIndex: 'view_count', width: 70, search: false },
    {
      title: '发布时间',
      dataIndex: 'post_time',
      width: 130,
      search: false,
      render: (_, r) => (r.post_time ? dayjs.unix(r.post_time).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 150,
      render: (_, record) => [
        <a key="edit" onClick={() => navigate(`/posts/${record.id}/edit`)}>
          编辑
        </a>,
        <Popconfirm
          key="del"
          title="确认删除该文章？"
          description="将同时删除关联评论与标签。"
          onConfirm={() => handleDelete(record.id)}
        >
          <a style={{ color: '#ff4d4f' }}>删除</a>
        </Popconfirm>,
      ],
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const page = params.current || 1
    const pageSize = params.pageSize || 20
    const status = (params.status as string) || ''
    const res = await api.posts({ page, status, pageSize })
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
      rowKey="id"
      columns={columns}
      request={request}
      pagination={{ defaultPageSize: 20, showSizeChanger: false }}
      toolBarRender={() => [
        <Button key="create" type="primary" icon={<PlusOutlined />} onClick={() => navigate('/posts/create')}>
          新建文章
        </Button>,
      ]}
    />
  )
}
