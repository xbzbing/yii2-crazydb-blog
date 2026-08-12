import { useRef, useState } from 'react'
import { ProTable, type ActionType, type ProColumns } from '@ant-design/pro-components'
import { Button, Tag, Tooltip, message, Modal, Descriptions } from 'antd'
import { EyeOutlined, StopOutlined, CheckCircleOutlined } from '@ant-design/icons'
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
  const [viewTarget, setViewTarget] = useState<User | null>(null)
  const [confirming, setConfirming] = useState(false)

  const handleToggle = async (action: string, id: number) => {
    setConfirming(true)
    try {
      const data = await api.userAction(action, id)
      message.success(data?.message || (action === 'ban' ? '用户已禁用。' : '用户已启用。'))
      actionRef.current?.reload()
    } catch (e) {
      message.error(e instanceof Error ? e.message : String(e))
    } finally {
      setConfirming(false)
    }
  }

  const columns: ProColumns<User>[] = [
    { title: 'ID', dataIndex: 'id', width: 60, search: false },
    { title: '用户名', dataIndex: 'username', width: 130 },
    { title: '昵称', dataIndex: 'nickname', width: 130 },
    { title: '邮箱', dataIndex: 'email', ellipsis: true },
    {
      title: '角色',
      dataIndex: 'role',
      width: 90,
      search: false,
      render: (_, r) => {
        const m = ROLE_MAP[r.role as keyof typeof ROLE_MAP] || ROLE_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 90,
      search: false,
      render: (_, r) => {
        const m = STATUS_MAP[r.status as keyof typeof STATUS_MAP] || STATUS_MAP[1]
        return <Tag color={m.color}>{m.text}</Tag>
      },
    },
    {
      title: '注册时间',
      dataIndex: 'register_time',
      width: 110,
      search: false,
      render: (_, r) => (r.register_time ? dayjs.unix(r.register_time).format('YYYY-MM-DD') : '-'),
    },
    {
      title: '操作',
      valueType: 'option',
      width: 40,
      search: false,
      render: (_, r) => (
        <Tooltip title="查看详情">
          <Button size="small" icon={<EyeOutlined />} onClick={() => setViewTarget(r)} />
        </Tooltip>
      ),
    },
  ]

  const request = async (params: { current?: number; pageSize?: number; [key: string]: unknown }) => {
    // ProTable 搜索表单以列 dataIndex 为参数名（username/nickname/email），
    // 后端统一按 keyword 模糊匹配三者，取任一非空值
    const keyword = (params.keyword as string) || (params.username as string) || (params.nickname as string) || (params.email as string) || ''
    const res = await api.users({ page: params.current || 1, keyword })
    return { data: res.items, total: res.total, success: true }
  }

  return (
    <>
      <ProTable
        headerTitle="用户列表"
        actionRef={actionRef}
        rowKey="id"
        columns={columns}
        request={request}
        pagination={{ defaultPageSize: 20, showSizeChanger: false }}
        scroll={{ x: 'max-content' }}
      />

      {/* 用户详情弹窗（含禁用/启用操作） */}
      <Modal
        title="用户详情"
        open={viewTarget !== null}
        onCancel={() => setViewTarget(null)}
        confirmLoading={confirming}
        footer={[
          <Button key="close" onClick={() => setViewTarget(null)}>
            关闭
          </Button>,
          viewTarget && viewTarget.status !== 4 ? (
            <Button
              key="ban"
              danger
              icon={<StopOutlined />}
              loading={confirming}
              onClick={() => handleToggle('ban', viewTarget.id)}
            >
              禁用
            </Button>
          ) : (
            <Button
              key="unban"
              icon={<CheckCircleOutlined />}
              loading={confirming}
              onClick={() => viewTarget && handleToggle('unban', viewTarget.id)}
            >
              启用
            </Button>
          ),
        ]}
      >
        {viewTarget && (
          <Descriptions column={1} size="small">
            <Descriptions.Item label="ID">{viewTarget.id}</Descriptions.Item>
            <Descriptions.Item label="用户名">{viewTarget.username}</Descriptions.Item>
            <Descriptions.Item label="昵称">{viewTarget.nickname}</Descriptions.Item>
            <Descriptions.Item label="邮箱">{viewTarget.email || '-'}</Descriptions.Item>
            <Descriptions.Item label="网站">{viewTarget.website || '-'}</Descriptions.Item>
            <Descriptions.Item label="角色">
              {ROLE_MAP[viewTarget.role as keyof typeof ROLE_MAP]?.text ?? viewTarget.role}
            </Descriptions.Item>
            <Descriptions.Item label="状态">
              {STATUS_MAP[viewTarget.status as keyof typeof STATUS_MAP]?.text ?? viewTarget.status}
            </Descriptions.Item>
            <Descriptions.Item label="注册时间">
              {viewTarget.register_time ? dayjs.unix(viewTarget.register_time).format('YYYY-MM-DD HH:mm') : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="最后活跃">
              {viewTarget.active_time ? dayjs.unix(viewTarget.active_time).format('YYYY-MM-DD HH:mm') : '-'}
            </Descriptions.Item>
            <Descriptions.Item label="个人简介">{viewTarget.info || '-'}</Descriptions.Item>
          </Descriptions>
        )}
      </Modal>
    </>
  )
}
