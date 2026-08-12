import { useRef } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Popconfirm, Tag, Space, message } from 'antd'
import dayjs from 'dayjs'
import { api } from '../api/client'
import type { User } from '../types/api'

const ROLE_MAP = {
  1: { text: '会员', color: 'default' },
  8: { text: '编辑', color: 'blue' },
  16: { text: '管理员', color: 'red' },
}

const STATUS_MAP = {
  1: { text: '正常', color: 'green' },
  2: { text: '未激活', color: 'orange' },
  4: { text: '已禁用', color: 'red' },
  8: { text: '已删除', color: 'default' },
}

export default function UserList() {
  const actionRef = useRef<ActionType>(null)

  const handleToggle = async (action: string, id: number, _username: string) => {
    try {
      const data = await api.userAction(action, id)
      message.success(data?.message || (action === 'ban' ? '用户已禁用。' : '用户已启用。'))
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    }
  }

  const columns: ProColumns<User>[] = [
    { title: 'ID', dataIndex: 'id', width: 70 },
    { title: '用户名', dataIndex: 'username', width: 140 },
    { title: '昵称', dataIndex: 'nickname', width: 140 },
    { title: '邮箱', dataIndex: 'email', ellipsis: true },
    {
      title: '角色',
      dataIndex: 'role',
      width: 100,
      render: (_, r) => {
        const m = ROLE_MAP[r.role as keyof typeof ROLE_MAP] || ROLE_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 100,
      render: (_, r) => {
        const m = STATUS_MAP[r.status as keyof typeof STATUS_MAP] || STATUS_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '注册时间',
      dataIndex: 'register_time',
      width: 130,
      render: (_, r) => (r.register_time ? dayjs.unix(r.register_time).format('YYYY-MM-DD HH:mm') : '-'),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 130,
      render: (_, r) => (
        <Space>
          {r.status !== 4 ? (
            <Popconfirm title={`确认禁用用户「${r.username}」？`} onConfirm={() => handleToggle('ban', r.id, r.username)}>
              <a style={{ color: '#ff4d4f' }}>禁用</a>
            </Popconfirm>
          ) : (
            <a onClick={() => handleToggle('unban', r.id, r.username)}>启用</a>
          )}
        </Space>
      ),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    const res = await api.users({ page: params.current || 1 })
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <ProTable
      headerTitle="用户列表"
      actionRef={actionRef}
      rowKey="id"
      columns={columns}
      request={request}
      search={false}
      pagination={{ defaultPageSize: 20, showSizeChanger: false }}
    />
  )
}
