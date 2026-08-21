import { useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Popconfirm, Tag, Space, message, Modal, Descriptions, Form, Input, Select, Spin, Tooltip } from 'antd'
import { EyeOutlined, EditOutlined, CheckOutlined, DeleteOutlined } from '@ant-design/icons'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { CommentItem } from '../types/api'

const STATUS_MAP = {
  approved: { text: '已通过', color: 'green' },
  unapproved: { text: '待审核', color: 'orange' },
  spam: { text: '垃圾', color: 'red' },
}

export default function CommentList() {
  const actionRef = useRef<ActionType>(null)
  const [detail, setDetail] = useState<CommentItem | null>(null)
  const [detailLoading, setDetailLoading] = useState(false)
  const [editVisible, setEditVisible] = useState(false)
  const [editRecord, setEditRecord] = useState<CommentItem | null>(null)
  const [editForm] = Form.useForm()
  const [saving, setSaving] = useState(false)

  const handleAction = async (action: string, id: number) => {
    try {
      await api.commentAction(action, id)
      message.success(action === 'approve' ? '评论已通过审核。' : '评论已删除。')
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const openDetail = async (record: CommentItem) => {
    setDetail(null)
    setDetailLoading(true)
    try {
      const data = await api.comment(record.id)
      setDetail(data.comment)
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setDetailLoading(false)
    }
  }

  const openEdit = async (record: CommentItem) => {
    setEditRecord(record)
    setEditVisible(true)
    try {
      const data = await api.comment(record.id)
      editForm.setFieldsValue({
        content: data.comment.content,
        nickname: data.comment.nickname,
        email: data.comment.email,
        url: data.comment.url || '',
        status: data.comment.status,
      })
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const submitEdit = async () => {
    if (!editRecord) return
    const values = await editForm.validateFields().catch(() => null)
    if (!values) return
    setSaving(true)
    try {
      const data = await api.commentUpdate(editRecord.id, values)
      if (data && data.ok === false) {
        message.error(Object.values(data.errors || {}).join('；') || '保存失败。')
        return
      }
      message.success(data?.message || '评论已更新。')
      setEditVisible(false)
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setSaving(false)
    }
  }

  const columns: ProColumns<CommentItem>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      width: 70,
      search: false,
      render: (_, r) => (
        <Button type="link" size="small" style={{ padding: 0 }} onClick={() => openDetail(r)}>
          {r.id}
        </Button>
      ),
    },
    { title: '昵称', dataIndex: 'nickname', width: 120, search: false },
    {
      title: '内容',
      dataIndex: 'content',
      width: 280,
      search: false,
      render: (_, r) => (r.reply_to ? `[回复] ${r.content}` : r.content),
    },
    {
      title: '被评论文章',
      dataIndex: 'post_title',
      width: 180,
      ellipsis: true,
      search: false,
      render: (_, r) =>
        r.post_url && r.post_title ? (
          <a href={r.post_url} target="_blank" rel="noopener noreferrer">
            {r.post_title}
          </a>
        ) : (
          <span style={{ color: '#bbb' }}>（文章已删除）</span>
        ),
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 110,
      valueType: 'select',
      valueEnum: STATUS_MAP,
      render: (_, r) => {
        const s = STATUS_MAP[r.status as keyof typeof STATUS_MAP] || STATUS_MAP.unapproved
        return <Tag color={s.color}>{s.text}</Tag>
      },
    },
    {
      title: '时间',
      dataIndex: 'create_time',
      width: 130,
      search: false,
      render: (_, r) => (r.create_time ? dayjs.unix(r.create_time).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 110,
      render: (_, r) => (
        <Space.Compact>
          <Tooltip title="查看详情">
            <Button size="small" icon={<EyeOutlined />} onClick={() => openDetail(r)} />
          </Tooltip>
          <Tooltip title="编辑">
            <Button size="small" icon={<EditOutlined />} onClick={() => openEdit(r)} />
          </Tooltip>
          {r.status !== 'approved' && (
            <Tooltip title="通过审核">
              <Button size="small" icon={<CheckOutlined />} onClick={() => handleAction('approve', r.id)} />
            </Tooltip>
          )}
          <Popconfirm title="确认删除该评论？" onConfirm={() => handleAction('delete', r.id)}>
            <Tooltip title="删除">
              <Button size="small" danger icon={<DeleteOutlined />} />
            </Tooltip>
          </Popconfirm>
        </Space.Compact>
      ),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const res = await api.comments({
      page: params.current || 1,
      status: (params.status as string) || '',
    })
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <>
      <ProTable
        headerTitle="评论列表"
        actionRef={actionRef}
        rowKey="id"
        columns={columns}
        request={request}
        pagination={{ defaultPageSize: 20, showSizeChanger: false }}
        scroll={{ x: 'max-content' }}
      />

      {/* 详情弹窗 */}
      <Modal
        title="评论详情"
        open={detail !== null}
        onCancel={() => setDetail(null)}
        footer={null}
        centered
        width={640}
      >
        {detailLoading && <Spin style={{ display: 'block', margin: '32px auto' }} />}
        {detail && (
          <Descriptions bordered column={1} size="small">
            <Descriptions.Item label="ID">{detail.id}</Descriptions.Item>
            <Descriptions.Item label="内容">{detail.content}</Descriptions.Item>
            <Descriptions.Item label="昵称">{detail.nickname}</Descriptions.Item>
            <Descriptions.Item label="邮箱">{detail.email || '-'}</Descriptions.Item>
            <Descriptions.Item label="网站">{detail.url || '-'}</Descriptions.Item>
            <Descriptions.Item label="IP">{detail.ip || '-'}</Descriptions.Item>
            <Descriptions.Item label="User Agent" style={{ wordBreak: 'break-all' }}>
              {detail.user_agent || '-'}
            </Descriptions.Item>
            <Descriptions.Item label="状态">
              {detail.status ? (STATUS_MAP[detail.status as keyof typeof STATUS_MAP]?.text ?? detail.status) : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="回复目标">
              {detail.reply_to ? `#${detail.reply_to}` : '无'}
            </Descriptions.Item>
            <Descriptions.Item label="创建时间">
              {detail.create_time ? dayjs.unix(detail.create_time).format('YYYY-MM-DD HH:mm:ss') : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="更新时间">
              {detail.update_time ? dayjs.unix(detail.update_time).format('YYYY-MM-DD HH:mm:ss') : '-'}
            </Descriptions.Item>
          </Descriptions>
        )}
      </Modal>

      {/* 编辑弹窗 */}
      <Modal
        title="编辑评论"
        open={editVisible}
        onCancel={() => setEditVisible(false)}
        onOk={submitEdit}
        confirmLoading={saving}
        width={600}
      >
        <Form form={editForm} layout="vertical" style={{ marginTop: 16 }}>
          <Form.Item name="content" label="内容" rules={[{ required: true, message: '请输入评论内容' }]}>
            <Input.TextArea rows={4} />
          </Form.Item>
          <Form.Item name="nickname" label="昵称" rules={[{ required: true, message: '请输入昵称' }]}>
            <Input />
          </Form.Item>
          <Form.Item name="email" label="邮箱">
            <Input />
          </Form.Item>
          <Form.Item name="url" label="网站">
            <Input placeholder="https://…" />
          </Form.Item>
          <Form.Item name="status" label="状态">
            <Select options={Object.entries(STATUS_MAP).map(([v, m]) => ({ value: v, label: m.text }))} />
          </Form.Item>
        </Form>
      </Modal>
    </>
  )
}
